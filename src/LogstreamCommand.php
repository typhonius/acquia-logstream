<?php

namespace AcquiaLogstream;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Logs;

/**
 * Class AcquiaLogstream
 *
 * @package AcquiaLogstream
 */
class LogstreamCommand extends Command
{
    protected static $defaultName = 'acquia:logstream';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setAliases(['logstream', 'stream'])
            ->setDescription('Streams logs directly from the Acquia Cloud')
            ->addArgument('key', InputArgument::REQUIRED, 'Acquia API key')
            ->addArgument('secret', InputArgument::REQUIRED, 'Acquia API secret')
            ->addArgument('environmentUuid', InputArgument::REQUIRED, 'UUID of the environment to stream')
            ->addOption(
                'logtypes',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Log types to stream',
                [
                    'bal-request',
                    'varnish-request',
                    'apache-request',
                    'apache-error',
                    'php-error',
                    'drupal-watchdog',
                    'drupal-request',
                    'mysql-slow'
                ]
            )
            ->addOption(
                'servers',
                's',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Servers to stream logs from e.g. web-1234.'
            )
            ->addOption(
                'colourise',
                'c',
                InputOption::VALUE_NONE,
                'Colorise the output based on HTTP status code.'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = [
            'key' => $input->getArgument('key'),
            'secret' => $input->getArgument('secret')
        ];

        $connector = new Connector($config);
        $client = Client::factory($connector);

        $client->addOption('headers', [
            'User-Agent' => sprintf(
                "%s/%s (https://github.com/typhonius/acquia-logstream)",
                $this->getApplication()->getName(),
                $this->getApplication()->getVersion()
            )
        ]);

        $logs = new Logs($client);

        $stream = $logs->stream($input->getArgument('environmentUuid'));

        $logstream = new LogstreamManager($input, $output);
        $logstream->setParams($stream->logstream->params);

        $logstream->setLogTypeFilter($input->getOption('logtypes'));
        $logstream->setLogServerFilter($input->getOption('servers'));
        $logstream->setColourise($input->getOption('colourise'));
        $logstream->stream();
        return 0;
    }
}
