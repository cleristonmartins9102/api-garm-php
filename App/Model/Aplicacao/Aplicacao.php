<?php
namespace App\Model\Aplicacao;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Modulo\ModuloSub;

/**
 *
 */
class Aplicacao extends Record
{
  private $modulos;
  const TABLENAME = "Aplicacao";

  public function get_listademodulo()
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_aplicacao', '=', $this->id_aplicacao));
    $repository = new Repository('App\Model\Aplicacao\AplicacaoModulo');
    $aplicacao_modulo = $repository->load($criteria);
    return $aplicacao_modulo;
  }
  public function get_modulos() {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_aplicacao', '=', $this->id_aplicacao));
    $repository = (new Repository(Modulo::class))->load($criteria);
    if (count($repository) === 0)
      return [];
    foreach ($repository as $key => $modulo) {
      $modulo->sub = $modulo->sub_modulos;
      $modulos[] = $modulo->toArray();
    }
    // $this->modulos = $modulos;
    return $modulos;
  }
}
