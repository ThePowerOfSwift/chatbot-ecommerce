<?php
require_once dirname(__FILE__) . '/includes/config.php';
require_once dirname(__FILE__) . '/includes/info.php';
require_once dirname(__FILE__) . '/includes/TelegramView.php';
require_once dirname(__FILE__) . '/includes/TemplatesView.php';
require_once dirname(__FILE__) . '/includes/DB.php';
require_once dirname(__FILE__) . '/includes/Logs.php';
require_once dirname(__FILE__) . '/includes/DataCenter.php';
class Controller {
    public function __construct() {
        $this->data_center = new DataCenter();
        $this->template_view = new TemplatesView();
        $this->logs = new Logs();
        global $list_commands;
        $this->commands = explode(',', $list_commands);
    }
    function Parsing($req) {
        $content = $req['message'];
        $text = strtolower(trim($content['text']));
		$response = array();
        
        //cek jika perintah diawali dengan tanda '/'
        if (preg_match("/^\/(.*)/is", $text, $correct)) {
            $command = $correct[1];
            if (in_array($command, $this->commands)) {
                $response = $this->displayData($req, $command);
            } else {
                $response = $this->displayData($req, "search");
            }
        }
        else {
            if (in_array($text, $this->commands)) {
                $response = $this->displayData($req, $text);
            } else {
                $response =  $this->displayData($req, "search");
            }
        }
        return $response;
    }
    function getData($keyword) {
        $result = $this->data_center->searchData($keyword);
		file_put_contents('query_result.txt', print_r($result,true));
        return $result;
    }
    function displayData($req, $router) {
        $message = $req['message']['text'];
        $type = $req['message']['chat']['type'];
        $username = $req['message']['from']['first_name'].' '.$req['message']['from']['last_name'];
        //$keyboard = array();
        
        switch($router){
            case 'start':
                $text[0] = $this->template_view->displayTheme('start', $username);
                if ($type == "private") {
                    $checkUser = $this->logs->checkUser($req['message']['from']['id']);
                    if ($checkUser == 0) {
                        $this->logs->saveUser($req['message']['from']);
                    } else {
                        $status = $this->logs->getUserStatus($req['message']['from']['id']);
                        if ($status == "non-active")
							$this->logs->updateUserStatus($req['message']['from'], "active");
                    }
                }
                break;
            case 'search':
                $data = $this->getData($message);
                $text[0] = $this->template_view->displayTheme('search', $data);
				break;
        }


        $respons = array(
            'router' => $router,
            'text' => $text,
            //'photo' => $photo,
            //'keyboard' => $keyboard
        );
        return $respons;
    }
    function sendMessages($req,$res) {
        $this->telegram_view = new TelegramView();
        $output = array();
        $message_id = $req['message']['message_id'];
        $chat_id = $req['message']['chat']['id'];
        if(isset($res['photo']) && $res['photo'] != ""){
            $this->telegram_view->telegramSendChatAction($chat_id, "upload_photo");
            $output['photo'][] = $this->telegram_view->sendPhoto($chat_id, $res['photo']);
            //file_put_contents('photo.txt', print_r($output,true));
            //insert log response from Telegram API
            $db_res = $this->logs->insertResponse($output['photo'][0], $res['router']);
        }
        if(isset($res['keyboard']) && $res['keyboard'] != "") {
            foreach ($res['text'] as $text) {
                $this->telegram_view->telegramSendChatAction($chat_id, "typing");
                $char_count = strlen($text);
                if ($char_count > LIMIT_TELEGRAM_CHAR) {
                    $temp_text = preg_split("/\n/", $text);
                    $arr_count = 0; $respons_arr = array();
                    foreach ($temp_text as $t) {
                        if ($t != "") {
                            if (strlen($respons_arr[$arr_count] . $t) <= LIMIT_TELEGRAM_CHAR) {
                                $respons_arr[$arr_count] .= $t . "\r\n";
                            } else {
                                $arr_count++;
                                $respons_arr[$arr_count] .= $t . "\r\n";
                            }
                        } elseif ($t == "") {
                            $respons_arr[$arr_count] .= "\r\n";
                        }
                    }
                    $i = 1; $count = 0;
                    foreach($respons_arr as $r) {
                        $r = '#page '.$i.'
            '.$r.'
            #page '.$i++.' of '.sizeof($respons_arr);
                        $output['text'][] = $this->telegram_view->telegramSendMesageKeyboard($chat_id, $message_id, $r,$res['keyboard']);
                        //insert log response from Telegram API
                        $db_res = $this->logs->insertResponse($output['text'][$count], $res['router']);
                        $count++;
                    }
                } else {
                    $output['text'][] = $this->telegram_view->telegramSendMesageKeyboard($chat_id, $message_id, $text,$res['keyboard']);
                    //insert log response from Telegram API
                    $db_res = $this->logs->insertResponse($output['text'][0], $res['router']);
                }
            }
        } elseif (isset($res['text'])) {
            foreach ($res['text'] as $text) {
                $this->telegram_view->telegramSendChatAction($chat_id,"typing");
                $char_count = strlen($text);
                if ($char_count > LIMIT_TELEGRAM_CHAR) {
                    $temp_text = preg_split("/\n/", $text);
                    $arr_count = 0; $respons_arr = array();
                    foreach ($temp_text as $t) {
                        if ($t != "") {
                            if (strlen($respons_arr[$arr_count] . $t) <= LIMIT_TELEGRAM_CHAR) {
                                $respons_arr[$arr_count] .= $t . "\r\n";
                            } else {
                                $arr_count++;
                                $respons_arr[$arr_count] .= $t . "\r\n";
                            }
                        } elseif ($t == "") {
                            $respons_arr[$arr_count] .= "\r\n";
                        }
                    }
                    $i = 1; $count = 0;
                    foreach($respons_arr as $r) {
                        $r = '#page '.$i.'
            '.$r.'
            #page '.$i++.' of '.sizeof($respons_arr);
                        $output['text'][] = $this->telegram_view->telegramSendMesageKeyboardHide($chat_id, $message_id, $r);
						file_put_contents('output_telegram_view.txt', print_r($output,true));
                        //insert log response from Telegram API
                        $db_res = $this->logs->insertResponse($output['text'][$count], $res['router']);
                        $count++;
                    }
                } else {
                    $output['text'][] = $this->telegram_view->telegramSendMesageKeyboardHide($chat_id, $message_id, $text);
                    //insert log response from Telegram API
                    $db_res = $this->logs->insertResponse($output['text'][0], $res['router']);
                }
            }
        }
    }
}
?>