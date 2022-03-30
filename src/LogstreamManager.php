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
    public const LOGSTREAM_URI = 'wss://logstream.acquia.com:443/ah_websocket/logstream/v1';
    // To make this future-proof, it would be better to query the logstream API for available streams.
    // @see https://github.com/typhonius/acquia-logstream/issues/143
    public const AVAILABLE_TYPES = [
        'bal-request' => 'Balancer request',
        'varnish-request' => 'Varnish request',
        'apache-request' => 'Apache request',
        'apache-error' => 'Apache error',
        'php-error' => 'PHP error',
        'drupal-watchdog' => 'Drupal watchdog',
        'drupal-request' => 'Drupal request',
        'mysql-slow' => 'MySQL slow query',
    ];

    // $input is currently unused but may be useful in the future.
    private $input; // @phpstan-ignore-line
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
     * The $params argument required for this class must be an object of
     * stdClass with properties for the following:
     * - site: The sitename to initiate logstreaming with.
     * - hmac: A HMAC hash passed back from the API.
     * - time: The timestamp.
     * - environment: The environment name to use for logstreaming.
     *
     * The easiest way to obtain the required parameters for this method is
     * to use the API to get a $param object.
     *
     * @see LogstreamCommand::execute
     *
     * @param \stdClass $params
     */
    public function setParams(\stdClass $params): void
    {
        array_walk(
            $this->requiredParams,
            function ($param, $key, $params) {
                if (!property_exists($params, $param)) {
                    throw new \Exception(sprintf('Missing parameter: (%s)', $param));
                }
            },
            $params
        );

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
    public function setLogServerFilter(array $servers): void
    {
        $this->servers = $servers;
    }

    /**
     * Gets servers to filter logs from.
     *
     * @return array
     */
    public function getLogServerFilter(): array
    {
        return $this->servers;
    }

    /**
     * Sets log types to filter logs from.
     *
     * @param array $types
     *
     * @throws \Exception
     */
    public function setLogTypeFilter(array $types): void
    {
        foreach ($types as $type) {
            if (!array_key_exists($type, self::AVAILABLE_TYPES)) {
                throw new \RuntimeException(sprintf('Invalid log type: (%s)', $type));
            }
        }
        $this->logTypes = $types;
    }

    /**
     * Gets log types to filter logs from.
     *
     * @return array
     */
    public function getLogTypeFilter(): array
    {
        return $this->logTypes;
    }

    /**
     * Sets the DNS server to use for the React connector.
     *
     * @param string $dns
     */
    public function setDns($dns): void
    {
        $this->dns = $dns;
    }

    /**
     * Gets the DNS server used by the React connector.
     *
     * @return string
     */
    public function getDns(): string
    {
        return $this->dns;
    }

    /**
     * Sets the timeout for the React connector.
     *
     * @param int $timeout
     */
    public function setTimeout($timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Gets the timeout used by the React connector.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Sets the output colourisation.
     *
     * @param bool $colourise
     */
    public function setColourise(bool $colourise): void
    {
        $this->colourise = $colourise;
    }

    /**
     * Gets the output colourisation.
     *
     * @return bool
     */
    public function getColourise(): bool
    {
        return $this->colourise;
    }

    /**
     * Streams the logs from the Acquia endpoint.
     */
    public function stream()
    {

        $loop = EventLoop::create();
        $reactConnector = new React(
            $loop,
            [
                'dns' => $this->dns,
                'timeout' => $this->timeout
            ]
        );

        $connector = new Ratchet($loop, $reactConnector);

        $connector(self::LOGSTREAM_URI)
            ->then(
                function (WebSocket $conn) {
                    $conn->on(
                        'message',
                        function (MessageInterface $msg) use ($conn) {
                            if ($send = $this->processMessage($msg)) {
                                $conn->send($send);
                            }
                        }
                    );

                    $conn->on(
                        'close',
                        function ($code = null, $reason = null) {
                            echo "Connection closed ({$code} - {$reason})\n";
                        }
                    );

                    $conn->send(json_encode($this->getAuthArray()));
                },
                function (\Exception $e) use ($loop) {
                    echo "Could not connect: {$e->getMessage()}\n";
                    $loop->stop();
                }
            );

        $loop->run();
    }

    protected function processMessage($msg)
    {
        $message = json_decode($msg);

        if ($message->cmd === 'available') {
            if (empty($this->logTypes) || in_array($message->type, $this->logTypes)) {
                if (empty($this->servers) || in_array($message->server, $this->servers)) {
                    $enable = [
                        'cmd' => 'enable',
                        'type' => $message->type,
                        'server' => $message->server
                    ];

                    return json_encode($enable);
                }
            }
        } elseif ($message->cmd === 'line') {
            $colour = $this->pickColour($message);

            if ($this->output->isVeryVerbose()) {
                $this->output->writeln(sprintf('<%s>%s</>', $colour, $msg));
            } elseif ($this->output->isVerbose()) {
                $this->output->writeln(
                    sprintf(
                        '<%s>%s %s %s</>',
                        $colour,
                        $message->log_type,
                        $message->server,
                        $message->text
                    )
                );
            } else {
                $this->output->writeln(sprintf('<%s>%s</>', $colour, $message->text));
            }
        } elseif ($message->cmd === 'error') {
            $this->output->writeln(sprintf('<%s>%s</>', 'fg=red', $msg));
        } else {
            if ($this->output->isDebug()) {
                $this->output->writeln($msg);
            }
        }
    }

    /**
     * Returns the authentication array to be passed to the wss endpoint.
     *
     * @return array
     */
    protected function getAuthArray(): array
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
     * @param  object $message
     * @return string
     */
    protected function pickColour($message): string
    {
        $colour = '/';
        if (!$this->colourise) {
            return $colour;
        }

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
