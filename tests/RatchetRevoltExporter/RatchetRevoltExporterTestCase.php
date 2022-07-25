<?php
namespace RatchetRevoltExporter;

use Amp\ReactAdapter\ReactAdapter;
use PHPUnit\Framework\TestCase;
use Prometheus\Storage\Redis;
use React\EventLoop\LoopInterface;

class RatchetRevoltExporterTestCase extends TestCase {
    protected LoopInterface $loop;

    public function setUp(): void {
        $this->loop = ReactAdapter::get();
    }
}
