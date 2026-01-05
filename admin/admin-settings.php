<?php
add_action('admin_menu', function(){
 add_submenu_page(
   'ajanlatok',
   'Értesítési beállítások',
   'Értesítések',
   'manage_options',
   'ajanlat-ertesitesek',
   'ak_settings_page'
 );
});

function ak_settings_page(){
 if(isset($_POST['save'])){
   update_option('ak_admin_emails', sanitize_textarea_field($_POST['ak_admin_emails']));
   echo '<div class="updated notice"><p>Mentve.</p></div>';
 }
 $emails = esc_textarea(get_option('ak_admin_emails'));
 echo "
 <div class='ak-admin-card'>
  <h2>Admin értesítési e-mail címek</h2>
  <p class='description'>Több cím vesszővel elválasztva</p>
  <form method='post'>
    <textarea name='ak_admin_emails' rows='3' style='width:100%;max-width:500px'>$emails</textarea><br><br>
    <button class='button button-primary' name='save'>Mentés</button>
  </form>
 </div>";
}