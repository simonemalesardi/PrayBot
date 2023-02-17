<?php

class KeyboardUser {
    private $connection;
    private $keyboard_id;
    private $chat_id;

    public function __construct(&$connectionDB, $keyboard, $chat_id) {
        $this->connection = $connectionDB;
        $this->keyboard_id = $keyboard;
        $this->chat_id = $chat_id;
    }

    public function setKeyboard($keyboard_obj, $request){
        $sql = "SELECT * FROM keyboards WHERE id = :id";
        $query = $this->connection->prepare($sql);
        $query->execute(['id' => $this->keyboard_id]);
        $buttons = $query->fetchAll();
        
        usort($buttons, function($a, $b) {
                return $a['position'] - $b['position'];
            });
            
        $array_buttons = [];
        
        foreach ($buttons as $button){
            $size = sizeof($array_buttons);
            if ($size==0) {
                array_push($array_buttons, [$button['command']]);
                $request::sendMessage([
                    'chat_id' => $this->chat_id,
                    'text' => 'new half'.$button['command'],
                ]);
            }
            else{
                if (sizeof($array_buttons[$size-1])<2 && $button['style']=='half') {
                    array_push($array_buttons[$size-1], $button['command']);  
                    $request::sendMessage([
                        'chat_id' => $this->chat_id,
                        'text' => 'existing half'.$button['command'],
                    ]);
                } else if (sizeof($array_buttons[$size-1])==2 && $button['style']=='half'){
                    array_push($array_buttons, [$button['command']]);  
                    $request::sendMessage([
                        'chat_id' => $this->chat_id,
                        'text' => 'new half'.$button['command'],
                    ]);
                } else {
                    array_push($array_buttons, [$button['command']]);  
                    array_push($array_buttons, []);  
                    $request::sendMessage([
                        'chat_id' => $this->chat_id,
                        'text' => 'new'.$button['command'],
                    ]);
                }
            }
        }

        foreach ($array_buttons as $button){
            $keyboard_obj->addRow(...$button); 
        }

        $request::sendMessage([
            'chat_id' => $this->chat_id,
            'text' => 'ok',
            'reply_markup' => $keyboard_obj,
        ]);
    }
}


?>