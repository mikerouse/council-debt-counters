<?php
use PHPUnit\Framework\TestCase;
use CouncilDebtCounters\Custom_Fields;

class CustomFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        global $wpdb;
        $wpdb = new WPDBStub();
    }

    public function test_update_and_get_value()
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . Custom_Fields::TABLE_FIELDS, [
            'name' => 'population',
            'label' => 'Population',
            'type' => 'number',
            'required' => 0,
        ]);

        $this->assertTrue(Custom_Fields::update_value(1, 'population', '12345'));
        $this->assertSame('12345', Custom_Fields::get_value(1, 'population'));

        $this->assertTrue(Custom_Fields::update_value(1, 'population', '67890'));
        $this->assertSame('67890', Custom_Fields::get_value(1, 'population'));
        $this->assertCount(1, $wpdb->values);
    }
}

