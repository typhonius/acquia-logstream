<?php

use AcquiaLogstream\LogstreamCommand;
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

$application = new Application();
$application->add(new LogstreamCommand());

$application->run();
exit;
