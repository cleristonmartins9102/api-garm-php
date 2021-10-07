<?php

namespace Domain\Proposta\PropostaTerminais;

interface DeletePropostaTerminal {  
  public function del(int $id_proposta): void;
}