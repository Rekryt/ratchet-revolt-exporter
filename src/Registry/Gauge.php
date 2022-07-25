<?php
namespace RatchetRevoltExporter\Registry;

use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use RatchetRevoltExporter\Register;
use React\EventLoop\Timer\Timer;

/**
 * Class Gauge
 * @package RatchetRevoltExporter\Registry
 */
class Gauge extends Register {
    /**
     * @var \Prometheus\Gauge
     */
    private \Prometheus\Gauge $collector;

    /**
     * Counter constructor.
     * @param CollectorRegistry $registry
     * @param string $namespace
     * @param string $name
     * @param string $help
     * @param array $labels
     * @param object|null $count
     * @throws MetricsRegistrationException
     */
    public function __construct(
        CollectorRegistry $registry,
        string $namespace,
        string $name,
        string $help,
        array $labels = [],
        object $count = null
    ) {
        $this->collector = $registry->getOrRegisterGauge($namespace, $name, $help, $labels);

        parent::__construct($registry, $namespace, $name, $help, $labels, $count);
    }

    /**
     * @param Timer $timer
     */
    public function execute(Timer $timer) {
        if (isset($this->count->value)) {
            $this->collector->set($this->getValue(), $this->count->labels);
        }
        parent::execute($timer);
    }
}
