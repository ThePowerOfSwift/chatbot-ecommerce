<?php

class DataCenter {
    public function __construct() {
        $this->db_datacenter = new DB(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
    }

    function searchData($keyword) {
        $table = DB_TABLE_DATA;

        $sql = "SELECT * FROM `$table` where product_name like '%{$keyword}%';";
        $ret = $this->db_datacenter->query($sql);
        return $ret;
    }
}
?>