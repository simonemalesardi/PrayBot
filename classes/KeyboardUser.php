<?php

class KeyboardUser {
    private $connection;
    private $chat_id;

    public function __construct(&$connectionDB) {
        $this->connection = $connectionDB;
    }

    public function setKeyboard($keyboard_obj, $keyboard_id){
        $sql = "SELECT * FROM keyboards WHERE id = :id";
        $query = $this->connection->prepare($sql);
        $query->execute(['id' => $keyboard_id]);
        $buttons = $query->fetchAll();
        
        
        usort($buttons, function($a, $b) {
                return $a['position'] - $b['position'];
            });

        $array_buttons = [];
        
        foreach ($buttons as $button){
            $size = sizeof($array_buttons);
            if ($size == 0) {
                array_push($array_buttons, [$button['command']]);
            }
            else{
                if (sizeof($array_buttons[$size-1])<2 && $button['style']=='half') {
                    array_push($array_buttons[$size-1], $button['command']);  
                } else if (sizeof($array_buttons[$size-1])==2 && $button['style']=='half'){
                    array_push($array_buttons, [$button['command']]);  
                } else {
                    array_push($array_buttons, [$button['command']]);  
                    array_push($array_buttons, []);  
                }
            }
        }

        foreach ($array_buttons as $button){
            $keyboard_obj->addRow(...$button); 
        }

        return $keyboard_obj;
    }

}


?>