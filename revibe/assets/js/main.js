/**
 * Revibe - Haupt-JavaScript
 * Interaktivität, Animationen, Anfrageliste
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialisierung
    initHeader();
    initMobileMenu();
    initInquiryList();
    initFAQ();
    initScrollAnimations();
    initCookieNotice();
    initSmoothScroll();
    initGallery();
    initCatalogFilters();
    initDateSelector();
});

// ============================================
// HEADER SCROLL-EFFEKT
// ============================================
function initHeader() {
    const header = document.querySelector('.header');
    if (!header) return;
    
    let lastScroll = 0;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        // Header-Hintergrund bei Scroll
        if (currentScroll > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    }, { passive: true });
}

// ============================================
// MOBILES MENÜ
// ============================================
function initMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMobile = document.querySelector('.nav-mobile');
    
    if (!menuToggle || !navMobile) return;
    
    menuToggle.addEventListener('click', function() {
        this.classList.toggle('active');
        navMobile.classList.toggle('active');
        document.body.style.overflow = navMobile.classList.contains('active') ? 'hidden' : '';
    });
    
    // Menü schließen bei Klick auf Link
    const mobileLinks = navMobile.querySelectorAll('a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
            menuToggle.classList.remove('active');
            navMobile.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
}

// ============================================
// ANFRAGELISTE (WARENKORB)
// ============================================
function initInquiryList() {
    updateInquiryBadge();
    initInquiryButtons();
    initInquirySidebar();
}

// Anfrageliste aus Cookie laden
function getInquiryList() {
    const cookie = document.cookie.split('; ').find(row => row.startsWith('jukebox_inquiry='));
    if (cookie) {
        try {
            return JSON.parse(decodeURIComponent(cookie.split('=')[1]));
        } catch (e) {
            return [];
        }
    }
    return [];
}

// Anfrageliste speichern
function saveInquiryList(list) {
    const json = JSON.stringify(list);
    const expires = new Date();
    expires.setDate(expires.getDate() + 30);
    document.cookie = `jukebox_inquiry=${encodeURIComponent(json)}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
}

// Zur Anfrageliste hinzufügen (Server-Session + Cookie)
function addToInquiryList(jukeboxId) {
    const dates = getSelectedRentalDates();
    let body = 'id=' + encodeURIComponent(jukeboxId);
    if (dates.start && dates.end) {
        body += '&date_start=' + encodeURIComponent(dates.start) + '&date_end=' + encodeURIComponent(dates.end);
    }

    fetch('includes/ajax.php?action=cartAdd', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Cookie synchronisieren
                const list = getInquiryList();
                if (!list.includes(jukeboxId)) {
                    list.push(jukeboxId);
                    saveInquiryList(list);
                }
                updateInquiryBadge();
                updateInquirySidebar();
                showNotification('Zur Anfrageliste hinzugefügt');
                
                const button = document.querySelector(`[data-jukebox-id="${jukeboxId}"].inquiry-btn`);
                if (button) updateInquiryButton(button, true);
            } else {
                showNotification('Bereits in der Anfrageliste', 'info');
            }
        });
    return true;
}

// Aus Anfrageliste entfernen (Server-Session + Cookie)
function removeFromInquiryList(jukeboxId) {
    fetch('includes/ajax.php?action=cartRemove', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(jukeboxId)
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                let list = getInquiryList();
                list = list.filter(id => id !== jukeboxId);
                saveInquiryList(list);
                updateInquiryBadge();
                updateInquirySidebar();
                
                const button = document.querySelector(`[data-jukebox-id="${jukeboxId}"].inquiry-btn`);
                if (button) updateInquiryButton(button, false);
            }
        });
}

// Badge aktualisieren
function updateInquiryBadge() {
    fetch('includes/ajax.php?action=cartGet')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const badges = document.querySelectorAll('.inquiry-badge-count');
                badges.forEach(badge => {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'flex' : 'none';
                });
            }
        })
        .catch(() => {
            const list = getInquiryList();
            const badges = document.querySelectorAll('.inquiry-badge-count');
            badges.forEach(badge => {
                badge.textContent = list.length;
                badge.style.display = list.length > 0 ? 'flex' : 'none';
            });
        });
}

// Datums-Selektor initialisieren (Katalog / Detailseite)
function initDateSelector() {
    const wrapper = document.querySelector('.date-selector');
    if (!wrapper) return;

    const input = wrapper.querySelector('.date-selector-input');
    const startInput = wrapper.querySelector('.date-selector-start');
    const endInput = wrapper.querySelector('.date-selector-end');
    const statusEl = wrapper.querySelector('.date-selector-status');
    if (!input || !startInput || !endInput) return;

    const lang = document.documentElement.lang || 'de';
    const defaultDates = startInput.value ? (endInput.value && endInput.value !== startInput.value ? [startInput.value, endInput.value] : startInput.value) : null;

    if (typeof flatpickr !== 'undefined') {
        flatpickr(input, {
            mode: 'range',
            minDate: 'today',
            dateFormat: 'd.m.Y',
            locale: lang === 'de' ? 'de' : 'en',
            allowInput: true,
            defaultDate: defaultDates,
            onChange: function(selectedDates, dateStr) {
                const start = selectedDates[0] ? formatDateLocal(selectedDates[0]) : '';
                const end = selectedDates[1] ? formatDateLocal(selectedDates[1]) : '';
                startInput.value = start;
                endInput.value = end || start;

                if (start && end) {
                    if (statusEl) statusEl.textContent = window.catalogDateSavingText || 'Mietzeitraum wird gespeichert ...';
                    fetch('includes/ajax.php?action=setCartDates', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'date_start=' + encodeURIComponent(start) + '&date_end=' + encodeURIComponent(end)
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && statusEl) {
                                statusEl.textContent = window.catalogDateSavedText || 'Mietzeitraum gespeichert.';
                            }
                        })
                        .catch(() => {
                            if (statusEl) statusEl.textContent = 'Fehler beim Speichern.';
                        });
                }
            }
        });
    }
}

function formatDateLocal(date) {
    const d = date.getDate().toString().padStart(2, '0');
    const m = (date.getMonth() + 1).toString().padStart(2, '0');
    const y = date.getFullYear();
    return d + '.' + m + '.' + y;
}

// Aktuellen Mietzeitraum aus dem Datums-Selektor holen
function getSelectedRentalDates() {
    const wrapper = document.querySelector('.date-selector');
    if (!wrapper) return { start: '', end: '' };
    return {
        start: wrapper.querySelector('.date-selector-start')?.value || '',
        end: wrapper.querySelector('.date-selector-end')?.value || ''
    };
}

// Anfrage-Buttons initialisieren
function initInquiryButtons() {
    const buttons = document.querySelectorAll('.inquiry-btn');
    if (buttons.length === 0) return;

    // Initial-Status vom Server holen (Session-basiert, nicht Cookie)
    fetch('includes/ajax.php?action=cartGet')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const cartItems = data.items || [];
                buttons.forEach(button => {
                    const jukeboxId = button.dataset.jukeboxId;
                    updateInquiryButton(button, cartItems.includes(jukeboxId));
                });
            }
        });

    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const jukeboxId = this.dataset.jukeboxId;

            if (this.classList.contains('in-list')) {
                removeFromInquiryList(jukeboxId);
                updateInquiryButton(this, false);
                return;
            }

            const dates = getSelectedRentalDates();
            if (!dates.start || !dates.end) {
                showNotification(window.catalogSelectDatesText || 'Bitte wählen Sie zuerst den Mietzeitraum.', 'info');
                const dateInput = document.querySelector('.date-selector-input');
                if (dateInput) dateInput.focus();
                return;
            }

            // Verfügbarkeit prüfen
            const btn = this;
            btn.disabled = true;
            fetch('includes/ajax.php?action=checkAvailability', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'jukebox_id=' + encodeURIComponent(jukeboxId) + '&date_start=' + encodeURIComponent(dates.start) + '&date_end=' + encodeURIComponent(dates.end)
            })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success && data.available) {
                        addToInquiryList(jukeboxId);
                        updateInquiryButton(btn, true);
                    } else {
                        showNotification(window.catalogNotAvailableText || 'Diese Jukebox ist im gewählten Zeitraum nicht verfügbar.', 'info');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    showNotification('Fehler bei der Verfügbarkeitsprüfung.', 'info');
                });
        });
    });
}

// Button-Status aktualisieren
function updateInquiryButton(button, inList) {
    if (inList) {
        button.classList.add('in-list');
        button.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
            <span>${button.dataset.textRemove || 'Entfernen'}</span>
        `;
    } else {
        button.classList.remove('in-list');
        button.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            <span>${button.dataset.textAdd || 'Zur Anfrage'}</span>
        `;
    }
}

// Anfrage-Sidebar
function initInquirySidebar() {
    const toggle = document.querySelector('.inquiry-badge');
    const sidebar = document.querySelector('.inquiry-sidebar');
    const overlay = document.querySelector('.inquiry-sidebar-overlay');
    const closeBtn = document.querySelector('.inquiry-sidebar-close');
    
    if (!toggle || !sidebar) return;
    
    function open() {
        sidebar.classList.add('active');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        updateInquirySidebar();
    }
    
    function close() {
        sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        open();
    });
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (overlay) overlay.addEventListener('click', close);
}

// Sidebar-Inhalt aktualisieren
function updateInquirySidebar() {
    const container = document.querySelector('.inquiry-sidebar-body');
    const footer = document.querySelector('.inquiry-sidebar-footer');
    if (!container) return;
    
    // Direkt aus der Server-Session laden (robuster als Cookie)
    fetch('includes/ajax.php?action=getCartItems')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.items.length === 0) {
                    container.innerHTML = `
                        <div class="inquiry-empty">
                            <div class="inquiry-empty-icon">🎵</div>
                            <p>Ihre Anfrageliste ist leer.</p>
                            <a href="catalog.php" class="btn btn-primary mt-4">Jukeboxen entdecken</a>
                        </div>
                    `;
                    if (footer) footer.innerHTML = '';
                } else {
                    container.innerHTML = data.items.map(item => `
                        <div class="inquiry-item">
                            <div class="inquiry-item-image">
                                <img src="${item.image}" alt="${item.name}">
                            </div>
                            <div class="inquiry-item-info">
                                <div class="inquiry-item-title">${item.name}</div>
                                <div class="inquiry-item-price">${item.price}</div>
                            </div>
                            <button class="inquiry-item-remove" onclick="removeFromInquiryList('${item.id}')" title="Entfernen">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 6L6 18M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    `).join('');
                    
                    if (footer) {
                        footer.innerHTML = `
                            <a href="contact.php" class="btn btn-primary btn-full">Direkt anfragen →</a>
                        `;
                    }
                }
            }
        })
        .catch(() => {
            container.innerHTML = '<p class="text-center">Fehler beim Laden der Daten.</p>';
        });
}

// ============================================
// FAQ AKKORDEON
// ============================================
function initFAQ() {
    const items = document.querySelectorAll('.faq-item');
    
    items.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', function() {
            const isActive = item.classList.contains('active');
            
            // Alle schließen
            items.forEach(i => i.classList.remove('active'));
            
            // Aktuelles öffnen wenn es geschlossen war
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
}

// ============================================
// SCROLL-ANIMATIONEN
// ============================================
function initScrollAnimations() {
    const reveals = document.querySelectorAll('.reveal');
    
    if (reveals.length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    reveals.forEach(el => observer.observe(el));
}

// ============================================
// COOKIE-HINWEIS
// ============================================
function initCookieNotice() {
    const notice = document.querySelector('.cookie-notice');
    if (!notice) return;
    
    // Prüfen ob bereits akzeptiert
    if (document.cookie.includes('cookies_accepted=true')) {
        return;
    }
    
    // Anzeigen
    setTimeout(() => {
        notice.classList.add('active');
    }, 1000);
    
    // Akzeptieren-Button
    const acceptBtn = notice.querySelector('.cookie-accept');
    if (acceptBtn) {
        acceptBtn.addEventListener('click', function() {
            const expires = new Date();
            expires.setFullYear(expires.getFullYear() + 1);
            document.cookie = `cookies_accepted=true; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
            notice.classList.remove('active');
        });
    }
}

// ============================================
// SMOOTH SCROLL
// ============================================
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ============================================
// BILDGALERIE
// ============================================
function initGallery() {
    const mainImage = document.querySelector('.detail-main-image img');
    const thumbnails = document.querySelectorAll('.detail-thumbnail');
    
    if (!mainImage || thumbnails.length === 0) return;
    
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const img = this.querySelector('img');
            mainImage.src = img.src;
            mainImage.alt = img.alt;
            
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// ============================================
// NOTIFICATIONS
// ============================================
function showNotification(message, type = 'success') {
    // Bestehende Notification entfernen
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    // Neue erstellen
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
    `;
    
    // Styles
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: ${type === 'success' ? '#22c55e' : '#3b82f6'};
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        transition: transform 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Animation
    requestAnimationFrame(() => {
        notification.style.transform = 'translateX(-50%) translateY(0)';
    });
    
    // Entfernen
    setTimeout(() => {
        notification.style.transform = 'translateX(-50%) translateY(100px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================
// FORMULAR-VALIDIERUNG
// ============================================
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        const value = field.value.trim();
        const formGroup = field.closest('.form-group');
        
        // Bestehende Fehler entfernen
        const existingError = formGroup?.querySelector('.form-error');
        if (existingError) existingError.remove();
        field.classList.remove('error');
        
        if (!value) {
            isValid = false;
            field.classList.add('error');
            
            if (formGroup) {
                const error = document.createElement('span');
                error.className = 'form-error';
                error.textContent = 'Dieses Feld ist erforderlich.';
                formGroup.appendChild(error);
            }
        }
        
        // E-Mail-Validierung
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                field.classList.add('error');
                
                if (formGroup) {
                    const error = document.createElement('span');
                    error.className = 'form-error';
                    error.textContent = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
                    formGroup.appendChild(error);
                }
            }
        }
    });
    
    return isValid;
}

// Formulare initialisieren
document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!validateForm(this)) {
            e.preventDefault();
        }
    });
});

// ============================================
// KATALOG-FILTER
// ============================================
function initCatalogFilters() {
    const form = document.getElementById('catalog-filter-form');
    if (!form) return;

    // Dropdown-Filter-Pills
    const pills = form.querySelectorAll('.filter-pill[data-filter]');
    
    pills.forEach(pill => {
        const toggle = pill.querySelector('.filter-pill-toggle');
        if (!toggle) return;

        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Andere Dropdowns schließen
            pills.forEach(otherPill => {
                if (otherPill !== pill) {
                    otherPill.classList.remove('is-open');
                }
            });

            // Aktuelles umschalten
            pill.classList.toggle('is-open');
        });
    });

    // Klick außerhalb schließt alle Dropdowns
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.filter-pill')) {
            pills.forEach(pill => pill.classList.remove('is-open'));
        }
    });

    // Dropdown-Inhalte nicht schließen wenn innerhalb geklickt wird
    form.querySelectorAll('.filter-pill-dropdown').forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            if (!e.target.closest('button[type="submit"]')) {
                e.stopPropagation();
            }
        });
    });

    // Neu-im-Sortiment Toggle: Formular direkt absenden
    const newArrivalToggle = form.querySelector('input[name="new_arrival"]');
    if (newArrivalToggle) {
        newArrivalToggle.addEventListener('change', function() {
            form.submit();
        });
    }

    // Preisfilter validieren
    const priceApplyBtn = form.querySelector('.filter-pill[data-filter="price"] button[type="submit"]');
    const priceMinInput = form.querySelector('input[name="price_min"]');
    const priceMaxInput = form.querySelector('input[name="price_max"]');

    if (priceApplyBtn && priceMinInput && priceMaxInput) {
        priceApplyBtn.addEventListener('click', function(e) {
            const min = parseFloat(priceMinInput.value);
            const max = parseFloat(priceMaxInput.value);

            if (!isNaN(min) && !isNaN(max) && min > max) {
                e.preventDefault();
                priceMaxInput.classList.add('error');
                showNotification('Der Maximalpreis muss höher sein als der Minimalpreis.', 'info');
            } else {
                priceMaxInput.classList.remove('error');
            }
        });

        // Fehler-Status bei Eingabe zurücksetzen
        [priceMinInput, priceMaxInput].forEach(input => {
            input.addEventListener('input', function() {
                priceMaxInput.classList.remove('error');
            });
        });
    }

    // Aktive Filter-Chips entfernen
    const chips = form.querySelectorAll('.filter-chip');
    chips.forEach(chip => {
        chip.addEventListener('click', function() {
            const type = this.dataset.type;
            const value = this.dataset.value;

            if (type === 'price') {
                // Preisfelder leeren
                const minInput = form.querySelector('input[name="price_min"]');
                const maxInput = form.querySelector('input[name="price_max"]');
                if (minInput) minInput.value = '';
                if (maxInput) maxInput.value = '';
            } else if (type === 'new_arrival') {
                const input = form.querySelector('input[name="new_arrival"]');
                if (input) input.checked = false;
            } else {
                // Checkbox deaktivieren
                const inputs = form.querySelectorAll(`input[name="${type}[]"]`);
                inputs.forEach(input => {
                    if (input.value === value) {
                        input.checked = false;
                    }
                });
            }

            form.submit();
        });
    });
}

// ============================================
// LAZY LOADING FÜR BILDER
// ============================================
if ('IntersectionObserver' in window) {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
}
