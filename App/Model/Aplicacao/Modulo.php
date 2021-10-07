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
class Modulo extends Record
{
  private $sub;

  const TABLENAME = "Modulo";

  public function get_aplicacao() {
    return new Aplicacao($this->id_aplicacao);
  }

  public function get_sub_modulos() {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_modulo', '=', $this->id_modulo));
    $repository = (new Repository(ModuloSub::class))->load($criteria);
    if (count($repository) === 0)
      return [];
    foreach ($repository as $key => &$mod_sub) {
      $mod_sub->category = $mod_sub->tipo->tipo;
      $sub[] = $mod_sub->toArray();
    }
    return $sub;
  }

  public function addSub(ModuloSub $sub) {
    $this->sub[] = $sub->toArray();
  }

  public function dump() {
    return [
      'nome'   => $this->nome,
      'legend' => $this->legend,
      'type'   => $this->type,
      'sub'    => $this->sub
    ];
  }
}
