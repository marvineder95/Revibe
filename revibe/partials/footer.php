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
                        <img src="<?php echo ASSETS_URL; ?>images/RevibeLogoPdf.png" alt="<?php echo e(COMPANY_NAME); ?> Logo" class="logo-img">
                    </a>
                    <p><?php echo __('footer_tagline'); ?></p>
                    
                    <ul class="footer-features">
                        <li>
                            <span class="footer-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                            </span>
                            <?php echo __('footer_feature_1'); ?>
                        </li>
                        <li>
                            <span class="footer-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                            </span>
                            <?php echo __('footer_feature_2'); ?>
                        </li>
                        <li>
                            <span class="footer-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            </span>
                            <?php echo __('footer_feature_3'); ?>
                        </li>
                        <li>
                            <span class="footer-feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </span>
                            <?php echo __('footer_feature_4'); ?>
                        </li>
                    </ul>
                </div>
                
                <!-- Navigation -->
                <div>
                    <h4 class="footer-title"><?php echo __('footer_navigation_title'); ?></h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_URL; ?>"><?php echo __('nav_home'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>catalog.php"><?php echo __('nav_catalog'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>process.php"><?php echo __('nav_process'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>about.php"><?php echo __('nav_about'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>reviews.php"><?php echo __('nav_reviews'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>faq.php"><?php echo __('nav_faq'); ?></a></li>
                        <li><a href="<?php echo BASE_URL; ?>contact.php"><?php echo __('nav_contact'); ?></a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div class="footer-contact">
                    <h4 class="footer-title"><?php echo __('footer_contact_title'); ?></h4>
                    <ul class="footer-contact-list">
                        <li>
                            <span class="footer-contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </span>
                            <span>
                                <?php echo COMPANY_NAME; ?><br>
                                <?php echo COMPANY_STREET; ?><br>
                                <?php echo COMPANY_ZIP; ?> <?php echo COMPANY_CITY; ?><br>
                                <?php echo COMPANY_COUNTRY; ?>
                            </span>
                        </li>
                        <li>
                            <span class="footer-contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.8 12.8 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.8 12.8 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </span>
                            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', COMPANY_PHONE); ?>"><?php echo COMPANY_PHONE; ?></a>
                        </li>
                        <li>
                            <span class="footer-contact-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </span>
                            <a href="mailto:<?php echo COMPANY_EMAIL; ?>"><?php echo COMPANY_EMAIL; ?></a>
                        </li>
                    </ul>
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
                    <p class="footer-social-text"><?php echo __('footer_social_text'); ?></p>
                    
                    <a href="https://www.point4studios.at" target="_blank" rel="noopener" class="footer-credit" title="point4studios">
                        <p class="footer-credit-text"><?php echo __('footer_credit_text'); ?></p>
                        <img src="<?php echo ASSETS_URL; ?>images/point4-lockup-light.png" alt="point4studios" class="footer-credit-logo">
                    </a>
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
