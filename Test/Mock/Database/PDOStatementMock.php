<?php

namespace YapepBase\Test\Mock\Database;

class PDOStatementMock extends \PDOStatement {
    protected $data;
    public function __construct($data) {
        $this->data = $data;
    }

    public function fetch($fetch_style = \PDO::ATTR_DEFAULT_FETCH_MODE, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
        switch ($fetch_style) {
            case \PDO::FETCH_ASSOC:
                $result = current($this->data);
                next($this->data);
                return $result;
            default:
                throw new \YapepBase\Exception\NotImplementedException();
        }
    }

    public function fetchColumn($column_number = 0) {
        $row = array_values(current($this->data));
        next($this->data);
        return $row[$column_number];
    }

    public function fetchAll($fetch_style = \PDO::ATTR_DEFAULT_FETCH_MODE, $fetch_argument = null, $ctor_args = array()) {
        switch ($fetch_style) {
            case \PDO::FETCH_ASSOC:
                return $this->data;
            default:
                throw new \YapepBase\Exception\NotImplementedException();
        }
    }
}