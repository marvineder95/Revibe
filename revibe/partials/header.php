<?php
/**
 * Header-Partial
 * Wird auf allen Seiten eingebunden
 */
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <?php
    $meta = getMetaTags($page ?? 'home', $metaData ?? []);
    echo implode("\n    ", $meta);
    ?>
    
    <!-- Google Fonts - Erweiterte kreative Schriftarten -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Righteous&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>images/favicon.png">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>images/favicon.png">
    
    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css?v=<?php echo filemtime(ROOT_PATH . 'assets/css/style.css'); ?>">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "<?php echo COMPANY_NAME; ?>",
        "description": "Premium-Jukeboxen für Ihr Event - mit Komplettservice",
        "url": "<?php echo BASE_URL; ?>",
        "telephone": "<?php echo COMPANY_PHONE; ?>",
        "email": "<?php echo COMPANY_EMAIL; ?>",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "<?php echo COMPANY_STREET; ?>",
            "addressLocality": "<?php echo COMPANY_CITY; ?>",
            "postalCode": "<?php echo COMPANY_ZIP; ?>",
            "addressCountry": "AT"
        },
        "areaServed": ["AT", "IT", "DE", "CH"],
        "serviceType": "Jukebox-Vermietung"
    }
    </script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <!-- Logo -->
                <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                    <img src="<?php echo ASSETS_URL; ?>images/RevibeLogo.svg" alt="<?php echo e(COMPANY_NAME); ?> Logo" class="logo-img">
                </a>
                
                <!-- Desktop Navigation -->
                <nav class="nav-desktop">
                    <a href="<?php echo BASE_URL; ?>index.php" class="nav-link <?php echo isActivePage('index'); ?>"><?php echo __('nav_home'); ?></a>
                    <a href="<?php echo BASE_URL; ?>catalog.php" class="nav-link <?php echo isActivePage('catalog'); ?>"><?php echo __('nav_catalog'); ?></a>
                    <a href="<?php echo BASE_URL; ?>process.php" class="nav-link <?php echo isActivePage('process'); ?>"><?php echo __('nav_process'); ?></a>
                    <a href="<?php echo BASE_URL; ?>contact.php" class="nav-link <?php echo isActivePage('contact'); ?>"><?php echo __('nav_contact'); ?></a>
                    <a href="<?php echo BASE_URL; ?>about.php" class="nav-link <?php echo isActivePage('about'); ?>"><?php echo __('nav_about'); ?></a>
                    <a href="<?php echo BASE_URL; ?>faq.php" class="nav-link <?php echo isActivePage('faq'); ?>"><?php echo __('nav_faq'); ?></a>
                </nav>
                
                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Language Switcher -->
                    <div class="lang-switcher">
                        <a href="<?php echo getLanguageSwitchUrl('de'); ?>" class="lang-link <?php echo getCurrentLanguage() === 'de' ? 'active' : ''; ?>" title="<?php echo __('lang_de'); ?>">DE</a>
                        <a href="<?php echo getLanguageSwitchUrl('en'); ?>" class="lang-link <?php echo getCurrentLanguage() === 'en' ? 'active' : ''; ?>" title="<?php echo __('lang_en'); ?>">EN</a>
                    </div>
                    
                    <!-- Inquiry Badge -->
                    <button class="inquiry-badge" title="<?php echo __('nav_inquiry_list'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 2v2M15 2v2M9 14h6M9 10h6M9 18h6M7 4h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                        </svg>
                        <span class="inquiry-badge-count" style="display: none;">0</span>
                    </button>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="menu-toggle" aria-label="Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation -->
    <nav class="nav-mobile">
        <a href="<?php echo BASE_URL; ?>index.php" class="nav-link"><?php echo __('nav_home'); ?></a>
        <a href="<?php echo BASE_URL; ?>catalog.php" class="nav-link"><?php echo __('nav_catalog'); ?></a>
        <a href="<?php echo BASE_URL; ?>process.php" class="nav-link"><?php echo __('nav_process'); ?></a>
        <a href="<?php echo BASE_URL; ?>contact.php" class="nav-link"><?php echo __('nav_contact'); ?></a>
        <a href="<?php echo BASE_URL; ?>about.php" class="nav-link"><?php echo __('nav_about'); ?></a>
        <a href="<?php echo BASE_URL; ?>faq.php" class="nav-link"><?php echo __('nav_faq'); ?></a>
    </nav>
    
    <!-- Inquiry Sidebar -->
    <div class="inquiry-sidebar-overlay"></div>
    <aside class="inquiry-sidebar">
        <div class="inquiry-sidebar-header">
            <h3><?php echo __('nav_inquiry_list'); ?></h3>
            <button class="inquiry-sidebar-close" aria-label="Close">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="inquiry-sidebar-body">
            <!-- Wird dynamisch gefüllt -->
        </div>
        <div class="inquiry-sidebar-footer">
            <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-primary btn-full">
                <?php echo getCurrentLanguage() === 'de' ? 'Direkt anfragen →' : 'Inquire now →'; ?>
            </a>
        </div>
    </aside>
