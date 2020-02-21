<?php

namespace AcquiaLogstream\Tests;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use PHPUnit\Framework\TestCase;
use AcquiaLogstream\LogstreamManager;

/**
 * Class CloudApiTestCase
 */
abstract class CloudApiTestCase extends TestCase
{

    protected $logstream;

    public function setUp() : void
    {
        $input = new ArgvInput();
        $output = new ConsoleOutput();
        $params = new \stdClass();
        $params->site = 'site';
        $params->t = 't';
        $params->environment = 'environment';
        $params->hmac = 'hmac';

        $this->logstream = new LogstreamManager($input, $output, $params);
    }

    public function getPrivateProperty($class, $propertyName) : \ReflectionProperty
    {
        $reflector = new \ReflectionClass(get_class($class));
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);

        return $property;
    }

    protected function setPrivateProperty($class, $propertyName, $value) : void
    {
        $reflection = new \ReflectionProperty(get_class($class), $propertyName);
        $reflection->setAccessible(true);
        $reflection->setValue($class, $value);
    }
}
