<?php
namespace App\Model\Servico;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;

class PacotePredicado extends Record
{
    const TABLENAME = "PacotePredicado";
    const MANYTOMANY = true;

    public function get_nomepredicado()
    {
        return new Predicado($this->id_predicado);
    }
}
