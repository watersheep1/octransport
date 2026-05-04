<?php
// Update these to match your local MySQL settings.
$config = [
    'db_host' => '127.0.0.1',
    'db_name' => 'car_service_db',
    'db_user' => 'root',
    'db_pass' => '',
    'google_client_id' => '',
    'google_client_secret' => '',
    'google_redirect_uri' => 'http://localhost/adminpanel/userpanel/google-callback.php',
];

$localConfigPath = __DIR__ . '/config.local.php';
if (file_exists($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $config = array_merge($config, $localConfig);
    }
}

return $config;
