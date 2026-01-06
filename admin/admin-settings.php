<?php
add_action('admin_menu', function () {
  add_submenu_page(
    'ajanlatok',
    'Értesítési beállítások',
    'Értesítések',
    'manage_options',
    'ajanlat-ertesitesek',
    'ak_settings_page'
  );
});

function ak_settings_page()
{
  if (isset($_POST['save'])) {
    update_option('ak_admin_emails', sanitize_textarea_field($_POST['ak_admin_emails']));
    // Csomaglista mentése: Soronként robbantva, üres sorok nélkül
    $pkgs = array_filter(array_map('trim', explode("\n", $_POST['ak_package_list'])));
    update_option('ak_package_list', implode("\n", $pkgs));
    echo '<div class="updated notice"><p>Beállítások mentve.</p></div>';
  }
  $emails = esc_textarea(get_option('ak_admin_emails'));
  $packages = esc_textarea(get_option('ak_package_list', "A lovaglás szerelmeseinek\nFittnesz hétvége\nPihenés a Gerecse lábánál\nRomantikus hétvége\nTöltődjön fel napfénnyel\nEsküvői csomagajánlat")); // Alapértelmezett

  echo "
 <div class='ak-admin-card'>
  <h2>Beállítások</h2>
  
  <form method='post'>
    <div style='margin-bottom: 25px;'>
        <h3>Csomagajánlatok (Dropdown opciók)</h3>
        <p class='description'>Minden csomag új sorba kerüljön.</p>
        <textarea name='ak_package_list' rows='8' style='width:100%;max-width:500px; font-family:monospace;'>$packages</textarea>
    </div>

    <div style='margin-bottom: 25px;'>
        <h3>Admin értesítési e-mail címek</h3>
        <p class='description'>Több cím vesszővel elválasztva</p>
        <textarea name='ak_admin_emails' rows='3' style='width:100%;max-width:500px'>$emails</textarea>
    </div>

    <button class='button button-primary' name='save'>Mentés</button>
  </form>
 </div>";
}