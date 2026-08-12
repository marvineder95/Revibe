# Revibe

Eine vollständige, produktionsreife Webseite für die Vermietung von Jukeboxen.

## Features

- **Modernes, luxuriöses Design** mit Retro-Diner-Vibes
- **Zweisprachig** (Deutsch/Englisch) mit einfacher Erweiterbarkeit
- **Responsiv** - Mobile-First Ansatz
- **SEO-optimiert** mit Structured Data
- **Admin-Bereich** für Jukebox-Verwaltung
- **Anfrageliste** - Mehrere Jukeboxen gleichzeitig anfragen
- **Kontaktformular** mit PHP-Mail-Versand
- **Cookie-Hinweis** für DSGVO-Konformität

## Systemanforderungen

- PHP 7.4 oder höher
- Apache/Nginx Webserver
- Schreibrechte für `/uploads/` und `/data/`

## Installation

### 1. Dateien hochladen

Laden Sie alle Dateien auf Ihren Webserver hoch (z.B. via FTP bei world4you).

### 2. Berechtigungen setzen

Stellen Sie sicher, dass folgende Verzeichnisse beschreibbar sind:
```
/uploads/
/data/
```

### 3. Admin-Passwort ändern

Bearbeiten Sie die Datei `/config/config.php`:

```php
// Standard-Login (ändern Sie dies!)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); // password
```

Generieren Sie einen neuen Passwort-Hash:
```php
<?php echo password_hash('ihr-neues-passwort', PASSWORD_DEFAULT); ?>
```

### 4. E-Mail-Einstellungen

Bearbeiten Sie in `/config/config.php`:
```php
define('MAIL_RECIPIENT', 'office@transportpeter.at');  // Ihre E-Mail
define('MAIL_SENDER', 'noreply@ihre-domain.at');        // Absender-E-Mail
```

### 5. Unternehmensdaten anpassen

Bearbeiten Sie in `/config/config.php`:
```php
define('COMPANY_NAME', 'Revibe');
define('COMPANY_STREET', 'Ihre Straße 123');
define('COMPANY_ZIP', '1010');
define('COMPANY_CITY', 'Wien');
define('COMPANY_PHONE', '+43 1 234 56 78');
define('COMPANY_EMAIL', 'office@ihre-domain.at');
```

## Verzeichnisstruktur

```
revibe/
├── admin/              # Admin-Bereich
│   ├── login.php       # Login-Seite
│   ├── dashboard.php   # Übersicht
│   ├── create.php      # Jukebox erstellen
│   ├── edit.php        # Jukebox bearbeiten
│   ├── delete.php      # Jukebox löschen
│   └── logout.php      # Logout
├── assets/             # Statische Dateien
│   ├── css/
│   │   └── style.css   # Hauptstylesheet
│   ├── js/
│   │   └── main.js     # Haupt-JavaScript
│   └── images/         # Bilder
├── config/
│   └── config.php      # Hauptkonfiguration
├── data/
│   └── jukeboxes.json  # Jukebox-Daten
├── includes/           # PHP-Funktionen
│   ├── functions.php   # Hilfsfunktionen
│   ├── language.php    # Übersetzungen
│   └── jukebox-model.php # Datenmodell
├── partials/           # Wiederverwendbare Teile
│   ├── header.php      # Seitenkopf
│   └── footer.php      # Seitenfuß
├── uploads/            # Hochgeladene Bilder
│   └── jukeboxes/
├── index.php           # Startseite
├── about.php           # Über uns
├── catalog.php         # Jukebox-Katalog
├── jukebox.php         # Detailseite
├── process.php         # Mietablauf
├── faq.php             # FAQ
├── contact.php         # Kontakt
├── impressum.php       # Impressum
└── datenschutz.php     # Datenschutz
```

## Admin-Bereich

Zugriff: `https://ihre-domain.at/admin/login.php`

**Standard-Login:**
- Benutzername: `admin`
- Passwort: `password` (bitte sofort ändern!)

## Jukeboxen verwalten

### Neue Jukebox hinzufügen

1. Im Admin-Bereich auf "Neue Jukebox" klicken
2. Alle Pflichtfelder ausfüllen
3. Hauptbild hochladen (empfohlen: 800x600px)
4. Optional: Galeriebilder hinzufügen
5. Speichern

### Bilder

- **Hauptbild**: Wird in Katalog und Detailseite angezeigt
- **Galeriebilder**: Zusätzliche Bilder auf der Detailseite
- **Empfohlenes Format**: JPG, 800-1200px Breite
- **Maximale Größe**: 5MB pro Bild

## Anpassungen

### Farben ändern

Bearbeiten Sie die CSS-Variablen in `/assets/css/style.css`:

```css
:root {
    --color-primary: #0066B1;        /* Brand-Blau */
    --color-secondary: #8B0000;      /* Dunkelrot */
    /* ... */
}
```

### Texte anpassen

Alle Texte befinden sich in `/includes/language.php` in den Arrays `$translations['de']` und `$translations['en']`.

### Neue Sprache hinzufügen

1. In `/includes/language.php` die Konstante erweitern:
```php
const AVAILABLE_LANGUAGES = ['de', 'en', 'fr'];
```

2. Übersetzungen hinzufügen:
```php
'fr' => [
    'meta_title_home' => 'Location de Jukebox...',
    // ...
]
```

## Sicherheit

- **CSRF-Token** in allen Formularen
- **Honeypot-Feld** gegen Spam
- **Session-Timeout** nach 30 Minuten
- **Passwort-Hashing** mit bcrypt
- **Eingabe-Sanitization** gegen XSS

## Performance

- Lazy Loading für Bilder
- Minimierte CSS/JS (optional weiter optimierbar)
- Effiziente JSON-Datenstruktur
- Keine externen Abhängigkeiten

## Troubleshooting

### Bilder werden nicht hochgeladen

- Prüfen Sie die Berechtigungen für `/uploads/`
- Stellen Sie sicher, dass `file_uploads = On` in php.ini
- Maximale Upload-Größe prüfen: `upload_max_filesize`

### E-Mails werden nicht versendet

- Prüfen Sie die E-Mail-Einstellungen in `config.php`
- Stellen Sie sicher, dass `mail()` auf dem Server aktiviert ist
- Alternativ: SMTP-Konfiguration hinzufügen

### Admin-Login funktioniert nicht

- Löschen Sie Browser-Cookies
- Prüfen Sie das Passwort-Hash in `config.php`
- Stellen Sie sicher, dass Sessions aktiviert sind

## Support

Bei Fragen oder Problemen:
- E-Mail: office@transportpeter.at
- Telefon: +43 1 234 56 78

## Lizenz

Dieses Projekt wurde exklusiv für Revibe erstellt.
Alle Rechte vorbehalten.

---

**Version:** 1.0  
**Erstellt:** 2024  
**Technologie:** PHP, HTML5, CSS3, Vanilla JavaScript
