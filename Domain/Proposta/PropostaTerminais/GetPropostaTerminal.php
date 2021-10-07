<?php

namespace Domain\Proposta\PropostaTerminais;

interface GetPropostaTerminal {  
  public function get(int $id_proposta);
}