<?php

use Core\Installer;
use Core\CliInstaller;
use Core\InstallRoute;
require_once __DIR__ . "/AutoLoader.php";
require_once __DIR__ . "/errorHandler.php";

$autoloader = new Autoloader();
$autoloader->addNamespace('InstallControllers', __DIR__ .'/InstallControllers/');
$autoloader->addNamespace('Core', __DIR__ .'/Core/');
$autoloader->register();

// Check if running in CLI mode
$isCliMode = php_sapi_name() === 'cli';

// Start session if not already started
if (!$isCliMode && session_status() == PHP_SESSION_NONE) {
    // Only start cookie-based sessions in browser mode
    session_start();
} elseif ($isCliMode && session_status() == PHP_SESSION_NONE) {
    // Use a non-cookie session in CLI mode
    session_start(['use_cookies' => 0]);
}

if ($isCliMode) {
    // CLI Installation Mode
    try {
        echo "Starting CLI installation...\n";
        $installer = new CliInstaller();
        $installer->run();
        echo "Installation completed successfully!\n";
        exit(0);
    } catch (Exception $e) {
        echo "Installation failed: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    // Browser Installation Mode
    $route = new InstallRoute(new Installer());
    if(isset($_SESSION['CurrentPage']))
    {
        $route->reroute('/','page/'. $_SESSION['CurrentPage']);
    }
    else
    {
        $route->reroute('/','page/1');
    }

    $route->get('/page/:pageNumber', 'show', ['pageNumber' => 'num']);
    $route->post('/page/submit', 'submit');

    $route->dispatch();
}