<?php
class WPDBStub {
    public $prefix = 'wp_';
    public $fields = [];
    public $values = [];
    private $nextFieldId = 1;
    private $nextValueId = 1;

    public function prepare($query, ...$args) {
        foreach ($args as $arg) {
            if (strpos($query, '%d') !== false) {
                $query = preg_replace('/%d/', (int)$arg, $query, 1);
                continue;
            }
            if (strpos($query, '%s') !== false) {
                $safe = str_replace("'", "''", $arg);
                $query = preg_replace('/%s/', "'" . $safe . "'", $query, 1);
            }
        }
        return $query;
    }

    public function get_row($query) {
        if (preg_match("/FROM {$this->prefix}" . \CouncilDebtCounters\Custom_Fields::TABLE_FIELDS . " WHERE name = '([^']+)'/i", $query, $m)) {
            $name = $m[1];
            foreach ($this->fields as $field) {
                if ($field['name'] === $name) {
                    return (object) $field;
                }
            }
        }
        return null;
    }

    public function get_var($query) {
        if (preg_match("/SELECT id FROM {$this->prefix}" . \CouncilDebtCounters\Custom_Fields::TABLE_VALUES . " WHERE council_id = (\d+) AND field_id = (\d+) AND financial_year = '([^']+)'/i", $query, $m)) {
            $cid = (int)$m[1];
            $fid = (int)$m[2];
            $fy  = $m[3];
            foreach ($this->values as $v) {
                if ($v['council_id'] == $cid && $v['field_id'] == $fid && $v['financial_year'] === $fy) {
                    return $v['id'];
                }
            }
            return null;
        }
        if (preg_match("/SELECT id FROM {$this->prefix}" . \CouncilDebtCounters\Custom_Fields::TABLE_VALUES . " WHERE council_id = (\d+) AND field_id = (\d+)/i", $query, $m)) {
            $cid = (int)$m[1];
            $fid = (int)$m[2];
            foreach ($this->values as $v) {
                if ($v['council_id'] == $cid && $v['field_id'] == $fid) {
                    return $v['id'];
                }
            }
            return null;
        }
        if (preg_match("/SELECT value FROM {$this->prefix}" . \CouncilDebtCounters\Custom_Fields::TABLE_VALUES . " WHERE council_id = (\d+) AND field_id = (\d+) AND financial_year = '([^']+)'/i", $query, $m)) {
            $cid = (int)$m[1];
            $fid = (int)$m[2];
            $fy  = $m[3];
            foreach ($this->values as $v) {
                if ($v['council_id'] == $cid && $v['field_id'] == $fid && $v['financial_year'] === $fy) {
                    return $v['value'];
                }
            }
            return null;
        }
        if (preg_match("/SELECT value FROM {$this->prefix}" . \CouncilDebtCounters\Custom_Fields::TABLE_VALUES . " WHERE council_id = (\d+) AND field_id = (\d+)/i", $query, $m)) {
            $cid = (int)$m[1];
            $fid = (int)$m[2];
            foreach ($this->values as $v) {
                if ($v['council_id'] == $cid && $v['field_id'] == $fid) {
                    return $v['value'];
                }
            }
            return null;
        }
        return null;
    }

    public function insert($table, $data, $format = null) {
        if (strpos($table, \CouncilDebtCounters\Custom_Fields::TABLE_FIELDS) !== false) {
            $data['id'] = $this->nextFieldId++;
            $this->fields[$data['id']] = $data;
            return 1;
        }
        if (strpos($table, \CouncilDebtCounters\Custom_Fields::TABLE_VALUES) !== false) {
            $data['id'] = $this->nextValueId++;
            $this->values[$data['id']] = $data;
            return 1;
        }
        return 0;
    }

    public function update($table, $data, $where, $format = null, $where_format = null) {
        if (strpos($table, \CouncilDebtCounters\Custom_Fields::TABLE_VALUES) !== false && isset($where['id'])) {
            $id = $where['id'];
            if (isset($this->values[$id])) {
                $this->values[$id] = array_merge($this->values[$id], $data);
                return 1;
            }
        }
        if (strpos($table, \CouncilDebtCounters\Custom_Fields::TABLE_FIELDS) !== false && isset($where['id'])) {
            $id = $where['id'];
            if (isset($this->fields[$id])) {
                $this->fields[$id] = array_merge($this->fields[$id], $data);
                return 1;
            }
        }
        return 0;
    }
}
