<?php
use PHPUnit\Framework\TestCase;
use CouncilDebtCounters\Custom_Fields;
use CouncilDebtCounters\Shortcode_Renderer;
use CouncilDebtCounters\CDC_Utils;

require_once __DIR__ . '/../includes/class-custom-fields.php';
require_once __DIR__ . '/../includes/class-shortcode-renderer.php';
require_once __DIR__ . '/../includes/class-cdc-utils.php';
require_once __DIR__ . '/../includes/class-counter-manager.php';

class ShortcodeRenderTest extends TestCase {
    protected function setUp(): void {
        global $wpdb;
        $wpdb = new WPDBStub();
        Custom_Fields::add_field('interest_paid','Interest','money');
        Custom_Fields::add_field('total_debt','Total Debt','money');
        update_option('cdc_enabled_counters',['debt','interest']);
    }

    public function test_render_interest_missing_shows_alert(){
        $html = Shortcode_Renderer::render_interest_counter(['id'=>1]);
        $this->assertStringContainsString('alert', $html);
    }

    public function test_render_debt_counter_uses_value(){
        $year = CDC_Utils::current_financial_year();
        Custom_Fields::update_value(1,'total_debt',200,$year);
        $html = Shortcode_Renderer::render_debt_counter(['id'=>1]);
        $this->assertStringNotContainsString('No Total Debt figure found', $html);
    }
}
