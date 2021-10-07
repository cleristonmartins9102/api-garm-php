<?php
namespace App\Model\Processo;

use App\Lib\Database\Record;
use App\Lib\Database\Filter;
use App\Lib\Database\Criteria;
use App\Lib\Database\Repository;
use App\Model\Fatura\Fatura;
use App\Model\Captacao\Captacao;
use App\Model\Captacao\CaptacaoLote;
use App\Model\Despacho\Despacho;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Lib\Tool\Register;
use DateTime;

class Processo extends Record
{
  const TABLENAME = 'Processo';
  private $itens = [];


  public function __construct($id_processo = null)
  {
    parent::__construct($id_processo);

    // Verificando se é fatura nova, caso sim, crie um numero e defina o status para aberta
    if (!isset($this->id_processo)) {
      $this->numero = $this->getLast() + 1;
      $criteria = new Criteria();
      $criteria->add(new Filter('status', '=', 'aberto'));
      $repository = new Repository('App\Model\Processo\ProcessoStatus');
      $status = $repository->load($criteria);
      // Verificando se encontrou o status
      if (count($status) > 0) {
        // Grava o id do status
        $this->id_processostatus = $status[0]->id_processostatus;
      }
      // print_r($this);exit
    }
  }

  public function store(Request $request = null, Response $response = null, Register $register = null)
  {     
    if ($lote = $this->captacaolote) {
      $response = parent::store($request, $response, $register);
      $lote->addEvento('g_processo', $this->id, $this);
    } else {
      // Verifica se processo é novo
      if (!isset($this->id_processo) && $this->id_captacao) {
        $response = parent::store($request, $response, $register);
        $this->movimentacao->addEvento('g_processo', $this->id, $this);
      } else {
        $response = parent::store($request, $response, $register);
      }
    }
    return $response;
  }

  public function get_dias_consumo() {
    $dta1 = new DateTime($this->dta_inicio);
    $dta2 = new DateTime($this->dta_final);
    return ((int) $dta1->diff($dta2)->days) + 1;
  }

  public function get_status()
  {
      return (new ProcessoStatus($this->id_processostatus))->status;
  }

  public function get_eventos() {
    return [];
  }

  public function get_historico() {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_processo', '=', $this->id_processo));
    $criteria->setProperty('order', 'created_at desc');
    $repository = new Repository(ProcessoHistorico::class);
    $object = $repository->load($criteria);
    $dataFull = array();
    foreach ($object as $idx => &$value) {
      $value->modulo = 'Processo';
      $dataFull[] = $value->getData();
    }
    return isset($dataFull) ? $dataFull : null;
  }

  public function deleteItens(array $item = null)
  {
    
    if ($item == null) {
      $criteria = new Criteria;
      $criteria->add(new Filter('id_processo', '=', $this->id_processo));
      // $repository = new Repository('App\Model\Processo\ProcessoPredicado');
      // $ic = $repository->load($criteria);

      $pro_predicado = new ProcessoPredicado;
      $pro_predicado->deleteByCriteria($criteria);

    }
    // exit();
  }

  public function get_movimentacao()
  {
    return self::checkTipo();
  }

  public function get_captacaolote() {
    if ($this->isLote())
      return new CaptacaoLote($this->id_captacaolote);
    
    return false;
  }

  public function get_itens()
  {
    $criteria = new Criteria;
    // Verifica se é despacho
    if ( empty($this->id_despacho) ) {
      // Verifica se é lote
      if ($lotes = $this->isLote()) {
        foreach ($lotes as $lote) {
          $dados = [ 
            'captacao' => $lote->id_captacao,
            'isLote' => true,
            'valor_mercadoria' => $lote->captacao->liberacao->valor_mercadoria
          ];          $criteria->add(new Filter('id_processo', '=', $this->id_processo));
          $criteria->add(new Filter('id_captacao', '=', $lote->id_captacao));
          $repository = new Repository('App\Model\Processo\ProcessoPredicado');
          $object = $repository->load($criteria);
          $criteria->clean();
          $items = [];
          foreach ($object as $item) {
            $items[] = $item->toArray();
          }
          $dados['itens'] = $items;
          $itens['all'][] = $dados;
          $this->itens = $itens;
        }
      } else {
        $dados = [ 
          'captacao' => $this->movimentacao->id_captacao,
          'isLote' => false,
          'valor_mercadoria' => $this->movimentacao->liberacao->valor_mercadoria
        ];
        $criteria->add(new Filter('id_processo', '=', $this->id_processo));
        $criteria->add(new Filter('id_captacao', '=', $this->movimentacao->id_captacao));
        $repository = new Repository('App\Model\Processo\ProcessoPredicado');
        $object = $repository->load($criteria);
        $items = [];
        foreach ($object as $key => $item) {
          $items[] = $item->toArray();
        }
        $dados['itens'] = $items;
        $itens['all'][] = $dados;
        $this->itens = $itens;
      }
    } else {
      $criteria->add(new Filter('id_processo', '=', $this->id_processo));
      $repository = new Repository('App\Model\Processo\ProcessoPredicado');
      $object = $repository->load($criteria);
      $items = [];
      foreach ($object as $key => $item) {
        $items[] = $item->toArray();
      }
      $dados['itens'] = $items;
      $itens['all'][] = $dados;
      $this->itens = $itens;
    }

    return $this->itens;
  }

       /**
     * Metodo para buscar os itens do processo para a captação
     */
  public function get_itensProcessoArray() {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_processo', '=', $this->id_processo));
    $itens_processo = (new Repository(ProcessoPredicado::class))->load($criteria);
    $itens = [];
    foreach ($itens_processo as $item) {
      $itens[] = $item->toArray();
    }
    return $itens;
  }


  public function isDespacho() {
    return is_null($this->id_despacho) ? false : true;
  }

  public function get_cliente()
  {
    $movimentacao = self::checkTipo();
    return $movimentacao->proposta->cliente;
  }

  public function get_proposta()
  {
    $movimentacao = self::checkTipo();
    return $movimentacao->proposta;
  }

  public function get_identificador()
  {
    $movimentacao = self::checkTipo();
    return $movimentacao->bl ? ($movimentacao->numero . '(Captação)') : ($movimentacao->numero . '(Despacho)');
  }

  public function get_captacao()
  {
    return new Captacao($this->id_captacao);
  }

  private function checkTipo()
  {
    if ($this->id_despacho) :
      return new Despacho($this->id_despacho);
    elseif ($this->id_captacao) :
      return new Captacao($this->id_captacao);
    endif;
  }

  public function isLote() {
    if (!is_null($this->id_captacaolote)) {
      $lote = new CaptacaoLote($this->id_captacaolote);
      return $lote->listaCaptacao;
    }
    return false;
  }

  public function get_lote() {
    $lote = new CaptacaoLote($this->id_captacaolote);
    return $lote;
  }

  public function addHistorico($ocorrencia) {
    $historico = new ProcessoHistorico;
    $historico->request = $this->request;
    $historico->response = $this->response;
    $historico->ocorrencia = $ocorrencia;
    $historico->id_processo = $this->id_processo  ?? $this->id;
    $historico->store();
  }

  /** 
   * Metodo que retorna as faturas do processo
   */
  public function get_fatura() {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_processo', '=', $this->id_processo));
    return (new Repository(Fatura::class))->load($criteria);
  }

  /**
   * Metodo que retorna se o processo já foi faturado
   */
  public function isFaturado() {
    return count($this->fatura) > 0;
  }

  /**
   * Metodo que recalcula as faturas do processo
   */
  public function recalcular_faturas() {
    foreach($this->fatura as $fatura) {
      if (!is_null($fatura->id_fatura)) {
          $fatura->removeProperty('valor_imposto_c');
          $fatura->recalcular();
      }
  }
  }
}
