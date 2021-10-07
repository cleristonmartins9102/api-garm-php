<?php
namespace App\Model\Servico;

use App\Lib\Database\Record;

class ItemCondicional extends Record
{
    const TABLENAME = 'ItemCondicional';

    public function get_item() {
        return new Predicado($this->id_predicadocondicionado); 
    }
}
