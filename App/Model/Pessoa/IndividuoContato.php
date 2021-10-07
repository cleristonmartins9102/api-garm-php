<?php
namespace App\Model\Pessoa;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
/**
 *
 */
class IndividuoContato extends Record
{
  const TABLENAME = "IndividuoContato";
  const MANYTOMANY = true;

  public function get_contatos()
  { 
    $criteria = new Criteria;
    $criteria->add(new Filter('id_individuo', '=', $this->id_individuo));
    return (new Repository(get_class()))->load($criteria);
  }
}
