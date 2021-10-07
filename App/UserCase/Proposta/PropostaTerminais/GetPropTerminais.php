<?php

namespace App\UserCase\Proposta\PropostaTerminais;

use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Record;
use App\Lib\Database\Repository;
use Domain\Proposta\PropostaTerminais\GetPropostaTerminal;

class GetPropTerminais extends Record implements GetPropostaTerminal {
  const TABLENAME = 'PropostaTerminal';
  public function get(int $id_proposta) {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_proposta', '=', $id_proposta));
    $repository = (new Repository(self::class))->load($criteria);
    if (count($repository) == 0) {
      return null;
    }
    $terminais = [];
    foreach($repository as $terminal) {
      $terminais[] = $terminal->id_terminal;
    }
    return $terminais;
  }
}