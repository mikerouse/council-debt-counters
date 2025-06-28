<?php
use PHPUnit\Framework\TestCase;
use CouncilDebtCounters\Docs_Manager;

require_once __DIR__ . '/../includes/class-docs-manager.php';

class DocsManagerTest extends TestCase {
    public function test_financial_years_include_future() {
        update_option('cdc_financial_years', null);
        update_option('cdc_default_financial_year', '2023/24');
        $years = Docs_Manager::financial_years();
        $this->assertContains('2024/25', $years);
        $this->assertContains('2025/26', $years);
        $this->assertGreaterThanOrEqual(12, count($years));
    }
}
