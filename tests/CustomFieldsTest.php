<?php

require_once __DIR__ . '/../includes/class-custom-fields.php';
use PHPUnit\Framework\TestCase;
use CouncilDebtCounters\Custom_Fields;

class FakeWpdb {
    public $prefix = 'wp_';
    public $fields = [];
    public $values = [];
    private $field_auto = 1;
    private $value_auto = 1;

    public function prepare($query, ...$args) {
        foreach ($args as $arg) {
            $query = preg_replace('/%d|%s/', is_int($arg) ? $arg : "'" . $arg . "'", $query, 1);
        }
        return $query;
    }

    public function insert($table, $data, $format = null) {
        if (strpos($table, Custom_Fields::TABLE_FIELDS) !== false) {
            $data['id'] = $this->field_auto++;
            $this->fields[$data['id']] = $data;
        } else {
            $data['id'] = $this->value_auto++;
            if (!isset($data['financial_year'])) {
                $data['financial_year'] = \CouncilDebtCounters\Docs_Manager::current_financial_year();
            }
            $this->values[$data['id']] = $data;
        }
    }

    public function update($table, $data, $where, $data_format = null, $where_format = null) {
        $id = $where['id'];
        if (strpos($table, Custom_Fields::TABLE_VALUES) !== false) {
            foreach ($this->values as &$row) {
                if ($row['id'] == $id) {
                    $row = array_merge($row, $data);
                }
            }
        } else {
            foreach ($this->fields as &$row) {
                if ($row['id'] == $id) {
                    $row = array_merge($row, $data);
                }
            }
        }
    }

    public function get_row($query) {
        if (preg_match('/FROM \w*' . Custom_Fields::TABLE_FIELDS . " WHERE name = '([^']+)'/", $query, $m)) {
            foreach ($this->fields as $row) {
                if ($row['name'] === $m[1]) {
                    return (object) $row;
                }
            }
        }
        if (preg_match('/FROM \w*' . Custom_Fields::TABLE_FIELDS . ' WHERE id = (\d+)/', $query, $m)) {
            return isset($this->fields[$m[1]]) ? (object) $this->fields[$m[1]] : null;
        }
        return null;
    }

    public function get_var($query) {
        if (preg_match("/SELECT id FROM \w*" . Custom_Fields::TABLE_VALUES . " WHERE council_id = (\\d+) AND field_id = (\\d+)(?: AND financial_year = '([^']+)')?/", $query, $m)) {
            foreach ($this->values as $row) {
                if ($row['council_id'] == $m[1] && $row['field_id'] == $m[2] && (!isset($m[3]) || $row['financial_year'] == $m[3])) {
                    return $row['id'];
                }
            }
            return null;
        }
        if (preg_match("/SELECT value FROM \w*" . Custom_Fields::TABLE_VALUES . " WHERE council_id = (\\d+) AND field_id = (\\d+)(?: AND financial_year = '([^']+)')?/", $query, $m)) {
            foreach ($this->values as $row) {
                if ($row['council_id'] == $m[1] && $row['field_id'] == $m[2] && (!isset($m[3]) || $row['financial_year'] == $m[3])) {
                    return $row['value'];
                }
            }
            return null;
        }
        return null;
    }
}

class CustomFieldsTest extends TestCase {
    private $wpdb;

    protected function setUp(): void {
        global $wpdb;
        $this->wpdb = new FakeWpdb();
        $wpdb = $this->wpdb;
    }

    public function test_serialized_values_are_returned_as_original() {
        Custom_Fields::add_field('test_field', 'Test Field', 'text');

        $array = ['foo' => 'bar'];
        $object = (object)['baz' => 123];

        $this->assertTrue(Custom_Fields::update_value(1, 'test_field', $array));
        $this->assertSame($array, Custom_Fields::get_value(1, 'test_field'));

        $this->assertTrue(Custom_Fields::update_value(1, 'test_field', $object));
        $this->assertEquals($object, Custom_Fields::get_value(1, 'test_field'));
    }

    public function test_values_are_separate_per_year() {
        Custom_Fields::add_field('year_field', 'Year Field', 'text');

        $this->assertTrue(Custom_Fields::update_value(1, 'year_field', 'old', '2022/23'));
        $this->assertTrue(Custom_Fields::update_value(1, 'year_field', 'new', '2023/24'));

        $this->assertSame('old', Custom_Fields::get_value(1, 'year_field', '2022/23'));
        $this->assertSame('new', Custom_Fields::get_value(1, 'year_field', '2023/24'));
    }
}