<?php
namespace App\Model\Terminal;

use App\Lib\Database\Record;
use App\Model\Pessoa\Individuo;

class Terminal extends Record
{
    const TABLENAME = "Terminal";

    public function get_status()
    {
        return new Status($this->id_status);
    }

    public function get_individuo()
    {   
        return new Individuo($this->id_individuo);
    }
}
