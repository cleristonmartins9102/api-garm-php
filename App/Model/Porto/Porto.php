<?php
namespace App\Model\Porto;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Pessoa\Cidade;

/**
 *
 */
class Porto extends Record
{
  const TABLENAME = "Porto";

  public function get_cidade() {
    return new Cidade($this->id_cidade);
  }
}
