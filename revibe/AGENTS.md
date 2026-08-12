# Revibe – Agent-Dokumentation

> Zuletzt aktualisiert: 2026-08-11
> Sprache der Codebasis: Deutsch (mit englischer UI-Übersetzung)

---

## Projektübersicht

**Revibe** ist eine produktionsreife, mehrsprachige Website (Deutsch/Englisch) zur Vermietung von Jukeboxen. Der öffentliche Bereich bietet einen Jukebox-Katalog, eine Detailansicht, einen session-basierten Anfragekorb mit Live-Preisberechnung, Transportkostenberechnung, ein Kontaktformular und ein Angebots-/Annahme-Workflow. Der passwortgeschützte Admin-Bereich dient der Verwaltung von Jukeboxen, Kategorien, Rabattregeln, Coupons, globalen Einstellungen, Anfragen, Angeboten, Rechnungen und einem Vermietungskalender.

Das Projekt liegt im Unterverzeichnis `revibe/`. Alle Pfade in dieser Dokumentation beziehen sich auf dieses Unterverzeichnis, sofern nicht anders angegeben.

### Wichtige Funktionsbereiche

- **Anfragekorb (Cart):** Session-basiert (`$_SESSION['jukebox_cart']`), mit synchronisiertem Cookie-Fallback (`jukebox_inquiry`) für die Sidebar.
- **Datumsauswahl:** Kunden müssen vor dem Hinzufügen einer Jukebox zum Warenkorb einen Mietzeitraum wählen. Die Verfügbarkeit wird per AJAX geprüft.
- **Live-Preisvorschau:** Berechnet Mietkosten, Mietdauer-/Mengenrabatte, Coupon-Rabatte, Transportkosten, USt. und die gesetzliche österreichische Vertragsgebühr (§ 33 TP 5 GebG 1957, 1 % des Bruttowertes) auf `request.php` und `contact.php`.
- **Produktkategorien:** Jukeboxen können Kategorien mit Farbe/Sortierung zugeordnet werden.
- **Rabattlogik:** Zwei Regeltypen in `discount_rules` – Mietdauer-Rabatt (`duration`) und Mengenrabatt (`quantity`).
- **Coupon-System:** Gültigkeitszeitraum, Mindestbestellwert, Mehrfachverwendung (`reusable`) und Kombinierbarkeit (`combinable`).
- **Transportkosten:** Berechnung via Google Maps Distance Matrix API mit Fallback.
- **Anfragen/Angebote/Rechnungen:** Anfragen erzeugen Reservierungen; Admin kann PDF-Angebote erstellen; Kunden können Angebote online annehmen/ablehnen; bei Annahme wird automatisch eine PDF-Rechnung erstellt.
- **Admin-Kalender:** Monatsansicht aller Vermietungen/Reservierungen mit Status-Filter.

---

## Technologie-Stack

- **Backend:** PHP 7.4+ (prozedural, kein Framework, keine Composer-Abhängigkeiten).
- **Datenbank:** SQLite (`data/jukebox.db`), automatisch initialisiert.
- **Frontend:** HTML5, Vanilla CSS (`assets/css/style.css`), Vanilla JavaScript (`assets/js/main.js`).
- **PDF:** TCPDF unter `includes/tcpdf/` für Angebots- und Rechnungs-PDFs.
- **Externe Ressourcen:** flatpickr wird über `cdn.jsdelivr.net` geladen (CSS/JS); Google Fonts und Google Maps API werden verwendet.
- **Webserver:** Apache (empfohlen, `.htaccess` vorhanden).
- **Mail-Versand:** PHP-native `mail()`-Funktion.
- **Abhängigkeiten:** Keine externen PHP-/JS-Pakete (kein Composer, kein npm, kein `package.json`, `pyproject.toml`, `Cargo.toml`, `Makefile` o. ä.).

---

## Verzeichnisstruktur

```
revibe/
├── admin/                  # Admin-Bereich (CRUD + Login)
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php       # Übersicht aller Jukeboxen
│   ├── create.php          # Jukebox erstellen
│   ├── edit.php            # Jukebox bearbeiten
│   ├── delete.php          # Löschbestätigung + Löschung
│   ├── settings.php        # Globale Einstellungen (Steuer, Transport, API, Bankdaten)
│   ├── categories.php      # Kategorien CRUD
│   ├── discounts.php       # Rabattregeln CRUD
│   ├── coupons.php         # Coupons CRUD
│   ├── offers.php          # Anfragen/Angebote verwalten
│   ├── invoices.php        # Rechnungen verwalten
│   ├── calendar.php        # Vermietungskalender
│   └── .htaccess           # Erhöhte PHP-Upload-Limits
├── assets/
│   ├── css/
│   │   ├── style.css       # Hauptstylesheet
│   │   └── flatpickr-custom.css
│   ├── js/
│   │   └── main.js         # Haupt-JavaScript
│   └── images/             # Leer; Bilder liegen unter uploads/
├── config/
│   └── config.php          # Zentrale Konfiguration & Bootstrap
├── data/
│   ├── admin_config.php    # Geschützte Admin-Zugangsdaten (automatisch generiert)
│   ├── jukebox.db          # SQLite-Datenbank
│   └── jukeboxes.json.backup # Backup der alten JSON-Daten
├── includes/
│   ├── tcpdf/              # TCPDF-Bibliothek für PDF-Generierung
│   ├── functions.php       # Hilfsfunktionen (Auth, Upload, Meta-Tags, Security-Header)
│   ├── language.php        # Übersetzungssystem (DE/EN)
│   ├── database.php        # SQLite-Verbindung & Initialisierung
│   ├── jukebox-model.php   # Datenmodell Jukeboxen (CRUD)
│   ├── settings-model.php  # Globale Einstellungen (Key-Value)
│   ├── categories-model.php# Kategorien CRUD
│   ├── discounts-model.php # Rabattregeln CRUD
│   ├── coupons-model.php   # Coupons CRUD
│   ├── inquiries-model.php # Anfragen CRUD
│   ├── offers-model.php    # Angebote CRUD
│   ├── invoices-model.php  # Rechnungen CRUD
│   ├── rentals-model.php   # Vermietungen / Reservierungen CRUD
│   ├── cart.php            # Session-basierter Anfragekorb
│   ├── pricing.php         # Zentrale Preisberechnung
│   ├── transport.php       # Google Maps API + Transportkosten
│   ├── pdf.php             # PDF-Generierung für Angebote/Rechnungen
│   └── ajax.php            # AJAX-Endpunkte (Cart, Pricing, Inquiry, Availability)
├── partials/
│   ├── header.php          # HTML-Head + Navigation + Inquiry-Sidebar
│   ├── footer.php          # Footer + Cookie-Hinweis + Script-Tag
│   ├── admin-sidebar.php   # Admin-Navigation
│   ├── date-selector.php   # Wiederverwendbarer Datums-Selektor
│   ├── pricing-summary.php # Preiszusammenfassung (vollständig)
│   └── pricing-compact.php # Kompakte Preiszusammenfassung für Kontaktseite
├── uploads/jukeboxes/      # Hochgeladene Jukebox-Bilder
├── uploads/pdfs/           # Generierte Angebots- und Rechnungs-PDFs
├── .htaccess               # Apache-Konfiguration (Security, Caching, Compression)
├── .user.ini               # PHP-Upload-Limits (Fallback)
├── php.ini                 # PHP-Konfiguration (Upload-Größen)
├── index.php               # Startseite
├── catalog.php             # Katalog-Übersicht
├── jukebox.php             # Detailseite einer Jukebox
├── request.php             # Anfragekorb + Preisvorschau
├── process.php             # Mietablauf-Seite
├── contact.php             # Kontaktformular + finale Preisübersicht
├── offer.php               # Öffentliche Angebotsseite (Annehmen/Ablehnen)
├── about.php               # Über-uns-Seite
├── faq.php                 # FAQ-Seite
├── impressum.php           # Impressum
├── datenschutz.php         # Datenschutzerklärung
└── 404.php                 # Fehlerseite
```

---

## Bootstrap & Einbindung

Jede öffentliche und Admin-Seite bindet zuerst `config/config.php` ein. Diese Datei:

1. Startet die PHP-Session mit sicheren Cookie-Einstellungen (`httponly`, `samesite=Lax`, `secure` je nach HTTPS, `use_strict_mode`).
2. Definiert Konstanten (`BASE_URL`, `ROOT_PATH`, `ASSETS_URL`, `UPLOADS_URL`, `DATA_PATH`, etc.).
3. Lädt die Admin-Zugangsdaten aus `data/admin_config.php` (wird beim ersten Aufruf automatisch mit zufälligem Passwort erstellt).
4. Lädt Unternehmensdaten und E-Mail-Einstellungen.
5. Bindet alle zentralen Include-Dateien ein: `functions.php`, `language.php`, alle Modelle, `cart.php`, `pricing.php`, `transport.php`.

**Wichtig:** Neue PHP-Seiten müssen immer mit `require_once 'config/config.php';` beginnen, gefolgt von `setSecurityHeaders();`.

**Admin-Seiten** müssen zusätzlich zu Beginn `isAdminLoggedIn()` prüfen und ggf. auf `login.php` umleiten.

---

## Konfigurationsdateien

| Datei | Zweck |
|-------|-------|
| `config/config.php` | Zentrale Bootstrap-Datei: Session-Sicherheit, Konstanten, Admin-Zugangsdaten, E-Mail-/Unternehmensdaten, Modul-Einbindung. |
| `data/admin_config.php` | Automatisch generierte Datei mit `ADMIN_USERNAME` und bcrypt-gehashtem `ADMIN_PASSWORD_HASH`. Wird bei Erstaufruf erstellt und mit `chmod 0600` geschützt. In `.gitignore` ausgeschlossen. |
| `.htaccess` (Root) | Apache-Security & Performance: UTF-8, Kompression, Caching, Directory-Listing aus, Blockierung von `/config/`, `/includes/`, `/data/`, Verhinderung von PHP-Ausführung in `/uploads/`, Security-Header, 404/403-Fehlerseiten. |
| `admin/.htaccess` | Erhöhte PHP-Upload-Limits für den Admin-Bereich (`upload_max_filesize 10M`, `post_max_size 50M`, `max_file_uploads 20`, `memory_limit 256M`). |
| `uploads/.htaccess` | Deaktiviert PHP-Ausführung im Upload-Verzeichnis und blockiert Skript-Extensions. |
| `uploads/pdfs/.htaccess` | PDF-Dateien direkt auslieferbar, PHP-Ausführung deaktiviert. |
| `php.ini` / `.user.ini` | PHP-Upload-Limits (`10M` / `50M` / `20` / `256M`, `max_execution_time 60s`). |
| `.gitignore` | Schließt Uploads, Logs, lokale Konfigurationen, Archive und `data/admin_config.php` aus. |

**Hinweis:** Im Root `.htaccess` sind öffentliche Upload-Limits auf `5M`/`5M` gesetzt, während Admin, `php.ini` und `.user.ini` `10M`/`50M` erlauben. Die Anwendung selbst begrenzt Uploads in `uploadImage()` zusätzlich auf 5 MB.

---

## Build-, Test- und Deployment-Prozess

### Kein Build-System

Dies ist eine klassische PHP-Website ohne Build-Schritt. Änderungen an PHP-, CSS- oder JS-Dateien sind nach dem Hochladen direkt aktiv. Es gibt keinen Composer-, npm-, Webpack- oder ähnlichen Build-Prozess.

### Keine automatisierten Tests

Es gibt **kein automatisiertes Test-Setup** (kein PHPUnit, kein JS-Test-Framework). Qualitätssicherung erfolgt manuell.

### Deployment

1. Alle Dateien auf den Webserver kopieren (z. B. per FTP).
2. Schreibrechte für folgende Verzeichnisse sicherstellen:
   - `/uploads/` (und Unterverzeichnisse)
   - `/uploads/pdfs/`
   - `/data/` (SQLite-DB und `admin_config.php` müssen beschreibbar sein)
3. `config/config.php` anpassen:
   - E-Mail-Empfänger und Absender eintragen (`MAIL_RECIPIENT`, `MAIL_SENDER`).
   - Unternehmensdaten aktualisieren (`COMPANY_NAME`, `COMPANY_STREET`, etc.).
4. Nach dem ersten Aufruf wird `data/admin_config.php` mit einem zufälligen Admin-Passwort erstellt. Das Passwort ist einmalig auf der Login-Seite sichtbar und muss anschließend geändert werden.

---

## Code-Style & Konventionen

- **Sprache:** Kommentare, Docblocks und Dokumentation werden auf Deutsch verfasst. UI-Texte sind zusätzlich auf Englisch übersetzt.
- **Formatierung:** 4 Leerzeichen Einrückung, keine Tabs, keine feste Zeilenlängenbegrenzung.
- **PHP-Tags:** Lange öffnende Tags `<?php` überall verwenden.
- **Ausgabeescaping:** Benutzergenerierte Inhalte werden mit `e($string)` (Wrapper für `htmlspecialchars()`) escaped.
- **Input-Sanitization:** `sanitizeInput()` (trim, stripslashes) wird im Datenmodell verwendet; kein `htmlspecialchars()` vor dem Speichern.
- **SQL:** Prepared Statements mit benannten Platzhaltern (`:name`) über PDO.
- **Konstanten:** Großbuchstaben mit Unterstrich (`SESSION_TIMEOUT`, `LOGIN_MAX_ATTEMPTS`, `DEFAULT_SETTINGS`).
- **Funktionen:** camelCase (`isAdminLoggedIn()`, `calculatePricing()`).
- **Datenbankspalten:** snake_case (`short_description`, `function_status`).
- **Dateiorganisation:** Datenmodelle liegen in `includes/*-model.php`, wiederverwendbare Layout-Teile in `partials/`, Admin-Seiten in `admin/`.

---

## Datenmodell

### Tabellen (SQLite)

**`jukeboxes`**

| Spalte | Typ | Default / Bemerkung |
|--------|-----|---------------------|
| `id` | TEXT | PRIMARY KEY (`jb_` + 16 Hex) |
| `name` | TEXT | NOT NULL |
| `name_en` | TEXT | |
| `manufacturer` | TEXT | |
| `model` | TEXT | |
| `year` | INTEGER | |
| `short_description` | TEXT | |
| `short_description_en` | TEXT | |
| `description` | TEXT | |
| `description_en` | TEXT | |
| `music_format` | TEXT | |
| `music_format_en` | TEXT | |
| `condition` | TEXT | |
| `condition_en` | TEXT | |
| `function_status` | TEXT | DEFAULT `"working"` – erlaubt: `working`, `deco`, `restored`, `original` |
| `power_connection` | TEXT | |
| `power_connection_en` | TEXT | |
| `dimensions` | TEXT | |
| `dimensions_en` | TEXT | |
| `price_day` | REAL | DEFAULT `0` |
| `featured` | INTEGER | DEFAULT `0` |
| `order` | INTEGER | DEFAULT `0` |
| `category_id` | TEXT | Nachträglich per `ALTER TABLE` hinzugefügt |
| `size` | TEXT | Nachträglich per `ALTER TABLE` hinzugefügt |
| `color` | TEXT | Nachträglich per `ALTER TABLE` hinzugefügt |
| `new_arrival` | INTEGER | DEFAULT `0` – Nachträglich per `ALTER TABLE` hinzugefügt |
| `main_image` | TEXT | |
| `gallery_images` | TEXT | JSON-Array |
| `created_at` | TEXT | |
| `updated_at` | TEXT | |

Indizes: `idx_featured`, `idx_order`

**`categories`**

| Spalte | Typ | Default |
|--------|-----|---------|
| `id` | TEXT | PRIMARY KEY (`cat_` + 8 Hex) |
| `name` | TEXT | NOT NULL |
| `name_en` | TEXT | |
| `description` | TEXT | |
| `description_en` | TEXT | |
| `color` | TEXT | DEFAULT `"#0066B1"` |
| `active` | INTEGER | DEFAULT `1` |
| `sort_order` | INTEGER | DEFAULT `0` |
| `created_at` | TEXT | |
| `updated_at` | TEXT | |

Indizes: `idx_categories_active`

**`discount_rules`**

| Spalte | Typ | Default |
|--------|-----|---------|
| `id` | TEXT | PRIMARY KEY (`disc_` + 8 Hex) |
| `type` | TEXT | NOT NULL – `duration` oder `quantity` |
| `threshold` | INTEGER | NOT NULL |
| `discount_percent` | REAL | DEFAULT `0` |
| `active` | INTEGER | DEFAULT `1` |
| `sort_order` | INTEGER | DEFAULT `0` |
| `created_at` | TEXT | |
| `updated_at` | TEXT | |

Indizes: `idx_discounts_type`, `idx_discounts_active`

**`coupons`**

| Spalte | Typ | Default |
|--------|-----|---------|
| `id` | TEXT | PRIMARY KEY (`cp_` + 8 Hex) |
| `code` | TEXT | UNIQUE NOT NULL (uppercase-normalisiert) |
| `description` | TEXT | |
| `discount_percent` | REAL | DEFAULT `0` |
| `valid_from` | TEXT | |
| `valid_until` | TEXT | |
| `active` | INTEGER | DEFAULT `1` |
| `min_order_value` | REAL | DEFAULT `0` |
| `reusable` | INTEGER | DEFAULT `1` |
| `combinable` | INTEGER | DEFAULT `1` |
| `created_at` | TEXT | |
| `updated_at` | TEXT | |

Indizes: `idx_coupons_code`, `idx_coupons_active`

**`settings`**

| Spalte | Typ | Default |
|--------|-----|---------|
| `key` | TEXT | PRIMARY KEY |
| `value` | TEXT | |

### Standard-Einstellungen (`DEFAULT_SETTINGS` in `settings-model.php`)

```php
'tax_rate' => '20'
'transport_price_per_km' => '0.85'
'transport_worker_hourly_rate' => '45'
'transport_worker_count' => '2'
'transport_setup_fee' => '120'
'contract_fee_enabled' => '1'
'contract_fee_percent' => '1.00'
'google_maps_api_key' => ''
'warehouse_address' => 'Oberstdorfer Straße 5, 2201 Sering, Österreich'
'company_bank_name' => ''
'company_iban' => ''
'company_bic' => ''
```

### Anfragen-Tabelle (`inquiries`)

| Spalte | Typ | Bemerkung |
|--------|-----|-----------|
| `id` | TEXT | PRIMARY KEY |
| `token` | TEXT | UNIQUE – öffentlicher Token für Angebot/Status |
| `status` | TEXT | `new`, `offer_sent`, `accepted`, `declined`, `expired`, `cancelled` |
| `firstname`, `lastname`, `company`, `email`, `phone`, `message` | TEXT | Kundendaten |
| `date_start`, `date_end` | TEXT | Mietzeitraum |
| `duration_days` | INTEGER | Anzahl Tage |
| `event_address` | TEXT | Veranstaltungsadresse |
| `pricing_json` | TEXT | Serialisierte Preisberechnung |
| `transport_distance_km`, `transport_duration_min`, `transport_costs`, `transport_error` | REAL/TEXT | Transportdaten |
| `created_at`, `updated_at` | TEXT | Zeitstempel |

Indizes: `idx_inquiries_token`, `idx_inquiries_status`, `idx_inquiries_email`

### Angebote-Tabelle (`offers`)

| Spalte | Typ | Bemerkung |
|--------|-----|-----------|
| `id` | TEXT | PRIMARY KEY |
| `inquiry_id` | TEXT | Fremdschlüssel zu `inquiries` |
| `offer_number` | TEXT | UNIQUE – z. B. `ANG-2026-00001` |
| `token` | TEXT | UNIQUE – öffentlicher Link-Token |
| `pdf_path` | TEXT | Pfad zum generierten PDF |
| `valid_until` | TEXT | Ablaufdatum |
| `status` | TEXT | `pending`, `accepted`, `declined`, `expired` |
| `created_at`, `accepted_at`, `declined_at`, `updated_at` | TEXT | Zeitstempel |

Indizes: `idx_offers_token`, `idx_offers_status`, `idx_offers_inquiry`

### Rechnungen-Tabelle (`invoices`)

| Spalte | Typ | Bemerkung |
|--------|-----|-----------|
| `id` | TEXT | PRIMARY KEY |
| `offer_id` | TEXT | Fremdschlüssel zu `offers` |
| `inquiry_id` | TEXT | Fremdschlüssel zu `inquiries` |
| `invoice_number` | TEXT | UNIQUE – z. B. `RE-2026-00001` |
| `pdf_path` | TEXT | Pfad zum generierten PDF |
| `amount_gross` | REAL | Rechnungsbetrag Brutto |
| `status` | TEXT | `open`, `paid` |
| `created_at`, `paid_at`, `updated_at` | TEXT | Zeitstempel |

Indizes: `idx_invoices_status`, `idx_invoices_offer`, `idx_invoices_inquiry`

### Vermietungen/Reservierungen-Tabelle (`rentals`)

| Spalte | Typ | Bemerkung |
|--------|-----|-----------|
| `id` | TEXT | PRIMARY KEY (`rent_` + 16 Hex) |
| `jukebox_id` | TEXT | Fremdschlüssel zu `jukeboxes` |
| `inquiry_id` | TEXT | Fremdschlüssel zu `inquiries` (optional) |
| `offer_id` | TEXT | Fremdschlüssel zu `offers` (optional) |
| `date_start` | TEXT | Mietbeginn (`Y-m-d`) |
| `date_end` | TEXT | Mietende (`Y-m-d`) |
| `status` | TEXT | `reserved`, `confirmed`, `cancelled` |
| `created_at`, `updated_at` | TEXT | Zeitstempel |

Indizes: `idx_rentals_jukebox`, `idx_rentals_dates`, `idx_rentals_status`, `idx_rentals_inquiry`, `idx_rentals_offer`

### Migration

Beim ersten Aufruf prüft `jukebox-model.php` automatisch, ob eine alte `data/jukeboxes.json` existiert. Falls ja und die SQLite-Tabelle ist leer, werden die Daten migriert und die JSON-Datei zu `.backup` umbenannt.

---

## Admin-Bereich

### Zugang

- URL: `/admin/login.php`
- Admin-Zugangsdaten werden automatisch in `data/admin_config.php` generiert (bei Erstaufruf).
- Session-Timeout: 30 Minuten (`SESSION_TIMEOUT = 1800`).
- Rate-Limiting: Nach 5 fehlgeschlagenen Login-Versuchen 15-minütige Sperre (`LOGIN_LOCKOUT_TIME = 900`).

### CRUD-Workflow

1. **Dashboard** (`dashboard.php`): Übersicht aller Jukeboxen.
2. **Create** (`create.php`): Formular mit Upload für Hauptbild + Galeriebilder.
3. **Edit** (`edit.php`): Gleiches Formular mit bestehenden Daten.
4. **Delete** (`delete.php`): Bestätigungsseite + Löschung inkl. Bilddateien.

### Verwaltungsseiten

- **Einstellungen** (`settings.php`): Steuersatz, Transportparameter (km-Satz, Stundensatz, Mitarbeiteranzahl, Pauschale), Google Maps API-Key, Lageradresse, Bankdaten, Vertragsgebühr (aktiv/inaktiv + Satz).
- **Kategorien** (`categories.php`): Name, Beschreibung, Farbe, Sortierung, Aktiv/Inaktiv.
- **Rabatte** (`discounts.php`): Regeln für Mietdauer-Rabatt (`duration`) und Mengenrabatt (`quantity`) mit Threshold + Prozent.
- **Coupons** (`coupons.php`): Code, Rabatt%, Gültigkeit, Mindestbestellwert, Mehrfachverwendung, Kombinierbarkeit.
- **Anfragen/Angebote** (`offers.php`): Anfragen-Übersicht, PDF-Angebot erstellen, Status-Tracking, öffentlicher Link zum Angebot.
- **Rechnungen** (`invoices.php`): Übersicht aller Rechnungen, Gesamtumsatz, Status, PDF-Download.
- **Kalender** (`calendar.php`): Monatsansicht aller Vermietungen/Reservierungen mit Status-Filter und Legende.

---

## Anfragekorb (Cart)

- **Speicherort:** `$_SESSION['jukebox_cart']`
- **Inhalt:** `items` (Array von Jukebox-IDs), `date_start`, `date_end`, `duration_days`, `event_address`, `coupon_code`, Transportdaten (`transport_calculated`, `transport_distance_km`, `transport_duration_min`, `transport_error`)
- **Legacy-Cookie `jukebox_inquiry`:** Wird weiterhin als Fallback für die Sidebar synchronisiert (30 Tage Laufzeit, `SameSite=Lax`, `httponly`).
- **Warenkorb-Seite:** `request.php`
- **AJAX-Endpunkte:** `includes/ajax.php` mit Actions `cartAdd`, `cartRemove`, `cartGet`, `updateCart`, `updateCartContact`, `getCartItems`, `checkAvailability`, `setCartDates`
- **Verfügbarkeitsprüfung:** `cartAdd` prüft vor dem Hinzufügen, ob die Jukebox im gewählten Zeitraum verfügbar ist. `request.php` und `contact.php` prüfen die Verfügbarkeit aller Cart-Items und blockieren den Abschluss bei Konflikten.

---

## Preisberechnung

Zentrale Funktion: `calculatePricing($cartItems, $days, $couponCode, $transportCosts)` in `includes/pricing.php`.

Bestandteile:

1. Mietsumme = Tagespreis × Anzahl Tage (pro Jukebox)
2. Mietdauer-Rabatt (z. B. ab 3 Tagen 5%)
3. Mengenrabatt (z. B. ab 2 Jukeboxen 10%)
4. Coupon-Rabatt (nur auf die bereits rabattierte Mietsumme)
5. Transportkosten (netto, nicht rabattfähig)
6. USt. auf Gesamt-Netto
7. Brutto-Gesamtsumme
8. Vertragsgebühr (1 % des Bruttowertes, falls aktiviert und Bemessungsgrundlage > 150 €)
9. Endbetrag inkl. Vertragsgebühr

Rabatte sind kumulativ und werden sequentiell abgezogen.

---

## Transportkosten

- **API:** Google Maps Distance Matrix API
- **Berechnung:** Einwegstrecke × 4 (Hinlieferung Hin+Rück + Abholung Hin+Rück)
- **Formel:** `(km × km-Satz) + (Fahrzeit in h × Stundensatz × Mitarbeiter) + Pauschale`
- **Fallback:** Wenn API-Key fehlt oder Route nicht berechenbar → Hinweis „Individuell berechnet“, Anfrage bleibt möglich.
- **Caching:** Transportergebnisse werden 10 Minuten in der Session gecacht (`$_SESSION['transport_cache']`).
- **Lageradresse:** Konfigurierbar in den Admin-Einstellungen, Default: `Oberstdorfer Straße 5, 2201 Sering, Österreich`

---

## Vermietungs- & Verfügbarkeits-Workflow

1. **Datumsauswahl im Frontend:** Kunde wählt auf `catalog.php` oder `jukebox.php` über `partials/date-selector.php` einen Mietzeitraum.
2. **Verfügbarkeitsprüfung:** Beim Klick auf „Zur Anfrage hinzufügen“ wird per AJAX (`action=checkAvailability`) geprüft, ob die Jukebox im Zeitraum verfügbar ist.
3. **Reservierung bei Anfrage:** Sendet der Kunde das Kontaktformular ab, werden in `contact.php` für jede Jukebox im Warenkorb `rentals`-Einträge mit Status `reserved` angelegt.
4. **Angebotserstellung:** Erstellt der Admin ein PDF-Angebot, werden die Reservierungen mit der `offer_id` verknüpft (`linkRentalsToOffer`).
5. **Annahme:** Nimmt der Kunde das Angebot an, werden die Reservierungen auf `confirmed` gesetzt.
6. **Ablehnung/Ablauf:** Wird das Angebot abgelehnt oder läuft es ab, werden die Reservierungen auf `cancelled` gesetzt.
7. **Admin-Kalender:** Zeigt alle Reservierungen mit Status (`reserved`, `confirmed`, `cancelled`) und Jukebox-Zuordnung.

---

## Angebots- & Rechnungs-Workflow

1. **Anfrage absenden:** Kunde füllt das Kontaktformular aus und sendet die Anfrage. Eine `inquiries`-Zeile wird angelegt, Reservierungen werden erstellt.
2. **Admin erstellt Angebot:** Im Admin (`admin/offers.php`) kann für eine Anfrage ein PDF-Angebot generiert werden. Das PDF wird unter `uploads/pdfs/` gespeichert.
3. **Kunde erhält E-Mail:** Bei Angebotserstellung wird eine E-Mail mit öffentlichem Link (`offer.php?token=...`) an den Kunden gesendet.
4. **Angebot annehmen/ablehnen:** Der Kunde öffnet den Link, sieht das PDF und kann das Angebot annehmen oder ablehnen.
5. **Rechnung bei Annahme:** Bei Annahme wird automatisch eine PDF-Rechnung erstellt, in `invoices` gespeichert und dem Kunden per E-Mail zugesandt.
6. **Rechnungsverwaltung:** Unter `admin/invoices.php` können Rechnungen eingesehen, als bezahlt markiert und heruntergeladen werden.

---

## Übersetzungssystem

- Sprachen sind in `includes/language.php` definiert.
- Verfügbare Sprachen: `['de', 'en']`
- Standard: `de`
- Sprachumschaltung erfolgt per GET-Parameter `?lang=en` und wird in der Session gespeichert.
- Kurzform für Übersetzungen: `__('schlüssel', ['platzhalter' => 'wert'])`
- Alle Texte (Meta-Tags, UI, Formularlabels, Admin-Texte) liegen in einem großen assoziativen Array pro Sprache.

---

## Frontend

- **CSS:** Mobile-First in `assets/css/style.css`, CSS-Custom-Properties für Farben/Typografie, Responsive Breakpoints bei `640px`, `768px`, `1024px`.
- **JS:** Vanilla JS in `assets/js/main.js` mit Komponenten für Header-Scroll, mobiles Menü, Anfrage-Sidebar, Formularvalidierung, FAQ-Akkordeon, Lazy Loading, Cookie-Banner, Datums-Selektor, Verfügbarkeitsprüfung.
- **Layout:** `partials/header.php` (Head, Navigation, Inquiry-Sidebar) und `partials/footer.php` (Footer, Cookie-Hinweis, Script-Tag).
- **Extern:** flatpickr für Datumsauswahl, Google Fonts.

---

## Testing

Da es keine automatisierten Tests gibt, erfolgt die Qualitätssicherung manuell:

1. Formular-Validierung auf der Kontaktseite testen (Pflichtfelder, E-Mail, DSGVO).
2. Admin-Login/Logout testen, Rate-Limiting prüfen.
3. Jukebox anlegen, bearbeiten, löschen und im Frontend prüfen.
4. Sprachwechsel (DE ↔ EN) auf verschiedenen Seiten testen.
5. Anfragekorb testen: hinzufügen, entfernen, Session-Überleben, Cookie-Synchronisation.
6. Preisberechnung mit unterschiedlichen Tagen, Mengen, Coupons und Adressen testen.
7. Bild-Upload mit verschiedenen Dateigrößen/Formaten testen.
8. Transportberechnung mit gültiger und ungültiger Adresse testen.
9. Verfügbarkeitsprüfung testen: Doppelbelegung einer Jukebox im gleichen Zeitraum muss verhindert werden.
10. Reservierungs-Workflow testen: Anfrage → Reservierung → Angebot → Annahme → Rechnung.
11. Admin-Kalender testen: Reservierungen und Status korrekt anzeigen.
12. Angebots- und Rechnungs-PDFs auf Layout und korrekte Daten prüfen.

---

## Sicherheitsaspekte

| Maßnahme | Umsetzung |
|----------|-----------|
| **Authentifizierung** | Session-basiert, bcrypt-Hash für Admin-Passwort |
| **Session-Timeout** | 30 Minuten Inaktivität |
| **Session-Sicherheit** | `httponly`, `samesite=Lax`, `secure` je nach HTTPS, `use_strict_mode` |
| **CSRF-Schutz** | Tokens in allen Formularen; AJAX prüft `HTTP_REFERER` |
| **Spam-Schutz** | Honeypot-Feld (`website`) im Kontaktformular + Rate-Limiting |
| **XSS-Prävention** | `htmlspecialchars()` bei allen Ausgaben via `e()` |
| **Upload-Sicherheit** | MIME-Type via `finfo_file()`, `getimagesize()`, Extension-Whitelist, zufälliger Dateiname, 5 MB Limit |
| **Path Traversal** | `deleteImage()` prüft `realpath()`; Upload-Pfade werden validiert |
| **Verzeichnisschutz** | `.htaccess` blockiert Zugriff auf `/config/`, `/includes/`, `/data/` |
| **PHP in Uploads** | Deaktiviert via `uploads/.htaccess` |
| **Sicherheits-Header** | `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy` |
| **Brute-Force** | Login: 5 Versuche = 15 Min. Sperre; Kontaktformular: 3 Absendungen in 5 Min. |
| **SQL-Injection** | PDO Prepared Statements mit benannten Platzhaltern in allen Modellen |

---

## Wichtige Hinweise für Agenten

1. **Keine neuen Abhängigkeiten** ohne Rücksprache einführen. Das Projekt ist bewusst abhängigkeitsfrei gehalten.
2. **Neue PHP-Seiten** müssen `config/config.php` laden und `setSecurityHeaders();` aufrufen.
3. **Admin-Seiten** müssen zu Beginn `isAdminLoggedIn()` prüfen.
4. **Datenbank-Schema-Änderungen** müssen in `includes/database.php` (Funktion `initDatabase()`) und in den betroffenen Model-Dateien (INSERT/UPDATE-Statements) synchron gepflegt werden.
5. **Neue Texte** gehören in `includes/language.php` in beide Spracharrays (`de` und `en`).
6. **Preisberechnung** darf nur in `includes/pricing.php` erfolgen.
7. **Transportberechnung** darf nur in `includes/transport.php` erfolgen.
8. **Der Warenkorb ist Session-basiert** – die Cookie-Logik existiert nur noch als Fallback für die Sidebar.
9. **Bilder** sollten im Format 800–1200 px Breite und unter 5 MB gehalten werden, damit der Upload reibungslos funktioniert.
10. **PDFs** werden unter `uploads/pdfs/` gespeichert. Das Verzeichnis muss beschreibbar sein.
11. **Verfügbarkeitsprüfung** erfolgt zentral in `includes/rentals-model.php` (`isJukeboxAvailable`).
12. **Reservierungen** werden bei Anfrage-Erstellung angelegt, bei Angebots-Annahme bestätigt und bei Ablehnung/Ablauf freigegeben.
13. **Wenn diese Datei geändert wird**, sollte auch `revibe/AGENTS.md` synchron gehalten werden.
