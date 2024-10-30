<?php

use AcquiaLogstream\LogstreamCommand;
use SelfUpdate\SelfUpdateCommand;
use SelfUpdate\SelfUpdateManager;
use Symfony\Component\Console\Application;

$pharPath = \Phar::running(true);
if ($pharPath) {
    $autoloaderPath = "$pharPath/vendor/autoload.php";
} else {
    if (file_exists(dirname(__DIR__).'/vendor/autoload.php')) {
        $autoloaderPath = dirname(__DIR__).'/vendor/autoload.php';
    } elseif (file_exists(dirname(__DIR__).'/../../autoload.php')) {
        $autoloaderPath = dirname(__DIR__) . '/../../autoload.php';
    } else {
        die("Could not find autoloader. Run 'composer install'.");
    }
}
$classLoader = require $autoloaderPath;

$version = trim(file_get_contents(dirname(__DIR__) . '/VERSION'));
$application = new Application('Logstream', $version);
$application->add(new LogstreamCommand());

$selfUpdateManager = new SelfUpdateManager('Logstream', $version, 'typhonius/acquia-logstream');
$selfUpdate = new SelfUpdateCommand($selfUpdateManager);
$application->add($selfUpdate);

$application->run();
exit;
