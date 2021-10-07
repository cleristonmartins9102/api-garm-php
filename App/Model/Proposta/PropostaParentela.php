<?php
namespace App\Model\Proposta;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;
use App\Model\Servico\Predicado;

class PropostaParentela extends Record
{
    const TABLENAME = "PropostaParentela";
    const MANYTOMANY = true;
}
