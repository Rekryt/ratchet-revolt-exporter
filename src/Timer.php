<?php
namespace RatchetRevoltExporter;

use Amp\ReactAdapter\ReactAdapter;
use Error;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricsRegistrationException;
use React\EventLoop\Loop;

/**
 * Class Timer
 * @package RatchetPrometheusExporter
 */
class Timer {
    /**
     * @var CollectorRegistry
     */
    private CollectorRegistry $registry;
    /**
     * @var float
     */
    private float $interval;
    /**
     * @var Registry\Counter[]
     */
    private array $counters;
    /**
     * @var Registry\Gauge[]
     */
    private array $gauges;
    /**
     * @var Registry\Histogram[]
     */
    private array $histograms;
    /**
     * @var Registry\Summary[]
     */
    private array $summaries;

    /**
     * Timer constructor.
     * @param CollectorRegistry $registry
     * @param object $params
     * @throws MetricsRegistrationException
     */
    public function __construct(CollectorRegistry $registry, object $params) {
        $loop = ReactAdapter::get();
        $this->registry = $registry;

        if (!is_numeric($params->interval)) {
            throw new Error('Timer interval must be float or integer');
        }
        $this->interval = (float) $params->interval;

        if (isset($params->counters)) {
            if (!is_array($params->counters)) {
                throw new Error('Timer counters must be array');
            }
            foreach ($params->counters as $item) {
                if (isset($item->labels) && !is_array($item->labels)) {
                    throw new Error('Timer counter labels must be array');
                }
                $counter = new Registry\Counter(
                    $registry,
                    $item->namespace,
                    $item->name,
                    $item->help,
                    $item->labels,
                    $item->count
                );
                $this->counters[] = $counter;
                $loop->addPeriodicTimer($this->interval, function (\React\EventLoop\Timer\Timer $timer) use ($counter) {
                    $counter->execute($timer);
                });
            }
        }
        if (isset($params->gauges)) {
            if (!is_array($params->gauges)) {
                throw new Error('Timer gauges must be array');
            }
            foreach ($params->gauges as $item) {
                if (isset($item->labels) && !is_array($item->labels)) {
                    throw new Error('Timer gauge labels must be array');
                }
                $gouge = new Registry\Gauge(
                    $registry,
                    $item->namespace,
                    $item->name,
                    $item->help,
                    $item->labels,
                    $item->count
                );
                $this->gauges[] = $gouge;
                $loop->addPeriodicTimer($this->interval, function (\React\EventLoop\Timer\Timer $timer) use ($gouge) {
                    $gouge->execute($timer);
                });
            }
        }

        if (isset($params->histograms)) {
            if (!is_array($params->histograms)) {
                throw new Error('Timer histograms must be array');
            }
            foreach ($params->histograms as $item) {
                if (isset($item->labels) && !is_array($item->labels)) {
                    throw new Error('Timer histogram labels must be array');
                }
                $histogram = new Registry\Histogram(
                    $registry,
                    $item->namespace,
                    $item->name,
                    $item->help,
                    $item->labels,
                    $item->buckets,
                    $item->count
                );
                $this->histograms[] = $histogram;
                $loop->addPeriodicTimer($this->interval, function (\React\EventLoop\Timer\Timer $timer) use (
                    $histogram
                ) {
                    $histogram->execute($timer);
                });
            }
        }

        if (isset($params->summaries)) {
            if (!is_array($params->summaries)) {
                throw new Error('Timer summaries must be array');
            }
            foreach ($params->summaries as $item) {
                if (isset($item->labels) && !is_array($item->labels)) {
                    throw new Error('Timer summary labels must be array');
                }
                $summary = new Registry\Summary(
                    $registry,
                    $item->namespace,
                    $item->name,
                    $item->help,
                    $item->labels,
                    $item->maxAgeSeconds,
                    $item->quantiles,
                    $item->count
                );
                $this->summaries[] = $summary;
                $loop->addPeriodicTimer($this->interval, function (\React\EventLoop\Timer\Timer $timer) use ($summary) {
                    $summary->execute($timer);
                });
            }
        }
    }
}
