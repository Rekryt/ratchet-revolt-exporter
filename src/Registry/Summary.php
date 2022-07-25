<?php
namespace RatchetRevoltExporter\Registry;

use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use RatchetRevoltExporter\Register;
use React\EventLoop\Timer\Timer;

/**
 * Class Summary
 * @package RatchetRevoltExporter\Registry
 */
class Summary extends Register {
    /**
     * @var \Prometheus\Summary
     */
    private \Prometheus\Summary $collector;

    /**
     * Counter constructor.
     * @param CollectorRegistry $registry
     * @param string $namespace
     * @param string $name
     * @param string $help
     * @param array $labels
     * @param int $maxAgeSeconds
     * @param array|null $quantiles
     * @param object|null $count
     * @throws MetricsRegistrationException
     */
    public function __construct(
        CollectorRegistry $registry,
        string $namespace,
        string $name,
        string $help,
        array $labels = [],
        int $maxAgeSeconds = 600,
        array $quantiles = null,
        object $count = null
    ) {
        $this->collector = $registry->getOrRegisterSummary(
            $namespace,
            $name,
            $help,
            $labels,
            $maxAgeSeconds,
            $quantiles
        );

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
