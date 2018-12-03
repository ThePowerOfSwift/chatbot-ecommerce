<?php

class Logs {
  public function __construct() {
    $this->db_logs = new DB(DB_LOGS_SERVER, DB_LOGS_USERNAME, DB_LOGS_PASSWORD, DB_LOGS_NAME);
  }

  function insertRequest($request=array(), $route) {
    $update_id = $request['update_id'];
    $message_id = $request['message']['message_id'];
    $user_id = $request['message']['from']['id'];
    $chat_id = $request['message']['chat']['id'];
    $type = $request['message']['chat']['type'];
    $first_name = mysql_escape_string($request['message']['from']['first_name']);
    $last_name = mysql_escape_string($request['message']['from']['last_name']);
    $username = mysql_escape_string($request['message']['from']['username']);
    $date = $request['message']['date'];
    if (isset($request['message']['text'])) {
      $text = mysql_escape_string($request['message']['text']);
    } else {
      $text = $request['message']['text'];
    }
    $full = mysql_escape_string(json_encode($request));

    $table = DB_BOTS_TABLE_REQUEST;
    $sql = "INSERT INTO `$table` (update_id, message_id, user_id, chat_id, type, first_name, last_name, username, date, text, command_type, full) VALUES ('{$update_id}', '{$message_id}', {$user_id}, {$chat_id}, '{$type}', '{$first_name}', '{$last_name}', '{$username}', '{$date}', '{$text}', '{$route}', '{$full}');";
    $ret = $this->db_logs->query($sql);
    return $ret;
  }

  function insertResponse($respons=array(), $route) {
    $text = ""; $video = ""; $audio = ""; $photo = "";
    if (isset($respons['result']['update_id'])) {
      $update_id = $respons['result']['update_id'];
    }
    if (isset($respons['result']['message_id'])) {
      $message_id = $respons['result']['message_id'];
    }
    if (isset($respons['result']['chat']['id'])) {
      $chat_id = $respons['result']['chat']['id'];
    }
    if (isset($respons['result']['chat']['first_name'])) {
      $first_name = $respons['result']['chat']['first_name'];
    }
    if (isset($respons['result']['chat']['last_name'])) {
      $last_name = $respons['result']['chat']['last_name'];
    }
    if (isset($respons['result']['chat']['username'])) {
      $username = $respons['result']['chat']['username'];
    }
    if (isset($respons['result']['date'])) {
      $date = $respons['result']['date'];
    }
    if (isset($respons['result']['text'])) {
      $text = mysql_escape_string($respons['result']['text']);
    } elseif (isset($respons['result']['video'])) {
      $video = mysql_escape_string(json_encode($respons['result']['video']));
    } elseif (isset($respons['result']['audio'])) {
      $audio = mysql_escape_string(json_encode($respons['result']['audio']));
    } elseif (isset($respons['result']['photo'])) {
      $photo = mysql_escape_string(json_encode($respons['result']['photo']));
    }
    
    $full = mysql_escape_string(json_encode($respons));
    
    $table = DB_BOTS_TABLE_RESPONSES;
    if ($text != "") {
      $sql = "INSERT INTO `$table` (update_id, message_id, chat_id, first_name, last_name, username, date, text, command_type, full) VALUES ('{$update_id}', '{$message_id}', {$chat_id}, '{$first_name}', '{$last_name}', '{$username}', '{$date}', '{$text}', '{$route}', '{$full}');";
    } elseif ($audio != "") {
      $sql = "INSERT INTO `$table` (update_id, message_id, chat_id, first_name, last_name, username, date, audio, command_type, full) VALUES ('{$update_id}', '{$message_id}', {$chat_id}, '{$first_name}', '{$last_name}', '{$username}', '{$date}', '{$audio}', '{$route}', '{$full}');";
    } elseif ($video != "") {
      $sql = "INSERT INTO `$table` (update_id, message_id, chat_id, first_name, last_name, username, date, video, command_type, full) VALUES ('{$update_id}', '{$message_id}', {$chat_id}, '{$first_name}', '{$last_name}', '{$username}', '{$date}', '{$video}', '{$route}', '{$full}');";
    } elseif ($photo != "") {
      $sql = "INSERT INTO `$table` (update_id, message_id, chat_id, first_name, last_name, username, date, photo, command_type, full) VALUES ('{$update_id}', '{$message_id}', {$chat_id}, '{$first_name}', '{$last_name}', '{$username}', '{$date}', '{$photo}', '{$route}', '{$full}');";
    }
    
    $ret = $this->db_logs->query($sql);
    return $ret;
  }
  
  function checkUser($userid) {
    $table = DB_BOTS_TABLE_USER;
    $sql = "select count(*) as count from `$table` where user_id = '{$userid}'";
    $result = $this->db_logs->fetch_assoc($this->db_logs->query($sql));
    return $result['count'];
  }
  
  function saveUser($user=array()) {
    $table = DB_BOTS_TABLE_USER;
    $userid = $user['id'];
    $first_name = mysql_escape_string($user['first_name']);
    $last_name = mysql_escape_string($user['last_name']);
    $username = mysql_escape_string($user['username']);
    $status = "active";
    $sql = "INSERT INTO `$table` (user_id, first_name, last_name, username, status) VALUES ({$userid}, '{$first_name}', '{$last_name}', '{$username}', '{$status}');";
    $ret = $this->db_logs->query($sql);
    return $ret;
  }
  
  function getUserStatus($userid) {
    $table = DB_BOTS_TABLE_USER;
    $sql = "SELECT * FROM `$table` where user_id = {$userid}";
    $result = $this->db_logs->fetch_assoc($this->db_logs->query($sql));
    return $result['status'];
  }
  
  function updateUserStatus($user=array(), $status) {
    $table = DB_BOTS_TABLE_USER;
    $userid = $user['id'];
    $sql = "UPDATE `$table` set status = '{$status}' where user_id = {$userid};";
    $ret = $this->db_logs->query($sql);
    return $ret;
  }
}