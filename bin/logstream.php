<?php

use AcquiaLogstream\LogstreamCommand;
use Symfony\Component\Console\Application;

if (strpos(basename(__FILE__), 'phar')) {
    $root = __DIR__;
    require_once 'phar://acquiacli.phar/vendor/autoload.php';
} else {
    if (file_exists(dirname(__DIR__).'/vendor/autoload.php')) {
        $root = dirname(__DIR__);
        require_once dirname(__DIR__) . '/vendor/autoload.php';
    } elseif (file_exists(dirname(__DIR__) . '/../../autoload.php')) {
        $root = dirname(__DIR__) . '/../../..';
        require_once dirname(__DIR__) . '/../../autoload.php';
    } else {
        $root = __DIR__;
        require_once 'phar://acquiacli.phar/vendor/autoload.php';
    }
}

$application = new Application();
$application->add(new LogstreamCommand());

$application->run();
exit;
