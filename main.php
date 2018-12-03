<?php

class BotTelegram {
    public $bot_token;

    public function __construct($bot_token) {
        $this->bot_token = $bot_token;

        $input = file_get_contents("php://input");
        $req = json_decode($input, true);

        if(isset($req['message'])) {
            $content = $req['message'];

            $this->controller = new Controller();
            $res = $this->controller->Parsing($req);
            $output = $this->controller->sendMessages($req, $res);
        }
    }
}

?>