[![Packagist](https://img.shields.io/packagist/v/typhonius/acquia-logstream.svg)](https://packagist.org/packages/typhonius/acquia-logstream)
[![Build Status](https://travis-ci.org/typhonius/acquia-logstream.svg?branch=master)](https://travis-ci.org/typhonius/acquia-logstream)
[![Total Downloads](https://poser.pugx.org/typhonius/acquia-logstream/downloads.png)](https://packagist.org/packages/typhonius/acquia-logstream)
[![License](https://poser.pugx.org/typhonius/acquia-logstream/license.png)]()

# Acquia Logstream


## Pre-installation

1. Run `composer install`


## Generating an API access token

To generate an API access token, login to [https://cloud.acquia.com](https://cloud.acquia.com), then visit [https://cloud.acquia.com/#/profile/tokens](https://cloud.acquia.com/#/profile/tokens), and click ***Create Token***.


* Provide a label for the access token, so it can be easily identified. Click ***Create Token***.

* Once the token has been generated, copy the api key and api secret to a secure place. Make sure you record it now: you will not be able to retrieve this access token's secret again.

## Usage

### Standalone tools

*Phar file*
A Phar file will be attached to each tagged release on GitHub. This can be downloaded and used immediately with `php logstream.phar acquia:logstream`

*Shell script*
A shell script has been included as a wrapper for the logstream command which will allow users to run the logstream command directly without further requirements. This can be invoked by running `./bin/logstream acquia:logstream` from the cloned directory.

The Acquia Logstream command takes three required arguments and three non-required options.
 
  
  
### PHP Library

The `LogstreamManager` class can be included in any other PHP library as it has been within the [Acquia Cli](https://github.com/typhonius/acquia_cli) tool. The simplest method of including and calling this library is as follows:

````
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Logs;
use AcquiaLogstream\LogstreamManager;

$config = [
    'key' => 'FILL ME',
    'secret' => 'FILL ME'
];

$environmentUuid = 'FILL ME'

$connector = new Connector($config);
$client = Client::factory($connector);
$logs = new Logs($client);

$stream = $logs->stream($environmentUuid);
$params = $stream->logstream->params;

$logstream = new LogstreamManager($input, $output, $params);
$logstream->stream();
````

More advanced usage allows for different options to be set and configured through methods on the `LogstreamManager` class.


## Command Parameters

The Logstream command takes three required arguments as parameters.

* Your Acquia API key
* Your Acquia API secret
* The environment UUID

There are also three optional parameters which can be used to filter log types, servers to stream logs from, and to enable colourisation.


## Examples

````
# Show help
./bin/logstream acquia:logstream --help

# Stream all logs from all servers
./bin/logstream acquia:logstream APIKEY APISECRET ENVIRONMENTUUID

# Stream Varnish and NGINX logs 
./bin/logstream acquia:logstream APIKEY APISECRET ENVIRONMENTUUID -t bal-request -t varnish-request

# Stream PHP error and Apache error logs from one server
./bin/logstream acquia:logstream APIKEY APISECRET ENVIRONMENTUUID -t php-error -t apache-error -s web-1234

# Stream all logs with colourisation to determine which log type is used
./bin/logstream acquia:logstream APIKEY APISECRET ENVIRONMENTUUID -c
````