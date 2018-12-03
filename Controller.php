<?php
require_once dirname(__FILE__) . '/includes/config.php';
require_once dirname(__FILE__) . '/includes/info.php';
require_once dirname(__FILE__) . '/includes/TelegramView.php';
require_once dirname(__FILE__) . '/includes/DB.php';
require_once dirname(__FILE__) . '/includes/Logs.php';

class Controller {
    public function __construct() {
        global $list_commands;

        $this->commands = explode(',', $list_commands);
    }

    function Parsing($req) {
        $content = $req['message'];
        $text = strtolower(trim($content['text']));
        
        //cek jika perintah diawali dengan tanda '/'
        if (preg_match("/^\/(.*)/is", $text, $matches)) {
        
        }
        else {

        }
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
            $db_res = $this->logs->insertResponse($output['photo'][0], $res['route']);
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
                        $db_res = $this->logs->insertResponse($output['text'][$count], $res['route']);
                        $count++;
                    }
                } else {
                    $output['text'][] = $this->telegram_view->telegramSendMesageKeyboard($chat_id, $message_id, $text,$res['keyboard']);
                    //insert log response from Telegram API
                    $db_res = $this->logs->insertResponse($output['text'][0], $res['route']);
                }
            }
        } else if(isset($res['inlineKeyboard'])){
            foreach ($res['text'] as $text) {
                $this->telegram_view->telegramSendChatAction($chat_id, "typing");
                $output['text'][] = $this->telegram_view->telegramSendMesageInlineKeyboard($chat_id, $text,$res['inlineKeyboard']);
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
                        //insert log response from Telegram API
                        $db_res = $this->logs->insertResponse($output['text'][$count], $res['route']);
                        $count++;
                    }
                } else {
                    $output['text'][] = $this->telegram_view->telegramSendMesageKeyboardHide($chat_id, $message_id, $text);
                    //insert log response from Telegram API
                    $db_res = $this->logs->insertResponse($output['text'][0], $res['route']);
                }
            }
        }
    }
}
?>