<?php
namespace RatchetRevoltExporter\Registry;

use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use RatchetRevoltExporter\Register;
use React\EventLoop\Timer\Timer;

/**
 * Class Counter
 * @package RatchetRevoltExporter\Registry
 */
class Counter extends Register {
    /**
     * @var \Prometheus\Counter
     */
    protected \Prometheus\Counter $counter;

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
        $this->counter = $registry->getOrRegisterCounter($namespace, $name, $help, $labels);

        parent::__construct($registry, $namespace, $name, $help, $labels, $count);
    }

    /**
     * @param Timer $timer
     */
    public function execute(Timer $timer) {
        if (isset($this->count->value)) {
            $this->counter->incBy($this->getValue(), $this->count->labels);
        }
        parent::execute($timer);
    }
}
