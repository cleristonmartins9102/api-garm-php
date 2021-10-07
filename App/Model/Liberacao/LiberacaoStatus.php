<?php
namespace App\Model\Liberacao;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;

class LiberacaoStatus extends Record
{
    const TABLENAME = "LiberacaoStatus";
}
