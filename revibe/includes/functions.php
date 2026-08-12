<?php
/**
 * Allgemeine Hilfsfunktionen
 */

/**
 * Sichere Ausgabe mit HTML-Escaping
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Prüft ob Admin eingeloggt ist
 */
function isAdminLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    // Timeout prüfen
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logoutAdmin();
        return false;
    }
    
    // Aktivität aktualisieren
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Admin-Login durchführen
 */
function loginAdmin($username, $password) {
    // Rate-Limiting prüfen
    if (isLoginLockedOut()) {
        return false;
    }
    
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        // Session-Fixation verhindern
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['last_activity'] = time();
        
        // Fehlversuche zurücksetzen
        unset($_SESSION['login_attempts']);
        unset($_SESSION['login_locked_until']);
        
        return true;
    }
    
    // Fehlversuch erfassen
    recordFailedLogin();
    return false;
}

/**
 * Fehlgeschlagenen Login-Versuch erfassen
 */
function recordFailedLogin() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['login_attempts']++;
    
    if ($_SESSION['login_attempts'] >= LOGIN_MAX_ATTEMPTS) {
        $_SESSION['login_locked_until'] = time() + LOGIN_LOCKOUT_TIME;
    }
}

/**
 * Prüft ob der Login aktuell gesperrt ist
 */
function isLoginLockedOut() {
    if (isset($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > time()) {
        return true;
    }
    
    // Sperre ist abgelaufen, zurücksetzen
    if (isset($_SESSION['login_locked_until'])) {
        unset($_SESSION['login_attempts']);
        unset($_SESSION['login_locked_until']);
    }
    
    return false;
}

/**
 * Verbleibende Sperrzeit in Sekunden
 */
function getLoginLockoutTimeRemaining() {
    if (isset($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > time()) {
        return $_SESSION['login_locked_until'] - time();
    }
    return 0;
}

/**
 * Admin-Logout
 */
function logoutAdmin() {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['last_activity']);
    session_destroy();
}

/**
 * Weiterleitung durchführen
 *
 * Relative Pfade, die mit "/" beginnen, werden automatisch
 * an BASE_URL angehängt, falls diese gesetzt ist.
 */
function redirect($url) {
    if (strpos($url, '/') === 0) {
        $base = rtrim(BASE_URL, '/');
        if ($base !== '' && strpos($url, $base . '/') !== 0) {
            $url = $base . $url;
        }
    }
    header('Location: ' . $url);
    exit;
}

/**
 * CSRF-Token generieren
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF-Token validieren
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF-Token zurücksetzen (nach erfolgreicher Aktion)
 */
function regenerateCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * E-Mail validieren
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Telefonnummer bereinigen
 */
function sanitizePhone($phone) {
    return preg_replace('/[^0-9+\-\s\(\)]/', '', $phone);
}

/**
 * Datum im österreichischen Format validieren (DD.MM.YYYY)
 */
function isValidDate($date) {
    $pattern = '/^(0[1-9]|[12][0-9]|3[01])\.(0[1-9]|1[0-2])\.(\d{4})$/';
    if (!preg_match($pattern, $date, $matches)) {
        return false;
    }
    return checkdate((int)$matches[2], (int)$matches[1], (int)$matches[3]);
}

/**
 * Bild-Upload durchführen (sicher)
 */
function uploadImage($file, $subdir = '') {
    $targetDir = UPLOADS_PATH . 'jukeboxes/';
    
    // Subdir validieren (nur alphanumerisch, Bindestrich, Unterstrich)
    if ($subdir) {
        $subdir = preg_replace('/[^a-zA-Z0-9_\-]/', '', $subdir);
        if ($subdir) {
            $targetDir .= $subdir . '/';
        }
    }
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Erlaubte Dateitypen anhand der tatsächlichen Datei validieren
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Dateigröße prüfen
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Datei zu groß. Maximale Größe: 5MB.'];
    }
    
    // MIME-Typ über finfo prüfen (nicht dem Client vertrauen)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($realMimeType, $allowedMimeTypes, true)) {
        return ['success' => false, 'error' => 'Ungültiges Dateiformat. Nur JPG, PNG und WebP erlaubt.'];
    }
    
    // Bild-Abmessungen prüfen (stellt sicher, dass es ein echtes Bild ist)
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'Die hochgeladene Datei ist kein gültiges Bild.'];
    }
    
    $imageMimeType = $imageInfo['mime'];
    if (!in_array($imageMimeType, $allowedMimeTypes, true)) {
        return ['success' => false, 'error' => 'Ungültiges Dateiformat. Nur JPG, PNG und WebP erlaubt.'];
    }
    
    // Extension validieren
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        // Extension anhand des echten MIME-Typs korrigieren
        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];
        $extension = $extensionMap[$realMimeType] ?? 'jpg';
    }
    
    // Eindeutigen Dateinamen generieren
    $filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $targetDir . $filename;
    
    // Stellen sicher, dass das Ziel innerhalb des Upload-Verzeichnisses liegt
    $realTargetDir = realpath($targetDir) ?: $targetDir;
    $realTargetPath = realpath(dirname($targetPath)) . '/' . basename($targetPath);
    if (strpos($realTargetPath, $realTargetDir) !== 0) {
        return ['success' => false, 'error' => 'Ungültiger Zielpfad.'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success' => true, 
            'filename' => ($subdir ? $subdir . '/' : '') . $filename,
            'url' => UPLOADS_URL . 'jukeboxes/' . ($subdir ? $subdir . '/' : '') . $filename
        ];
    }
    
    return ['success' => false, 'error' => 'Upload fehlgeschlagen.'];
}

/**
 * Bild löschen (mit Path-Traversal-Schutz)
 */
function deleteImage($filename) {
    if (empty($filename)) {
        return false;
    }
    
    $baseDir = realpath(UPLOADS_PATH . 'jukeboxes/');
    if (!$baseDir) {
        return false;
    }
    
    $path = realpath(UPLOADS_PATH . 'jukeboxes/' . $filename);
    
    if ($path === false) {
        return false;
    }
    
    // Sicherstellen, dass die Datei innerhalb des Upload-Verzeichnisses liegt
    if (strpos($path, $baseDir) !== 0) {
        return false;
    }
    
    if (is_file($path)) {
        return unlink($path);
    }
    
    return false;
}

/**
 * SEO-Meta-Tags generieren
 */
function getMetaTags($page = 'home', $customData = []) {
    $lang = getCurrentLanguage();
    $translations = getTranslations($lang);
    
    $defaults = [
        'title' => $translations['meta_title_' . $page] ?? $translations['meta_title_home'],
        'description' => $translations['meta_description_' . $page] ?? $translations['meta_description_home'],
        'image' => ASSETS_URL . 'images/og-default.jpg',
        'url' => BASE_URL
    ];
    
    $meta = array_merge($defaults, $customData);
    
    return [
        '<meta charset="UTF-8">',
        '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
        '<meta name="description" content="' . e($meta['description']) . '">',
        '<meta name="robots" content="index, follow">',
        '<title>' . e($meta['title']) . '</title>',
        '<!-- Open Graph -->',
        '<meta property="og:title" content="' . e($meta['title']) . '">',
        '<meta property="og:description" content="' . e($meta['description']) . '">',
        '<meta property="og:image" content="' . e($meta['image']) . '">',
        '<meta property="og:url" content="' . e($meta['url']) . '">',
        '<meta property="og:type" content="website">',
        '<meta property="og:locale" content="' . ($lang === 'de' ? 'de_AT' : 'en_US') . '">',
        '<!-- Twitter Card -->',
        '<meta name="twitter:card" content="summary_large_image">',
        '<meta name="twitter:title" content="' . e($meta['title']) . '">',
        '<meta name="twitter:description" content="' . e($meta['description']) . '">',
        '<meta name="twitter:image" content="' . e($meta['image']) . '">',
        '<!-- Favicon -->',
        '<link rel="icon" type="image/svg+xml" href="' . ASSETS_URL . 'images/favicon.svg">'
    ];
}

/**
 * Aktive Seite für Navigation markieren
 */
function isActivePage($page) {
    $current = basename($_SERVER['PHP_SELF'], '.php');
    return $current === $page ? 'active' : '';
}

/**
 * Formatierter Preis
 */
function formatPrice($price, $period = 'day') {
    $lang = getCurrentLanguage();
    $translations = getTranslations($lang);
    $periodText = $translations['price_' . $period] ?? '/ Tag';
    return 'ab ' . number_format($price, 0, ',', '.') . ' €' . $periodText;
}

/**
 * Sicherheits-Header setzen
 */
function setSecurityHeaders() {
    // Clickjacking-Schutz
    header('X-Frame-Options: DENY');
    // MIME-Type-Sniffing verhindern
    header('X-Content-Type-Options: nosniff');
    // XSS-Filter aktivieren (legacy)
    header('X-XSS-Protection: 1; mode=block');
    // Referrer-Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Content-Security-Policy (locker genug für diese Seite)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://images.unsplash.com https://via.placeholder.com; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");
}

/**
 * Rate-Limiting für Formulare (z. B. Kontaktformular)
 */
function isFormRateLimited($formKey = 'default', $maxAttempts = 5, $windowSeconds = 300) {
    $key = 'form_rate_' . $formKey;
    $now = time();
    
    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    // Alte Einträge entfernen
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $windowSeconds) {
        return $timestamp > ($now - $windowSeconds);
    });
    
    return count($_SESSION[$key]) >= $maxAttempts;
}

/**
 * Formular-Rate-Limiting erfassen
 */
function recordFormSubmission($formKey = 'default') {
    $key = 'form_rate_' . $formKey;
    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    $_SESSION[$key][] = time();
}
