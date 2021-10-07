<?php

namespace App\UserCase\Proposta\PropostaTerminais;

use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Record;
use Domain\Proposta\PropostaTerminais\DeletePropostaTerminal;

class DeletePropTerminais extends Record implements DeletePropostaTerminal {
  const TABLENAME = 'PropostaTerminal';
  public function del(int $id_proposta): void {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_proposta', '=', $id_proposta));
    $this->deleteByCriteria($criteria);
  }
}