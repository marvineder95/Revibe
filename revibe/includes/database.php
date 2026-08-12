<?php
/**
 * SQLite Datenbank-Verwaltung
 * Filebasierte Datenbank für Revibe
 */

// Pfad zur SQLite Datenbank
const DB_PATH = DATA_PATH . 'jukebox.db';

/**
 * Datenbank-Verbindung herstellen
 */
function getDbConnection() {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // SQLite für Foreign Keys aktivieren
        $db->exec('PRAGMA foreign_keys = ON');
        
        return $db;
    } catch (PDOException $e) {
        error_log('Datenbank-Fehler: ' . $e->getMessage());
        return null;
    }
}

/**
 * Datenbank-Tabellen initialisieren (falls nicht vorhanden)
 */
function initDatabase() {
    $db = getDbConnection();
    if (!$db) return false;
    
    try {
        // Jukeboxen Tabelle
        $db->exec('
            CREATE TABLE IF NOT EXISTS jukeboxes (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                name_en TEXT,
                manufacturer TEXT,
                model TEXT,
                year INTEGER,
                short_description TEXT,
                short_description_en TEXT,
                description TEXT,
                description_en TEXT,
                music_format TEXT,
                music_format_en TEXT,
                condition TEXT,
                condition_en TEXT,
                function_status TEXT DEFAULT "working",
                power_connection TEXT,
                power_connection_en TEXT,
                dimensions TEXT,
                dimensions_en TEXT,
                price_day REAL DEFAULT 0,
                featured INTEGER DEFAULT 0,
                "order" INTEGER DEFAULT 0,
                category_id TEXT,
                size TEXT,
                color TEXT,
                new_arrival INTEGER DEFAULT 0,
                main_image TEXT,
                gallery_images TEXT, -- JSON Array
                created_at TEXT,
                updated_at TEXT
            )
        ');
        
        // Index für schnellere Abfragen
        $db->exec('CREATE INDEX IF NOT EXISTS idx_featured ON jukeboxes(featured)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_order ON jukeboxes("order")');
        
        // Zusätzliche Spalten nachträglich hinzufügen (für Bestandsdatenbanken)
        $columnsToAdd = ['category_id' => 'TEXT', 'size' => 'TEXT', 'color' => 'TEXT', 'new_arrival' => 'INTEGER DEFAULT 0'];
        foreach ($columnsToAdd as $column => $type) {
            try {
                $db->exec("ALTER TABLE jukeboxes ADD COLUMN {$column} {$type}");
            } catch (PDOException $e) {
                // Spalte existiert bereits – ignorieren
            }
        }
        
        // Kategorien Tabelle
        $db->exec('
            CREATE TABLE IF NOT EXISTS categories (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                name_en TEXT,
                description TEXT,
                description_en TEXT,
                color TEXT DEFAULT "#0066B1",
                active INTEGER DEFAULT 1,
                sort_order INTEGER DEFAULT 0,
                created_at TEXT,
                updated_at TEXT
            )
        ');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_categories_active ON categories(active)');
        
        // Rabattregeln Tabelle
        $db->exec('
            CREATE TABLE IF NOT EXISTS discount_rules (
                id TEXT PRIMARY KEY,
                type TEXT NOT NULL, -- "duration" oder "quantity"
                threshold INTEGER NOT NULL,
                discount_percent REAL NOT NULL DEFAULT 0,
                active INTEGER DEFAULT 1,
                sort_order INTEGER DEFAULT 0,
                created_at TEXT,
                updated_at TEXT
            )
        ');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_discounts_type ON discount_rules(type)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_discounts_active ON discount_rules(active)');
        
        // Coupons Tabelle
        $db->exec('
            CREATE TABLE IF NOT EXISTS coupons (
                id TEXT PRIMARY KEY,
                code TEXT UNIQUE NOT NULL,
                description TEXT,
                discount_percent REAL NOT NULL DEFAULT 0,
                valid_from TEXT,
                valid_until TEXT,
                active INTEGER DEFAULT 1,
                min_order_value REAL DEFAULT 0,
                reusable INTEGER DEFAULT 1,
                combinable INTEGER DEFAULT 1,
                created_at TEXT,
                updated_at TEXT
            )
        ');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_coupons_active ON coupons(active)');
        
        // Settings Tabelle (Key-Value Store)
        $db->exec('
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            )
        ');

        // Anfragen Tabelle
        $db->exec('
            CREATE TABLE IF NOT EXISTS inquiries (
                id TEXT PRIMARY KEY,
                token TEXT UNIQUE NOT NULL,
                status TEXT DEFAULT "new",
                firstname TEXT,
                lastname TEXT,
                company TEXT,
                email TEXT,
                phone TEXT,
                message TEXT,
                date_start TEXT,
                date_end TEXT,
                duration_days INTEGER DEFAULT 1,
                event_address TEXT,
                pricing_json TEXT,
                transport_distance_km REAL DEFAULT 0,
                transport_duration_min INTEGER DEFAULT 0,
                transport_costs REAL DEFAULT 0,
                transport_error TEXT,
                created_at TEXT,
                updated_at TEXT
            )
        ');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_inquiries_token ON inquiries(token)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_inquiries_status ON inquiries(status)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_inquiries_email ON inquiries(email)');

        // Angebote Tabelle
        $db->exec('
            CREATE TABLE IF NOT EXISTS offers (
                id TEXT PRIMARY KEY,
                inquiry_id TEXT NOT NULL,
                offer_number TEXT UNIQUE NOT NULL,
                token TEXT UNIQUE NOT NULL,
                pdf_path TEXT,
                valid_until TEXT,
                status TEXT DEFAULT "pending",
                created_at TEXT,
                accepted_at TEXT,
                declined_at TEXT,
                updated_at TEXT,
                FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE CASCADE
            )
        ');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_offers_token ON offers(token)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_offers_status ON offers(status)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_offers_inquiry ON offers(inquiry_id)');

        // Rechnungen Tabelle
        $db->exec('
            CREATE TABLE IF NOT EXISTS invoices (
                id TEXT PRIMARY KEY,
                offer_id TEXT NOT NULL,
                inquiry_id TEXT NOT NULL,
                invoice_number TEXT UNIQUE NOT NULL,
                pdf_path TEXT,
                amount_gross REAL DEFAULT 0,
                status TEXT DEFAULT "open",
                created_at TEXT,
                paid_at TEXT,
                updated_at TEXT,
                FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
                FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE CASCADE
            )
        ');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_invoices_offer ON invoices(offer_id)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_invoices_inquiry ON invoices(inquiry_id)');

        // Vermietungen / Reservierungen Tabelle
        $db->exec('
            CREATE TABLE IF NOT EXISTS rentals (
                id TEXT PRIMARY KEY,
                jukebox_id TEXT NOT NULL,
                inquiry_id TEXT,
                offer_id TEXT,
                date_start TEXT NOT NULL,
                date_end TEXT NOT NULL,
                status TEXT DEFAULT "reserved",
                created_at TEXT,
                updated_at TEXT,
                FOREIGN KEY (jukebox_id) REFERENCES jukeboxes(id) ON DELETE CASCADE,
                FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE CASCADE,
                FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL
            )
        ');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_rentals_jukebox ON rentals(jukebox_id)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_rentals_dates ON rentals(date_start, date_end)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_rentals_status ON rentals(status)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_rentals_inquiry ON rentals(inquiry_id)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_rentals_offer ON rentals(offer_id)');

        return true;
    } catch (PDOException $e) {
        error_log('Datenbank-Initialisierungsfehler: ' . $e->getMessage());
        return false;
    }
}

/**
 * Migration von JSON zu SQLite durchführen
 * Wird einmalig beim ersten Aufruf ausgeführt
 */
function migrateFromJson() {
    $jsonFile = DATA_PATH . 'jukeboxes.json';
    
    // Prüfen ob JSON-Datei existiert
    if (!file_exists($jsonFile)) {
        return true;
    }
    
    // Prüfen ob bereits Daten in SQLite vorhanden sind
    $db = getDbConnection();
    if (!$db) return false;
    
    try {
        $stmt = $db->query('SELECT COUNT(*) as count FROM jukeboxes');
        $result = $stmt->fetch();
        
        // Wenn bereits Daten vorhanden, nicht migrieren
        if ($result['count'] > 0) {
            return true;
        }
    } catch (PDOException $e) {
        // Tabelle existiert noch nicht, Initialisierung nötig
        initDatabase();
    }
    
    // JSON-Daten laden
    $jsonData = file_get_contents($jsonFile);
    $jukeboxes = json_decode($jsonData, true);
    
    if (empty($jukeboxes) || !is_array($jukeboxes)) {
        return true;
    }
    
    // Daten migrieren
    $db = getDbConnection();
    $db->beginTransaction();
    
    try {
        $stmt = $db->prepare('
            INSERT INTO jukeboxes (
                id, name, name_en, manufacturer, model, year,
                short_description, short_description_en, description, description_en,
                music_format, music_format_en, condition, condition_en, function_status,
                power_connection, power_connection_en, dimensions, dimensions_en,
                price_day, featured, "order", category_id, size, color, new_arrival, main_image, gallery_images, created_at, updated_at
            ) VALUES (
                :id, :name, :name_en, :manufacturer, :model, :year,
                :short_description, :short_description_en, :description, :description_en,
                :music_format, :music_format_en, :condition, :condition_en, :function_status,
                :power_connection, :power_connection_en, :dimensions, :dimensions_en,
                :price_day, :featured, :order, :category_id, :size, :color, :new_arrival, :main_image, :gallery_images, :created_at, :updated_at
            )
        ');
        
        foreach ($jukeboxes as $jukebox) {
            $stmt->execute([
                ':id' => $jukebox['id'] ?? generateJukeboxId(),
                ':name' => $jukebox['name'] ?? '',
                ':name_en' => $jukebox['name_en'] ?? '',
                ':manufacturer' => $jukebox['manufacturer'] ?? '',
                ':model' => $jukebox['model'] ?? '',
                ':year' => $jukebox['year'] ?? null,
                ':short_description' => $jukebox['short_description'] ?? '',
                ':short_description_en' => $jukebox['short_description_en'] ?? '',
                ':description' => $jukebox['description'] ?? '',
                ':description_en' => $jukebox['description_en'] ?? '',
                ':music_format' => $jukebox['music_format'] ?? '',
                ':music_format_en' => $jukebox['music_format_en'] ?? '',
                ':condition' => $jukebox['condition'] ?? '',
                ':condition_en' => $jukebox['condition_en'] ?? '',
                ':function_status' => $jukebox['function_status'] ?? 'working',
                ':power_connection' => $jukebox['power_connection'] ?? '',
                ':power_connection_en' => $jukebox['power_connection_en'] ?? '',
                ':dimensions' => $jukebox['dimensions'] ?? '',
                ':dimensions_en' => $jukebox['dimensions_en'] ?? '',
                ':price_day' => $jukebox['price_day'] ?? 0,
                ':featured' => !empty($jukebox['featured']) ? 1 : 0,
                ':order' => $jukebox['order'] ?? 0,
                ':category_id' => $jukebox['category_id'] ?? null,
                ':size' => $jukebox['size'] ?? null,
                ':color' => $jukebox['color'] ?? null,
                ':new_arrival' => !empty($jukebox['new_arrival']) ? 1 : 0,
                ':main_image' => $jukebox['main_image'] ?? '',
                ':gallery_images' => json_encode($jukebox['gallery_images'] ?? []),
                ':created_at' => $jukebox['created_at'] ?? date('Y-m-d H:i:s'),
                ':updated_at' => $jukebox['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
        
        $db->commit();
        
        // JSON-Datei als Backup umbenennen
        rename($jsonFile, $jsonFile . '.backup');
        
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Migrationsfehler: ' . $e->getMessage());
        return false;
    }
}
