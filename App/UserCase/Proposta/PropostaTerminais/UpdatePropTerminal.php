<?php

namespace App\UserCase\Proposta\PropostaTerminais;

use App\Lib\Database\Record;
use Domain\Proposta\PropostaTerminais\UpdatePropostaTerminal;

class UpdatePropTerminal extends Record implements UpdatePropostaTerminal {
  const TABLENAME = 'PropostaTerminal';
  public function update(int $id_proposta, int $id_terminal): void {
    
  }
}