<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Repository;
use App\Lib\Database\Filter;

class Cidade extends Record
{
  const TABLENAME = 'Cidade';

  public function get_estado()
  {
    return new Estado($this->id_estado);
  }
}


