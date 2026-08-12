<?php
/**
 * Jukebox-Datenmodell
 * SQLite-basierte Speicherung
 */

require_once __DIR__ . '/database.php';

// Konstanten
const JUKEBOX_UPLOAD_DIR = 'jukeboxes/';

// Initialisierung beim ersten Aufruf
initDatabase();
migrateFromJson();

/**
 * Alle Jukeboxen laden
 */
function getAllJukeboxes($sortBy = 'order', $order = 'ASC', $filters = []) {
    $db = getDbConnection();
    if (!$db) return [];
    
    // Erlaubte Sortierfelder (SQL Injection verhindern)
    $allowedSortFields = ['name', 'price_day', 'year', 'order', 'created_at'];
    $sortBy = in_array($sortBy, $allowedSortFields, true) ? $sortBy : 'order';
    $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    
    try {
        $whereConditions = [];
        $params = [];
        
        // Hersteller (Marke)
        if (!empty($filters['manufacturer']) && is_array($filters['manufacturer'])) {
            $placeholders = implode(',', array_fill(0, count($filters['manufacturer']), '?'));
            $whereConditions[] = "manufacturer IN ($placeholders)";
            $params = array_merge($params, $filters['manufacturer']);
        }
        
        // Größe
        if (!empty($filters['size']) && is_array($filters['size'])) {
            $placeholders = implode(',', array_fill(0, count($filters['size']), '?'));
            $whereConditions[] = "size IN ($placeholders)";
            $params = array_merge($params, $filters['size']);
        }
        
        // Farbe
        if (!empty($filters['color']) && is_array($filters['color'])) {
            $placeholders = implode(',', array_fill(0, count($filters['color']), '?'));
            $whereConditions[] = "color IN ($placeholders)";
            $params = array_merge($params, $filters['color']);
        }
        
        // Funktionsstatus
        if (!empty($filters['function_status']) && is_array($filters['function_status'])) {
            $placeholders = implode(',', array_fill(0, count($filters['function_status']), '?'));
            $whereConditions[] = "function_status IN ($placeholders)";
            $params = array_merge($params, $filters['function_status']);
        }
        
        // Preisbereich
        if (!empty($filters['price_min']) && is_numeric($filters['price_min'])) {
            $whereConditions[] = "price_day >= ?";
            $params[] = (float)$filters['price_min'];
        }
        if (!empty($filters['price_max']) && is_numeric($filters['price_max'])) {
            $whereConditions[] = "price_day <= ?";
            $params[] = (float)$filters['price_max'];
        }
        
        // Neu bei uns
        if (!empty($filters['new_arrival'])) {
            $whereConditions[] = "new_arrival = 1";
        }
        
        // Kategorie
        if (!empty($filters['category_id']) && is_array($filters['category_id'])) {
            $placeholders = implode(',', array_fill(0, count($filters['category_id']), '?'));
            $whereConditions[] = "category_id IN ($placeholders)";
            $params = array_merge($params, $filters['category_id']);
        }
        
        $whereSql = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Sichere Identifier-Quoting für SQLite
        $quotedSortBy = '`' . str_replace('`', '``', $sortBy) . '`';
        $stmt = $db->prepare("SELECT * FROM jukeboxes $whereSql ORDER BY $quotedSortBy $order");
        $stmt->execute($params);
        $jukeboxes = $stmt->fetchAll();
        
        // Gallery-Images von JSON decodieren
        foreach ($jukeboxes as &$jukebox) {
            $jukebox['gallery_images'] = json_decode($jukebox['gallery_images'] ?? '[]', true) ?: [];
            $jukebox['featured'] = (bool)$jukebox['featured'];
            $jukebox['new_arrival'] = (bool)$jukebox['new_arrival'];
            $jukebox['price_day'] = (float)$jukebox['price_day'];
            $jukebox['year'] = $jukebox['year'] ? (int)$jukebox['year'] : null;
            $jukebox['order'] = (int)$jukebox['order'];
        }
        
        return $jukeboxes;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden aller Jukeboxen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Einzelne Jukebox laden
 */
function getJukeboxById($id) {
    $db = getDbConnection();
    if (!$db) return null;
    
    try {
        $stmt = $db->prepare('SELECT * FROM jukeboxes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $jukebox = $stmt->fetch();
        
        if (!$jukebox) return null;
        
        // Gallery-Images von JSON decodieren
        $jukebox['gallery_images'] = json_decode($jukebox['gallery_images'] ?? '[]', true) ?: [];
        $jukebox['featured'] = (bool)$jukebox['featured'];
        $jukebox['new_arrival'] = (bool)$jukebox['new_arrival'];
        $jukebox['price_day'] = (float)$jukebox['price_day'];
        $jukebox['year'] = $jukebox['year'] ? (int)$jukebox['year'] : null;
        $jukebox['order'] = (int)$jukebox['order'];
        
        return $jukebox;
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Jukebox: ' . $e->getMessage());
        return null;
    }
}

/**
 * Featured Jukeboxen laden
 */
function getFeaturedJukeboxes($limit = 3) {
    $db = getDbConnection();
    if (!$db) return [];
    
    try {
        // Zuerst featured Jukeboxen laden
        $stmt = $db->prepare('
            SELECT * FROM jukeboxes 
            WHERE featured = 1 
            ORDER BY `order` ASC, name ASC 
            LIMIT :limit
        ');
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $featured = $stmt->fetchAll();
        
        // Falls nicht genug, mit nicht-featured auffüllen
        if (count($featured) < $limit) {
            $existingIds = array_column($featured, 'id');
            $placeholders = !empty($existingIds) ? implode(',', array_fill(0, count($existingIds), '?')) : '';
            
            $sql = 'SELECT * FROM jukeboxes';
            if (!empty($existingIds)) {
                $sql .= ' WHERE id NOT IN (' . $placeholders . ')';
            }
            $sql .= ' ORDER BY `order` ASC, name ASC LIMIT ?';
            
            $stmt = $db->prepare($sql);
            $params = !empty($existingIds) ? array_merge($existingIds, [$limit - count($featured)]) : [$limit - count($featured)];
            $stmt->execute($params);
            $additional = $stmt->fetchAll();
            
            $featured = array_merge($featured, $additional);
        }
        
        // Gallery-Images decodieren
        foreach ($featured as &$jukebox) {
            $jukebox['gallery_images'] = json_decode($jukebox['gallery_images'] ?? '[]', true) ?: [];
            $jukebox['featured'] = (bool)$jukebox['featured'];
            $jukebox['price_day'] = (float)$jukebox['price_day'];
        }
        
        return array_slice($featured, 0, $limit);
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Featured Jukeboxen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Jukebox speichern (neu oder aktualisieren)
 */
function saveJukebox($data, $id = null) {
    $db = getDbConnection();
    if (!$db) return false;
    
    // ID generieren falls neu
    $jukeboxId = $id ?: generateJukeboxId();
    
    // Daten vorbereiten (kein htmlspecialchars() vor dem Speichern!)
    $jukebox = [
        ':id' => $jukeboxId,
        ':name' => sanitizeInput($data['name'] ?? ''),
        ':name_en' => sanitizeInput($data['name_en'] ?? ''),
        ':manufacturer' => sanitizeInput($data['manufacturer'] ?? ''),
        ':model' => sanitizeInput($data['model'] ?? ''),
        ':year' => validateYear($data['year'] ?? null),
        ':short_description' => sanitizeInput($data['short_description'] ?? ''),
        ':short_description_en' => sanitizeInput($data['short_description_en'] ?? ''),
        ':description' => sanitizeInput($data['description'] ?? ''),
        ':description_en' => sanitizeInput($data['description_en'] ?? ''),
        ':music_format' => sanitizeInput($data['music_format'] ?? ''),
        ':music_format_en' => sanitizeInput($data['music_format_en'] ?? ''),
        ':condition' => sanitizeInput($data['condition'] ?? ''),
        ':condition_en' => sanitizeInput($data['condition_en'] ?? ''),
        ':function_status' => sanitizeFunctionStatus($data['function_status'] ?? 'working'),
        ':power_connection' => sanitizeInput($data['power_connection'] ?? ''),
        ':power_connection_en' => sanitizeInput($data['power_connection_en'] ?? ''),
        ':dimensions' => sanitizeInput($data['dimensions'] ?? ''),
        ':dimensions_en' => sanitizeInput($data['dimensions_en'] ?? ''),
        ':price_day' => validatePrice($data['price_day'] ?? 0),
        ':featured' => !empty($data['featured']) ? 1 : 0,
        ':order' => !empty($data['order']) ? (int)$data['order'] : 0,
        ':category_id' => sanitizeInput($data['category_id'] ?? ''),
        ':size' => sanitizeInput($data['size'] ?? ''),
        ':color' => sanitizeInput($data['color'] ?? ''),
        ':new_arrival' => !empty($data['new_arrival']) ? 1 : 0,
        ':main_image' => sanitizeImageFilename($data['main_image'] ?? ''),
        ':gallery_images' => json_encode(sanitizeGalleryImages($data['gallery_images'] ?? [])),
        ':updated_at' => date('Y-m-d H:i:s')
    ];
    
    try {
        // Prüfen ob Jukebox bereits existiert
        $existing = getJukeboxById($jukeboxId);
        
        if ($existing) {
            // UPDATE
            $stmt = $db->prepare('
                UPDATE jukeboxes SET
                    name = :name,
                    name_en = :name_en,
                    manufacturer = :manufacturer,
                    model = :model,
                    year = :year,
                    short_description = :short_description,
                    short_description_en = :short_description_en,
                    description = :description,
                    description_en = :description_en,
                    music_format = :music_format,
                    music_format_en = :music_format_en,
                    condition = :condition,
                    condition_en = :condition_en,
                    function_status = :function_status,
                    power_connection = :power_connection,
                    power_connection_en = :power_connection_en,
                    dimensions = :dimensions,
                    dimensions_en = :dimensions_en,
                    price_day = :price_day,
                    featured = :featured,
                    `order` = :order,
                    category_id = :category_id,
                    size = :size,
                    color = :color,
                    new_arrival = :new_arrival,
                    main_image = :main_image,
                    gallery_images = :gallery_images,
                    updated_at = :updated_at
                WHERE id = :id
            ');
        } else {
            // INSERT
            $jukebox[':created_at'] = date('Y-m-d H:i:s');
            $stmt = $db->prepare('
                INSERT INTO jukeboxes (
                    id, name, name_en, manufacturer, model, year,
                    short_description, short_description_en, description, description_en,
                    music_format, music_format_en, condition, condition_en, function_status,
                    power_connection, power_connection_en, dimensions, dimensions_en,
                    price_day, featured, `order`, category_id, size, color, new_arrival, main_image, gallery_images, created_at, updated_at
                ) VALUES (
                    :id, :name, :name_en, :manufacturer, :model, :year,
                    :short_description, :short_description_en, :description, :description_en,
                    :music_format, :music_format_en, :condition, :condition_en, :function_status,
                    :power_connection, :power_connection_en, :dimensions, :dimensions_en,
                    :price_day, :featured, :order, :category_id, :size, :color, :new_arrival, :main_image, :gallery_images, :created_at, :updated_at
                )
            ');
        }
        
        $stmt->execute($jukebox);
        return $jukeboxId;
    } catch (PDOException $e) {
        error_log('Fehler beim Speichern der Jukebox: ' . $e->getMessage());
        return false;
    }
}

/**
 * Jukebox löschen
 */
function deleteJukebox($id) {
    $db = getDbConnection();
    if (!$db) return false;
    
    // Jukebox laden um Bilder zu löschen
    $jukebox = getJukeboxById($id);
    if (!$jukebox) return false;
    
    try {
        // Aus Datenbank löschen
        $stmt = $db->prepare('DELETE FROM jukeboxes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        
        // Bilder löschen
        if (!empty($jukebox['main_image'])) {
            deleteImage($jukebox['main_image']);
        }
        if (!empty($jukebox['gallery_images'])) {
            foreach ($jukebox['gallery_images'] as $image) {
                deleteImage($image);
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Fehler beim Löschen der Jukebox: ' . $e->getMessage());
        return false;
    }
}

/**
 * Eindeutige ID generieren
 */
function generateJukeboxId() {
    return 'jb_' . bin2hex(random_bytes(8));
}

/**
 * Eingabe bereinigen (für Datenbank-Speicherung)
 * WICHTIG: Kein htmlspecialchars() hier – das gehört in die Ausgabe!
 */
function sanitizeInput($input) {
    if ($input === null) return '';
    $input = trim((string)$input);
    $input = stripslashes($input);
    return $input;
}

/**
 * Baujahr validieren
 */
function validateYear($year) {
    if (empty($year)) return null;
    $year = (int)$year;
    if ($year < 1900 || $year > 2099) return null;
    return $year;
}

/**
 * Preis validieren
 */
function validatePrice($price) {
    if (empty($price)) return 0;
    $price = (float)str_replace(',', '.', (string)$price);
    if ($price < 0) return 0;
    if ($price > 999999) return 999999;
    return $price;
}

/**
 * Funktionsstatus validieren
 */
function sanitizeFunctionStatus($status) {
    $allowed = ['working', 'deco', 'restored', 'original'];
    return in_array($status, $allowed, true) ? $status : 'working';
}

/**
 * Bild-Dateiname bereinigen
 */
function sanitizeImageFilename($filename) {
    if (empty($filename)) return '';
    // Nur erlaubte Zeichen: alphanumerisch, Unterstrich, Bindestrich, Punkt, Slash
    $filename = preg_replace('/[^a-zA-Z0-9_\.\-\/]/', '', $filename);
    return $filename;
}

/**
 * Galerie-Bilder bereinigen
 */
function sanitizeGalleryImages($images) {
    if (!is_array($images)) return [];
    $clean = [];
    foreach ($images as $img) {
        $img = sanitizeImageFilename($img);
        if ($img) {
            $clean[] = $img;
        }
    }
    return array_values(array_unique($clean));
}

/**
 * Jukebox-Bild-URL generieren
 */
function getJukeboxImageUrl($filename, $size = 'full') {
    if (empty($filename)) {
        // Externer Placeholder wenn kein Bild hochgeladen wurde
        return 'https://images.unsplash.com/photo-1514525253440-b393452e8d26?w=600&q=80';
    }
    
    return UPLOADS_URL . 'jukeboxes/' . $filename;
}

/**
 * Lokalisierten Wert abrufen
 */
function getLocalizedValue($jukebox, $field, $lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    $localizedField = $field . ($lang === 'en' ? '_en' : '');
    
    if (!empty($jukebox[$localizedField])) {
        return $jukebox[$localizedField];
    }
    
    // Fallback auf Standardsprache
    return $jukebox[$field] ?? '';
}

/**
 * Funktionsstatus übersetzen
 */
function getFunctionStatusLabel($status, $lang = null) {
    $lang = $lang ?: getCurrentLanguage();
    
    $labels = [
        'de' => [
            'working' => 'Voll funktionsfähig',
            'deco' => 'Deko-Objekt',
            'restored' => 'Restauriert',
            'original' => 'Originalzustand'
        ],
        'en' => [
            'working' => 'Fully Functional',
            'deco' => 'Decorative Object',
            'restored' => 'Restored',
            'original' => 'Original Condition'
        ]
    ];
    
    return $labels[$lang][$status] ?? $status;
}

/**
 * Anfrageliste aus Cookie laden
 */
function getInquiryList() {
    if (isset($_COOKIE['jukebox_inquiry'])) {
        $list = json_decode($_COOKIE['jukebox_inquiry'], true);
        if (is_array($list)) {
            // Nur existierende Jukeboxen behalten
            $validIds = [];
            foreach ($list as $id) {
                if (getJukeboxById($id)) {
                    $validIds[] = $id;
                }
            }
            return $validIds;
        }
    }
    return [];
}

/**
 * Anfrageliste speichern
 */
function saveInquiryList($list) {
    $json = json_encode(array_values($list));
    setcookie('jukebox_inquiry', $json, [
        'expires' => time() + (30 * 24 * 60 * 60), // 30 Tage
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Zur Anfrageliste hinzufügen
 */
function addToInquiryList($jukeboxId) {
    $list = getInquiryList();
    if (!in_array($jukeboxId, $list)) {
        $list[] = $jukeboxId;
        saveInquiryList($list);
    }
    return $list;
}

/**
 * Aus Anfrageliste entfernen
 */
function removeFromInquiryList($jukeboxId) {
    $list = getInquiryList();
    $list = array_diff($list, [$jukeboxId]);
    saveInquiryList(array_values($list));
    return $list;
}

/**
 * Anfrageliste leeren
 */
function clearInquiryList() {
    saveInquiryList([]);
}

/**
 * Anfrageliste mit Details laden
 */
function getInquiryListWithDetails() {
    $ids = getInquiryList();
    $jukeboxes = [];
    
    foreach ($ids as $id) {
        $jukebox = getJukeboxById($id);
        if ($jukebox) {
            $jukeboxes[] = $jukebox;
        }
    }
    
    return $jukeboxes;
}

/**
 * Ist Jukebox in Anfrageliste?
 */
function isInInquiryList($jukeboxId) {
    $list = getInquiryList();
    return in_array($jukeboxId, $list);
}


/**
 * Hilfsfunktionen für Katalog-Filter
 */

/**
 * Alle eindeutigen Hersteller für Filter laden
 */
function getFilterManufacturers() {
    $db = getDbConnection();
    if (!$db) return [];
    
    try {
        $stmt = $db->query('SELECT DISTINCT manufacturer FROM jukeboxes WHERE manufacturer IS NOT NULL AND manufacturer != "" ORDER BY manufacturer ASC');
        return array_column($stmt->fetchAll(), 'manufacturer');
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Hersteller: ' . $e->getMessage());
        return [];
    }
}

/**
 * Alle eindeutigen Größen für Filter laden
 */
function getFilterSizes() {
    $db = getDbConnection();
    if (!$db) return [];
    
    try {
        $stmt = $db->query('SELECT DISTINCT size FROM jukeboxes WHERE size IS NOT NULL AND size != "" ORDER BY size ASC');
        return array_column($stmt->fetchAll(), 'size');
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Größen: ' . $e->getMessage());
        return [];
    }
}

/**
 * Alle eindeutigen Farben für Filter laden
 */
function getFilterColors() {
    $db = getDbConnection();
    if (!$db) return [];
    
    try {
        $stmt = $db->query('SELECT DISTINCT color FROM jukeboxes WHERE color IS NOT NULL AND color != "" ORDER BY color ASC');
        return array_column($stmt->fetchAll(), 'color');
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Farben: ' . $e->getMessage());
        return [];
    }
}

/**
 * Preisspanne der Jukeboxen ermitteln
 */
function getPriceRange() {
    $db = getDbConnection();
    if (!$db) return ['min' => 0, 'max' => 0];
    
    try {
        $stmt = $db->query('SELECT MIN(price_day) as min, MAX(price_day) as max FROM jukeboxes');
        $result = $stmt->fetch();
        return [
            'min' => (float)($result['min'] ?? 0),
            'max' => (float)($result['max'] ?? 0)
        ];
    } catch (PDOException $e) {
        error_log('Fehler beim Laden der Preisspanne: ' . $e->getMessage());
        return ['min' => 0, 'max' => 0];
    }
}
