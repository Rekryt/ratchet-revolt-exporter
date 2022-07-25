<?php
namespace RatchetRevoltExporter;

use Prometheus\CollectorRegistry;
use React\EventLoop\Timer\Timer;

/**
 * Class Register
 * @package RatchetPrometheusExporter
 */
class Register {
    /**
     * @var CollectorRegistry
     */
    protected CollectorRegistry $registry;

    /**
     * @var string
     */
    protected string $namespace;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $help;

    /**
     * @var array
     */
    protected array $labels;

    /**
     * @var object e.g. {value: 1, labels: ['blue']}
     */
    protected object $count;

    /**
     * @var float
     */
    private float $lastValue;

    /**
     * Counter constructor.
     * @param CollectorRegistry $registry
     * @param $namespace
     * @param $name
     * @param string $help
     * @param array $labels
     * @param object|null $count
     */
    public function __construct(
        CollectorRegistry $registry,
        $namespace,
        $name,
        $help = '',
        $labels = [],
        $count = null
    ) {
        $this->registry = $registry;
        $this->namespace = $namespace;
        $this->name = $name;
        $this->help = $help;
        $this->labels = $labels;
        $this->count = $count ? $count : (object) ['value' => 1, 'labels' => []];
    }

    /**
     * @param Timer $timer
     */
    public function execute(Timer $timer) {
        if (isset($this->count->value)) {
            if ($_ENV['DEBUG'] == 'true') {
                $this->log();
            }
        }
    }

    /**
     * @return float|int
     */
    protected function getValue(): float {
        $lastValue = $this->count->value;
        if (is_string($this->count->value)) {
            $lastValue = shell_exec($this->count->value);
        }
        $this->lastValue = (float) $lastValue;
        return $this->lastValue;
    }

    /**
     * Log to console
     */
    protected function log() {
        echo json_encode(
            (object) [
                'date' => date_create()->format('Y-m-d H:i:s'),
                'namespace' => $this->namespace,
                'name' => $this->name,
                'labels' => implode(',', $this->count->labels),
                'metric' => get_class($this),
                'value' => $this->lastValue,
            ]
        ) . PHP_EOL;
    }
}
