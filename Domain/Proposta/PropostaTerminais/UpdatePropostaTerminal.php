<?php

namespace Domain\Proposta\PropostaTerminais;

interface UpdatePropostaTerminal {  
  public function update(int $id_proposta, int $id_terminal): void;
}