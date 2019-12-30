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
 * Class AcquiaCli
 * @package AcquiaCli
 */
class LogstreamCommand extends Command
{

    const NAME = 'AcquiaLogstream';

    const VERSION = '0.0.1-dev';

    protected static $defaultName = 'acquia:logstream';

    /**
     * AcquiaLogstream constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'Acquia API key')
            ->addArgument('secret', InputArgument::REQUIRED, 'Acquia API secret')
            ->addArgument('environmentUuid', InputArgument::REQUIRED, 'UUID of the environment to stream')
            ->addOption(
                'logtypes',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Log types to stream (separate multiple types with a comma)',
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
                'Servers to stream logs from.'
            )
            ->addOption(
                'colourise',
                'c',
                InputOption::VALUE_NONE,
                'Colorise the output based on HTTP status code.'
            );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = [
            'key' => $input->getArgument('key'),
            'secret' => $input->getArgument('secret')
        ];
        
        $connector = new Connector($config);
        $client = Client::factory($connector);
        $logs = new Logs($client);

        $stream = $logs->stream($input->getArgument('environmentUuid'));
        $params = $stream->logstream->params;

        $logstream = new LogstreamManager($input, $output, $params);

        $logstream->setLogTypeFilter($input->getOption('logtypes'));
        $logstream->setLogServerFilter($input->getOption('servers'));
        $logstream->setColourise($input->getOption('colourise'));
        $logstream->stream();
    }
}
