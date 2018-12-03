<?php
ini_set('max_execution_time', 300);
class TelegramView {
  public function __construct() {
    
  }
  
  public function apiRequestWebhook($method, $parameters) {
    if (!is_string($method)) {
      error_log("Method name must be a string\n");
      return false;
    }

    if (!$parameters) {
      $parameters = array();
    } else if (!is_array($parameters)) {
      error_log("Parameters must be an array\n");
      return false;
    }

    $parameters["method"] = $method;

    header("Content-Type: application/json");
    echo json_encode($parameters);

    return true;
  }

  public function apiRequest($method, $parameters) {
    if (!is_string($method)) {
      error_log("Method name must be a string\n");
      return false;
    }
    if (!$parameters) {
      $parameters = array();
    } else if (!is_array($parameters)) {
      error_log("Parameters must be an array\n");
      return false;
    }
    foreach ($parameters as $key => &$val) {
      // encoding to JSON array parameters, for example reply_markup
      if (!is_numeric($val) && !is_string($val)) {
        $val = json_encode($val);
      }
    }
    $url = "https://api.telegram.org/bot".BOT_TOKEN."/";
    $url .= $method."?".http_build_query($parameters);
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    $res = $this->exec_curl_request($handle);
    return $res;
  }

  function exec_curl_request($handle) {
    $response = curl_exec($handle);
  
    if ($response === false) {
      $errno = curl_errno($handle);
      $error = curl_error($handle);
      error_log("Curl returned error $errno: $error\n");
      curl_close($handle);
      return false;
    }
  
    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);
  
    if ($http_code >= 500) {
      // do not wat to DDOS server if something goes wrong
      sleep(10);
      return false;
    } else if ($http_code != 200) {
      $response = json_decode($response, true);
      error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
      if ($http_code == 401) {
        throw new Exception('Invalid access token provided');
      } else if ($http_code == 400) { //general error: message too long, message must be non-empty, illegal tag
        #throw new Exception('Error');
        //return "Error";
        return $response;
      } else if ($http_code == 403) { //bot kicked from group, trying to post to group
        #throw new Exception('Cannot access');
        //return "Cannot access";
        return $response;
      }
      //return "Error";
      return $response;
    } else { //200 = success
      $response = json_decode($response,true);
      /*if (isset($response['description'])) {
        error_log("Request was successfull: {$response['description']}\n");
      }*/
  //    $response = $response['result'];
      //file_put_contents('respons.txt', $response);
      return $response;
    }
  }
  
  private function curlFile($path)
  {
    if (is_array($path)) {
      if (!isset($path['file_id'])) {
        throw new Exception('Input file id');
      }
      return $path['file_id'];
    }
    $realPath = realpath($path);
    if (!$realPath) {
      throw new Exception('File not found');
    }
    if (class_exists('CURLFile')) {
      return new CURLFile($realPath);
    }
    return '@' . $realPath;
  }
  
  public function sendPhoto($chat_id, $img){
    $bot_url    = "https://api.telegram.org/bot".BOT_TOKEN."/";
    $url        = $bot_url . "sendPhoto?chat_id=" . $chat_id ;
    if ($img['source'] == "url") {
      $photo = $img['image'];
    } else {
      $photo = $this->curlFile($img['image']);
    }

    $post_fields = array('chat_id'   => $chat_id,
                         'photo'     => $photo
                        );
    
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type:multipart/form-data"
    ));
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    $output = curl_exec($ch);
    $arr_output = json_decode($output,true);
    return $arr_output;
  }

  public function sendMessage($chat_id, $text) {
    $request_url    = "https://api.telegram.org/bot".BOT_TOKEN."/sendMessage";
    
    $data = array(
        'chat_id' => $chat_id,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
        'text'  => $text
    );
    // use key 'http' even if you send the request to https://...
    $options = array(
      'http' => array(
          'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
          'method'  => 'POST',
          'content' => http_build_query($data),
      ),
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($request_url, false, $context);
    return $result;
  }

  public function telegramSendChatAction($chat_id, $action) {
    $data = array(
        'chat_id' => $chat_id,
        'action' => $action
    );

    $this->apiRequest("sendChatAction", $data);
  }

  public function telegramSendMesageKeyboard($chat_id, $message_id, $text, $keyboardbtn) {
    // = array(array('A', 'B'))
    $data = array(
        'chat_id' => $chat_id,
        'parse_mode' => 'HTML',
        'text'  => $text,
        'disable_web_page_preview' => false,
        'reply_markup' => array(
                            'keyboard' => $keyboardbtn,
                            'one_time_keyboard' => true,
                            'resize_keyboard' => true,
                            'selective' => false
                       )
    );

    $res = $this->apiRequest("sendMessage", $data);
    return $res;
  }

  public function telegramSendMesageKeyboardHide($chat_id, $message_id, $text) {
    $data = array(
        'chat_id' => $chat_id,
        'parse_mode' => 'HTML',
        'text'  => $text,
        'disable_web_page_preview' => true,
        'reply_markup' => array(
                          'remove_keyboard' => true,
                          'selective' => false
                          )
    );
    $res = $this->apiRequest("sendMessage", $data);
    return $res;
  }
  
  public function telegramSendMesageInlineKeyboard($chat_id, $text, $inlineKeyboard) {
    /*$inlineKeyboard = array('inline_keyboard' => array(array(array('text' =>  "", 'callback_data' => "/"), array('text' =>  "", 'url' => ""), array('text' =>  "", 'url' => ""))),);*/

    $markup = json_encode($inlineKeyboard, true);
    $data = array(
        'chat_id' => $chat_id,
        'parse_mode' => 'HTML',
        'text'  => $text,
        'disable_web_page_preview' => true,
        'reply_markup' => $markup
    );
    $res = $this->apiRequest("sendMessage", $data);
    return $res;
  }
  
  public function answerCallbackQuery($id, $text) {
    $data = array(
        'callback_query_id' => $id,
        'text' => ""
    );
    $res = $this->apiRequest("answerCallbackQuery", $data);
    return $res;
  }
  
  public function editMessageText($chat_id, $message_id, $text, $inlineKeyboard) {
    //$inlineKeyboard = array('inline_keyboard' => array(array(array('text' =>  "", 'callback_data' => "/"), array('text' =>  "", 'url' => ""), array('text' =>  "", 'url' => ""))),);

    $markup = json_encode($inlineKeyboard, true);
    $data = array(
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'parse_mode' => 'HTML',
        'text'  => $text,
        'disable_web_page_preview' => true,
        'reply_markup' => $markup
    );
    $res = $this->apiRequest("editMessageText", $data);
    return $res;
  }
}

?>