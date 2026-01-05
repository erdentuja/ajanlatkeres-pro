<?php
add_action('admin_menu',function(){
 add_menu_page('Ajánlatok','Ajánlatok','manage_options','ajanlatok','ak_admin','dashicons-email-alt',26);
});

function ak_admin(){
 global $wpdb;

 // Teljes szerkesztés mentése
 if(isset($_POST['update'])){
    check_admin_referer('ak_update_ajanlat');
    $wpdb->update($wpdb->prefix.'ajanlatkeres',[
        'name'     => sanitize_text_field($_POST['name']),
        'email'    => sanitize_email($_POST['email']),
        'phone'    => sanitize_text_field($_POST['phone']),
        'arrival'  => sanitize_text_field($_POST['arrival']),
        'rooms'    => intval($_POST['rooms']),
        'nights'   => intval($_POST['nights']),
        'adults'   => intval($_POST['adults']),
        'children' => intval($_POST['children']),
        'package'  => sanitize_text_field($_POST['package']),
        'note'     => sanitize_textarea_field($_POST['note']),
        'status'   => sanitize_text_field($_POST['status'])
    ],['id' => intval($_POST['id'])]);
    echo '<div class="updated notice"><p>Sikeres mentés.</p></div>';
 }

 // Részletes szerkesztő nézet dizájnos elrendezéssel
 if(isset($_GET['edit'])){
  $id = intval($_GET['edit']);
  $r = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ajanlatkeres WHERE id=$id");
  if(!$r) return;
  ?>
  <div class='ak-admin-card'>
    <div class="ak-card-header"><h2>Ajánlat szerkesztése</h2></div>
    <div class="ak-card-body">
        <form method='post' class='ak-admin-form'>
            <?php wp_nonce_field('ak_update_ajanlat'); ?>
            <input type='hidden' name='id' value='<?php echo $r->id; ?>'>
            
            <div class="ak-section-title">Személyes adatok</div>
            <div class="ak-admin-grid">
                <div style="grid-column: span 2;"><label>Név</label><input name='name' value='<?php echo esc_attr($r->name); ?>'></div>
                <div><label>Email</label><input name='email' value='<?php echo esc_attr($r->email); ?>'></div>
                <div><label>Telefon</label><input name='phone' value='<?php echo esc_attr($r->phone); ?>'></div>
            </div>

            <div class="ak-section-title">Foglalási adatok</div>
            <div class="ak-admin-grid">
                <div><label>Érkezés dátuma</label><input type='date' name='arrival' value='<?php echo $r->arrival; ?>'></div>
                <div>
                    <label>Csomag</label>
                    <select name="package">
                        <option value="A lovaglás szerelmeseinek" <?php selected($r->package, 'A lovaglás szerelmeseinek'); ?>>A lovaglás szerelmeseinek</option>
                        <option value="Fittnesz hétvége" <?php selected($r->package, 'Fittnesz hétvége'); ?>>Fittnesz hétvége</option>
                        <option value="Pihenés a Gerecse lábánál" <?php selected($r->package, 'Pihenés a Gerecse lábánál'); ?>>Pihenés a Gerecse lábánál</option>
                        <option value="Romantikus hétvége" <?php selected($r->package, 'Romantikus hétvége'); ?>>Romantikus hétvége</option>
                        <option value="Töltődjön fel napfénnyel" <?php selected($r->package, 'Töltődjön fel napfénnyel'); ?>>Töltődjön fel napfénnyel</option>
                        <option value="Esküvői csomagajánlat" <?php selected($r->package, 'Esküvői csomagajánlat'); ?>>Esküvői csomagajánlat</option>
                    </select>
                </div>
            </div>

            <div class="ak-admin-grid ak-admin-grid-four">
                <div><label>Szobák száma</label><input name='rooms' value='<?php echo $r->rooms; ?>'></div>
                <div><label>Éjszakák száma</label><input name='nights' value='<?php echo $r->nights; ?>'></div>
                <div><label>Felnőttek száma</label><input name='adults' value='<?php echo $r->adults; ?>'></div>
                <div><label>Gyermek 0-3 év</label><input name='children' value='<?php echo $r->children; ?>'></div>
            </div>

            <label>Megjegyzés</label>
            <textarea name='note' rows="4"><?php echo esc_textarea($r->note); ?></textarea>
            
            <label>Státusz</label>
            <select name='status'>
                <option value="uj" <?php selected($r->status, 'uj'); ?>>Új</option>
                <option value="feldolgozva" <?php selected($r->status, 'feldolgozva'); ?>>Feldolgozva</option>
            </select>
            
            <div style="margin-top:30px; border-top: 1px solid #f1f5f9; padding-top: 25px;">
                <button class='button button-primary' name='update'>Módosítások mentése</button>
                <a href='admin.php?page=ajanlatok' class='button'>Vissza a listához</a>
            </div>
        </form>
    </div>
  </div>
  <?php
  return;
 }

 // Lista nézet automatikus státusz mentéssel és színes állapotokkal
 $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ajanlatkeres ORDER BY created DESC");
 ?>
 <div class='ak-admin-card'>
    <div class="ak-card-header"><h2>Beérkezett ajánlatkérések</h2></div>
    <table class='wp-list-table widefat fixed striped'>
        <thead>
            <tr>
                <th>Dátum</th>
                <th>Név</th>
                <th>Email</th>
                <th>Csomag</th>
                <th style="width:160px">Státusz</th>
                <th>Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rows as $r): ?>
            <tr>
                <td><?php echo $r->created; ?></td>
                <td><b><?php echo esc_html($r->name); ?></b></td>
                <td><a href="mailto:<?php echo esc_attr($r->email); ?>"><?php echo esc_html($r->email); ?></a></td>
                <td><?php echo esc_html($r->package); ?></td>
                <td>
                    <div class="status-wrapper">
                        <select class="ak-status-select status-<?php echo $r->status; ?>" data-id="<?php echo $r->id; ?>">
                            <option value="uj" <?php selected($r->status, 'uj'); ?>>Új</option>
                            <option value="feldolgozva" <?php selected($r->status, 'feldolgozva'); ?>>Feldolgozva</option>
                        </select>
                        <span class="save-indicator">✓</span>
                    </div>
                </td>
                <td><a class='button button-small' href='?page=ajanlatok&edit=<?php echo $r->id; ?>'>Szerkesztés</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
 </div>
 <?php
}