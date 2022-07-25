<?php

namespace RatchetRevoltExporter;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Psr\Http\Message\ResponseInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use React\Http\Browser;
use React\Socket\SocketServer;
use function Amp\async;
use function Amp\delay;

class SmokeTest extends RatchetRevoltExporterTestCase {
    public function testHTTPServer() {
        $registry = new CollectorRegistry(new InMemory(), false);
        // Add HTTP server to loop
        new IoServer(
            new HttpServer(new HTTPRegistryServer($registry)),
            new SocketServer('0.0.0.0:8080', [], $this->loop),
            $this->loop
        );

        $counter = $registry->getOrRegisterCounter('test', 'test', 'test');
        $counter->incBy(1);

        // Async test request
        async(function (): void {
            $client = new Browser(null, $this->loop);
            $client
                ->get('http://127.0.0.1:8080/metrics')
                ->then(function (ResponseInterface $response) {
                    $body = (string) $response->getBody();
                    $this->assertEquals("# HELP test_test test\n# TYPE test_test counter\ntest_test 1\n", $body);
                })
                ->then(function () {
                    $this->loop->stop();
                });
        });

        $this->loop->run();
    }
}
