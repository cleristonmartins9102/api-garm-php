<?php
namespace App\Model\Servico;

use App\Lib\Database\Record;

class ItemMaster extends Record
{
    const TABLENAME = 'ItemMaster';

    public function get_master() {
        return (new Predicado($this->id_predicadomaster))->toArray();
    }
}
