<?php
/**
 * Admin: Angebot manuell erstellen
 *
 * Ermöglicht es dem Admin, ein Angebot direkt zu erstellen – z. B. nach
 * einem Telefonat oder persönlichen Gespräch mit dem Kunden.
 */
require_once '../config/config.php';
require_once INCLUDES_PATH . 'pdf.php';

setSecurityHeaders();

// Login-Check
if (!isAdminLoggedIn()) {
    redirect('/admin/login.php');
}

$jukeboxes = getAllJukeboxes();
$lang = getCurrentLanguage();
$pageTitle = __('admin_create_offer_manual_title');

$errors = [];
$formSuccess = false;

// Formularfelder vorbereiten
$firstname = '';
$lastname = '';
$company = '';
$email = '';
$phone = '';
$message = '';
$dateStart = '';
$dateEnd = '';
$eventStreet = '';
$eventHousenumber = '';
$eventZip = '';
$eventCity = '';
$eventCountry = '';
$selectedJukeboxes = [];
$customItems = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = __('admin_error_csrf');
    }

    $firstname = sanitizeInput($_POST['firstname'] ?? '');
    $lastname = sanitizeInput($_POST['lastname'] ?? '');
    $company = sanitizeInput($_POST['company'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizePhone($_POST['phone'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');

    $dateStart = sanitizeInput($_POST['date_start'] ?? '');
    $dateEnd = sanitizeInput($_POST['date_end'] ?? '');

    $eventStreet = sanitizeInput($_POST['event_street'] ?? '');
    $eventHousenumber = sanitizeInput($_POST['event_housenumber'] ?? '');
    $eventZip = sanitizeInput($_POST['event_zip'] ?? '');
    $eventCity = sanitizeInput($_POST['event_city'] ?? '');
    $eventCountrySelect = sanitizeInput($_POST['event_country_select'] ?? '');
    $eventCountryOther = sanitizeInput($_POST['event_country_other'] ?? '');
    $eventCountry = $eventCountrySelect === 'other' ? $eventCountryOther : $eventCountrySelect;

    $selectedJukeboxes = $_POST['jukebox_ids'] ?? [];
    if (!is_array($selectedJukeboxes)) {
        $selectedJukeboxes = [];
    }
    $selectedJukeboxes = array_filter($selectedJukeboxes);

    // Validierung
    if (empty($firstname)) $errors[] = __('form_firstname');
    if (empty($lastname)) $errors[] = __('form_lastname');
    if (empty($email) || !isValidEmail($email) || preg_match('/[\r\n]/', $email)) $errors[] = __('form_email');
    if (empty($phone)) $errors[] = __('form_phone');

    if (empty($dateStart) || empty($dateEnd)) {
        $errors[] = __('admin_error_create_offer_dates');
    } else {
        $startTs = strtotime($dateStart);
        $endTs = strtotime($dateEnd);
        $todayTs = strtotime(date('Y-m-d'));
        if (!$startTs || !$endTs) {
            $errors[] = __('admin_error_create_offer_dates');
        } elseif ($startTs < $todayTs || $endTs < $todayTs) {
            $errors[] = __('admin_error_create_offer_past_dates');
        } elseif ($endTs < $startTs) {
            $errors[] = __('admin_error_create_offer_date_order');
        }
    }

    if (empty($selectedJukeboxes)) {
        $errors[] = __('admin_error_create_offer_no_jukebox');
    }

    if (empty($errors)) {
        // Veranstaltungsadresse zusammensetzen
        $eventAddress = trim($eventStreet . ' ' . $eventHousenumber);
        if ($eventZip || $eventCity) {
            $eventAddress .= "\n" . trim($eventZip . ' ' . $eventCity);
        }
        if ($eventCountry) {
            $eventAddress .= ($eventAddress ? ', ' : '') . $eventCountry;
        }

        // Mietdauer berechnen
        $startDate = new DateTime($dateStart);
        $endDate = new DateTime($dateEnd);
        $durationDays = max(1, (int)$startDate->diff($endDate)->days + 1);

        // Zusätzliche Positionen aus Formular
        $positionNames = $_POST['position_name'] ?? [];
        $positionUnits = $_POST['position_unit'] ?? [];
        $positionQtys = $_POST['position_qty'] ?? [];
        if (!is_array($positionNames)) $positionNames = [];
        if (!is_array($positionUnits)) $positionUnits = [];
        if (!is_array($positionQtys)) $positionQtys = [];
        $customItems = [];
        foreach ($positionNames as $i => $rawName) {
            $name = sanitizeInput($rawName);
            $unit = (float)str_replace(',', '.', sanitizeInput($positionUnits[$i] ?? ''));
            $qty = (float)str_replace(',', '.', sanitizeInput($positionQtys[$i] ?? ''));
            if ($name === '' && $unit <= 0) {
                continue; // leere Zeile überspringen
            }
            $customItems[] = [
                'name' => $name,
                'unit_price' => $unit,
                'quantity' => $qty
            ];
        }

        // Ausgewählte Jukeboxen laden
        $cartItems = [];
        foreach ($selectedJukeboxes as $jbId) {
            $jb = getJukeboxById($jbId);
            if ($jb) {
                $cartItems[] = $jb;
            }
        }

        if (empty($cartItems)) {
            $errors[] = __('admin_error_create_offer_no_jukebox');
        } else {
            // Preisberechnung (Transport/Diverses über zusätzliche Positionen)
            $transportCosts = 0;
            $transportDistance = 0;
            $transportDuration = 0;
            $transportError = '';

            $pricing = calculatePricing($cartItems, $durationDays, '', $transportCosts, $customItems);

            // Anfrage erstellen
            $inquiryData = [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'company' => $company,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'duration_days' => $durationDays,
                'event_address' => $eventAddress,
                'pricing_json' => $pricing,
                'transport_distance_km' => $transportDistance,
                'transport_duration_min' => $transportDuration,
                'transport_costs' => $transportCosts,
                'transport_error' => $transportError
            ];

            $inquiry = createInquiry($inquiryData);

            if (!$inquiry) {
                $errors[] = __('admin_error_create_offer_inquiry');
            } else {
                // Reservierungen für alle ausgewählten Jukeboxen anlegen
                foreach ($cartItems as $jb) {
                    $rental = createRental([
                        'jukebox_id' => $jb['id'],
                        'inquiry_id' => $inquiry['id'],
                        'date_start' => $inquiry['date_start'],
                        'date_end' => $inquiry['date_end'],
                        'status' => 'reserved'
                    ]);
                    if (!$rental) {
                        error_log('Manuelle Angebotserstellung: Reservierung konnte nicht angelegt werden für Jukebox ' . $jb['id'] . ' / Inquiry ' . $inquiry['id']);
                    }
                }

                // Angebot erstellen, PDF generieren und per E-Mail versenden
                $offer = buildAndSendOffer($inquiry['id'], 3);

                if ($offer) {
                    redirect('/admin/offers.php?success=offer_created');
                } else {
                    $errors[] = __('admin_error_offer_create');
                }
            }
        }
    }
}

include PARTIALS_PATH . 'admin-header.php';
?>

            <?php if ($formSuccess): ?>
            <div style="padding: var(--space-4); background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color: #22c55e; margin-bottom: 0;"><?php echo e(__('admin_success_offer_created')); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div style="padding: var(--space-4); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); margin-bottom: var(--space-6);">
                <p style="color: #ef4444; margin-bottom: var(--space-2);"><?php echo e(__('admin_error_create_offer')); ?>:</p>
                <ul style="margin-bottom: 0; color: #ef4444;">
                    <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 style="font-size: var(--text-xl); margin-bottom: 0;"><?php echo __('admin_create_offer_manual_heading'); ?></h2>
                    <a href="/admin/offers.php" class="btn btn-dark btn-sm"><?php echo __('admin_back_to_offers'); ?></a>
                </div>
                <div class="admin-card-body">
                    <form method="POST" action="" data-validate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                        <!-- Kundendaten -->
                        <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4); margin-top: 0;"><?php echo __('admin_customer_data'); ?></h3>
                        <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-6);">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('form_firstname'); ?> *</label>
                                <input type="text" name="firstname" class="form-input" value="<?php echo e($firstname); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('form_lastname'); ?> *</label>
                                <input type="text" name="lastname" class="form-input" value="<?php echo e($lastname); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('form_company'); ?></label>
                                <input type="text" name="company" class="form-input" value="<?php echo e($company); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('form_email'); ?></label>
                                <input type="email" name="email" class="form-input" value="<?php echo e($email); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('form_phone'); ?></label>
                                <input type="tel" name="phone" class="form-input" value="<?php echo e($phone); ?>" required>
                            </div>
                        </div>

                        <!-- Mietzeitraum -->
                        <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4); margin-top: var(--space-8);"><?php echo __('admin_rental_dates'); ?></h3>
                        <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-6);">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('admin_date_start'); ?> *</label>
                                <input type="date" name="date_start" class="form-input" value="<?php echo e($dateStart); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('admin_date_end'); ?> *</label>
                                <input type="date" name="date_end" class="form-input" value="<?php echo e($dateEnd); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <!-- Veranstaltungsadresse -->
                        <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4); margin-top: var(--space-8);"><?php echo __('admin_event_address'); ?></h3>
                        <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-6);">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('admin_event_street'); ?></label>
                                <input type="text" name="event_street" class="form-input" value="<?php echo e($eventStreet); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('admin_event_housenumber'); ?></label>
                                <input type="text" name="event_housenumber" class="form-input" value="<?php echo e($eventHousenumber); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('admin_event_zip'); ?></label>
                                <input type="text" name="event_zip" class="form-input" value="<?php echo e($eventZip); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('admin_event_city'); ?></label>
                                <input type="text" name="event_city" class="form-input" value="<?php echo e($eventCity); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label"><?php echo __('admin_event_country'); ?></label>
                                <?php
                                $predefinedCountries = ['Österreich', 'Deutschland', 'Tschechien', 'Slowakei', 'Ungarn', 'Slowenien', 'Italien', 'Schweiz', 'Liechtenstein'];
                                $isOther = $eventCountry !== '' && !in_array($eventCountry, $predefinedCountries, true);
                                ?>
                                <select name="event_country_select" class="form-input" id="eventCountrySelect">
                                    <option value="Österreich" <?php echo $eventCountry === 'Österreich' ? 'selected' : ''; ?>>Österreich</option>
                                    <?php foreach (array_diff($predefinedCountries, ['Österreich']) as $c): ?>
                                    <option value="<?php echo e($c); ?>" <?php echo $eventCountry === $c ? 'selected' : ''; ?>><?php echo e($c); ?></option>
                                    <?php endforeach; ?>
                                    <option value="other" <?php echo $isOther ? 'selected' : ''; ?>><?php echo e(__('admin_event_country_other')); ?></option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; display: <?php echo $isOther ? 'block' : 'none'; ?>;" id="eventCountryOtherWrapper">
                                <label class="form-label"><?php echo __('admin_event_country_other_label'); ?></label>
                                <input type="text" name="event_country_other" class="form-input" id="eventCountryOther" value="<?php echo e($isOther ? $eventCountry : ''); ?>">
                            </div>
                        </div>

                        <!-- Jukebox-Auswahl -->
                        <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4); margin-top: var(--space-8);"><?php echo __('admin_select_jukeboxes'); ?></h3>
                        <?php if (!empty($jukeboxes)): ?>
                        <div style="overflow-x: auto; margin-bottom: var(--space-6);">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th><?php echo __('admin_jukebox_name'); ?></th>
                                        <th><?php echo __('admin_jukebox_price_day'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jukeboxes as $jb): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="jukebox_ids[]" value="<?php echo e($jb['id']); ?>" <?php echo in_array($jb['id'], $selectedJukeboxes) ? 'checked' : ''; ?>>
                                        </td>
                                        <td><?php echo e(getLocalizedValue($jb, 'name')); ?></td>
                                        <td><?php echo formatMoney($jb['price_day']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p style="color: var(--color-gray-500); margin-bottom: var(--space-6);"><?php echo __('admin_no_jukeboxes'); ?></p>
                        <?php endif; ?>

                        <!-- Zusätzliche Positionen -->
                        <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4); margin-top: var(--space-8);"><?php echo __('admin_positions_title'); ?></h3>
                        <div id="customPositions" style="margin-bottom: var(--space-4);">
                            <?php
                            // Bestehende Positionen (z. B. nach Validierungsfehler) erneut rendern
                            $positionRows = !empty($customItems) ? $customItems : [];
                            foreach ($positionRows as $pos):
                            ?>
                            <div class="custom-position-row" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: var(--space-3); margin-bottom: var(--space-3); align-items: center;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <input type="text" name="position_name[]" class="form-input" value="<?php echo e($pos['name']); ?>" placeholder="<?php echo e(__('admin_position_text')); ?>">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <input type="text" name="position_unit[]" class="form-input" value="<?php echo e($pos['unit_price']); ?>" placeholder="<?php echo e(__('admin_position_unit')); ?>">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <input type="text" name="position_qty[]" class="form-input" value="<?php echo e($pos['quantity']); ?>" placeholder="<?php echo e(__('admin_position_quantity')); ?>">
                                </div>
                                <button type="button" class="btn btn-danger btn-sm btn-remove-position" title="<?php echo e(__('admin_position_remove')); ?>" style="background: transparent; border: 1px solid #ef4444; color: #ef4444;">&times;</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-dark btn-sm" id="btnAddPosition" style="margin-bottom: var(--space-6);">+ <?php echo __('admin_position_add'); ?></button>

                        <!-- Nachricht -->
                        <div class="form-group" style="margin-bottom: var(--space-6);">
                            <label class="form-label"><?php echo __('form_message'); ?></label>
                            <textarea name="message" class="form-input" rows="4"><?php echo e($message); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg"><?php echo __('admin_create_offer_submit'); ?></button>
                    </form>
                </div>
            </div>

            <script>
                (function() {
                    var countrySelect = document.getElementById('eventCountrySelect');
                    var otherWrapper = document.getElementById('eventCountryOtherWrapper');
                    var otherInput = document.getElementById('eventCountryOther');

                    function toggleOtherCountry() {
                        if (!countrySelect || !otherWrapper) return;
                        if (countrySelect.value === 'other') {
                            otherWrapper.style.display = 'block';
                            if (otherInput) otherInput.required = true;
                        } else {
                            otherWrapper.style.display = 'none';
                            if (otherInput) {
                                otherInput.required = false;
                                otherInput.value = '';
                            }
                        }
                    }

                    if (countrySelect) {
                        countrySelect.addEventListener('change', toggleOtherCountry);
                        toggleOtherCountry();
                    }

                    // Zusätzliche Positionen: Zeilen hinzufügen/entfernen
                    var positionsContainer = document.getElementById('customPositions');
                    var btnAddPosition = document.getElementById('btnAddPosition');

                    function createPositionRow() {
                        var row = document.createElement('div');
                        row.className = 'custom-position-row';
                        row.style.display = 'grid';
                        row.style.gridTemplateColumns = '2fr 1fr 1fr auto';
                        row.style.gap = 'var(--space-3)';
                        row.style.marginBottom = 'var(--space-3)';
                        row.style.alignItems = 'center';

                        var fieldNames = ['position_name[]', 'position_unit[]', 'position_qty[]'];
                        var placeholders = [
                            <?php echo json_encode(__('admin_position_text')); ?>,
                            <?php echo json_encode(__('admin_position_unit')); ?>,
                            <?php echo json_encode(__('admin_position_quantity')); ?>
                        ];

                        fieldNames.forEach(function(fieldName, idx) {
                            var group = document.createElement('div');
                            group.className = 'form-group';
                            group.style.marginBottom = '0';
                            var input = document.createElement('input');
                            input.type = 'text';
                            input.name = fieldName;
                            input.className = 'form-input';
                            input.placeholder = placeholders[idx];
                            group.appendChild(input);
                            row.appendChild(group);
                        });

                        var removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-danger btn-sm btn-remove-position';
                        removeBtn.title = <?php echo json_encode(__('admin_position_remove')); ?>;
                        removeBtn.style.background = 'transparent';
                        removeBtn.style.border = '1px solid #ef4444';
                        removeBtn.style.color = '#ef4444';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.addEventListener('click', function() {
                            row.remove();
                        });
                        row.appendChild(removeBtn);

                        return row;
                    }

                    if (btnAddPosition && positionsContainer) {
                        btnAddPosition.addEventListener('click', function() {
                            positionsContainer.appendChild(createPositionRow());
                        });

                        // Entfernen-Buttons für serverseitig gerenderte Zeilen
                        positionsContainer.addEventListener('click', function(e) {
                            var btn = e.target.closest('.btn-remove-position');
                            if (btn) {
                                btn.closest('.custom-position-row').remove();
                            }
                        });
                    }
                })();
            </script>

<?php include PARTIALS_PATH . 'admin-footer.php'; ?>
