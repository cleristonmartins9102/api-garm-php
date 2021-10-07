<?php

namespace App\UserCase\Proposta;

use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Proposta\Proposta;
use App\Model\Proposta\PropostaParentela;
use App\UserCase\Helper\UserCaseResponse;
use App\UserCase\Proposta\Helper\ClonePropostaPredicado;
use App\UserCase\Proposta\Helper\CreatePropostaNumber;
use Domain\Model\Response;
use Domain\Proposta\CreateDeal;
use Domain\Proposta\CreateNumber;

use function App\Presentation\Http\serverError;

class RenovarProposta implements CreateDeal
{
  protected Proposta $proposta;
  protected CreateNumber $createNumber;
  public Response $response;
  public function __construct(Proposta $proposta, CreateNumber $createNumber)
  {
    $this->proposta = $proposta;
    $this->createNumber = $createNumber;
    return $this;
  }
  public function create(): Response
  {
    try {
      // Intanciando proposta do banco
      $proposta = $this->proposta;
      // Definindo o nome da coluna que não vai clonar
      $notClone = array('id_proposta', 'numero', 'created_at', 'updated_at', 'last_modificated', 'create_by', 'id_doc_proposta', 'id_aceite', 'updated_by');

      // Instancia um novo objeto proposta que vai ser o clone
      $propostaClone = clone $proposta;
      $propostaClone->removeProperty($notClone);
      // Definindo status de renovação
      $propostaClone->tipo = 'renovação';
      $number = $this->createNumber->create();
      $propostaClone->numero = $number->body;
      $propostaClone->status = 'ativa';
      $propostaClone->dta_emissao = date('Y-m-d');
      $propostaClone->dta_aceite = null;
      $propostaClone->dta_validade = date('Y-m-d', strtotime($propostaClone->dta_emissao . "+6 month"));
      // Gravando
      // delete from Proposta where num > 657
      // delete from Captacao where id_proposta > 599
      // select * from Proposta


      $propostaClone->store();

      // Clonando os predicados
      $criteria = new Criteria;
      $criteria->add(new Filter('id_proposta', '=', $proposta->id_proposta));
      $predicados = (new Repository('App\Model\Proposta\PropostaPredicado'))->load($criteria);

      //Verificando se encontrou predicados
      if (count($predicados) > 0) {
        // Percorrendo pelo array de dados dos predicados
        $clone = new ClonePropostaPredicado($predicados, $propostaClone);
        $response = $clone->clone();
        if ($response->statusCode !== 200) return serverError($response->body);
      }

      // Definindo status da proposta clonada para Inativa
      $proposta->status = "inativa";

      // Gravando
      $proposta->store();


      // Gravando parentela
      $this->createParent($propostaClone, $proposta->id_proposta);

      $result['id_proposta'] = $propostaClone->id;

      $this->response = new UserCaseResponse(200, $result);
      return $this->response;
    } catch (\Throwable $th) {
      $this->response = serverError($th);
      return serverError($th);
    }
  }

  private function createParent(Proposta $proposta_filha, int $id_proposta_pai)
  {
    // Gravando a parentela da proposta
    $parentela = new PropostaParentela();
    $parentela->id_pai = $id_proposta_pai;
    $parentela->id_filho = $proposta_filha->id;
    return $parentela->store();
  }
}
