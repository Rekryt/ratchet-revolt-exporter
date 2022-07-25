<?php
namespace RatchetRevoltExporter;

use Exception;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\StorageException;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;

/**
 * Class HttpRegistryServer
 * @package RatchetRevoltExporter
 */
class HTTPRegistryServer implements HttpServerInterface {
    /**
     * @var CollectorRegistry
     */
    protected CollectorRegistry $registry;

    /**
     * HttpRegistryServer constructor
     *
     * @param CollectorRegistry $collectorRegistry
     */
    public function __construct(CollectorRegistry $collectorRegistry) {
        $this->registry = $collectorRegistry;
    }

    /**
     * @param ConnectionInterface $conn
     * @param RequestInterface|null $request
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null) {
        $renderer = new RenderTextFormat();
        try {
            $body = $renderer->render($this->registry->getMetricFamilySamples());
            $e = "\r\n";
            # prettier-ignore
            $headers = [
                'HTTP/1.1 200 OK',
                'Date: ' . date('D') . ', ' . date('m') . ' ' . date('M') . ' ' . date('Y') . ' ' . date('H:i:s') . ' GMT',
                'Server: RatchetRevoltExporter',
                'Connection: close',
                'Content-Type: ' . RenderTextFormat::MIME_TYPE,
                'Content-Length: ' . strlen($body),
            ];
        } catch (\Exception $e) {
            $headers = ['HTTP/1.1 500'];
            $body = '';
            echo $e->getMessage() . PHP_EOL;
        }

        $headers = implode($e, $headers) . $e . $e;

        $conn->send($headers . $body);
        $conn->close();
    }

    /**
     * @param ConnectionInterface $conn
     */
    function onClose(ConnectionInterface $conn) {
    }

    /**
     * @param ConnectionInterface $conn
     * @param Exception $e
     */
    function onError(ConnectionInterface $conn, Exception $e) {
    }

    /**
     * @param ConnectionInterface $from
     * @param string $msg
     */
    function onMessage(ConnectionInterface $from, $msg) {
    }
}
