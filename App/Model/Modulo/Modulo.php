<?php
namespace App\Model\Modulo;

use App\Lib\Database\Record;
use App\Model\Aplicacao\Aplicacao;

class Modulo extends Record
{
    const TABLENAME = 'Modulo';
    
    public function get_aplicacao() {
        return new Aplicacao($this->id_aplicacao);
    }
}
