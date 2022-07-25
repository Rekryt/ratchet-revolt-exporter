<?php
/**
 * RevoltPHP Ratchet Prometheus Exporter
 *
 * @package RatchetRevoltExporter
 * @author Krupkin Sergey <rekrytkw@gmail.com>
 */
namespace RatchetRevoltExporter;

// Load dependencies
require_once 'vendor/autoload.php';

use Doctrine\Common\Collections\ArrayCollection;
use Prometheus\CollectorRegistry;
use Psr\Http\Message\ResponseInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use React\Http\Browser;
use React\Socket\SocketServer;
use Amp\ReactAdapter\ReactAdapter;

$host = isset($_ENV['HOST']) ? $_ENV['HOST'] : '0.0.0.0';
$port = isset($_ENV['HTTP_PORT']) ? $_ENV['HTTP_PORT'] : 80;

// Get react Loop over amphp Loop
$loop = ReactAdapter::get();

/**
 * Set redis options
 * @see https://github.com/promphp/prometheus_client_php#usage
 */
RedisStorage::setDefaultOptions([
    'host' => $_ENV['REDIS_HOST'] ? $_ENV['REDIS_HOST'] : 'redis',
    'port' => $_ENV['REDIS_PORT'] ? $_ENV['REDIS_PORT'] : 6379,
    'password' => $_ENV['REDIS_PASSWORD'] ? $_ENV['REDIS_PASSWORD'] : null,
    'database' => $_ENV['REDIS_DB'] ? $_ENV['REDIS_DB'] : 0,
    'timeout' => 0.1, // in seconds
    'read_timeout' => '10', // in seconds
    'persistent_connections' => false,
]);

/**
 * Set mysql options
 */
DB::setDefaultOptions([
    'host' => $_ENV['MYSQL_HOST'] ? $_ENV['MYSQL_HOST'] : 'mysql',
    'port' => $_ENV['MYSQL_PORT'] ? $_ENV['MYSQL_PORT'] : '3306',
    'user' => $_ENV['MYSQL_USER'] ? $_ENV['MYSQL_USER'] : 'mysql',
    'password' => $_ENV['MYSQL_PASSWORD'] ? $_ENV['MYSQL_PASSWORD'] : 'mysql',
    'db' => $_ENV['MYSQL_DB'] ? $_ENV['MYSQL_DB'] : 'db',
]);

try {
    $registry = new CollectorRegistry(new RedisStorage(), false);
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    exit();
}

// Create socket and http server
$socket = new SocketServer($host . ':' . $port, [], $loop);
$http = new HttpServer(new HTTPRegistryServer($registry));

// Add HTTP server to loop
new IoServer($http, $socket, $loop);
echo 'Listening on: ' . $host . ':' . $port . PHP_EOL;

// Example gauge from mysql
$gauge = $registry->getOrRegisterGauge('rpe', 'testTable', 'Value from mysql');
$loop->addPeriodicTimer(1, function () use ($gauge) {
    $query = new Query();
    $query
        ->select('value')
        ->from('testTable')
        ->fetch()
        ->then(function (ArrayCollection $result) use ($gauge) {
            $query = new Query();
            $newValue = $result->first()['value'] + 1;
            $query
                ->update('testTable')
                ->set(['value' => $newValue])
                ->execute()
                ->then(function () use ($newValue, $gauge) {
                    $gauge->set($newValue);
                    echo $newValue . PHP_EOL;
                });
        });
});

// Example gauge from http
$client = new Browser(null, $loop);
$rates = $registry->getOrRegisterGauge(
    'rpe',
    'rates',
    'Value from http api ' . 'https://api.exchangerate.host/latest',
    ['ticker']
);
$loop->addPeriodicTimer(5, function () use ($client, $rates) {
    $client->get('https://api.exchangerate.host/latest')->then(function (ResponseInterface $response) use ($rates) {
        $json = json_decode((string) $response->getBody());
        foreach ($json->rates as $ticker => $rate) {
            $rates->set($rate, [$ticker]);
        }
    });
});

$loop->addSignal(SIGTERM, function () use ($loop) {
    $loop->stop(); // Gracefully stopping
});

// Start
$loop->run();
