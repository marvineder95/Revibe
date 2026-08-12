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
        // Hauptbild upload (nur wenn neues Bild hochgeladen)
        $mainImage = $jukebox['main_image'];
        if (!empty($_FILES['main_image']['tmp_name'])) {
            $result = uploadImage($_FILES['main_image']);
            if ($result['success']) {
                // Altes Bild löschen
                if ($mainImage) deleteImage($mainImage);
                $mainImage = $result['filename'];
            } else {
                $error = $result['error'];
            }
        }
        
        // Galerie-Bilder upload
        $galleryImages = $jukebox['gallery_images'] ?? [];
        if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['tmp_name'])) {
            $fileCount = count($_FILES['gallery_images']['tmp_name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $tmpName = $_FILES['gallery_images']['tmp_name'][$i];
                if (!empty($tmpName) && $_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'tmp_name' => $tmpName,
                        'name' => $_FILES['gallery_images']['name'][$i],
                        'type' => $_FILES['gallery_images']['type'][$i],
                        'size' => $_FILES['gallery_images']['size'][$i]
                    ];
                    $result = uploadImage($file);
                    if ($result['success']) {
                        $galleryImages[] = $result['filename'];
                    }
                }
            }
        }
        
        // Zu löschende Galeriebilder entfernen
        $imagesToDelete = isset($_POST['delete_gallery_images']) && is_array($_POST['delete_gallery_images']) 
            ? $_POST['delete_gallery_images'] 
            : [];
        foreach ($imagesToDelete as $img) {
            $img = sanitizeImageFilename($img);
            if ($img && in_array($img, $jukebox['gallery_images'] ?? [], true)) {
                deleteImage($img);
                $galleryImages = array_diff($galleryImages, [$img]);
            }
        }
        
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
                'price_day' => $_POST['price_day'] ?? 0,
                'featured' => isset($_POST['featured']) ? true : false,
                'order' => $_POST['order'] ?? 0,
                'category_id' => $_POST['category_id'] ?? '',
                'size' => $_POST['size'] ?? '',
                'color' => $_POST['color'] ?? '',
                'new_arrival' => isset($_POST['new_arrival']) ? true : false,
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
?>
<!DOCTYPE html>
<html lang="<?php echo e($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin_edit_jukebox'); ?> | <?php echo COMPANY_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
</head>
<body class="admin-body">
    <div class="admin-app">
        <?php require_once ROOT_PATH . 'partials/admin-sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-topbar">
                <button type="button" class="admin-mobile-toggle" onclick="openAdminSidebar()" aria-label="Menü">☰</button>
                <a href="/admin/dashboard.php" class="admin-sidebar-logo">
                    <?php if (file_exists(ROOT_PATH . 'assets/images/RevibeLogoPdf.png')): ?>
                    <img src="<?php echo ASSETS_URL; ?>images/RevibeLogoPdf.png" alt="<?php echo e(COMPANY_NAME); ?>" style="height: 28px;">
                    <?php else: ?>
                    <span><?php echo e(COMPANY_NAME); ?></span>
                    <?php endif; ?>
                </a>
                <a href="/admin/logout.php" class="btn btn-dark btn-sm"><?php echo __('admin_logout'); ?></a>
            </header>

            <main class="admin-main">
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
                                        <label class="form-label">Sortierung</label>
                                        <input type="number" name="order" class="form-input" value="<?php echo e($jukebox['order'] ?? 0); ?>" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bilder -->
                            <div>
                                <h3 style="margin-bottom: var(--space-4); color: var(--color-primary);">Bilder</h3>
                                
                                <!-- Aktuelles Hauptbild -->
                                <?php if ($jukebox['main_image']): ?>
                                <div class="form-group">
                                    <label class="form-label">Aktuelles Hauptbild</label>
                                    <div style="max-width: 200px;">
                                        <img src="<?php echo e(getJukeboxImageUrl($jukebox['main_image'])); ?>" alt="" style="width: 100%; border-radius: var(--radius-md);">
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Neues Hauptbild (optional)</label>
                                    <input type="file" name="main_image" class="form-input" accept="image/*">
                                </div>
                                
                                <!-- Aktuelle Galeriebilder -->
                                <?php if (!empty($jukebox['gallery_images'])): ?>
                                <div class="form-group">
                                    <label class="form-label">Aktuelle Galeriebilder</label>
                                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-2);">
                                        <?php foreach ($jukebox['gallery_images'] as $img): ?>
                                        <div style="position: relative;">
                                            <img src="<?php echo e(getJukeboxImageUrl($img)); ?>" alt="" style="width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: var(--radius-md);">
                                            <label style="position: absolute; top: var(--space-1); right: var(--space-1); padding: var(--space-1) var(--space-2); background: rgba(239, 68, 68, 0.9); color: white; font-size: 10px; border-radius: var(--radius-sm); cursor: pointer;">
                                                <input type="checkbox" name="delete_gallery_images[]" value="<?php echo e($img); ?>" style="display: none;">
                                                Löschen
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Neue Galeriebilder hinzufügen</label>
                                    <input type="file" name="gallery_images[]" class="form-input" accept="image/*" multiple>
                                </div>
                            </div>
                            
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

    <script>
        function openAdminSidebar() {
            document.getElementById('adminSidebar').classList.add('active');
            document.getElementById('adminOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAdminSidebar() {
            document.getElementById('adminSidebar').classList.remove('active');
            document.getElementById('adminOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }
    </script>
</body>
</html>
