<?php
use PHPUnit\Framework\TestCase;
use CouncilDebtCounters\Counter_Manager;

class CounterManagerTest extends TestCase
{
    public function test_seconds_since_fy_start()
    {
        date_default_timezone_set('UTC');
        $now = time();
        $year = date('Y', $now);
        $start = strtotime("$year-04-01");
        if ($now < $start) {
            $start = strtotime(($year - 1) . '-04-01');
        }
        $end = strtotime((date('Y', $start) + 1) . '-04-01');
        $elapsed = max(0, $now - $start);
        $expected = min($elapsed, $end - $start);
        $this->assertSame($expected, Counter_Manager::seconds_since_fy_start());
    }
}

