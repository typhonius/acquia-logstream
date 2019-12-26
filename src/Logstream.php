<?php

namespace AcquiaLogstream;

use React\Socket\Connector as React;
use Ratchet\Client\Connector as Ratchet;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;

class Logstream
{

    const LOGSTREAM_URI = 'wss://logstream.acquia.com:443/ah_websocket/logstream/v1';

    private $site;
    private $hmac;
    private $time;
    private $environment;
    private $dns = '1.1.1.1';
    private $timeout = 10;


    public function __construct($site, $hmac, $time, $environment)
    {
        $this->site = $site;
        $this->hmac = $hmac;
        $this->time = $time;
        $this->environment = $environment;
    }

    public function stream($loop)
    {
        $reactConnector = new React($loop, [
            'dns' => $this->dns,
            'timeout' => $this->timeout
        ]);

        $connector = new Ratchet($loop, $reactConnector);

        $connector(self::LOGSTREAM_URI)
        ->then(function (WebSocket $conn) {
            $conn->on('message', function (MessageInterface $msg) use ($conn) {
                echo "Received: {$msg}\n";
                $message = json_decode($msg);

                if ($message->cmd == 'available') {
                    $enable = [
                        'cmd' => 'enable',
                        'type' => $message->type,
                        'server' => $message->server
                    ];

                    $conn->send(json_encode($enable));
                }
            });

            $conn->on('close', function ($code = null, $reason = null) {
                echo "Connection closed ({$code} - {$reason})\n";
            });


            $string = [
                'site' => $this->site,
                'd' => $this->hmac,
                't' => $this->time,
                'env' => $this->environment,
                'cmd' => 'stream-environment'
            ];

            $conn->send(json_encode($string));
        }, function (\Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }

    public function setDns($dns)
    {
        $this->dns = $dns;
    }
    public function getDns()
    {
        return $this->dns;
    }
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
    public function getTimeout()
    {
        return $this->timeout;
    }
}
