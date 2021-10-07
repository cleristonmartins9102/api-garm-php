<?php

namespace App\UserCase\Proposta\Helper;

use App\Model\Proposta\Proposta;
use App\UserCase\Helper\UserCaseResponse;
use App\UserCase\Protocol\ClonePredicado;
use Domain\Model\Response;

use function App\UserCase\Helper\serverError;

class ClonePropostaPredicado implements ClonePredicado
{
  protected array $predicados;
  protected Proposta $proposta;
  public function __construct(array $predicados, Proposta $proposta)
  {
    $this->predicados = $predicados;
    $this->proposta = $proposta;
  }

  public function clone(): Response
  {
    $notClone = array('id_propostapredicado', 'created_at', 'updated_at', 'last_modificated', 'create_by');
    try {
      foreach ($this->predicados as $key => &$predicado) {
        $predicadoClone = clone $predicado;
        $predicadoClone->removeProperty($notClone);
        // Alteando o id da proposta para a clonada
        $predicadoClone->id_proposta = $this->proposta->id;
        // Gravando
        $predicadoClone->store();
      }
      $response = new UserCaseResponse(200, 'success');
      return $response;
    } catch (\Throwable $th) {
      return serverError($th);
    }
  }
}
