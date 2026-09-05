<?php
/**
 * Jukebox bearbeiten
 */
require_once '../config/config.php';

setSecurityHeaders();

// Login-Check
if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

// Jukebox laden
$id = $_GET['id'] ?? '';
$jukebox = getJukeboxById($id);

if (!$jukebox) {
    redirect('/admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token prüfen
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Sicherheitsfehler. Bitte laden Sie die Seite neu.';
    } else {
        // Bilder verarbeiten
        $orderedImages = [];
        $uploadErrors = [];
        $imageOrder = isset($_POST['image_order']) && is_array($_POST['image_order']) ? $_POST['image_order'] : [];

        // Neue Dateien indizieren
        $newFiles = [];
        if (isset($_FILES['new_images']) && is_array($_FILES['new_images']['tmp_name'])) {
            $fileCount = count($_FILES['new_images']['tmp_name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['new_images']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                $newFiles[$i] = [
                    'tmp_name' => $_FILES['new_images']['tmp_name'][$i],
                    'name' => $_FILES['new_images']['name'][$i],
                    'type' => $_FILES['new_images']['type'][$i],
                    'size' => $_FILES['new_images']['size'][$i]
                ];
            }
        }

        foreach ($imageOrder as $orderValue) {
            if (strpos($orderValue, 'new:') === 0) {
                $idx = (int) substr($orderValue, 4);
                if (!isset($newFiles[$idx])) {
                    continue;
                }
                $file = $newFiles[$idx];
                $result = uploadImage($file);
                if ($result['success']) {
                    $orderedImages[] = $result['filename'];
                } else {
                    $uploadErrors[] = "'" . $file['name'] . "': " . $result['error'];
                }
            } elseif (!empty($orderValue)) {
                $img = sanitizeImageFilename($orderValue);
                if ($img) {
                    $orderedImages[] = $img;
                }
            }
        }

        // Bilder löschen, die nicht mehr in der Reihenfolge vorkommen
        $oldImages = array_filter(array_merge([$jukebox['main_image'] ?? ''], $jukebox['gallery_images'] ?? []));
        $keptImages = array_filter($orderedImages);
        foreach ($oldImages as $oldImg) {
            if (!in_array($oldImg, $keptImages, true)) {
                deleteImage($oldImg);
            }
        }

        // Upload-Fehler anzeigen
        if (!empty($uploadErrors)) {
            $error = 'Upload-Fehler:<br>' . implode('<br>', $uploadErrors);
        }

        $mainImage = $orderedImages[0] ?? '';
        $galleryImages = array_values(array_slice($orderedImages, 1));
        
        if (!$error) {
            // Jukebox-Daten
            $data = [
                'name' => $_POST['name'] ?? '',
                'name_en' => $_POST['name_en'] ?? '',
                'manufacturer' => $_POST['manufacturer'] ?? '',
                'model' => $_POST['model'] ?? '',
                'year' => $_POST['year'] ?: null,
                'short_description' => $_POST['short_description'] ?? '',
                'short_description_en' => $_POST['short_description_en'] ?? '',
                'description' => $_POST['description'] ?? '',
                'description_en' => $_POST['description_en'] ?? '',
                'music_format' => $_POST['music_format'] ?? '',
                'music_format_en' => $_POST['music_format_en'] ?? '',
                'condition' => $_POST['condition'] ?? '',
                'condition_en' => $_POST['condition_en'] ?? '',
                'function_status' => $_POST['function_status'] ?? 'working',
                'power_connection' => $_POST['power_connection'] ?? '',
                'power_connection_en' => $_POST['power_connection_en'] ?? '',
                'dimensions' => $_POST['dimensions'] ?? '',
                'dimensions_en' => $_POST['dimensions_en'] ?? '',
                'equipment' => $_POST['equipment'] ?? '',
                'equipment_en' => $_POST['equipment_en'] ?? '',
                'weight' => $_POST['weight'] ?? null,
                'warehouse_address' => $_POST['warehouse_address'] ?? '',
                'price_day' => $_POST['price_day'] ?? 0,
                'featured' => isset($_POST['featured']) ? true : false,
                'order' => $_POST['order'] ?? 0,
                'category_id' => $_POST['category_id'] ?? '',
                'size' => $_POST['size'] ?? '',
                'color' => $_POST['color'] ?? '',
                'new_arrival' => isset($_POST['new_arrival']) ? true : false,
                'tags' => $_POST['tags'] ?? [],
                'main_image' => $mainImage,
                'gallery_images' => array_values($galleryImages),
                'created_at' => $jukebox['created_at']
            ];
            
            if (saveJukebox($data, $id)) {
                $galleryCount = count($galleryImages);
                redirect('/admin/dashboard.php?success=update&gallery=' . $galleryCount);
            } else {
                $error = 'Fehler beim Speichern.';
            }
        }
    }
}

$lang = getCurrentLanguage();
$pageTitle = __('admin_edit_jukebox');

include PARTIALS_PATH . 'admin-header.php';
?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo __('admin_edit_jukebox'); ?>: <?php echo e($jukebox['name']); ?></h2>
                </div>
                <div class="admin-card-body">
                    <?php if ($error): ?>
                    <div style="padding: var(--space-4); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                        <p style="color: #ef4444; margin-bottom: 0;"><?php echo e($error); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <div style="display: grid; gap: var(--space-8);">
                            <!-- Basisdaten -->
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Basisdaten</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Name (DE) *</label>
                                        <input type="text" name="name" class="form-input" value="<?php echo e($jukebox['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Name (EN)</label>
                                        <input type="text" name="name_en" class="form-input" value="<?php echo e($jukebox['name_en'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Hersteller</label>
                                        <input type="text" name="manufacturer" class="form-input" value="<?php echo e($jukebox['manufacturer']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Modell</label>
                                        <input type="text" name="model" class="form-input" value="<?php echo e($jukebox['model']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Baujahr</label>
                                        <input type="number" name="year" class="form-input" min="1900" max="2099" value="<?php echo e($jukebox['year']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Preis pro Tag (€) *</label>
                                        <input type="number" name="price_day" class="form-input" min="0" step="0.01" value="<?php echo e($jukebox['price_day']); ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Kategorie</label>
                                        <select name="category_id" class="form-select">
                                            <option value="">— Ohne Kategorie —</option>
                                            <?php foreach (getAllCategories(true) as $cat): ?>
                                            <option value="<?php echo e($cat['id']); ?>" <?php echo ($jukebox['category_id'] ?? '') === $cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filter & Katalog -->
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Filter & Katalog</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Größe</label>
                                        <input type="text" name="size" class="form-input" value="<?php echo e($jukebox['size'] ?? ''); ?>" placeholder="z.B. Klein, Mittel, Groß">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Farbe</label>
                                        <input type="text" name="color" class="form-input" value="<?php echo e($jukebox['color'] ?? ''); ?>" placeholder="z.B. Rot, Schwarz, Holz">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="new_arrival" value="1" <?php echo !empty($jukebox['new_arrival']) ? 'checked' : ''; ?>>
                                        <span>Neu im Sortiment</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Beschreibungen -->
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Beschreibungen</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Kurzbeschreibung (DE)</label>
                                        <textarea name="short_description" class="form-textarea" rows="2"><?php echo e($jukebox['short_description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Kurzbeschreibung (EN)</label>
                                        <textarea name="short_description_en" class="form-textarea" rows="2"><?php echo e($jukebox['short_description_en'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Ausführliche Beschreibung (DE)</label>
                                        <textarea name="description" class="form-textarea" rows="4"><?php echo e($jukebox['description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Ausführliche Beschreibung (EN)</label>
                                        <textarea name="description_en" class="form-textarea" rows="4"><?php echo e($jukebox['description_en'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Technische Daten -->
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Technische Daten</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Musikformat (DE)</label>
                                        <input type="text" name="music_format" class="form-input" value="<?php echo e($jukebox['music_format']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Musikformat (EN)</label>
                                        <input type="text" name="music_format_en" class="form-input" value="<?php echo e($jukebox['music_format_en'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Zustand (DE)</label>
                                        <input type="text" name="condition" class="form-input" value="<?php echo e($jukebox['condition']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Zustand (EN)</label>
                                        <input type="text" name="condition_en" class="form-input" value="<?php echo e($jukebox['condition_en'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Funktionsstatus</label>
                                        <select name="function_status" class="form-select">
                                            <option value="working" <?php echo $jukebox['function_status'] === 'working' ? 'selected' : ''; ?>>Voll funktionsfähig</option>
                                            <option value="deco" <?php echo $jukebox['function_status'] === 'deco' ? 'selected' : ''; ?>>Deko-Objekt</option>
                                            <option value="restored" <?php echo $jukebox['function_status'] === 'restored' ? 'selected' : ''; ?>>Restauriert</option>
                                            <option value="original" <?php echo $jukebox['function_status'] === 'original' ? 'selected' : ''; ?>>Originalzustand</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Stromanschluss (DE)</label>
                                        <input type="text" name="power_connection" class="form-input" value="<?php echo e($jukebox['power_connection']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Stromanschluss (EN)</label>
                                        <input type="text" name="power_connection_en" class="form-input" value="<?php echo e($jukebox['power_connection_en'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Abmessungen (DE)</label>
                                        <input type="text" name="dimensions" class="form-input" value="<?php echo e($jukebox['dimensions']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Abmessungen (EN)</label>
                                        <input type="text" name="dimensions_en" class="form-input" value="<?php echo e($jukebox['dimensions_en'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Gewicht (kg)</label>
                                        <input type="number" name="weight" class="form-input" min="0" step="0.1" value="<?php echo e($jukebox['weight'] ?? ''); ?>" placeholder="z.B. 85">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Bestückung (DE)</label>
                                        <input type="text" name="equipment" class="form-input" value="<?php echo e($jukebox['equipment'] ?? ''); ?>" placeholder="z.B. 100 CDs, 7 Vinyl-Singles, Bluetooth">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Bestückung (EN)</label>
                                        <input type="text" name="equipment_en" class="form-input" value="<?php echo e($jukebox['equipment_en'] ?? ''); ?>" placeholder="z.B. 100 CDs, 7 vinyl singles, Bluetooth">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Lageradresse</label>
                                        <input type="text" name="warehouse_address" class="form-input" value="<?php echo e($jukebox['warehouse_address'] ?? WAREHOUSE_ADDRESS_DEFAULT); ?>" placeholder="z.B. Oberstdorfer Straße 5, 2201 Seyring, Österreich">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Sortierung</label>
                                        <input type="number" name="order" class="form-input" value="<?php echo e($jukebox['order'] ?? 0); ?>" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tags -->
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);"><?php echo __('admin_tags'); ?></h3>
                                <div class="admin-tags-selection">
                                    <?php foreach (JUKEBOX_TAGS as $tagKey => $tagConfig): ?>
                                    <label class="form-checkbox admin-tag-checkbox admin-tag-checkbox-<?php echo e($tagConfig['color']); ?>">
                                        <input type="checkbox" name="tags[]" value="<?php echo e($tagKey); ?>" <?php echo in_array($tagKey, $jukebox['tags'] ?? [], true) ? 'checked' : ''; ?>>
                                        <span><?php echo e(getJukeboxTagLabel($tagKey)); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Bilder -->
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Bilder</h3>
                                <div class="admin-image-manager">
                                    <div class="admin-image-upload">
                                        <label class="form-label" style="cursor: pointer; margin-bottom: 0;">
                                            <span>Weitere Bilder auswählen</span>
                                            <input type="file" name="new_images[]" id="new-images-input" class="form-input" accept="image/*" multiple style="display: none;">
                                        </label>
                                        <p style="color: var(--color-text-muted); font-size: var(--text-sm); margin-top: var(--space-2); margin-bottom: 0;">Bilder per Drag & Drop sortieren. Das erste Bild wird automatisch zum Hauptbild.</p>
                                    </div>
                                    <div id="admin-image-list" class="admin-image-grid">
                                        <?php
                                        $allImages = [];
                                        if (!empty($jukebox['main_image'])) {
                                            $allImages[] = $jukebox['main_image'];
                                        }
                                        if (!empty($jukebox['gallery_images'])) {
                                            $allImages = array_merge($allImages, $jukebox['gallery_images']);
                                        }
                                        foreach ($allImages as $index => $img):
                                        ?>
                                        <div class="admin-image-item <?php echo $index === 0 ? 'is-main' : ''; ?>" draggable="true" data-existing="<?php echo e($img); ?>">
                                            <img src="<?php echo e(getJukeboxImageUrl($img)); ?>" alt="">
                                            <button type="button" class="admin-image-delete" aria-label="Entfernen">&times;</button>
                                            <input type="hidden" name="image_order[]" class="admin-image-order-input" value="<?php echo e($img); ?>">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <script>
                            (function() {
                                var input = document.getElementById('new-images-input');
                                var list = document.getElementById('admin-image-list');
                                var form = input.closest('form');
                                var fileMap = {};
                                var nextIndex = 0;

                                function updateMainState() {
                                    var items = list.querySelectorAll('.admin-image-item');
                                    items.forEach(function(item, index) {
                                        item.classList.toggle('is-main', index === 0);
                                    });
                                }

                                function updateOrderInputs() {
                                    list.querySelectorAll('.admin-image-order-input').forEach(function(el) { el.remove(); });
                                    var items = list.querySelectorAll('.admin-image-item');
                                    items.forEach(function(item) {
                                        var existing = item.getAttribute('data-existing');
                                        var idx = item.getAttribute('data-file-index');
                                        var hidden = document.createElement('input');
                                        hidden.type = 'hidden';
                                        hidden.name = 'image_order[]';
                                        hidden.className = 'admin-image-order-input';
                                        hidden.value = existing ? existing : 'new:' + idx;
                                        item.appendChild(hidden);
                                    });
                                }

                                function createItem(file, index) {
                                    var div = document.createElement('div');
                                    div.className = 'admin-image-item';
                                    div.setAttribute('draggable', 'true');
                                    div.setAttribute('data-file-index', index);
                                    var url = URL.createObjectURL(file);
                                    div.innerHTML = '<img src="' + url + '" alt="">' +
                                        '<button type="button" class="admin-image-delete" aria-label="Entfernen">&times;</button>';
                                    div.querySelector('.admin-image-delete').addEventListener('click', function(e) {
                                        e.stopPropagation();
                                        div.remove();
                                        updateMainState();
                                        updateOrderInputs();
                                    });
                                    div.addEventListener('dragstart', function(e) {
                                        div.classList.add('dragging');
                                        e.dataTransfer.effectAllowed = 'move';
                                    });
                                    div.addEventListener('dragend', function() {
                                        div.classList.remove('dragging');
                                        updateOrderInputs();
                                        updateMainState();
                                    });
                                    div.addEventListener('dragover', function(e) {
                                        e.preventDefault();
                                        var dragging = list.querySelector('.dragging');
                                        if (!dragging || dragging === div) return;
                                        var rect = div.getBoundingClientRect();
                                        var offsetX = e.clientX - rect.left;
                                        if (offsetX < rect.width / 2) {
                                            list.insertBefore(dragging, div);
                                        } else {
                                            list.insertBefore(dragging, div.nextSibling);
                                        }
                                    });
                                    return div;
                                }

                                // Bestehende Items mit Drag & Drop versehen
                                list.querySelectorAll('.admin-image-item').forEach(function(item) {
                                    item.querySelector('.admin-image-delete').addEventListener('click', function(e) {
                                        e.stopPropagation();
                                        item.remove();
                                        updateMainState();
                                        updateOrderInputs();
                                    });
                                    item.addEventListener('dragstart', function(e) {
                                        item.classList.add('dragging');
                                        e.dataTransfer.effectAllowed = 'move';
                                    });
                                    item.addEventListener('dragend', function() {
                                        item.classList.remove('dragging');
                                        updateOrderInputs();
                                        updateMainState();
                                    });
                                    item.addEventListener('dragover', function(e) {
                                        e.preventDefault();
                                        var dragging = list.querySelector('.dragging');
                                        if (!dragging || dragging === item) return;
                                        var rect = item.getBoundingClientRect();
                                        var offsetX = e.clientX - rect.left;
                                        if (offsetX < rect.width / 2) {
                                            list.insertBefore(dragging, item);
                                        } else {
                                            list.insertBefore(dragging, item.nextSibling);
                                        }
                                    });
                                });

                                input.addEventListener('change', function() {
                                    var files = Array.from(input.files);
                                    files.forEach(function(file) {
                                        var idx = nextIndex++;
                                        fileMap[idx] = file;
                                        list.appendChild(createItem(file, idx));
                                    });
                                    input.value = '';
                                    updateMainState();
                                    updateOrderInputs();
                                });

                                form.addEventListener('submit', function() {
                                    var items = list.querySelectorAll('.admin-image-item[data-file-index]');
                                    var dt = new DataTransfer();
                                    items.forEach(function(item, position) {
                                        var idx = item.getAttribute('data-file-index');
                                        if (fileMap[idx]) {
                                            dt.items.add(fileMap[idx]);
                                            item.setAttribute('data-file-index', position);
                                        }
                                    });
                                    input.files = dt.files;
                                    updateOrderInputs();
                                });

                                list.addEventListener('dragover', function(e) { e.preventDefault(); });
                                updateMainState();
                                updateOrderInputs();
                            })();
                            </script>
                            
                            <!-- Einstellungen -->
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Einstellungen</h3>
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="featured" value="1" <?php echo !empty($jukebox['featured']) ? 'checked' : ''; ?>>
                                        <span>Als Highlight anzeigen</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: var(--space-4); margin-top: var(--space-8); padding-top: var(--space-8); border-top: 1px solid var(--color-gray-700);">
                            <a href="/admin/dashboard.php" class="btn btn-dark"><?php echo __('btn_cancel'); ?></a>
                            <button type="submit" class="btn btn-primary"><?php echo __('btn_save'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            </main>
        </div>
    </div>
</div>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
