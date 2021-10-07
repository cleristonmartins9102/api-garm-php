<?php

namespace App\UserCase\Proposta;

use Domain\Proposta\GetItemProposta;
use App\Model\Proposta\PropostaPredicado;

class GetItemPropostaById implements GetItemProposta
{
  protected int $id;
  function __construct(int $id)
  {
    $this->id = $id;
  }
  public function get(): PropostaPredicado
  {
    return new PropostaPredicado($this->id);
  }
}
