<?php

namespace AcquiaLogstream\Tests;

use Symfony\Component\Console\Output\OutputInterface;

class LogstreamManagerTest extends AcquiaLogstreamTestCase
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
        $this->logstream->setLogTypeFilter(['apache-request']);
        $property = $this->getPrivateProperty($this->logstream, 'logTypes');

        $this->assertSame($property->getValue($this->logstream), [0 => 'apache-request']);
    }

    public function testSetInvalidLogTypeFilter()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Invalid log type: (apache-access)');
        $this->logstream->setLogTypeFilter(['apache-access']);
    }

    public function testGetLogTypeFilter()
    {
        $property = $this->setPrivateProperty($this->logstream, 'logTypes', ['apache-request']);
        $this->assertSame($this->logstream->getLogTypeFilter(), [0 => 'apache-request']);
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

    public function testPickColour()
    {
        // Test without colourisation.
        $message = new \stdClass();
        $message->log_type = 'apache-request';
        $colour = $this->invokeMethod($this->logstream, 'pickColour', [$message]);
        $this->assertEquals('/', $colour);

        // Turn on colourisation and check a sample of log types
        $this->logstream->setColourise(true);
        $colour = $this->invokeMethod($this->logstream, 'pickColour', [$message]);
        $this->assertEquals('fg=yellow', $colour);

        $message->log_type = 'apache-error';
        $colour = $this->invokeMethod($this->logstream, 'pickColour', [$message]);
        $this->assertEquals('fg=red', $colour);

        $message->log_type = 'varnish-request';
        $colour = $this->invokeMethod($this->logstream, 'pickColour', [$message]);
        $this->assertEquals('fg=green', $colour);

        $this->logstream->setColourise(false);
    }

    public function testProcessMessage()
    {
        // Check available response
        $message = new \stdClass();
        $message->server = 'bal-12345';
        $message->cmd = 'available';
        $message->type = 'bal-request';
        $response = $this->invokeMethod($this->logstream, 'processMessage', [json_encode($message)]);
        $this->assertEquals('{"cmd":"enable","type":"bal-request","server":"bal-12345"}', $response);

        // Check line response
        $message1 = new \stdClass();
        $message1->server = 'bal-12345';
        $message1->cmd = 'line';
        $message1->log_type = 'varnish-request';
        // phpcs:disable Generic.Files.LineLength.TooLong
        $message1->text = '{"time": "[31/Mar/2020:02:42:43 +0000]", "status": "200", "bytes": "28", "method": "POST", "host": "www.example.com.au", "url": "/node/101/edit", "query": "?ajax_form=1&_wrapper_format=drupal_ajax", "referrer": "https://www.example.com.au/node/101/edit", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36", "client_ip": "127.0.0.1", "time_firstbyte": "0.251411", "hitmiss": "miss", "handling": "pass", "forwarded_for": "127.0.0.2, 127.0.0.3", "request_id": "v-4c0f50ba-72f9-11ea-9b7e-23cb5f242a04", "ah_log": "", "ah_application_id": "5a367d31-ca9e-4ee4-aa42-e5a6f50e108a", "ah_environment": "prod", "ah_trace_id": "XoKuI6wQADYAADV2x7oAAAAU"}"';
        // phpcs:enable
        $this->invokeMethod($this->logstream, 'processMessage', [json_encode($message1)]);
        // phpcs:disable Generic.Files.LineLength.TooLong
        $lineResponse = <<< LINE
{"time": "[31/Mar/2020:02:42:43 +0000]", "status": "200", "bytes": "28", "method": "POST", "host": "www.example.com.au", "url": "/node/101/edit", "query": "?ajax_form=1&_wrapper_format=drupal_ajax", "referrer": "https://www.example.com.au/node/101/edit", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36", "client_ip": "127.0.0.1", "time_firstbyte": "0.251411", "hitmiss": "miss", "handling": "pass", "forwarded_for": "127.0.0.2, 127.0.0.3", "request_id": "v-4c0f50ba-72f9-11ea-9b7e-23cb5f242a04", "ah_log": "", "ah_application_id": "5a367d31-ca9e-4ee4-aa42-e5a6f50e108a", "ah_environment": "prod", "ah_trace_id": "XoKuI6wQADYAADV2x7oAAAAU"}"
LINE;
        // phpcs:enable
        $this->assertEquals($lineResponse . PHP_EOL, $this->output->fetch());

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $this->invokeMethod($this->logstream, 'processMessage', [json_encode($message1)]);
        // phpcs:disable Generic.Files.LineLength.TooLong
        $lineResponse = <<< LINE
varnish-request bal-12345 {"time": "[31/Mar/2020:02:42:43 +0000]", "status": "200", "bytes": "28", "method": "POST", "host": "www.example.com.au", "url": "/node/101/edit", "query": "?ajax_form=1&_wrapper_format=drupal_ajax", "referrer": "https://www.example.com.au/node/101/edit", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36", "client_ip": "127.0.0.1", "time_firstbyte": "0.251411", "hitmiss": "miss", "handling": "pass", "forwarded_for": "127.0.0.2, 127.0.0.3", "request_id": "v-4c0f50ba-72f9-11ea-9b7e-23cb5f242a04", "ah_log": "", "ah_application_id": "5a367d31-ca9e-4ee4-aa42-e5a6f50e108a", "ah_environment": "prod", "ah_trace_id": "XoKuI6wQADYAADV2x7oAAAAU"}"
LINE;
        // phpcs:enable
        $this->assertEquals($lineResponse . PHP_EOL, $this->output->fetch());

        $this->output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->invokeMethod($this->logstream, 'processMessage', [json_encode($message1)]);
        // phpcs:disable Generic.Files.LineLength.TooLong
        $lineResponse = <<< LINE
{"server":"bal-12345","cmd":"line","log_type":"varnish-request","text":"{\"time\": \"[31\/Mar\/2020:02:42:43 +0000]\", \"status\": \"200\", \"bytes\": \"28\", \"method\": \"POST\", \"host\": \"www.example.com.au\", \"url\": \"\/node\/101\/edit\", \"query\": \"?ajax_form=1&_wrapper_format=drupal_ajax\", \"referrer\": \"https:\/\/www.example.com.au\/node\/101\/edit\", \"user_agent\": \"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/80.0.3987.149 Safari\/537.36\", \"client_ip\": \"127.0.0.1\", \"time_firstbyte\": \"0.251411\", \"hitmiss\": \"miss\", \"handling\": \"pass\", \"forwarded_for\": \"127.0.0.2, 127.0.0.3\", \"request_id\": \"v-4c0f50ba-72f9-11ea-9b7e-23cb5f242a04\", \"ah_log\": \"\", \"ah_application_id\": \"5a367d31-ca9e-4ee4-aa42-e5a6f50e108a\", \"ah_environment\": \"prod\", \"ah_trace_id\": \"XoKuI6wQADYAADV2x7oAAAAU\"}\""}
LINE;
        // phpcs:enable
        $this->assertEquals($lineResponse . PHP_EOL, $this->output->fetch());

        // Check enable response
        $message2 = new \stdClass();
        $message2->server = 'bal-12345';
        $message2->cmd = 'success';
        $message2->msg = new \stdClass();
        $message2->msg->cmd = 'enable';
        $message2->msg->type = 'bal-request';
        $message2->msg->server = 'bal-12345';
        $this->invokeMethod($this->logstream, 'processMessage', [json_encode($message2)]);
        $this->assertEquals('', $this->output->fetch());

        $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $this->invokeMethod($this->logstream, 'processMessage', [json_encode($message2)]);

        $lineResponse = <<< LINE
{"server":"bal-12345","cmd":"success","msg":{"cmd":"enable","type":"bal-request","server":"bal-12345"}}
LINE;

        $this->assertEquals($lineResponse . PHP_EOL, $this->output->fetch());

        // Check connected response
        $message3 = new \stdClass();
        $message3->server = 'bal-12345';
        $message3->cmd = 'connected';
        $this->output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
        $this->invokeMethod($this->logstream, 'processMessage', [json_encode($message3)]);
        $this->assertEquals('', $this->output->fetch());

        $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        $this->invokeMethod($this->logstream, 'processMessage', [json_encode($message3)]);

        $lineResponse = <<< LINE
{"server":"bal-12345","cmd":"connected"}
LINE;

        $this->assertEquals($lineResponse . PHP_EOL, $this->output->fetch());

        // Check error response
        $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

        $message = new \stdClass();
        $message->cmd = 'error';
        $message->msg = 'error';
        $this->invokeMethod($this->logstream, 'processMessage', [json_encode($message)]);
        $this->assertEquals('{"cmd":"error","msg":"error"}' . PHP_EOL, $this->output->fetch());
    }

    public function testAuthArray()
    {
        $authArray = [
            'site' => 'site',
            'd' => 'hmac',
            't' => 't',
            'env' => 'environment',
            'cmd' => 'stream-environment'
        ];

        $response = $this->invokeMethod($this->logstream, 'getAuthArray', []);
        $this->assertEquals($authArray, $response);
    }

    public function testSetParamsException()
    {
        $params = new \stdClass();
        $params->site = 'site';
        $params->t = 't';
        $params->environment = 'environment';

        $this->expectException('Exception');
        $this->expectExceptionMessage('Missing parameter: (hmac)');
        $this->logstream->setParams($params);
    }
}
