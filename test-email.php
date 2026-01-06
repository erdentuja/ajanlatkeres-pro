<?php
// Mock WP functions
function ak_get_admin_emails()
{
    return 'admin@test.com';
}
function plugin_dir_path($file)
{
    return __DIR__ . '/';
}
function get_option($opt)
{
    return 'admin@pollus.hu';
}
function wp_mail($to, $subject, $message, $headers)
{
    echo "--- EMAIL SENT ---\n";
    echo "To: " . print_r($to, true) . "\n";
    echo "Subject: $subject\n";
    echo "Headers: " . print_r($headers, true) . "\n";
    echo "Message Length: " . strlen($message) . " chars\n";
    echo "Message Preview: " . substr(strip_tags($message), 0, 100) . "...\n";
    echo "------------------\n\n";
    return true;
}

// Mock Data
$data = [
    'name' => 'Teszt Elek',
    'email' => 'vendeg@test.com',
    'phone' => '+36301234567',
    'arrival' => '2024-06-01',
    'rooms' => 1,
    'nights' => 2,
    'adults' => 2,
    'children' => 0,
    'package' => 'Romantikus hétvége',
    'note' => 'Teszt üzenet',
    'status' => 'uj'
];

echo "Simulating Form Submission...\n";

// Logic from ajanlatkeres-pro.php (copied for testing)
$admin_emails = ak_get_admin_emails();
$headers = ['Content-Type: text/html; charset=UTF-8'];

// Admin Email
$admin_msg = file_get_contents('emails/admin.html');
foreach ($data as $key => $value) {
    $admin_msg = str_replace('{{' . $key . '}}', $value, $admin_msg);
}
wp_mail($admin_emails, 'Új Foglalási ajánlatkérés - ' . $data['name'], $admin_msg, $headers);

// User Confirmation Email
if (!empty($data['email'])) {
    $user_msg = file_get_contents('emails/user.html');
    foreach ($data as $key => $value) {
        $user_msg = str_replace('{{' . $key . '}}', $value, $user_msg);
    }
    $user_headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Pollushof Panzió & Étterem <' . get_option('admin_email') . '>'
    ];
    wp_mail($data['email'], 'Visszaigazolás - Ajánlatkérését fogadtuk', $user_msg, $user_headers);
}
