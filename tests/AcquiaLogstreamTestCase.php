<?php

namespace AcquiaLogstream\Tests;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use PHPUnit\Framework\TestCase;
use AcquiaLogstream\LogstreamManager;

/**
 * Class AcquiaLogstreamTestCase
 */
abstract class AcquiaLogstreamTestCase extends TestCase
{

    protected $output;
    protected $logstream;

    public function setUp(): void
    {
        $input = new ArgvInput();
        $this->output = new BufferedOutput();
        $params = new \stdClass();
        $params->site = 'site';
        $params->t = 't';
        $params->environment = 'environment';
        $params->hmac = 'hmac';

        $this->logstream = new LogstreamManager($input, $this->output);
        $this->logstream->setParams($params);
    }

    public function getPrivateProperty($class, $propertyName): \ReflectionProperty
    {
        $reflector = new \ReflectionClass(get_class($class));
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);

        return $property;
    }

    protected function setPrivateProperty($class, $propertyName, $value): void
    {
        $reflection = new \ReflectionProperty(get_class($class), $propertyName);
        $reflection->setAccessible(true);
        $reflection->setValue($class, $value);
    }

    protected function invokeMethod(&$class, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($class));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($class, $parameters);
    }
}
