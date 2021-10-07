<?php

namespace Domain\Proposta;

use App\Model\Proposta\PropostaPredicado;

interface GetItemProposta {
  public function get(): PropostaPredicado;
}