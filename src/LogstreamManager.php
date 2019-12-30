<?php

namespace AcquiaLogstream;

use React\Socket\Connector as React;
use Ratchet\Client\Connector as Ratchet;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use React\EventLoop\Factory as EventLoop;

class LogstreamManager
{

    const LOGSTREAM_URI = 'wss://logstream.acquia.com:443/ah_websocket/logstream/v1';

    private $input;
    private $output;
    private $logTypes = [];
    private $servers = [];
    private $site;
    private $hmac;
    private $time;
    private $environment;
    private $dns = '1.1.1.1';
    private $timeout = 10;
    private $colourise = false;

    public function __construct(InputInterface $input, OutputInterface $output, object $params)
    {
        $this->input = $input;
        $this->output = $output;
        $this->site = $params->site;
        $this->hmac = $params->hmac;
        $this->time = $params->t;
        $this->environment = $params->environment;
    }

    public function stream()
    {

        $loop = EventLoop::create();
        $reactConnector = new React($loop, [
            'dns' => $this->dns,
            'timeout' => $this->timeout
        ]);

        $connector = new Ratchet($loop, $reactConnector);

        $connector(self::LOGSTREAM_URI)
        ->then(function (WebSocket $conn) {
            $conn->on('message', function (MessageInterface $msg) use ($conn) {
                $message = json_decode($msg);

                switch ($message->cmd) {
                    case 'available':
                        if (empty($this->logTypes) || in_array($message->type, $this->logTypes)) {
                            if (empty($this->servers) || in_array($message->server, $this->servers)) {
                                $enable = [
                                    'cmd' => 'enable',
                                    'type' => $message->type,
                                    'server' => $message->server
                                ];
    
                                $conn->send(json_encode($enable));
                            }
                        }
                        break;
                    case 'connected':
                    case 'success':
                        if ($this->output->isDebug()) {
                            $this->output->writeln($msg);
                        }
                        break;
                    case 'line':
                        $colour = '/';
                        if ($this->colourise) {
                            if (isset($message->http_status)) {
                                switch ($message->http_status) {
                                    case (preg_match('/^2/', $message->http_status) ? true : false):
                                        $colour = 'fg=green';
                                        break;
                                    case (preg_match('/^3/', $message->http_status) ? true : false):
                                        $colour = 'fg=cyan';
                                        break;
                                    case (preg_match('/^4/', $message->http_status) ? true : false):
                                        $colour = 'fg=yellow';
                                        break;
                                    case (preg_match('/^5/', $message->http_status) ? true : false):
                                        $colour = 'fg=red';
                                        break;
                                }
                            }
                        }

                        if ($this->output->isVerbose()) {
                            $this->output->writeln('<' . $colour . '>' . $msg . '</>');
                        } else {
                            $this->output->writeln('<' . $colour . '>' . $message->text . '</>');
                        }
                        
                        break;
                    case 'error':
                        // $this->output->writeln('<fg=green>' . $msg. '</>');
                        break;
                    default:
                        break;
                }
            });

            $conn->on('close', function ($code = null, $reason = null) {
                echo "Connection closed ({$code} - {$reason})\n";
            });

            $stream = [
                'site' => $this->site,
                'd' => $this->hmac,
                't' => $this->time,
                'env' => $this->environment,
                'cmd' => 'stream-environment'
            ];

            $conn->send(json_encode($stream));
        }, function (\Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }

    public function setLogServerFilter(array $servers)
    {
        $this->servers = $servers;
    }

    public function getLogServerFilter()
    {
        return $this->servers;
    }

    public function setLogTypeFilter(array $types)
    {
        $this->logTypes = $types;
    }

    public function getLogTypeFilter()
    {
        return $this->logTypes;
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
    public function setColourise(bool $colourise)
    {
        $this->colourise = $colourise;
    }
    public function ggetColourise()
    {
        return $this->colourise;
    }
}
