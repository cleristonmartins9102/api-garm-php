<?php

namespace App\UserCase\Proposta;

use App\Domain\Fatura\GetFaturaItem;
use App\Domain\Proposta\GetItemProposta;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Depot\Depot;
use App\Model\Margem\Margem;
use App\Model\Proposta\Proposta;
use App\Model\Proposta\PropostaPredicado;
use App\Model\Rule\ChargeRule;
use App\Model\Servico\Pacote;
use App\Model\Servico\PacotePredicado;
use Domain\Model\Response;
use Domain\Operacao;

use function App\UserCase\Helper\ok;
use function App\UserCase\Helper\serverError;

class FindItemFromProposta implements GetItemProposta
{
  protected int $id_item;
  protected int $dimensao;
  protected Margem $margem;
  protected Depot $depot;
  protected object $operacao;
  protected Proposta $proposta;
  public function __construct($id = null, $dimensao = null, Operacao $operacao = null)
  {
    $this->id_item = $id;
    $this->dimensao = $dimensao;
    $this->operacao = $operacao;
    if (!is_null($operacao)) {
      $this->margem = $operacao->getMargem();
      $this->depot = $operacao->getDepot();
      $this->proposta = $operacao->getProposta();
    }
  }
  public function get(): PropostaPredicado
  {
    try {
      $criteria = new Criteria;
      if (!is_null($this->operacao)) {
        // Para propostas com data de emissao acima de 2021-08-1
        if ($this->proposta->dta_emissao >= '2021-08-01') {
          $terminal = $this->operacao->terminal_redestinacao->id_terminal;
          $criteria->add(new Filter('id_terminal', '=', $terminal));
          $cr = (new Repository(ChargeRule::class))->load($criteria);
          $criteria->clean();
          foreach ($cr as $key => $charge_rule) {
            if ($charge_rule->id_predicado == $this->id_item) {
              if ($charge_rule->limit_day)
                $criteria->add(new Filter('franquia_periodo', '=', $charge_rule->limit_day));
              break;
            } else {
              $predicado = $charge_rule->predicado;
              $family = $predicado->check_is_same_family((int)$this->id_item);
              if ($family) {
                $id = $predicado->id_predicado;
                break;
              }
            }
          }
        }
      }

      $cri_margem = new Criteria;
  
      $mar = new Margem();
      $mar('margem', 'ambas');
      $cri_margem->add(new Filter('id_margem', '=', $mar->id_margem));
  
      if (!is_null($this->margem) and is_numeric($this->margem)) {
        $mar('id_margem', $this->margem);
        $cri_margem->add(new Filter('id_margem', '=', $mar->id_margem), $cri_margem::OR_OPERATOR);
      }
      
      if (!is_null($this->proposta)) {
        $criteria->add(new Filter('id_proposta', '=', $this->proposta->id_proposta));
        $criteria->add(new Filter('id_predicado', '=', $this->id_item));
      }
  
      // Caso for despacho, faça a busca usando os critérios como id_depot e id_cidade
      if ($this->depot && $this->depot->isLoaded()) {
        $criteria->add(new Filter('id_depot', '=', $this->depot->id_depot));
  
        // Buscar Cidade
        $cri_cidade = new Criteria;
        $cri_cidade->add(new Filter('id_propostapredicado', '=', 'PropostaPredicado.id_propostapredicado', false));
        $cri_cidade->add(new Filter('id_cidade', '=', $this->depot->individuo->endereco->cidade->id_cidade));
        $rep_cidade = new Repository('App\Model\Proposta\PropostaPredicadoCidade');
  
        $criteria->add(new Filter('EXISTS(' . $rep_cidade->dump($cri_cidade) . ')'));
      }
      $cri_dimensao = new Criteria;
  
      if ($this->dimensao && $this->dimensao !== 'ambos') {
        $cri_dimensao->add(new Filter('dimensao', '=', $this->dimensao));
      }
      $cri_dimensao->add(new Filter('dimensao', '=', 'ambos'), $cri_dimensao::OR_OPERATOR);
  
      $criteria->add($cri_dimensao);
      $criteria->add($cri_margem);
  
      $repository = new Repository('App\Model\Proposta\PropostaPredicado');
      $predicado = $repository->load($criteria);
      if (count($predicado) > 0) {
        return ok($predicado[0]);
      }
      // Recursivo caso nao encontre ele busca novamente sem o depot
      if (!is_null($this->depot)) {
        $this->depot = null;  
        $predicado = $this->get();
      }
  
      /**
       * Verificando se o servico foi encontrado, caso não, 
       * ele verifica se o servico faz parte de algum pacote, 
       * caso sim ele faz um recursivo com o pacote como parametro
       */
      if (count($predicado) === 0) {
        $criteria->clean();
        $criteria->add(new Filter('id_predicado', '=', $this->id_item));
        $pacote_predicado = (new Repository(PacotePredicado::class))->load($criteria);
        foreach ($pacote_predicado as $pacote) {
          $this->id_item = (new Pacote($pacote->id_pacote))->id_predicado;
          if ($this->depot && $this->depot->isLoaded()) {
            $predicado = $this->get();
          } else {
            $this->depot = null;
            $predicado = $this->get();
          }
          return $predicado;
          if (is_array($predicado) && count($predicado) > 0) break;
        }
      }
      $response = count($predicado) > 0 ? $predicado[0] : new PropostaPredicado();
      return ok($response);
    } catch (\Throwable $th) {
       return serverError($th); 
    }
  }
}
