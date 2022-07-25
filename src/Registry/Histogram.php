<?php
namespace RatchetRevoltExporter\Registry;

use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use RatchetRevoltExporter\Register;
use React\EventLoop\Timer\Timer;

/**
 * Class Histogram
 * @package RatchetRevoltExporter\Registry
 */
class Histogram extends Register {
    /**
     * @var \Prometheus\Histogram
     */
    private \Prometheus\Histogram $collector;

    /**
     * Counter constructor.
     * @param CollectorRegistry $registry
     * @param string $namespace
     * @param string $name
     * @param string $help
     * @param array $labels
     * @param array|null $buckets
     * @param object|null $count
     * @throws MetricsRegistrationException
     */
    public function __construct(
        CollectorRegistry $registry,
        string $namespace,
        string $name,
        string $help,
        array $labels = [],
        array $buckets = null,
        object $count = null
    ) {
        $this->collector = $registry->getOrRegisterHistogram($namespace, $name, $help, $labels, $buckets);

        parent::__construct($registry, $namespace, $name, $help, $labels, $count);
    }

    /**
     * @param Timer $timer
     */
    public function execute(Timer $timer) {
        if (isset($this->count->value)) {
            $this->collector->observe($this->getValue(), $this->count->labels);
        }
        parent::execute($timer);
    }
}
