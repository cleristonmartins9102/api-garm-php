<?php
namespace App\Model\Processo;

use App\Lib\Database\Record;
use App\Model\Servico\Predicado;

class ProcessoPredicado extends Record
{
    const TABLENAME = 'ProcessoPredicado';
    
    public function get_predicado()
    {
        return new Predicado($this->id_predicado);
    }

    public function get_processo() {
        return new Processo($this->id_processo);
    }
}
