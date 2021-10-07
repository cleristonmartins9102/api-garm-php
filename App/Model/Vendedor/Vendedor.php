<?php
namespace App\Model\Vendedor;

use App\Model\Pessoa\Individuo;
use App\Model\Proposta\Proposta;
use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Repository;
use App\Lib\Database\Filter;
/**
 *
 */
class Vendedor extends Record
{
  const TABLENAME = "Vendedor";

  public function get_pessoa_nome() {
    return (new Individuo($this->id_individuo))->nome;
  }

  public function get_pessoa() {
    return (new Individuo($this->id_individuo));
  }

  /**
   * Retorna a quantidade de propostas em nome do vendedor
   * @return Number 
   */
  public function get_qtd_proposta() {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_vendedor', '=', $this->id_vendedor));
    return count((new Repository(Proposta::class))->load($criteria));
  }

  /**
   * Retorna a quantidade de propostas ativa em nome do vendedor
   * @return Number 
   */
  public function get_qtd_proposta_ativa() {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_vendedor', '=', $this->id_vendedor));
    $propostas = ((new Repository(Proposta::class))->load($criteria));
    $ativas = 0;
    foreach ($propostas as $proposta) {
      if ($proposta->status === 'ativa')
        $ativas++;
    }
    return $ativas;
  }
}
