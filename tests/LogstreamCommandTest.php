<?php

namespace AcquiaLogstream\Tests;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use PHPUnit\Framework\TestCase;
use AcquiaLogstream\LogstreamCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Class LogstreamCommandTest
 */
class LogstreamCommandTest extends TestCase
{

    public function testEmptyCommand()
    {
        $application = new Application('LogstreamTest');
        $application->add(new LogstreamCommand());
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run(['acquia:logstream']);
        $realOutput = $tester->getDisplay();

        $expectedOutput = <<< OUTPUT

                                                                   
  Not enough arguments (missing: "key, secret, environmentUuid").  
                                                                   

acquia:logstream [-t|--logtypes LOGTYPES] [-s|--servers SERVERS] [-c|--colourise] [--] <key> <secret> <environmentUuid>

OUTPUT;

        $this->assertEquals($expectedOutput . PHP_EOL, $realOutput);
    }

    public function testCommandHelp()
    {
        $application = new Application('LogstreamTest');
        $application->add(new LogstreamCommand());
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run(['acquia:logstream', '--help']);
        $realOutput = $tester->getDisplay();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expectedOutput = <<< OUTPUT
Description:
  Streams logs directly from the Acquia Cloud

Usage:
  acquia:logstream [options] [--] <key> <secret> <environmentUuid>
  logstream
  stream

Arguments:
  key                      Acquia API key
  secret                   Acquia API secret
  environmentUuid          UUID of the environment to stream

Options:
  -t, --logtypes=LOGTYPES  Log types to stream [default: ["bal-request","varnish-request","apache-request","apache-error","php-error","drupal-watchdog","drupal-request","mysql-slow"]] (multiple values allowed)
  -s, --servers=SERVERS    Servers to stream logs from e.g. web-1234. (multiple values allowed)
  -c, --colourise          Colorise the output based on HTTP status code.
  -h, --help               Display this help message
  -q, --quiet              Do not output any message
  -V, --version            Display this application version
      --ansi               Force ANSI output
      --no-ansi            Disable ANSI output
  -n, --no-interaction     Do not ask any interactive question
  -v|vv|vvv, --verbose     Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
OUTPUT;
        // phpcs:enable

        $this->assertEquals($expectedOutput . PHP_EOL, $realOutput);
    }
}
