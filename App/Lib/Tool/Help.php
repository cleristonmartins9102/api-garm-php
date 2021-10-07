<?php
namespace App\Lib\Tool;

class Help
{
    public function __get($prop) {
        if (method_exists($this, 'get_'. $prop)) {
            return call_user_func(array($this, 'get_'.$prop));
        }
    }

    // public function __set($prop, $value) {
    //     if (method_exists($this, $prop)) {
    //         call_user_func('set_'. $prop);
    //     }
    // }
}
