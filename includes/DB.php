<?php

class DB {
    var $link;
    var $error;

    function __construct($db_server='', $db_username='', $db_password='', $db_name='') {
        if ($db_server == '' || $db_username == '' || $db_password == '' || $db_name == '') {
          $this -> DB_Connect(DATABASE_SERVER, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);
        } else {
          $this -> DB_Connect($db_server, $db_username, $db_password, $db_name);
        }
    }

    function DB_Connect($database_server, $database_username, $database_password, $database_name) {
        if ($link = mysqli_connect($database_server, $database_username, $database_password, $database_name)) {
            mysqli_query($link, "SET NAMES 'utf8'");
            $this -> link = $link;
        } else
            die("Error " . mysqli_error($link));

    }

    function query($sql) {
        $res = mysqli_query($this -> link, $sql);
        if ($res) {
            $ret = $res;
        } else {
            $ret = false;
            $this -> error = "Failed to run query: (" . $this -> link -> errno . ") " . $this -> link -> error;
        }
        return $ret;
    }

    function fetch_assoc($result) {
        return mysqli_fetch_assoc($result);
    }

    function num_rows($result) {
        return mysqli_num_rows($result);
    }
}
?>