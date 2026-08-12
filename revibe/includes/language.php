<?php
/**
 * Mehrsprachigkeitssystem
 * Unterstützt Deutsch und Englisch, erweiterbar für weitere Sprachen
 */

// Verfügbare Sprachen
const AVAILABLE_LANGUAGES = ['de', 'en'];
const DEFAULT_LANGUAGE = 'de';

/**
 * Aktuelle Sprache ermitteln oder setzen
 */
function getCurrentLanguage() {
    // WICHTIG: URL-Parameter hat höchste Priorität (Sprachwechsel via Button)
    if (isset($_GET['lang']) && in_array($_GET['lang'], AVAILABLE_LANGUAGES)) {
        $_SESSION['language'] = $_GET['lang'];
        return $_GET['lang'];
    }
    
    // Aus Session laden wenn vorhanden
    if (isset($_SESSION['language']) && in_array($_SESSION['language'], AVAILABLE_LANGUAGES)) {
        return $_SESSION['language'];
    }
    
    // Aus Browser-Präferenz
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browserLang, AVAILABLE_LANGUAGES)) {
            $_SESSION['language'] = $browserLang;
            return $browserLang;
        }
    }
    
    return DEFAULT_LANGUAGE;
}

/**
 * Sprache wechseln
 */
function setLanguage($lang) {
    if (in_array($lang, AVAILABLE_LANGUAGES)) {
        $_SESSION['language'] = $lang;
        return true;
    }
    return false;
}

/**
 * Sprachwechsel-URL generieren
 */
function getLanguageSwitchUrl($lang) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    
    // Bestehenden lang-Parameter entfernen
    $currentUrl = preg_replace('/([?&])lang=[^&]+(&|$)/', '$1', $currentUrl);
    $currentUrl = rtrim($currentUrl, '?&');
    
    $separator = strpos($currentUrl, '?') !== false ? '&' : '?';
    return $currentUrl . $separator . 'lang=' . $lang;
}

/**
 * Übersetzungen laden
 */
function getTranslations($lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    $translations = [
        // ============================================
        // META-TAGS
        // ============================================
        'de' => [
            'meta_title_home' => 'Revibe | Premium-Jukeboxen für Ihr Event',
            'meta_description_home' => 'Mieten Sie hochwertige Jukeboxen für Ihre Veranstaltung. Komplettservice mit Lieferung, Aufbau und Abholung in AT, IT, DE und CH.',
            'meta_title_about' => 'Über uns | Revibe',
            'meta_description_about' => 'Erfahren Sie mehr über unsere Leidenschaft für Jukeboxen und unseren erstklassigen Komplettservice für Events in Österreich.',
            'meta_title_catalog' => 'Jukebox-Katalog | Alle Modelle zur Miete',
            'meta_description_catalog' => 'Entdecken Sie unsere exklusive Sammlung von Jukeboxen. Von klassischen Retro-Modellen bis hin zu modernen Digital-Jukeboxen.',
            'meta_title_contact' => 'Kontakt | Jukebox-Anfrage stellen',
            'meta_description_contact' => 'Kontaktieren Sie uns für Ihre Jukebox-Anfrage. Wir erstellen Ihnen ein individuelles Angebot für Ihre Veranstaltung.',
            'meta_title_faq' => 'FAQ | Häufig gestellte Fragen',
            'meta_description_faq' => 'Antworten auf alle Fragen zur Jukebox-Vermietung: Lieferung, Aufbau, Preise, Technik und mehr.',
            'meta_title_process' => 'Mietablauf | So einfach geht\'s',
            'meta_description_process' => 'Der einfache Weg zu Ihrer Jukebox: Auswahl, Anfrage, Angebot, Lieferung und Abholung. Komplettservice inklusive.',
            'meta_title_offer' => 'Ihr Angebot | Revibe',
            'meta_description_offer' => 'Ihr persönliches Angebot von Revibe. Ansehen, annehmen oder ablehnen.',
            
            // Navigation
            'nav_home' => 'Startseite',
            'nav_about' => 'Über uns',
            'nav_catalog' => 'Jukeboxen',
            'nav_process' => 'Mietablauf',
            'nav_faq' => 'FAQ',
            'nav_contact' => 'Kontakt',
            'nav_inquiry_list' => 'Anfrageliste',
            'nav_inquiry_list_empty' => 'Anfrageliste ist leer',
            
            // Sprachumschalter
            'lang_de' => 'Deutsch',
            'lang_en' => 'English',
            'lang_switch' => 'Sprache wechseln',
            
            // Hero
            'hero_label' => 'Jukebox-Verleih',
            'hero_title' => 'Jukebox mieten für Ihr Event',
            'hero_subtitle' => 'Originale Premium-Jukeboxen – inklusive Lieferung, Aufbau & Abholung. Perfekt für Hochzeiten, Firmenfeiern und private Feiern.',
            'hero_cta_primary' => 'Jukeboxen entdecken',
            'hero_cta_secondary' => 'Direkt anfragen',
            
            // Intro
            'intro_title' => 'Musikgeschichte zum Anfassen',
            'intro_text' => 'Unsere Jukeboxen sind mehr als nur Musikplayer – sie sind Zeitmaschinen, die Ihr Event in eine einzigartige Erlebniswelt verwandeln. Von authentischen Retro-Klassikern bis hin zu modernen Digital-Systemen bieten wir den perfekten Soundtrack für jede Gelegenheit.',
            'intro_feature_1_title' => 'Lieferung in AT, IT, DE, CH',
            'intro_feature_1_text' => 'Wir liefern Ihre Jukebox in Österreich, Italien, Deutschland und die Schweiz. Für andere Länder kontaktieren Sie uns einfach.',
            'intro_feature_2_title' => 'Komplettservice',
            'intro_feature_2_text' => 'Lieferung, Aufbau, Technik-Check und Abholung – alles aus einer Hand, ohne Stress für Sie.',
            'intro_feature_3_title' => 'Authentische Stücke',
            'intro_feature_3_text' => 'Jede Jukebox in unserer Sammlung ist ein sorgfältig ausgewähltes Original oder hochwertige Reproduktion.',
            
            // Featured Jukeboxes
            'featured_title' => 'Unsere Highlights',
            'featured_subtitle' => 'Diese Jukeboxen begeistern Ihre Gäste',
            'featured_cta' => 'Alle Jukeboxen ansehen',
            'view_details' => 'Details ansehen',
            'add_to_inquiry' => 'Zur Anfrage hinzufügen',
            'remove_from_inquiry' => 'Entfernen',
            
            // Benefits
            'benefits_title' => 'Warum Revibe?',
            'benefit_1_title' => 'Kein Stress, nur Freude',
            'benefit_1_text' => 'Wir kümmern uns um alles Technische. Sie genießen Ihr Event und Ihre Gäste sind begeistert.',
            'benefit_2_title' => 'Flexibel & Individuell',
            'benefit_2_text' => 'Ob eine Nacht oder eine Woche – wir finden die passende Mietdauer und das ideale Modell für Ihre Veranstaltung.',
            'benefit_3_title' => 'Echte Hingucker',
            'benefit_3_text' => 'Unsere Jukeboxen sind Blickfang und Gesprächsthema zugleich. Sie schaffen Atmosphäre wie nichts anderes.',
            'benefit_4_title' => 'Professioneller Service',
            'benefit_4_text' => 'Jahrelange Erfahrung, zuverlässige Technik und persönliche Betreuung – von der ersten Anfrage bis zur Abholung.',
            
            // Process
            'process_title' => 'So einfach funktioniert\'s',
            'process_step_1_number' => '01',
            'process_step_1_title' => 'Jukebox wählen',
            'process_step_1_text' => 'Stöbern Sie in unserem Katalog und wählen Sie Ihre Favoriten aus. Mehrere Boxen gleichzeitig sind möglich.',
            'process_step_2_number' => '02',
            'process_step_2_title' => 'Anfrage senden',
            'process_step_2_text' => 'Teilen Sie uns Datum, Ort und Dauer mit. Wir prüfen die Verfügbarkeit und erstellen Ihr Angebot.',
            'process_step_3_number' => '03',
            'process_step_3_title' => 'Angebot erhalten',
            'process_step_3_text' => 'Sie erhalten ein transparentes Angebot inklusive aller Kosten – ohne versteckte Gebühren.',
            'process_step_4_number' => '04',
            'process_step_4_title' => 'Wir liefern und bauen auf',
            'process_step_4_text' => 'Pünktlich zum Event bringen wir Ihre Jukebox, bauen sie auf und machen sie betriebsbereit.',
            'process_step_5_number' => '05',
            'process_step_5_title' => 'Event genießen',
            'process_step_5_text' => 'Ihre Gäste haben die Zeit ihres Lebens.',
            'process_step_6_number' => '06',
            'process_step_6_title' => 'Wir holen ab',
            'process_step_6_text' => 'Nach dem Event kommen wir zur vereinbarten Zeit und holen die Jukebox wieder ab.',
            
            // CTA Section
            'cta_title' => 'Bereit für den perfekten Soundtrack?',
            'cta_text' => 'Lassen Sie uns gemeinsam Ihr unvergessliches Event planen. Kontaktieren Sie uns jetzt für ein individuelles Angebot.',
            'cta_button' => 'Jetzt Event planen',
            
            // FAQ Preview
            'faq_preview_title' => 'Häufige Fragen',
            'faq_preview_cta' => 'Alle Fragen & Antworten',
            
            // Footer
            'footer_tagline' => 'Premium-Jukeboxen für unvergessliche Events',
            'footer_contact_title' => 'Kontakt',
            'footer_legal_title' => 'Rechtliches',
            'footer_imprint' => 'Impressum',
            'footer_privacy' => 'Datenschutz',
            'footer_copyright' => '© {year} Revibe. Alle Rechte vorbehalten.',
            
            // About Page
            'about_title' => 'Unsere Geschichte',
            'about_subtitle' => 'Leidenschaft für Musik, Liebe zum Detail',
            'about_text_1' => 'Revibe wurde aus einer einfachen Idee geboren: Menschen die Magie authentischer Jukeboxen erlebbar zu machen. Was als kleine Sammlung begann, ist heute eine der exklusivsten Jukebox-Verleih-Services in Österreich.',
            'about_text_2' => 'Jede Jukebox in unserem Bestand ist sorgfältig ausgewählt – sei es ein original restauriertes Stück der 50er Jahre oder eine hochwertige Neuauflage mit modernster Technik. Wir glauben daran, dass Musik mehr ist als nur Hintergrundgeräusche. Sie ist die Seele jedes großartigen Events.',
            'about_text_3' => 'Unser Team lebt diese Philosophie. Von der ersten Beratung bis zur Abholung stehen wir Ihnen mit Fachwissen und Engagement zur Seite. Denn Ihr erfolgreiches Event ist auch unser Erfolg.',
            'about_values_title' => 'Unsere Werte',
            'about_value_1_title' => 'Authentizität',
            'about_value_1_text' => 'Wir bieten echte Erlebnisse – keine Imitate, keine Kompromisse.',
            'about_value_2_title' => 'Zuverlässigkeit',
            'about_value_2_text' => 'Was wir versprechen, halten wir. Punkt.',
            'about_value_3_title' => 'Leidenschaft',
            'about_value_3_text' => 'Wir lieben, was wir tun. Das spüren Sie in jedem Kontakt.',
            
            // Catalog Page
            'catalog_title' => 'Unsere Jukeboxen',
            'catalog_subtitle' => 'Finden Sie das perfekte Modell für Ihr Event',
            'catalog_empty' => 'Derzeit sind keine Jukeboxen verfügbar.',
            'catalog_filters' => 'Filter',
            'catalog_sort' => 'Sortierung',
            'sort_name_asc' => 'Name A-Z',
            'sort_name_desc' => 'Name Z-A',
            'sort_price_asc' => 'Preis aufsteigend',
            'sort_price_desc' => 'Preis absteigend',
            'items_selected' => '{count} Jukebox(en) ausgewählt',
            'clear_selection' => 'Auswahl löschen',
            'catalog_results_count' => '{count} Ergebnisse',
            'catalog_reset_filters' => 'Filter zurücksetzen',
            'catalog_filter_manufacturer' => 'Hersteller',
            'catalog_filter_size' => 'Größe',
            'catalog_filter_color' => 'Farbe',
            'catalog_filter_function' => 'Funktion',
            'catalog_filter_category' => 'Kategorie',
            'catalog_filter_price' => 'Preis',
            'catalog_filter_new_arrival' => 'Neu im Sortiment',
            'catalog_filter_all' => 'Alle',
            'catalog_filter_select' => 'Auswählen',
            'catalog_filter_apply' => 'Anwenden',
            'catalog_filter_close' => 'Schließen',
            'catalog_filter_price_per_day' => '€ / Tag',
            'catalog_select_dates' => 'Wählen Sie zuerst den Mietzeitraum',
            'catalog_date_selector_hint' => 'Datumsauswahl wird für alle ausgewählten Jukeboxen verwendet.',
            'catalog_date_saving' => 'Mietzeitraum wird gespeichert ...',
            'catalog_date_saved' => 'Mietzeitraum gespeichert.',
            'catalog_not_available' => 'Diese Jukebox ist im gewählten Zeitraum nicht verfügbar.',
            'catalog_select_dates_first' => 'Bitte wählen Sie zuerst den Mietzeitraum.',
            'catalog_date_modal_intro' => 'Wählen Sie Ihren Mietzeitraum, um Jukeboxen zu sehen, die an Ihrem Eventtag wirklich verfügbar sind. Jede Box ist ein Einzelstück und kann nur einmal pro Zeitraum vermietet werden.',
            'catalog_date_info_title' => 'Warum zuerst das Datum?',
            'catalog_date_info_text' => 'Unsere Jukeboxen sind echte Einzelstücke. Indem Sie zuerst den Mietzeitraum wählen, sehen Sie sofort, welche Boxen an Ihrem Termin noch frei sind – und vermeiden Enttäuschungen bei der Anfrage.',
            'catalog_date_skip' => 'Vorerst überspringen',
            'catalog_date_view_all' => 'Gesamtes Sortiment ansehen',
            'catalog_date_selected_period' => 'Gewählter Zeitraum',
            'catalog_date_change' => 'Ändern',

            // Jukebox Detail
            'detail_manufacturer' => 'Hersteller',
            'detail_model' => 'Modell',
            'detail_year' => 'Baujahr',
            'detail_format' => 'Musikformat',
            'detail_condition' => 'Zustand',
            'detail_function' => 'Funktionsstatus',
            'detail_power' => 'Stromanschluss',
            'detail_size' => 'Abmessungen',
            'detail_price' => 'Mietpreis',
            'detail_gallery' => 'Bildergalerie',
            'detail_inquiry' => 'Diese Jukebox anfragen',
            'detail_back' => 'Zurück zum Katalog',
            'status_working' => 'Voll funktionsfähig',
            'status_deco' => 'Deko-Objekt',
            'status_restored' => 'Restauriert',
            'status_original' => 'Originalzustand',
            
            // Process Page
            'process_page_title' => 'Der Mietablauf',
            'process_page_subtitle' => 'So kommen Sie zu Ihrer Jukebox',
            'process_detail_intro' => 'Wir haben den Mietprozess so einfach wie möglich gestaltet. In sechs Schritten von der Idee zum unvergesslichen Event-Erlebnis.',
            'process_delivery_title' => 'Liefergebiete & Transportkosten',
            'process_delivery_text' => 'Wir beliefern Österreich, Italien, Deutschland und die Schweiz (AT, IT, DE, CH). Für Lieferungen in andere Länder erstellen wir gerne ein individuelles Angebot. Die Transportkosten werden transparent nach Entfernung und Aufwand kalkuliert.',
            'process_timing_title' => 'Buchungszeitraum',
            'process_timing_text' => 'Wir empfehlen eine frühzeitige Buchung, besonders für Hochzeiten in der Hauptsaison (Mai-September). Eine Reservierung ist bereits 12 Monate im Voraus möglich. Für kurzfristige Anfragen prüfen wir gerne die Verfügbarkeit.',
            
            // FAQ Page
            'faq_title' => 'Häufig gestellte Fragen',
            'faq_subtitle' => 'Antworten auf Ihre wichtigsten Fragen',
            'faq_category_general' => 'Allgemeines',
            'faq_category_technical' => 'Technik & Aufbau',
            'faq_category_pricing' => 'Preise & Buchung',
            'faq_question_1' => 'Wie weit im Voraus sollte ich buchen?',
            'faq_answer_1' => 'Für Hochzeiten und Events in der Hauptsaison (Mai-September) empfehlen wir eine Buchung 3-6 Monate im Voraus. Für kurzfristige Termine prüfen wir gerne die Verfügbarkeit. Je nach Verfügbarkeit sind auch kurzfristige bzw. spontane Termine möglich.',
            'faq_question_2' => 'Welche Stromanschlüsse werden benötigt?',
            'faq_answer_2' => 'Die meisten unserer Jukeboxen benötigen einen standard 230V-Schuko-Anschluss. Details zum Strombedarf finden Sie bei jeder Jukebox in der Beschreibung.',
            'faq_question_3' => 'Wie werden die Transportkosten berechnet?',
            'faq_answer_3' => 'Die Transportkosten richten sich nach der Entfernung zum Veranstaltungsort und dem Aufwand für Lieferung und Abholung. Sie erhalten im Angebot eine transparente Aufschlüsselung aller Kosten.',
            'faq_question_4' => 'Gibt es eine Mindestmietdauer?',
            'faq_answer_4' => 'Die Mindestmietdauer beträgt einen Tag (24 Stunden). Für mehrtägige Events oder Messen erstellen wir gerne ein individuelles Pauschalangebot.',
            'faq_question_5' => 'Kann ich mehrere Jukeboxen gleichzeitig mieten?',
            'faq_answer_5' => 'Absolut! Viele Kunden buchen mehrere Jukeboxen für große Events oder um verschiedene Bereiche zu bespielen. Für jede zusätzliche Jukebox profitieren Sie von attraktiven Mengenrabatten.',
            'faq_question_6' => 'Wer kümmert sich um den Aufbau?',
            'faq_answer_6' => 'Der Aufbau ist Teil unseres Komplettservices. Wir liefern, stellen auf, schließen an und testen alles vor Ort. Sie müssen sich um nichts kümmern.',
            'faq_question_7' => 'Was passiert bei technischen Problemen?',
            'faq_answer_7' => 'Alle unsere Jukeboxen werden vor jeder Vermietung gründlich geprüft. Sollte dennoch etwas passieren, stehen wir telefonisch zur Verfügung und kommen bei Bedarf vor Ort vorbei.',
            'faq_question_8' => 'Welche Musik- und Wiedergabemöglichkeiten bietet die Jukebox?',
            'faq_answer_8' => 'Das Musikangebot und die Wiedergabemöglichkeiten variieren je nach Modell. Während einige Jukeboxen klassische CDs oder Schallplatten abspielen, verfügen andere über eine Bluetooth-Funktion für individuelles Streaming. Die jeweiligen Ausstattungsmerkmale und Musikoptionen finden Sie direkt bei der Beschreibung des entsprechenden Modells.',
            'faq_cta_title' => 'Noch Fragen?',
            'faq_cta_text' => 'Wir beantworten Ihre Fragen gerne persönlich.',
            'faq_cta_button' => 'Kontakt aufnehmen',
            
            // Contact Page
            'contact_title' => 'Kontaktieren Sie uns',
            'contact_subtitle' => 'Wir freuen uns auf Ihre Anfrage',
            'contact_info_title' => 'Kontaktdaten',
            'contact_form_title' => 'Anfrageformular',
            'contact_form_intro' => 'Füllen Sie das Formular aus und wir melden uns innerhalb von 24 Stunden bei Ihnen.',
            'contact_selected_jukeboxes' => 'Ausgewählte Jukeboxen',
            'contact_no_selection' => 'Keine Jukeboxen ausgewählt.',
            'contact_browse_catalog' => 'Zum Katalog',
            
            // Form Fields
            'form_firstname' => 'Vorname',
            'form_firstname_placeholder' => 'Ihr Vorname',
            'form_lastname' => 'Nachname',
            'form_lastname_placeholder' => 'Ihr Nachname',
            'form_company' => 'Firma',
            'form_company_placeholder' => 'Firmenname (optional)',
            'form_email' => 'E-Mail *',
            'form_email_placeholder' => 'ihre@email.at',
            'form_phone' => 'Telefon *',
            'form_phone_placeholder' => '+43 123 456 7890',
            'form_location' => 'Veranstaltungsort *',
            'form_location_placeholder' => 'Adresse des Events',
            'form_date' => 'Zeitraum *',
            'form_date_placeholder' => 'TT.MM.JJJJ - TT.MM.JJJJ',
            'form_duration' => 'Mietdauer *',
            'form_duration_placeholder' => 'z.B. 1 Tag, 3 Tage, 1 Woche',
            'form_message' => 'Ihre Nachricht',
            'form_message_placeholder' => 'Weitere Details, Wünsche, Fragen...',
            'form_privacy' => 'Ich stimme der Verarbeitung meiner Daten gemäß Datenschutzerklärung zu. *',
            'form_submit' => 'Anfrage senden',
            'form_required' => 'Pflichtfelder sind mit * markiert',
            
            // Form Validation
            'error_required' => 'Dieses Feld ist erforderlich.',
            'error_email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'error_phone' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
            'error_date' => 'Bitte geben Sie ein gültiges Datum im Format TT.MM.JJJJ ein.',
            'error_privacy' => 'Bitte stimmen Sie der Datenschutzerklärung zu.',
            
            // Form Success/Error
            'form_success_title' => 'Vielen Dank!',
            'form_success_message' => 'Ihre Anfrage wurde erfolgreich gesendet. Wir melden uns innerhalb von 24 Stunden bei Ihnen.',
            'form_error_title' => 'Es ist ein Fehler aufgetreten',
            'form_error_message' => 'Bitte überprüfen Sie Ihre Eingaben und versuchen Sie es erneut.',
            'form_error_send' => 'Die Nachricht konnte nicht gesendet werden. Bitte versuchen Sie es später erneut oder kontaktieren Sie uns telefonisch.',
            
            // Transport notice
            'transport_notice_title' => 'Liefergebiete',
            'transport_notice_text' => 'Wir liefern fix nach Österreich, Italien, Deutschland und Schweiz. Für Lieferungen in andere Länder kontaktieren Sie uns bitte für ein individuelles Angebot.',
            
            // Price
            'price_day' => ' / Tag',
            'price_weekend' => ' / Wochenende',
            'price_week' => ' / Woche',
            
            // Buttons
            'btn_send' => 'Senden',
            'btn_cancel' => 'Abbrechen',
            'btn_save' => 'Speichern',
            'btn_delete' => 'Löschen',
            'btn_edit' => 'Bearbeiten',
            'btn_create' => 'Neu erstellen',
            'btn_back' => 'Zurück',
            'btn_close' => 'Schließen',
            'btn_show_more' => 'Mehr anzeigen',
            'btn_show_less' => 'Weniger anzeigen',
            
            // Cookie Notice
            'cookie_title' => 'Cookie-Hinweis',
            'cookie_text' => 'Diese Website verwendet Cookies für die beste Funktionalität.',
            'cookie_accept' => 'Verstanden',
            
            // Admin
            'admin_title' => 'Admin-Bereich',
            'admin_login_title' => 'Admin-Login',
            'admin_username' => 'Benutzername',
            'admin_password' => 'Passwort',
            'admin_login_button' => 'Anmelden',
            'admin_login_error' => 'Ungültige Anmeldedaten.',
            'admin_login_lockout' => 'Zu viele Fehlversuche. Bitte versuchen Sie es in {minutes} Minute(n) erneut.',
            'admin_logout' => 'Abmelden',
            'admin_dashboard_title' => 'Dashboard',
            'admin_jukeboxes_title' => 'Jukeboxen verwalten',
            'admin_create_jukebox' => 'Neue Jukebox',
            'admin_edit_jukebox' => 'Jukebox bearbeiten',
            'admin_delete_confirm' => 'Möchten Sie diese Jukebox wirklich löschen?',
            'admin_no_jukeboxes' => 'Keine Jukeboxen vorhanden.',
            'admin_success_create' => 'Jukebox erfolgreich erstellt.',
            'admin_success_update' => 'Jukebox erfolgreich aktualisiert.',
            'admin_success_delete' => 'Jukebox erfolgreich gelöscht.',
            'admin_success_password_changed' => 'Passwort wurde erfolgreich geändert.',
            'admin_error_save' => 'Fehler beim Speichern.',
            'admin_error_delete' => 'Fehler beim Löschen.',
            'admin_error_csrf' => 'Sicherheitsfehler. Bitte laden Sie die Seite neu.',
            'admin_error_password_empty' => 'Bitte alle Passwortfelder ausfüllen.',
            'admin_error_password_current_wrong' => 'Das aktuelle Passwort ist falsch.',
            'admin_error_password_mismatch' => 'Die neuen Passwörter stimmen nicht überein.',
            'admin_error_password_too_short' => 'Das neue Passwort muss mindestens 8 Zeichen haben.',
            'admin_offers_title' => 'Angebote verwalten',
            'admin_invoices_title' => 'Rechnungen verwalten',
            'admin_dashboard_stats' => 'Übersicht',
            'admin_open_offers' => 'Offene Angebote',
            'admin_accepted_offers' => 'Angenommene Angebote',
            'admin_open_invoices' => 'Offene Rechnungen',
            'admin_total_revenue' => 'Gesamtumsatz',
            'admin_open_revenue' => 'Offener Umsatz',
            'admin_view_pdf' => 'PDF ansehen',
            'admin_mark_paid' => 'Als bezahlt markieren',
            'admin_back_to_dashboard' => 'Zurück zum Dashboard',
            'admin_offer_number' => 'Angebotsnummer',
            'admin_invoice_number' => 'Rechnungsnummer',
            'admin_customer' => 'Kunde',
            'admin_date' => 'Datum',
            'admin_status' => 'Status',
            'admin_amount' => 'Betrag',
            'admin_actions' => 'Aktionen',
            'admin_no_offers' => 'Keine Angebote vorhanden.',
            'admin_no_invoices' => 'Keine Rechnungen vorhanden.',
            'admin_offer_status_pending' => 'Ausstehend',
            'admin_offer_status_accepted' => 'Angenommen',
            'admin_offer_status_declined' => 'Abgelehnt',
            'admin_invoice_status_open' => 'Offen',
            'admin_invoice_status_paid' => 'Bezahlt',
            'admin_offer_email_subject' => 'Ihr unverbindliches Angebot von {company}',
            'admin_invoice_email_subject' => 'Ihre Rechnung von {company}',
            'admin_offer_email_body' => "<p>Hallo {name},</p><p>vielen Dank für Ihre Anfrage. Im Anhang finden Sie Ihr unverbindliches Angebot.</p><p>Sie können das Angebot auch online einsehen und direkt annehmen oder ablehnen:</p><p style=\"margin:24px 0;\"><a href=\"{offer_link}\" style=\"display:inline-block;padding:12px 24px;background:#22c55e;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;margin-right:12px;\">Angebot annehmen</a><a href=\"{offer_link}\" style=\"display:inline-block;padding:12px 24px;background:#ef4444;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;\">Angebot ablehnen</a></p><p>Alternativ können Sie das Angebot auch über diesen Link öffnen:<br><a href=\"{offer_link}\">{offer_link}</a></p><p>Das Angebot ist gültig bis {valid_until}.</p><p>Mit freundlichen Grüßen<br><strong>{company} Team</strong></p>",
            'admin_invoice_email_body' => "<p>Hallo {name},</p><p>vielen Dank für die Annahme unseres Angebots. Im Anhang finden Sie Ihre Rechnung.</p><p>Bitte überweisen Sie den Betrag innerhalb von 14 Tagen auf das in der Rechnung angegebene Konto.</p><p>Mit freundlichen Grüßen<br><strong>{company} Team</strong></p>",
            'admin_invoice_marked_paid' => 'Rechnung wurde als bezahlt markiert.',
            'admin_invoice_mark_paid_error' => 'Rechnung konnte nicht als bezahlt markiert werden.',
            'admin_calendar' => 'Vermietungskalender',
            'admin_calendar_title' => 'Vermietungskalender',
            'admin_rentals' => 'Vermietungen',
            'admin_rental_status_reserved' => 'Reserviert',
            'admin_rental_status_confirmed' => 'Bestätigt',
            'admin_rental_status_cancelled' => 'Storniert',
            'admin_rental_status_completed' => 'Abgeschlossen',
            'admin_calendar_prev' => '← Zurück',
            'admin_calendar_next' => 'Weiter →',
            'admin_calendar_today' => 'Heute',
            'admin_calendar_jukebox' => 'Jukebox',
            'admin_calendar_period' => 'Zeitraum',
            'admin_calendar_status' => 'Status',
            'admin_no_rentals' => 'Keine Vermietungen im gewählten Zeitraum.',
            'admin_settings_password_title' => 'Admin-Passwort ändern',
            'admin_settings_password_current' => 'Aktuelles Passwort',
            'admin_settings_password_new' => 'Neues Passwort',
            'admin_settings_password_confirm' => 'Neues Passwort bestätigen',
            'admin_settings_password_button' => 'Passwort ändern',
            
            // Offer page
            'offer_title' => 'Ihr Angebot',
            'offer_error_missing_token' => 'Kein Angebotstoken angegeben.',
            'offer_error_not_found' => 'Angebot nicht gefunden.',
            'offer_error_expired' => 'Dieses Angebot ist nicht mehr gültig.',
            'offer_error_csrf' => 'Sicherheitsfehler. Bitte laden Sie die Seite neu.',
            'offer_error_accept' => 'Das Angebot konnte nicht angenommen werden.',
            'offer_error_decline' => 'Das Angebot konnte nicht abgelehnt werden.',
            'offer_error_invoice_create' => 'Die Rechnung konnte nicht erstellt werden.',
            'offer_error_invoice_pdf' => 'Die Rechnungs-PDF konnte nicht erstellt werden.',
            'offer_success_accepted' => 'Vielen Dank! Wir haben Ihre Annahme erhalten und senden Ihnen die Rechnung zu.',
            'offer_success_declined' => 'Das Angebot wurde abgelehnt.',
            'offer_download_invoice' => 'Rechnung herunterladen',
            'offer_number' => 'Angebotsnummer',
            'offer_valid_until' => 'Gültig bis',
            'offer_intro' => 'Vielen Dank für Ihre Anfrage. Hier finden Sie die Details zu Ihrem persönlichen Angebot.',
            'offer_customer' => 'Kunde',
            'offer_view_pdf' => 'Angebot als PDF',
            'offer_confirm_accept' => 'Möchten Sie dieses Angebot annehmen?',
            'offer_confirm_decline' => 'Möchten Sie dieses Angebot ablehnen?',
            'offer_accept' => 'Angebot annehmen',
            'offer_decline' => 'Angebot ablehnen',
            'offer_status_accepted' => 'Angebot angenommen',
            'offer_status_declined' => 'Angebot abgelehnt',
            
            // Imprint
            'imprint_title' => 'Impressum',
            'imprint_responsible' => 'Verantwortlich für den Inhalt',
            'imprint_company' => 'Unternehmen',
            'imprint_address' => 'Adresse',
            'imprint_contact' => 'Kontakt',
            'imprint_vat' => 'UID-Nummer',
            'imprint_court' => 'Firmenbuch',
            'imprint_disclaimer' => 'Haftungsausschluss',
            'imprint_disclaimer_text' => 'Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für die Inhalte externer Links. Für den Inhalt der verlinkten Seiten sind ausschließlich deren Betreiber verantwortlich.',
            
            // Privacy
            'privacy_title' => 'Datenschutzerklärung',
            'privacy_intro' => 'Der Schutz Ihrer persönlichen Daten ist uns ein besonderes Anliegen. Wir verarbeiten Ihre Daten daher ausschließlich auf Grundlage der gesetzlichen Bestimmungen.',
            'privacy_data_title' => 'Erhebung und Verarbeitung personenbezogener Daten',
            'privacy_data_text' => 'Wir erheben und verarbeiten personenbezogene Daten nur, soweit dies für die Bereitstellung unserer Dienste erforderlich ist. Dies umfasst insbesondere Kontaktdaten für Anfragen und Buchungen.',
            'privacy_rights_title' => 'Ihre Rechte',
            'privacy_rights_text' => 'Ihnen stehen grundsätzlich die Rechte auf Auskunft, Berichtigung, Löschung, Einschränkung, Datenübertragbarkeit und Widerspruch zu.',
            'privacy_contact_title' => 'Kontakt Datenschutz',
            'privacy_contact_text' => 'Bei Fragen zur Verarbeitung Ihrer personenbezogenen Daten kontaktieren Sie uns bitte unter der oben angegebenen E-Mail-Adresse.',
            
            // Request / Cart
            'request_title' => 'Ihre Anfrage',
            'request_subtitle' => 'Unverbindliche Preisschätzung',
            'request_selected_jukeboxes' => 'Ausgewählte Jukeboxen',
            'request_rental_data' => 'Mietdaten',
            'request_rental_period' => 'Mietzeitraum',
            'request_event_address' => 'Event-Adresse (vollständig)',
            'request_discount_code' => 'Rabattcode',
            'request_apply' => 'Anwenden',
            'cart_empty_title' => 'Noch keine Jukeboxen ausgewählt.',
            'cart_add_boxes' => 'Jukeboxen hinzufügen',
            'cart_remove' => 'Entfernen',
            'request_continue_browsing' => 'Weiter stöbern',
            'request_price_summary' => 'Preiszusammenfassung',
            'request_back_overview' => 'Zurück zur Übersicht',
            'request_continue_inquiry' => 'Weiter zur Anfrage',
            'request_days' => 'Tage',
            'request_day' => 'Tag',
            'request_your_inquiry' => 'Ihre Anfragedaten',
            'request_address' => 'Adresse',
            'request_non_binding' => 'Unverbindliche Preisschätzung',
            'request_calculating' => 'Berechne...',
            'request_price_subtotal_jukeboxes' => 'Zwischensumme Jukeboxen',
            'request_rental_net' => 'Mietkosten netto',
            'request_transport' => 'Transport',
            'request_total_net' => 'Gesamt netto',
            'request_vat' => 'USt.',
            'request_total_gross' => 'Gesamt brutto',
            'request_contract_fee' => 'Vertragsgebühr ({percent} %)',
            'request_contract_fee_hint' => 'Gemäß § 33 Tarifpunkt 5 Gebührengesetz 1957 (GebG).',
            'request_total_with_contract_fee' => 'Gesamtbetrag inkl. Vertragsgebühr',
            'request_duration_discount' => 'Mietdauer-Rabatt',
            'request_quantity_discount' => 'Mengenrabatt',
            'request_coupon_discount' => 'Coupon-Rabatt',
            'request_individually_calculated' => 'Individuell berechnet',
            'request_enter_address' => 'Bitte Adresse angeben',
            'request_coupon_valid' => 'Coupon gültig.',
            'request_coupon_invalid' => 'Ungültiger Coupon.',
            'request_coupon_expired' => 'Coupon abgelaufen.',
            'request_coupon_inactive' => 'Coupon inaktiv.',
            'request_coupon_min_order' => 'Mindestbestellwert nicht erreicht.',
            'request_hint_non_binding' => 'Unverbindliche Preisschätzung',
            'request_hint_no_booking' => 'Keine verbindliche Buchung.',
            'request_hint_final_offer' => 'Endgültiges Angebot nach Prüfung der Verfügbarkeit.',
            'request_hint_transport_depends' => 'Transportkosten abhängig von Standort und tatsächlichen Gegebenheiten vor Ort.',
            'cart_date_required' => 'Bitte wählen Sie zuerst einen Mietzeitraum.',
            'cart_item_not_available' => 'Mindestens eine ausgewählte Jukebox ist im gewählten Zeitraum nicht verfügbar.',
            'cart_item_not_available_period' => 'Nicht verfügbar: {name} ({start} - {end})',
        ],
        
        // ============================================
        // ENGLISH TRANSLATIONS
        // ============================================
        'en' => [
            'meta_title_home' => 'Revibe | Premium Jukeboxes for Your Event',
            'meta_description_home' => 'Rent high-quality jukeboxes for your event. Full service with delivery, setup and pickup in AT, IT, DE and CH.',
            'meta_title_about' => 'About Us | Revibe',
            'meta_description_about' => 'Learn more about our passion for jukeboxes and our first-class full service for events in Austria.',
            'meta_title_catalog' => 'Jukebox Catalog | All Models for Rent',
            'meta_description_catalog' => 'Discover our exclusive collection of jukeboxes. From classic retro models to modern digital jukeboxes.',
            'meta_title_contact' => 'Contact | Request a Jukebox',
            'meta_description_contact' => 'Contact us for your jukebox inquiry. We will create a custom quote for your event.',
            'meta_title_faq' => 'FAQ | Frequently Asked Questions',
            'meta_description_faq' => 'Answers to all questions about jukebox rental: delivery, setup, prices, technology and more.',
            'meta_title_process' => 'Rental Process | How It Works',
            'meta_description_process' => 'The easy way to your jukebox: selection, inquiry, quote, delivery and pickup. Full service included.',
            'meta_title_offer' => 'Your Offer | Revibe',
            'meta_description_offer' => 'Your personal offer from Revibe. View, accept or decline.',
            
            // Navigation
            'nav_home' => 'Home',
            'nav_about' => 'About Us',
            'nav_catalog' => 'Jukeboxes',
            'nav_process' => 'Rental Process',
            'nav_faq' => 'FAQ',
            'nav_contact' => 'Contact',
            'nav_inquiry_list' => 'Inquiry List',
            'nav_inquiry_list_empty' => 'Inquiry list is empty',
            
            // Language Switcher
            'lang_de' => 'Deutsch',
            'lang_en' => 'English',
            'lang_switch' => 'Switch language',
            
            // Hero
            'hero_label' => 'Jukebox Rental',
            'hero_title' => 'Rent a Jukebox for Your Event',
            'hero_subtitle' => 'Original premium jukeboxes – including delivery, setup & pickup. Perfect for weddings, corporate events and private parties.',
            'hero_cta_primary' => 'Discover Jukeboxes',
            'hero_cta_secondary' => 'Request Now',
            
            // Intro
            'intro_title' => 'Music History to Touch',
            'intro_text' => 'Our jukeboxes are more than just music players – they are time machines that transform your event into a unique experience. From authentic retro classics to modern digital systems, we offer the perfect soundtrack for every occasion.',
            'intro_feature_1_title' => 'Delivery to AT, IT, DE, CH',
            'intro_feature_1_text' => 'We deliver your jukebox to Austria, Italy, Germany and Switzerland. Contact us for deliveries to other countries.',
            'intro_feature_2_title' => 'Full Service',
            'intro_feature_2_text' => 'Delivery, setup, tech check and pickup – everything from one source, no stress for you.',
            'intro_feature_3_title' => 'Authentic Pieces',
            'intro_feature_3_text' => 'Every jukebox in our collection is carefully selected – either an original or high-quality reproduction.',
            
            // Featured Jukeboxes
            'featured_title' => 'Our Highlights',
            'featured_subtitle' => 'These Jukeboxes Will Delight Your Guests',
            'featured_cta' => 'View All Jukeboxes',
            'view_details' => 'View Details',
            'add_to_inquiry' => 'Add to Inquiry',
            'remove_from_inquiry' => 'Remove',
            
            // Benefits
            'benefits_title' => 'Why Revibe?',
            'benefit_1_title' => 'No Stress, Just Joy',
            'benefit_1_text' => 'We take care of all the technical details. You enjoy your event and your guests are delighted.',
            'benefit_2_title' => 'Flexible & Individual',
            'benefit_2_text' => 'Whether one night or a week – we find the right rental period and the perfect model for your event.',
            'benefit_3_title' => 'Real Eye-Catchers',
            'benefit_3_text' => 'Our jukeboxes are both a visual highlight and a conversation starter. They create atmosphere like nothing else.',
            'benefit_4_title' => 'Professional Service',
            'benefit_4_text' => 'Years of experience, reliable technology and personal support – from first inquiry to final pickup.',
            
            // Process
            'process_title' => 'How It Works',
            'process_step_1_number' => '01',
            'process_step_1_title' => 'Choose Jukebox',
            'process_step_1_text' => 'Browse our catalog and select your favorites. Multiple boxes at once are possible.',
            'process_step_2_number' => '02',
            'process_step_2_title' => 'Send Inquiry',
            'process_step_2_text' => 'Let us know the date, location and duration. We check availability and create your quote.',
            'process_step_3_number' => '03',
            'process_step_3_title' => 'Receive Quote',
            'process_step_3_text' => 'You receive a transparent quote including all costs – no hidden fees.',
            'process_step_4_number' => '04',
            'process_step_4_title' => 'We deliver and set up',
            'process_step_4_text' => 'Right on time for your event, we deliver your jukebox, set it up and make it ready to go.',
            'process_step_5_number' => '05',
            'process_step_5_title' => 'Enjoy Event',
            'process_step_5_text' => 'Your guests have the time of their lives.',
            'process_step_6_number' => '06',
            'process_step_6_title' => 'We Pick Up',
            'process_step_6_text' => 'After the event, we come at the agreed time to pick up the jukebox.',
            
            // CTA Section
            'cta_title' => 'Ready for the Perfect Soundtrack?',
            'cta_text' => 'Let us plan your unforgettable event together. Contact us now for a custom quote.',
            'cta_button' => 'Plan Event Now',
            
            // FAQ Preview
            'faq_preview_title' => 'Common Questions',
            'faq_preview_cta' => 'All Questions & Answers',
            
            // Footer
            'footer_tagline' => 'Premium jukeboxes for unforgettable events',
            'footer_contact_title' => 'Contact',
            'footer_legal_title' => 'Legal',
            'footer_imprint' => 'Imprint',
            'footer_privacy' => 'Privacy Policy',
            'footer_copyright' => '© {year} Revibe. All rights reserved.',
            
            // About Page
            'about_title' => 'Our Story',
            'about_subtitle' => 'Passion for Music, Love for Detail',
            'about_text_1' => 'Revibe was born from a simple idea: to make the magic of authentic jukeboxes tangible for people. What started as a small collection is now one of the most exclusive jukebox rental services in Austria.',
            'about_text_2' => 'Every jukebox in our inventory is carefully selected – whether it\'s an original restored piece from the 1950s or a high-quality reissue with modern technology. We believe that music is more than just background noise. It is the soul of every great event.',
            'about_text_3' => 'Our team lives this philosophy. From the first consultation to the final pickup, we are at your side with expertise and commitment. Because your successful event is our success too.',
            'about_values_title' => 'Our Values',
            'about_value_1_title' => 'Authenticity',
            'about_value_1_text' => 'We offer real experiences – no imitations, no compromises.',
            'about_value_2_title' => 'Reliability',
            'about_value_2_text' => 'We keep our promises. Period.',
            'about_value_3_title' => 'Passion',
            'about_value_3_text' => 'We love what we do. You\'ll feel it in every contact.',
            
            // Catalog Page
            'catalog_title' => 'Our Jukeboxes',
            'catalog_subtitle' => 'Find the perfect model for your event',
            'catalog_empty' => 'No jukeboxes available at the moment.',
            'catalog_filters' => 'Filters',
            'catalog_sort' => 'Sort by',
            'sort_name_asc' => 'Name A-Z',
            'sort_name_desc' => 'Name Z-A',
            'sort_price_asc' => 'Price ascending',
            'sort_price_desc' => 'Price descending',
            'items_selected' => '{count} jukebox(es) selected',
            'clear_selection' => 'Clear selection',
            'catalog_results_count' => '{count} results',
            'catalog_reset_filters' => 'Reset filters',
            'catalog_filter_manufacturer' => 'Manufacturer',
            'catalog_filter_size' => 'Size',
            'catalog_filter_color' => 'Color',
            'catalog_filter_function' => 'Function',
            'catalog_filter_category' => 'Category',
            'catalog_filter_price' => 'Price',
            'catalog_filter_new_arrival' => 'New Arrival',
            'catalog_filter_all' => 'All',
            'catalog_filter_select' => 'Select',
            'catalog_filter_apply' => 'Apply',
            'catalog_filter_close' => 'Close',
            'catalog_filter_price_per_day' => '€ / day',
            'catalog_select_dates' => 'Select rental period first',
            'catalog_date_selector_hint' => 'Date selection is used for all selected jukeboxes.',
            'catalog_date_saving' => 'Saving rental period ...',
            'catalog_date_saved' => 'Rental period saved.',
            'catalog_not_available' => 'This jukebox is not available in the selected period.',
            'catalog_select_dates_first' => 'Please select a rental period first.',
            'catalog_date_modal_intro' => 'Select your rental period to see jukeboxes that are actually available on your event date. Each box is a one-off piece and can only be rented once per period.',
            'catalog_date_info_title' => 'Why select the date first?',
            'catalog_date_info_text' => 'Our jukeboxes are unique one-off pieces. By selecting the rental period first, you immediately see which boxes are still free on your date – and avoid disappointment when inquiring.',
            'catalog_date_skip' => 'Skip for now',
            'catalog_date_view_all' => 'View full assortment',
            'catalog_date_selected_period' => 'Selected period',
            'catalog_date_change' => 'Change',

            // Jukebox Detail
            'detail_manufacturer' => 'Manufacturer',
            'detail_model' => 'Model',
            'detail_year' => 'Year',
            'detail_format' => 'Music Format',
            'detail_condition' => 'Condition',
            'detail_function' => 'Function Status',
            'detail_power' => 'Power Connection',
            'detail_size' => 'Dimensions',
            'detail_price' => 'Rental Price',
            'detail_gallery' => 'Gallery',
            'detail_inquiry' => 'Inquire This Jukebox',
            'detail_back' => 'Back to Catalog',
            'status_working' => 'Fully Functional',
            'status_deco' => 'Decorative Object',
            'status_restored' => 'Restored',
            'status_original' => 'Original Condition',
            
            // Process Page
            'process_page_title' => 'The Rental Process',
            'process_page_subtitle' => 'How to Get Your Jukebox',
            'process_detail_intro' => 'We have made the rental process as simple as possible. Six steps from idea to unforgettable event experience.',
            'process_delivery_title' => 'Delivery Areas & Transport Costs',
            'process_delivery_text' => 'We deliver to Austria, Italy, Germany and Switzerland (AT, IT, DE, CH). For deliveries to other countries, we are happy to create a custom quote. Transport costs are calculated transparently based on distance and effort.',
            'process_timing_title' => 'Booking Period',
            'process_timing_text' => 'We recommend early booking, especially for weddings during peak season (May-September). Reservations are possible up to 12 months in advance. For last-minute inquiries, we are happy to check availability.',
            
            // FAQ Page
            'faq_title' => 'Frequently Asked Questions',
            'faq_subtitle' => 'Answers to Your Most Important Questions',
            'faq_category_general' => 'General',
            'faq_category_technical' => 'Technical & Setup',
            'faq_category_pricing' => 'Pricing & Booking',
            'faq_question_1' => 'How far in advance should I book?',
            'faq_answer_1' => 'For weddings and events during peak season (May-September), we recommend booking 3-6 months in advance. For short-term dates, we are happy to check availability. Depending on availability, short-term or spontaneous dates are also possible.',
            'faq_question_2' => 'What power connections are required?',
            'faq_answer_2' => 'Most of our jukeboxes require a standard 230V Schuko connection. Details on power requirements can be found in the description of each jukebox.',
            'faq_question_3' => 'How are transport costs calculated?',
            'faq_answer_3' => 'Transport costs depend on the distance to the event location and the effort required for delivery and pickup. You will receive a transparent breakdown of all costs in your quote.',
            'faq_question_4' => 'Is there a minimum rental period?',
            'faq_answer_4' => 'The minimum rental period is one day (24 hours). For multi-day events or trade shows, we are happy to create a custom package deal.',
            'faq_question_5' => 'Can I rent multiple jukeboxes at once?',
            'faq_answer_5' => 'Absolutely! Many customers book multiple jukeboxes for large events or to cover different areas. For each additional jukebox, you benefit from attractive volume discounts.',
            'faq_question_6' => 'Who takes care of the setup?',
            'faq_answer_6' => 'Setup is part of our full service. We deliver, set up, connect and test everything on site. You don\'t have to worry about anything.',
            'faq_question_7' => 'What happens with technical problems?',
            'faq_answer_7' => 'All our jukeboxes are thoroughly checked before each rental. Should something still happen, we are available by phone and will come on site if needed.',
            'faq_question_8' => 'What music and playback options does the jukebox offer?',
            'faq_answer_8' => 'The music selection and playback options vary depending on the model. While some jukeboxes play classic CDs or vinyl records, others have a Bluetooth function for individual streaming. You can find the respective features and music options directly in the description of the corresponding model.',
            'faq_cta_title' => 'Still Have Questions?',
            'faq_cta_text' => 'We are happy to answer your questions personally.',
            'faq_cta_button' => 'Get in Touch',
            
            // Contact Page
            'contact_title' => 'Contact Us',
            'contact_subtitle' => 'We Look Forward to Your Inquiry',
            'contact_info_title' => 'Contact Details',
            'contact_form_title' => 'Inquiry Form',
            'contact_form_intro' => 'Fill out the form and we will get back to you within 24 hours.',
            'contact_selected_jukeboxes' => 'Selected Jukeboxes',
            'contact_no_selection' => 'No jukeboxes selected.',
            'contact_browse_catalog' => 'Browse Catalog',
            
            // Form Fields
            'form_firstname' => 'First Name',
            'form_firstname_placeholder' => 'Your first name',
            'form_lastname' => 'Last Name',
            'form_lastname_placeholder' => 'Your last name',
            'form_company' => 'Company',
            'form_company_placeholder' => 'Company name (optional)',
            'form_email' => 'Email *',
            'form_email_placeholder' => 'your@email.com',
            'form_phone' => 'Phone *',
            'form_phone_placeholder' => '+43 123 456 7890',
            'form_location' => 'Event Location *',
            'form_location_placeholder' => 'Address of the event',
            'form_date' => 'Date Range *',
            'form_date_placeholder' => 'DD.MM.YYYY - DD.MM.YYYY',
            'form_duration' => 'Rental Duration *',
            'form_duration_placeholder' => 'e.g. 1 day, 3 days, 1 week',
            'form_message' => 'Your Message',
            'form_message_placeholder' => 'Additional details, wishes, questions...',
            'form_privacy' => 'I agree to the processing of my data according to the privacy policy. *',
            'form_submit' => 'Send Inquiry',
            'form_required' => 'Required fields are marked with *',
            
            // Form Validation
            'error_required' => 'This field is required.',
            'error_email' => 'Please enter a valid email address.',
            'error_phone' => 'Please enter a valid phone number.',
            'error_date' => 'Please enter a valid date in DD.MM.YYYY format.',
            'error_privacy' => 'Please agree to the privacy policy.',
            
            // Form Success/Error
            'form_success_title' => 'Thank You!',
            'form_success_message' => 'Your inquiry has been sent successfully. We will get back to you within 24 hours.',
            'form_error_title' => 'An Error Occurred',
            'form_error_message' => 'Please check your entries and try again.',
            'form_error_send' => 'The message could not be sent. Please try again later or contact us by phone.',
            
            // Transport notice
            'transport_notice_title' => 'Delivery Areas',
            'transport_notice_text' => 'We deliver to Austria, Italy, Germany and Switzerland. For deliveries to other countries, please contact us for a custom quote.',
            
            // Price
            'price_day' => ' / day',
            'price_weekend' => ' / weekend',
            'price_week' => ' / week',
            
            // Buttons
            'btn_send' => 'Send',
            'btn_cancel' => 'Cancel',
            'btn_save' => 'Save',
            'btn_delete' => 'Delete',
            'btn_edit' => 'Edit',
            'btn_create' => 'Create New',
            'btn_back' => 'Back',
            'btn_close' => 'Close',
            'btn_show_more' => 'Show More',
            'btn_show_less' => 'Show Less',
            
            // Cookie Notice
            'cookie_title' => 'Cookie Notice',
            'cookie_text' => 'This website uses cookies for the best functionality.',
            'cookie_accept' => 'Understood',
            
            // Admin
            'admin_title' => 'Admin Area',
            'admin_login_title' => 'Admin Login',
            'admin_username' => 'Username',
            'admin_password' => 'Password',
            'admin_login_button' => 'Login',
            'admin_login_error' => 'Invalid login credentials.',
            'admin_login_lockout' => 'Too many failed attempts. Please try again in {minutes} minute(s).',
            'admin_logout' => 'Logout',
            'admin_dashboard_title' => 'Dashboard',
            'admin_jukeboxes_title' => 'Manage Jukeboxes',
            'admin_create_jukebox' => 'New Jukebox',
            'admin_edit_jukebox' => 'Edit Jukebox',
            'admin_delete_confirm' => 'Do you really want to delete this jukebox?',
            'admin_no_jukeboxes' => 'No jukeboxes available.',
            'admin_success_create' => 'Jukebox created successfully.',
            'admin_success_update' => 'Jukebox updated successfully.',
            'admin_success_delete' => 'Jukebox deleted successfully.',
            'admin_success_password_changed' => 'Password changed successfully.',
            'admin_error_save' => 'Error saving.',
            'admin_error_delete' => 'Error deleting.',
            'admin_error_csrf' => 'Security error. Please reload the page.',
            'admin_error_password_empty' => 'Please fill in all password fields.',
            'admin_error_password_current_wrong' => 'Current password is incorrect.',
            'admin_error_password_mismatch' => 'New passwords do not match.',
            'admin_error_password_too_short' => 'New password must be at least 8 characters.',
            'admin_offers_title' => 'Manage Offers',
            'admin_invoices_title' => 'Manage Invoices',
            'admin_dashboard_stats' => 'Overview',
            'admin_open_offers' => 'Open Offers',
            'admin_accepted_offers' => 'Accepted Offers',
            'admin_open_invoices' => 'Open Invoices',
            'admin_total_revenue' => 'Total Revenue',
            'admin_open_revenue' => 'Open Revenue',
            'admin_view_pdf' => 'View PDF',
            'admin_mark_paid' => 'Mark as paid',
            'admin_back_to_dashboard' => 'Back to Dashboard',
            'admin_offer_number' => 'Offer Number',
            'admin_invoice_number' => 'Invoice Number',
            'admin_customer' => 'Customer',
            'admin_date' => 'Date',
            'admin_status' => 'Status',
            'admin_amount' => 'Amount',
            'admin_actions' => 'Actions',
            'admin_no_offers' => 'No offers available.',
            'admin_no_invoices' => 'No invoices available.',
            'admin_offer_status_pending' => 'Pending',
            'admin_offer_status_accepted' => 'Accepted',
            'admin_offer_status_declined' => 'Declined',
            'admin_invoice_status_open' => 'Open',
            'admin_invoice_status_paid' => 'Paid',
            'admin_offer_email_subject' => 'Your non-binding offer from {company}',
            'admin_invoice_email_subject' => 'Your invoice from {company}',
            'admin_offer_email_body' => "<p>Hello {name},</p><p>thank you for your inquiry. Please find your non-binding offer attached.</p><p>You can also view the offer online and accept or decline it directly:</p><p style=\"margin:24px 0;\"><a href=\"{offer_link}\" style=\"display:inline-block;padding:12px 24px;background:#22c55e;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;margin-right:12px;\">Accept offer</a><a href=\"{offer_link}\" style=\"display:inline-block;padding:12px 24px;background:#ef4444;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;\">Decline offer</a></p><p>Alternatively, you can open the offer via this link:<br><a href=\"{offer_link}\">{offer_link}</a></p><p>The offer is valid until {valid_until}.</p><p>Best regards<br><strong>{company} Team</strong></p>",
            'admin_invoice_email_body' => "<p>Hello {name},</p><p>thank you for accepting our offer. Please find your invoice attached.</p><p>Please transfer the amount within 14 days to the account stated in the invoice.</p><p>Best regards<br><strong>{company} Team</strong></p>",
            'admin_invoice_marked_paid' => 'Invoice marked as paid.',
            'admin_invoice_mark_paid_error' => 'Invoice could not be marked as paid.',
            'admin_calendar' => 'Rental Calendar',
            'admin_calendar_title' => 'Rental Calendar',
            'admin_rentals' => 'Rentals',
            'admin_rental_status_reserved' => 'Reserved',
            'admin_rental_status_confirmed' => 'Confirmed',
            'admin_rental_status_cancelled' => 'Cancelled',
            'admin_rental_status_completed' => 'Completed',
            'admin_calendar_prev' => '← Back',
            'admin_calendar_next' => 'Next →',
            'admin_calendar_today' => 'Today',
            'admin_calendar_jukebox' => 'Jukebox',
            'admin_calendar_period' => 'Period',
            'admin_calendar_status' => 'Status',
            'admin_no_rentals' => 'No rentals in the selected period.',
            'admin_settings_password_title' => 'Change Admin Password',
            'admin_settings_password_current' => 'Current Password',
            'admin_settings_password_new' => 'New Password',
            'admin_settings_password_confirm' => 'Confirm New Password',
            'admin_settings_password_button' => 'Change Password',
            
            // Offer page
            'offer_title' => 'Your Offer',
            'offer_error_missing_token' => 'No offer token provided.',
            'offer_error_not_found' => 'Offer not found.',
            'offer_error_expired' => 'This offer is no longer valid.',
            'offer_error_csrf' => 'Security error. Please reload the page.',
            'offer_error_accept' => 'The offer could not be accepted.',
            'offer_error_decline' => 'The offer could not be declined.',
            'offer_error_invoice_create' => 'The invoice could not be created.',
            'offer_error_invoice_pdf' => 'The invoice PDF could not be created.',
            'offer_success_accepted' => 'Thank you! We have received your acceptance and will send you the invoice.',
            'offer_success_declined' => 'The offer has been declined.',
            'offer_download_invoice' => 'Download invoice',
            'offer_number' => 'Offer Number',
            'offer_valid_until' => 'Valid until',
            'offer_intro' => 'Thank you for your inquiry. Here are the details of your personal offer.',
            'offer_customer' => 'Customer',
            'offer_view_pdf' => 'Offer as PDF',
            'offer_confirm_accept' => 'Do you want to accept this offer?',
            'offer_confirm_decline' => 'Do you want to decline this offer?',
            'offer_accept' => 'Accept offer',
            'offer_decline' => 'Decline offer',
            'offer_status_accepted' => 'Offer accepted',
            'offer_status_declined' => 'Offer declined',
            
            // Imprint
            'imprint_title' => 'Imprint',
            'imprint_responsible' => 'Responsible for Content',
            'imprint_company' => 'Company',
            'imprint_address' => 'Address',
            'imprint_contact' => 'Contact',
            'imprint_vat' => 'VAT Number',
            'imprint_court' => 'Commercial Register',
            'imprint_disclaimer' => 'Disclaimer',
            'imprint_disclaimer_text' => 'Despite careful content control, we assume no liability for the content of external links. The operators of the linked pages are solely responsible for their content.',
            
            // Privacy
            'privacy_title' => 'Privacy Policy',
            'privacy_intro' => 'The protection of your personal data is particularly important to us. We therefore process your data exclusively on the basis of legal regulations.',
            'privacy_data_title' => 'Collection and Processing of Personal Data',
            'privacy_data_text' => 'We collect and process personal data only to the extent necessary to provide our services. This includes in particular contact data for inquiries and bookings.',
            'privacy_rights_title' => 'Your Rights',
            'privacy_rights_text' => 'You generally have the rights to information, correction, deletion, restriction, data portability and objection.',
            'privacy_contact_title' => 'Contact Data Protection',
            'privacy_contact_text' => 'If you have any questions about the processing of your personal data, please contact us at the email address provided above.',
            
            // Request / Cart
            'request_title' => 'Your Inquiry',
            'request_subtitle' => 'Non-binding price estimate',
            'request_selected_jukeboxes' => 'Selected Jukeboxes',
            'request_rental_data' => 'Rental Data',
            'request_rental_period' => 'Rental Period',
            'request_event_address' => 'Event Address (full)',
            'request_discount_code' => 'Discount Code',
            'request_apply' => 'Apply',
            'cart_empty_title' => 'No jukeboxes selected yet.',
            'cart_add_boxes' => 'Add jukeboxes',
            'cart_remove' => 'Remove',
            'request_continue_browsing' => 'Continue browsing',
            'request_price_summary' => 'Price Summary',
            'request_back_overview' => 'Back to overview',
            'request_continue_inquiry' => 'Continue to inquiry',
            'request_days' => 'days',
            'request_day' => 'day',
            'request_your_inquiry' => 'Your Inquiry Details',
            'request_address' => 'Address',
            'request_non_binding' => 'Non-binding price estimate',
            'request_calculating' => 'Calculating...',
            'request_price_subtotal_jukeboxes' => 'Jukebox Subtotal',
            'request_rental_net' => 'Rental net',
            'request_transport' => 'Transport',
            'request_total_net' => 'Total net',
            'request_vat' => 'VAT',
            'request_total_gross' => 'Total gross',
            'request_contract_fee' => 'Contract fee ({percent} %)',
            'request_contract_fee_hint' => 'Pursuant to § 33 Tarifpunkt 5 Gebührengesetz 1957 (Austrian Stamp Duty Act).',
            'request_total_with_contract_fee' => 'Total including contract fee',
            'request_duration_discount' => 'Duration Discount',
            'request_quantity_discount' => 'Quantity Discount',
            'request_coupon_discount' => 'Coupon Discount',
            'request_individually_calculated' => 'Calculated individually',
            'request_enter_address' => 'Please enter address',
            'request_coupon_valid' => 'Coupon valid.',
            'request_coupon_invalid' => 'Invalid coupon.',
            'request_coupon_expired' => 'Coupon expired.',
            'request_coupon_inactive' => 'Coupon inactive.',
            'request_coupon_min_order' => 'Minimum order value not reached.',
            'request_hint_non_binding' => 'Non-binding price estimate',
            'request_hint_no_booking' => 'No binding booking.',
            'request_hint_final_offer' => 'Final offer after availability check.',
            'request_hint_transport_depends' => 'Transport costs depend on location and actual on-site conditions.',
            'cart_date_required' => 'Please select a rental period first.',
            'cart_item_not_available' => 'At least one selected jukebox is not available in the selected period.',
            'cart_item_not_available_period' => 'Not available: {name} ({start} - {end})',
        ]
    ];
    
    return $translations[$lang] ?? $translations[DEFAULT_LANGUAGE];
}

/**
 * Kurzform für Übersetzungen
 */
function __($key, $replacements = []) {
    $lang = getCurrentLanguage();
    $translations = getTranslations($lang);
    $text = $translations[$key] ?? $key;
    
    // Platzhalter ersetzen
    foreach ($replacements as $search => $replace) {
        $text = str_replace('{' . $search . '}', $replace, $text);
    }
    
    return $text;
}
