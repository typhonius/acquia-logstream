<?php

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Logs;
use AcquiaLogstream\Logstream;
use React\EventLoop\Factory as EventLoop;

if (count($argv) !== 4) {
    print 'Usage: php ./bin/logstream.php key secret environmentUuid' . PHP_EOL;
    exit;
}

$key = $argv[1];
$secret = $argv[2];
$environmentUuid = $argv[3];

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

$config = [
    'key' => $key,
    'secret' => $secret
];

$connector = new Connector($config);
$client = Client::factory($connector);

$logs = new Logs($client);
$stream = $logs->stream($environmentUuid);

$params = $stream->logstream->params;
$loop = EventLoop::create();
$logstream = new Logstream($params->site, $params->hmac, $params->t, $params->environment);

$logstream->stream($loop);
