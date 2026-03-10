<?php
/*
Plugin Name: Ajánlatkérés Pro
Description: Ajánlatkérő űrlap – szép admin UI + HTML email. Használat: [ajanlatkeres] és [ajanlat_lista]
Version: 1.38
Author: András
*/
if (!defined('ABSPATH'))
    exit;

global $wpdb;
$table = $wpdb->prefix . 'ajanlatkeres';

/**
 * Adatbázis tábla létrehozása aktiváláskor
 */
register_activation_hook(__FILE__, function () use ($wpdb, $table) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $sql = "CREATE TABLE $table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        email VARCHAR(255),
        phone VARCHAR(100),
        arrival DATE,
        rooms INT,
        nights INT,
        adults INT,
        children INT,
        package VARCHAR(255),
        note TEXT,
        status VARCHAR(20) DEFAULT 'uj',
        created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) {$wpdb->get_charset_collate()};";
    dbDelta($sql);
    if (!get_option('ak_admin_emails')) {
        update_option('ak_admin_emails', get_option('admin_email'));
    }
});

/**
 * Frontend stílusok és scriptek betöltése
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('ak-form-css', plugin_dir_url(__FILE__) . 'assets/css/form.css', [], '1.0.1');
    wp_enqueue_script('ak-form-js', plugin_dir_url(__FILE__) . 'assets/js/form.js', ['jquery'], '1.0.1', true);

    $script_data = [
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ak_submit_nonce')
    ];

    // Csak adminisztrátoroknak adjuk át az admin nonce-okat
    if (current_user_can('manage_options')) {
        $script_data['admin_nonce'] = wp_create_nonce('ak_admin_nonce');
        $script_data['status_nonce'] = wp_create_nonce('ak_status_nonce');
    }

    wp_localize_script('ak-form-js', 'akAjax', $script_data);
});

/**
 * Admin stílusok betöltése
 */
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style('ak-admin-css', plugin_dir_url(__FILE__) . 'admin/css/admin.css');
    wp_enqueue_script('ak-admin-js', plugin_dir_url(__FILE__) . 'admin/js/admin.js', ['jquery'], '1.0', true);
    wp_localize_script('ak-admin-js', 'akAdminAjax', [
        'nonce' => wp_create_nonce('ak_status_nonce')
    ]);
});

// Admin felületek behúzása
require_once plugin_dir_path(__FILE__) . 'admin/admin-list.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-settings.php';

/**
 * Piktogram meghatározása
 */
function ak_get_pkg_icon($pkg)
{
    $p = mb_strtolower($pkg);
    if (strpos($p, 'esküvő') !== false || strpos($p, 'wedding') !== false)
        return '💍';
    if (strpos($p, 'lovaglás') !== false)
        return '🐎';
    if (strpos($p, 'fittnesz') !== false || strpos($p, 'fitness') !== false)
        return '🏋️';
    if (strpos($p, 'gerecse') !== false || strpos($p, 'pihenés') !== false)
        return '🌳';
    if (strpos($p, 'romantikus') !== false || strpos($p, 'romantika') !== false)
        return '❤️';
    if (strpos($p, 'napfénnyel') !== false || strpos($p, 'napsütés') !== false)
        return '☀️';
    return '📦';
}

function ak_get_admin_emails()
{
    $opt = get_option('ak_admin_emails');
    return $opt ? array_map('trim', explode(',', $opt)) : [get_option('admin_email')];
}

function ak_get_active_packages()
{
    $opt = get_option('ak_package_list');
    if ($opt) {
        return array_filter(array_map('trim', explode("\n", $opt)));
    }
    // Fallback defaults
    return [
        'A lovaglás szerelmeseinek',
        'Fittnesz hétvége',
        'Pihenés a Gerecse lábánál',
        'Romantikus hétvége',
        'Töltődjön fel napfénnyel',
        'Esküvői csomagajánlat'
    ];
}

/**
 * SHORTCODE 1: Ajánlatkérő űrlap [ajanlatkeres]
 */
add_shortcode('ajanlatkeres', 'ak_shortcode_form');
function ak_shortcode_form($atts)
{
    ob_start();
    $today = date('Y-m-d');
    $n1 = rand(1, 9);
    $n2 = rand(1, 7);
    $sum = $n1 + $n2;
    $captcha_token = md5($sum . 'ak_salt');
    ?>
    <div class="ak-wrapper">
        <form id="ajanlatForm" class="ak-card">
            <h3>Foglalási Ajánlatkérés</h3>
            <label>Az Ön Neve *</label>
            <input type="text" name="name" required placeholder="Adja meg teljes nevét">
            <div class="ak-row">
                <div class="ak-field-group"><label>E-mail *</label><input type="email" name="email" required
                        placeholder="pelda@email.hu"></div>
                <div class="ak-field-group"><label>Telefon</label><input type="tel" name="phone" placeholder="+36..."></div>
            </div>
            <label>Érkezés Várható Dátuma *</label>
            <input type="date" name="arrival" min="<?php echo $today; ?>" required>
            <div class="ak-row">
                <div class="ak-mini"><label>Szobák száma</label>
                    <div class="ak-counter"><button type="button" class="minus">-</button><input type="text" name="rooms"
                            value="1" readonly><button type="button" class="plus">+</button></div>
                </div>
                <div class="ak-mini"><label>Éjszakák száma</label>
                    <div class="ak-counter"><button type="button" class="minus">-</button><input type="text" name="nights"
                            value="2" readonly><button type="button" class="plus">+</button></div>
                </div>
                <div class="ak-mini"><label>Felnőtt</label>
                    <div class="ak-counter"><button type="button" class="minus">-</button><input type="text" name="adults"
                            value="2" readonly><button type="button" class="plus">+</button></div>
                </div>
                <div class="ak-mini"><label>Gyermek</label>
                    <div class="ak-counter"><button type="button" class="minus">-</button><input type="text" name="children"
                            value="0" readonly><button type="button" class="plus">+</button></div>
                </div>
            </div>
            <label>Választott Csomagajánlat</label>
            <select name="package">
                <option value="">-- Kérjük válasszon --</option>
                <?php foreach (ak_get_active_packages() as $pkg): ?>
                    <option value="<?php echo esc_attr($pkg); ?>"><?php echo esc_html($pkg); ?></option>
                <?php endforeach; ?>
            </select>
            <label>Megjegyzés</label>
            <textarea name="note" rows="4"></textarea>
            <div class="ak-captcha-row" style="margin-top:20px;">
                <label>Biztonsági ellenőrzés: Mennyi <?php echo "$n1 + $n2"; ?>? *</label>
                <input type="text" name="captcha_answer" required style="max-width:120px;">
                <input type="hidden" name="captcha_token" value="<?php echo $captcha_token; ?>">
            </div>
            <button type="submit" class="ak-submit">Küldés</button>
            <div class="ak-msg"></div>
        </form>

        <!-- Success Modal -->
        <div id="ak-success-modal" class="ak-modal">
            <div class="ak-modal-content" style="text-align:center; max-width:500px">
                <div class="ak-modal-header" style="justify-content:center; border:none; padding-bottom:0">
                    <h3 style="font-size:24px; color:#8b5e3c;">Köszönjük!</h3>
                </div>
                <div style="padding:20px 0; font-size:16px; line-height:1.6;">
                    Ajánlatkérését sikeresen elküldtük!<br>
                    Hamarosan felvesszük Önnel a kapcsolatot.
                </div>
                <div class="ak-modal-actions" style="justify-content:center; border:none; padding-top:10px">
                    <button type="button" class="ak-submit ak-redirect-home"
                        style="margin:0; width:auto; padding:12px 30px; font-size:14px;">Rendben</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * SHORTCODE 2: Frontend Admin Lista [ajanlat_lista]
 */
add_shortcode('ajanlat_lista', 'ak_shortcode_admin_list');
function ak_shortcode_admin_list($atts)
{
    $code = isset($_POST['ak_code']) ? $_POST['ak_code'] : (isset($_GET['access']) ? $_GET['access'] : '');

    if ($code !== 'Ph-159753') {
        ob_start();
        ?>
        <div class="ak-wrapper">
            <div class="ak-card" style="text-align:center; padding: 60px 40px;">
                <h3
                    style="margin-top:0; color:#8b5e3c; font-family:Georgia, serif; text-transform:uppercase; letter-spacing:2px;">
                    Admin Belépés</h3>
                <form method="post" style="max-width:300px; margin:0 auto">
                    <input type="password" name="ak_code" placeholder="Kód..."
                        style="width:100%; padding:12px; margin-bottom:15px; border:1px solid #d6c9bb; text-align:center">
                    <button type="submit" class="ak-submit" style="margin:0">Belépés</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ajanlatkeres ORDER BY created DESC");
    ob_start();
    // Pass password to JS for AJAX calls
    echo '<script>var ak_fe_pass = "' . esc_js($code) . '";</script>';
    ?>
    <script>
        // Auto-Logout & Manual Logout Logic
        (function () {
            var idleTime = 0;
            var idleLimit = 10; // 10 perc
            var idleInterval = setInterval(timerIncrement, 60000); // 1 percenként

            function timerIncrement() {
                idleTime++;
                if (idleTime >= idleLimit) {
                    akLogout();
                }
            }

            function resetTimer() {
                idleTime = 0;
            }

            // Aktivitás figyelése
            window.onload = resetTimer;
            window.onmousemove = resetTimer;
            window.onmousedown = resetTimer; // Clicks
            window.ontouchstart = resetTimer; // Touchscreen
            window.onclick = resetTimer;     // Touchpad clicks
            window.onkeydown = resetTimer;

            window.akLogout = function () {
                // URL paraméterek tisztítása (access=...)
                var url = new URL(window.location.href);
                url.searchParams.delete('access');
                // Form újraküldés elkerülése érdekében tiszta URL-re navigálunk
                window.location.href = url.toString();
            };
        })();
    </script>

    <div class="ak-wrapper" style="max-width:1100px; margin:0 auto">
        <div class="ak-card" style="max-width:100%; padding:40px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="color:#8b5e3c; margin:0; font-family:Georgia, serif;">Ajánlatok Kezelése</h2>
                <button onclick="akLogout()" class="ak-submit" title="Kijelentkezés"
                    style="margin:0; background:#d32f2f; border-color:#b91c1c; padding:3px 8px; font-size:10px; text-transform:uppercase; letter-spacing:0.5px; font-weight:bold; display:inline-flex; align-items:center; gap:4px; line-height: 1; width:auto;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Kilépés
                </button>
            </div>
            <div style="overflow-x:auto">
                <table class="ak-fe-table">
                    <thead>
                        <tr>
                            <th>Dátum</th>
                            <th>Vendég</th>
                            <th>Csomag</th>
                            <th>Státusz</th>
                            <th style="text-align:right">Műveletek</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr id="ak-row-<?php echo $r->id; ?>">
                                <td style="font-size:11px; color:#999"><?php echo date('m.d H:i', strtotime($r->created)); ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($r->name); ?></strong><br>
                                    <small style="color:#888;"><?php echo esc_html($r->email); ?></small>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span style="font-size:24px;"><?php echo ak_get_pkg_icon($r->package); ?></span>
                                        <span style="font-size:13px; color:#444;"><?php echo esc_html($r->package); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="status-wrapper">
                                        <label class="ak-switch">
                                            <input type="checkbox" class="ak-fe-toggle" data-id="<?php echo $r->id; ?>" <?php checked($r->status, 'feldolgozva'); ?>>
                                            <span class="ak-slider"></span>
                                        </label>
                                        <span
                                            class="ak-status-label <?php echo $r->status === 'feldolgozva' ? 'status-label-feldolgozva' : 'status-label-uj'; ?>">
                                            <?php echo ($r->status === 'feldolgozva' ? 'Feldolgozva' : 'Új'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <button class="ak-fe-edit ak-icon-btn" data-id="<?php echo $r->id; ?>"
                                        title="Szerkesztés">✏️</button>
                                    <button class="ak-fe-delete ak-icon-btn" data-id="<?php echo $r->id; ?>"
                                        title="Törlés">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Frontend Edit Modal -->
    <div id="ak-edit-modal" class="ak-modal" style="display:none;">
        <div class="ak-modal-content">
            <div class="ak-modal-header">
                <h3>Ajánlat Szerkesztése</h3>
                <span class="ak-modal-close">&times;</span>
            </div>
            <form id="ak-edit-form">
                <input type="hidden" name="id" id="edit-id">
                <div class="ak-row">
                    <div class="ak-field-group">
                        <label>Név</label>
                        <input type="text" name="name" id="edit-name" required>
                    </div>
                    <div class="ak-field-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit-email" required>
                    </div>
                </div>
                <div class="ak-row">
                    <div class="ak-field-group">
                        <label>Telefon</label>
                        <input type="text" name="phone" id="edit-phone">
                    </div>
                    <div class="ak-field-group">
                        <label>Érkezés</label>
                        <input type="date" name="arrival" id="edit-arrival">
                    </div>
                </div>
                <div class="ak-row">
                    <div class="ak-mini">
                        <label>Szobák</label>
                        <input type="number" name="rooms" id="edit-rooms">
                    </div>
                    <div class="ak-mini">
                        <label>Éjszakák</label>
                        <input type="number" name="nights" id="edit-nights">
                    </div>
                    <div class="ak-mini">
                        <label>Felnőtt</label>
                        <input type="number" name="adults" id="edit-adults">
                    </div>
                    <div class="ak-mini">
                        <label>Gyermek</label>
                        <input type="number" name="children" id="edit-children">
                    </div>
                </div>
                <div class="ak-field-group">
                    <label>Csomag</label>
                    <select name="package" id="edit-package">
                        <option value="">-- Nincs csomag --</option>
                        <?php foreach (ak_get_active_packages() as $pkg): ?>
                            <option value="<?php echo esc_attr($pkg); ?>"><?php echo esc_html($pkg); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ak-field-group">
                    <label>Megjegyzés</label>
                    <textarea name="note" id="edit-note" rows="3"></textarea>
                </div>
                <div class="ak-modal-actions">
                    <button type="button" class="ak-btn-cancel ak-modal-close-btn">Mégse</button>
                    <button type="submit" class="ak-submit"
                        style="margin-top:0; width:auto; padding:12px 25px;">Mentés</button>
                </div>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * AJAX: Űrlap beküldése
 */
add_action('wp_ajax_ajanlat_submit', 'ak_handle_submit');
add_action('wp_ajax_nopriv_ajanlat_submit', 'ak_handle_submit');
function ak_handle_submit()
{
    check_ajax_referer('ak_submit_nonce', 'nonce');
    $user_answer = isset($_POST['captcha_answer']) ? intval($_POST['captcha_answer']) : 0;
    $token = isset($_POST['captcha_token']) ? sanitize_text_field($_POST['captcha_token']) : '';
    if (md5($user_answer . 'ak_salt') !== $token) {
        wp_send_json_error(['message' => 'Hibás biztonsági ellenőrzés!']);
    }
    global $wpdb;
    $data = [
        'name' => sanitize_text_field($_POST['name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'arrival' => sanitize_text_field($_POST['arrival']),
        'rooms' => intval($_POST['rooms']),
        'nights' => intval($_POST['nights']),
        'adults' => intval($_POST['adults']),
        'children' => intval($_POST['children']),
        'package' => sanitize_text_field($_POST['package']),
        'note' => sanitize_textarea_field($_POST['note']),
        'status' => 'uj'
    ];
    if ($wpdb->insert($wpdb->prefix . 'ajanlatkeres', $data)) {
        $admin_emails = ak_get_admin_emails();
        $headers = [];
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: Pollushof Panzió & Étterem <' . get_option('admin_email') . '>';

        // Prepare replacement arrays
        $search = array_map(function ($key) {
            return '{{' . $key . '}}';
        }, array_keys($data));
        $replace = array_values($data);

        // Admin Email
        $admin_msg = file_get_contents(plugin_dir_path(__FILE__) . 'emails/admin.html');
        $admin_msg = str_replace($search, $replace, $admin_msg);

        // Tárgy frissítve kérésre
        wp_mail($admin_emails, 'Új Foglalási ajánlatkérés - ' . $data['name'], $admin_msg, $headers);

        // User Confirmation Email
        if (!empty($data['email'])) {
            $user_msg = file_get_contents(plugin_dir_path(__FILE__) . 'emails/user.html');
            $user_msg = str_replace($search, $replace, $user_msg);
            $user_headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: Pollushof Panzió & Étterem <' . get_option('admin_email') . '>'
            ];
            wp_mail($data['email'], 'Visszaigazolás - Ajánlatkérését fogadtuk', $user_msg, $user_headers);
        }

        wp_send_json_success(['message' => 'Köszönjük! Ajánlatkérését sikeresen elküldtük.']);
    } else {
        wp_send_json_error(['message' => 'Hiba történt a mentés során.']);
    }
}

/**
 * AJAX: Státusz frissítés (Admin & FE Admin - Kijelentkezett módban is!)
 */
add_action('wp_ajax_ak_update_status', 'ak_ajax_update_status');
add_action('wp_ajax_nopriv_ak_update_status', 'ak_ajax_update_status'); // BIZTONSÁG: Jelszóval engedélyezve
function ak_ajax_update_status()
{
    // Nonce ellenőrzés csak ha van bejelentkezve, vagy a publikus nonce-ot használjuk (de az most nincs)
    // Egyszerűsítés: Ha van jelszó, átengedjük nonce nélkül is, vagy csinálunk egy publikus nonce-ot.
    // Mivel a nonce-ot elrejtettük, a 'check_ajax_referer' elbukna a vendégeknél.

    $is_admin = current_user_can('manage_options');
    $pass = isset($_POST['fe_pass']) ? $_POST['fe_pass'] : '';
    $is_fe_auth = ($pass === 'Ph-159753');

    if (!$is_admin && !$is_fe_auth) {
        wp_send_json_error(['message' => 'Nincs jogosultsága ehhez a művelethez.']);
    }

    if ($is_admin)
        check_ajax_referer('ak_status_nonce', 'nonce');

    global $wpdb;
    $wpdb->update(
        $wpdb->prefix . 'ajanlatkeres',
        ['status' => sanitize_text_field($_POST['status'])],
        ['id' => intval($_POST['id'])]
    );
    wp_send_json_success();
}

/**
 * AJAX: Frontend Törlés (Kijelentkezett módban is!)
 */
add_action('wp_ajax_ak_fe_delete', 'ak_ajax_fe_delete');
add_action('wp_ajax_nopriv_ak_fe_delete', 'ak_ajax_fe_delete'); // BIZTONSÁG: Jelszóval engedélyezve
function ak_ajax_fe_delete()
{
    $is_admin = current_user_can('manage_options');
    $pass = isset($_POST['fe_pass']) ? $_POST['fe_pass'] : '';
    $is_fe_auth = ($pass === 'Ph-159753');

    if (!$is_admin && !$is_fe_auth) {
        wp_send_json_error(['message' => 'Nincs jogosultsága ehhez a művelethez.']);
    }

    if ($is_admin)
        check_ajax_referer('ak_admin_nonce', 'nonce');

    global $wpdb;
    if ($wpdb->delete($wpdb->prefix . 'ajanlatkeres', ['id' => intval($_POST['id'])])) {
        wp_send_json_success();
    }
    wp_send_json_error();
}

/**
 * AJAX: Részletek lekérése Szerkesztéshez (Frontend Admin)
 */
add_action('wp_ajax_ak_fe_get_details', 'ak_ajax_fe_get_details');
add_action('wp_ajax_nopriv_ak_fe_get_details', 'ak_ajax_fe_get_details');
function ak_ajax_fe_get_details()
{
    $pass = isset($_POST['fe_pass']) ? $_POST['fe_pass'] : '';
    if ($pass !== 'Ph-159753' && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Hozzáférés megtagadva.']);
    }

    global $wpdb;
    $id = intval($_POST['id']);
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ajanlatkeres WHERE id = %d", $id));

    if ($row) {
        wp_send_json_success($row);
    } else {
        wp_send_json_error(['message' => 'Nem található.']);
    }
}

/**
 * AJAX: Részletek Mentése (Frontend Admin)
 */
add_action('wp_ajax_ak_fe_save_details', 'ak_ajax_fe_save_details');
add_action('wp_ajax_nopriv_ak_fe_save_details', 'ak_ajax_fe_save_details');
function ak_ajax_fe_save_details()
{
    $pass = isset($_POST['fe_pass']) ? $_POST['fe_pass'] : '';
    if ($pass !== 'Ph-159753' && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Hozzáférés megtagadva.']);
    }

    global $wpdb;
    $id = intval($_POST['id']);
    $data = [
        'name' => sanitize_text_field($_POST['name']),
        'email' => sanitize_email($_POST['email']),
        'phone' => sanitize_text_field($_POST['phone']),
        'arrival' => sanitize_text_field($_POST['arrival']),
        'rooms' => intval($_POST['rooms']),
        'nights' => intval($_POST['nights']),
        'adults' => intval($_POST['adults']),
        'children' => intval($_POST['children']),
        'package' => sanitize_text_field($_POST['package']),
        'note' => sanitize_textarea_field($_POST['note']),
    ];

    if ($wpdb->update($wpdb->prefix . 'ajanlatkeres', $data, ['id' => $id])) {
        wp_send_json_success(['message' => 'Sikeres mentés!']);
    } else {
        // Ha nem változott semmi, az update false-al térhet vissza, de az nem hiba
        wp_send_json_success(['message' => 'Mentve (nem volt módosítás).']);
    }
}