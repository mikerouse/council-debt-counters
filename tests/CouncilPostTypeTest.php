<?php
use PHPUnit\Framework\TestCase;
use CouncilDebtCounters\Custom_Fields;
use CouncilDebtCounters\Council_Post_Type;
use CouncilDebtCounters\CDC_Utils;

require_once __DIR__ . '/../includes/class-custom-fields.php';
require_once __DIR__ . '/../includes/class-council-post-type.php';
require_once __DIR__ . '/../includes/class-cdc-utils.php';

class CouncilPostTypeTest extends TestCase {
    protected function setUp(): void {
        global $wpdb;
        $wpdb = new WPDBStub();
        Custom_Fields::add_field('current_liabilities','Current','money');
        Custom_Fields::add_field('long_term_liabilities','Long','money');
        Custom_Fields::add_field('finance_lease_pfi_liabilities','Lease','money');
        Custom_Fields::add_field('manual_debt_entry','Manual','money');
        Custom_Fields::add_field('total_debt','Total Debt','money');
        Custom_Fields::add_field('non_council_tax_income','Non Council','money');
        Custom_Fields::add_field('council_tax_general_grants_income','CT Grants','money');
        Custom_Fields::add_field('government_grants_income','Gov Grants','money');
        Custom_Fields::add_field('all_other_income','Other','money');
        Custom_Fields::add_field('total_income','Total Income','money');
    }

    public function test_calculate_total_debt_skips_if_no_data() {
        $year = CDC_Utils::current_financial_year();
        Custom_Fields::update_value(1,'total_debt',123.0,$year);
        Council_Post_Type::calculate_total_debt(1,$year);
        $this->assertSame(123.0, Custom_Fields::get_value(1,'total_debt',$year));
    }

    public function test_calculate_total_debt_sums_values() {
        $year = CDC_Utils::current_financial_year();
        Custom_Fields::update_value(1,'current_liabilities',100,$year);
        Custom_Fields::update_value(1,'long_term_liabilities',50,$year);
        Council_Post_Type::calculate_total_debt(1,$year);
        $this->assertSame(150.0, Custom_Fields::get_value(1,'total_debt',$year));
    }

    public function test_calculate_total_income_sums_values() {
        $year = CDC_Utils::current_financial_year();
        Custom_Fields::update_value(1,'non_council_tax_income',100,$year);
        Custom_Fields::update_value(1,'council_tax_general_grants_income',50,$year);
        Custom_Fields::update_value(1,'government_grants_income',25,$year);
        Custom_Fields::update_value(1,'all_other_income',5,$year);
        Council_Post_Type::calculate_total_income(1,$year);
        $this->assertSame(180.0, Custom_Fields::get_value(1,'total_income',$year));
    }
}
