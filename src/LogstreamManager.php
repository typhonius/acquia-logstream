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
    private $requiredParams = ['site', 'hmac', 't', 'environment'];

    /**
     * LogstreamManager constructor.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Sets required parameters for connecting.
     *
     * @param \stdClass $params
     */
    public function setParams(\stdClass $params) : void
    {
        array_walk($this->requiredParams, function ($param, $key, $params) {
            if (!property_exists($params, $param)) {
                throw new \Exception(sprintf('Missing parameter: (%s)', $param));
            }
        }, $params);

        $this->site = $params->site;
        $this->hmac = $params->hmac;
        $this->time = $params->t;
        $this->environment = $params->environment;
    }

    /**
     * Sets servers to filter logs from.
     *
     * @param array $servers
     */
    public function setLogServerFilter(array $servers) : void
    {
        $this->servers = $servers;
    }

    /**
     * Gets servers to filter logs from.
     *
     * @return array
     */
    public function getLogServerFilter() : array
    {
        return $this->servers;
    }

    /**
     * Sets log types to filter logs from.
     *
     * @param array $types
     */
    public function setLogTypeFilter(array $types) : void
    {
        $this->logTypes = $types;
    }

    /**
     * Gets log types to filter logs from.
     *
     * @return array
     */
    public function getLogTypeFilter() : array
    {
        return $this->logTypes;
    }

    /**
     * Sets the DNS server to use for the React connector.
     *
     * @param string $dns
     */
    public function setDns($dns) : void
    {
        $this->dns = $dns;
    }

    /**
     * Gets the DNS server used by the React connector.
     *
     * @return string
     */
    public function getDns() : string
    {
        return $this->dns;
    }

    /**
     * Sets the timeout for the React connector.
     *
     * @param int $timeout
     */
    public function setTimeout($timeout) : void
    {
        $this->timeout = $timeout;
    }

    /**
     * Gets the timeout used by the React connector.
     *
     * @return int
     */
    public function getTimeout() : int
    {
        return $this->timeout;
    }

    /**
     * Sets the output colourisation.
     *
     * @param bool $colourise
     */
    public function setColourise(bool $colourise) : void
    {
        $this->colourise = $colourise;
    }

    /**
     * Gets the output colourisation.
     *
     * @return bool
     */
    public function getColourise() : bool
    {
        return $this->colourise;
    }

    /**
     * Streams the logs from the Acquia endpoint.
     */
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
                            $colour = $this->pickColour($message);
                        }

                        if ($this->output->isVeryVerbose()) {
                            $this->output->writeln("<${colour}>${msg}</>");
                        } elseif ($this->output->isVerbose()) {
                            $type = $message->log_type;
                            $server = $message->server;
                            $text = $message->text;
                            $this->output->writeln("<${colour}> ${type} ${server} ${text}</>");
                        } else {
                            $this->output->writeln("<${colour}>" . $message->text . "</>");
                        }
                        
                        break;
                    case 'error':
                        $this->output->writeln("<fg=red>${msg}</>");
                        break;
                    default:
                        break;
                }
            });

            $conn->on('close', function ($code = null, $reason = null) {
                echo "Connection closed ({$code} - {$reason})\n";
            });

            $conn->send(json_encode($this->getAuthArray()));
        }, function (\Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }

    /**
     * Returns the authentication array to be passed to the wss endpoint.
     *
     * @return array
     */
    private function getAuthArray() : array
    {
        return [
            'site' => $this->site,
            'd' => $this->hmac,
            't' => $this->time,
            'env' => $this->environment,
            'cmd' => 'stream-environment'
        ];
    }

    /**
     * Picks the colour to be output depending on log type.
     *
     * @param object $message
     * @return string
     */
    private function pickColour($message) : string
    {
        $colour = '/';
        if (isset($message->log_type)) {
            switch ($message->log_type) {
                case 'apache-error':
                case 'php-error':
                case 'mysql-slow':
                    $colour = 'fg=red';
                    break;
                case 'apache-request':
                case 'drupal-request':
                    $colour = 'fg=yellow';
                    break;

                case 'bal-request':
                case 'varnish-request':
                    $colour = 'fg=green';
                    break;
            }
        }
        return $colour;
    }
}
