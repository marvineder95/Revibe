<?php
/**
 * Footer-Partial
 * Wird auf allen Seiten eingebunden
 */
?>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand -->
                <div class="footer-brand">
                    <a href="<?php echo BASE_URL; ?>" class="logo footer-logo">
                        <img src="<?php echo ASSETS_URL; ?>images/RevibeLogo.svg" alt="<?php echo e(COMPANY_NAME); ?> Logo" class="logo-img">
                    </a>
                    <p><?php echo __('footer_tagline'); ?></p>
                </div>
                
                <!-- Navigation -->
                <div>
                    <h4 class="footer-title">Navigation</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_URL; ?>"><?php echo __('nav_home'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>about.php"><?php echo __('nav_about'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>catalog.php"><?php echo __('nav_catalog'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>process.php"><?php echo __('nav_process'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>faq.php"><?php echo __('nav_faq'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>contact.php"><?php echo __('nav_contact'); ?></a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div class="footer-contact">
                    <h4 class="footer-title"><?php echo __('footer_contact_title'); ?></h4>
                    <p><?php echo COMPANY_NAME; ?></p>
                    <p><?php echo COMPANY_STREET; ?></p>
                    <p><?php echo COMPANY_ZIP; ?> <?php echo COMPANY_CITY; ?></p>
                    <p><?php echo COMPANY_COUNTRY; ?></p>
                    <p style="margin-top: var(--space-3);">
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', COMPANY_PHONE); ?>"><?php echo COMPANY_PHONE; ?></a>
                    </p>
                    <p>
                        <a href="mailto:<?php echo COMPANY_EMAIL; ?>"><?php echo COMPANY_EMAIL; ?></a>
                    </p>
                </div>
                
                <!-- Social Media -->
                <div>
                    <h4 class="footer-title">Social Media</h4>
                    <div class="footer-social">
                        <a href="https://instagram.com" target="_blank" rel="noopener" class="social-link" title="Instagram">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        <a href="https://facebook.com" target="_blank" rel="noopener" class="social-link" title="Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="https://linkedin.com" target="_blank" rel="noopener" class="social-link" title="LinkedIn">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                    <p style="font-size: var(--text-xs); color: var(--color-gray-500); margin-top: var(--space-2);">Folgen Sie uns für Updates</p>
                </div>
                
                <!-- Legal -->
                <div>
                    <h4 class="footer-title"><?php echo __('footer_legal_title'); ?></h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_URL; ?>impressum.php"><?php echo __('footer_imprint'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>datenschutz.php"><?php echo __('footer_privacy'); ?></a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom -->
            <div class="footer-bottom">
                <p class="footer-copyright">
                    <?php echo __('footer_copyright', ['year' => date('Y')]); ?>
                </p>
                <div class="footer-legal">
                    <a href="<?php echo BASE_URL; ?>impressum.php"><?php echo __('footer_imprint'); ?></a>
                    <a href="<?php echo BASE_URL; ?>datenschutz.php"><?php echo __('footer_privacy'); ?></a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Cookie Notice -->
    <div class="cookie-notice">
        <div class="cookie-notice-content">
            <p><?php echo __('cookie_text'); ?></p>
            <button class="btn btn-primary btn-sm cookie-accept"><?php echo __('cookie_accept'); ?></button>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="<?php echo ASSETS_URL; ?>js/main.js?v=<?php echo filemtime(ROOT_PATH . 'assets/js/main.js'); ?>"></script>
</body>
</html>
