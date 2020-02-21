<?php

namespace AcquiaLogstream\Tests;

class LogstreamManagerTest extends CloudApiTestCase
{

    public function testSetLogServerFilter()
    {
        $property = $this->getPrivateProperty($this->logstream, 'servers');

        $this->logstream->setLogServerFilter(['srv-123']);
        $this->assertSame($property->getValue($this->logstream), [0 => 'srv-123']);

        $this->logstream->setLogServerFilter(['srv-123', 'web-123']);
        $this->assertSame($property->getValue($this->logstream), [0 => 'srv-123', 1 => 'web-123']);
    }

    public function testGetLogServerFilter()
    {
        $property = $this->setPrivateProperty($this->logstream, 'servers', ['srv-123']);
        $this->assertSame($this->logstream->getLogServerFilter(), [0 => 'srv-123']);

        $property = $this->setPrivateProperty($this->logstream, 'servers', ['srv-123', 'web-123']);
        $this->assertSame($this->logstream->getLogServerFilter(), [0 => 'srv-123', 1 => 'web-123']);
    }

    public function testSetLogTypeFilter()
    {
        $this->logstream->setLogTypeFilter(['apache-access']);
        $property = $this->getPrivateProperty($this->logstream, 'logTypes');

        $this->assertSame($property->getValue($this->logstream), [0 => 'apache-access']);
    }

    public function testGetLogTypeFilter()
    {
        $property = $this->setPrivateProperty($this->logstream, 'logTypes', ['apache-access']);
        $this->assertSame($this->logstream->getLogTypeFilter(), [0 => 'apache-access']);
    }

    public function testSetDns()
    {
        $this->logstream->setDns('1.0.1.0');
        $property = $this->getPrivateProperty($this->logstream, 'dns');

        $this->assertSame($property->getValue($this->logstream), '1.0.1.0');
    }

    public function testGetDns()
    {
        $property = $this->setPrivateProperty($this->logstream, 'dns', '1.0.1.0');
        $this->assertSame($this->logstream->getDns(), '1.0.1.0');
    }

    public function testSetTimeout()
    {
        $this->logstream->setTimeout(60);
        $property = $this->getPrivateProperty($this->logstream, 'timeout');

        $this->assertSame($property->getValue($this->logstream), 60);
    }

    public function testGetTimeout()
    {
        $property = $this->setPrivateProperty($this->logstream, 'timeout', 60);
        $this->assertSame($this->logstream->getTimeout(), 60);
    }

    public function testSetOutputColorisation()
    {
        $this->logstream->setColourise(true);
        $property = $this->getPrivateProperty($this->logstream, 'colourise');

        $this->assertSame($property->getValue($this->logstream), true);
    }

    public function testGetOutputColorisation()
    {
        $property = $this->setPrivateProperty($this->logstream, 'colourise', false);
        $this->assertSame($this->logstream->getColourise(), false);
    }
}
