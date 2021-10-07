<?php
namespace App\Model\Proposta;

use App\Lib\Database\Record;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Proposta\PropostaPredicado;
use App\Model\Vendedor\Vendedor;
use App\Model\Qualificacao\Qualificacao;
use App\Model\Pessoa\Individuo;
use App\Model\Documento\Upload;
/**
 *
 */
class VwProposta extends Record
{
  const TABLENAME = "VwProposta";

  public function addPredProposta(PropostaPredicado $predicado) 
  {
    $predicado->store();
  }
  
  public function get_vendedor()
  {
    return new Vendedor($this->id_vendedor);
  }

  public function get_qualificacao()
  {
    return new Qualificacao($this->id_qualificacao);
  }

  public function get_cliente()
  {
    return new Individuo($this->id_cliente);
  }

  public function get_servico()
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_proposta', '=', $this->id_proposta));
    $repository = new Repository('App\Model\Proposta\PropostaPredicado');
    $object = $repository->load($criteria);
    return $object;
  }

  public function get_anexo_proposta()
  {
    return new Upload($this->id_doc_proposta);
  }

  public function get_anexo_aceite()
  {
    return new Upload($this->id_aceite);
  }
}
