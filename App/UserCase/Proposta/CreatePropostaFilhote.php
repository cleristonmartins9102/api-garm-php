<?php

namespace App\UserCase\Proposta;

use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Proposta\Proposta;
use App\Model\Proposta\PropostaParentela;
use App\Model\Proposta\PropostaPredicado;
use App\UserCase\Helper\UserCaseResponse;
use App\UserCase\Proposta\Helper\CreatePropostaNumber;
use Domain\Model\Response;
use Domain\Proposta\CreateDeal;
use Domain\Proposta\CreateNumber;

class CreatePropostaFilhote implements CreateDeal
{
  protected CreateNumber $createNumber;
  protected Proposta $proposta;
  public Response $response;
  protected int $lastNumber;
  protected array $dataArray;
  public function __construct(Proposta $proposta, CreateNumber $createNumber)
  {
    $this->createNumber = $createNumber;
    $this->pai = $proposta;
    $this->dataArray = $proposta->getData();
  }
  public function create(): Response
  {
    try {
      $notClone = array('id_proposta', 'numero', 'created_at', 'classificacao', 'updated_at', 'last_modificated', 'create_by', 'id_doc_proposta', 'id_aceite');
      $child = new Proposta;
      foreach ($this->dataArray as $key => $value) {
        // Verificando se a chave é a coluna que não vai clonar
        if (!in_array($key, $notClone)) {
          $child->{$key} = $value;
        }
      }
      $child->numero = $this->createNumber->create()->body;
      $child->num = strstr($child->numero, '/', true);
      $child->dta_emissao = date('Y-m-d');
      $child->dta_validade = date('Y-m-d');
      $child->dta_aceite = null;
   
      // Definindo status de nova
      $child->tipo = 'nova';
      // Devinindo classificacao como comum
      $child->classificacao = 'comum';
      $child->store();
   
      $this->createParent($child, $this->pai->id_proposta);
  
      $criteria = new Criteria;
      $criteria->add(new Filter('id_proposta', '=', $this->pai->id_proposta));
      $predicados = (new Repository('App\Model\Proposta\PropostaPredicado'))->load($criteria);
      //Verificando se encontrou predicados
      if (count($predicados) > 0) {
        // Percorrendo pela array de objetos de predicados
  
        foreach ($predicados as $key => &$valuePre) {
          $valuePre = $valuePre->toArray();
          $PropostaPredicado = new PropostaPredicado();
          foreach ($valuePre as $key => $value) {
            if ($key !== 'id_propostapredicado') {
              $PropostaPredicado->{$key} = $value;
            }
          }
          // Alteando o id da proposta para a clonada
          $PropostaPredicado->id_proposta = $child->id;
          // Gravando
          $PropostaPredicado->store();
        }
  
        // Gravando
        $this->pai->store();
      }
      return new UserCaseResponse(200, [ 'id_proposta' => $child->id ]);
    } catch (\Throwable $th) {
      return new UserCaseResponse(500, $th);
    }
  }

  private function createParent(Proposta $proposta_filha, $id_proposta_pai)
  {
    // Gravando a parentela da proposta
    $parentela = new PropostaParentela();
    $parentela->id_pai = $id_proposta_pai;
    $parentela->id_filho = $proposta_filha->id;
    return $parentela->store();
  }
}
