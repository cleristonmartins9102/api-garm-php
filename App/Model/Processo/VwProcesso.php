<?php
namespace App\Model\Processo;

use App\Lib\Database\Record;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Captacao\Captacao;
use App\Model\Despacho\Despacho;
use App\Model\Proposta\Proposta;
use App\Model\Captacao\CaptacaoLote;
use App\Model\Fatura\Fatura;


class VwProcesso extends Record
{
  const TABLENAME = "VwProcesso";
  private $itens;

  public function get_itens()
  {
    $criteria = new Criteria;
    $criteria->add(new Filter('id_processo', '=', $this->id_processo));
    $repository = new Repository('App\Model\Processo\ProcessoPredicado');
    $object = $repository->load($criteria);
    foreach ($object as $key => $item) {
      $this->itens[] = array_merge($item->predicado->toArray(), $item->toArray());
    }
    return $this->itens;
  }

  public function get_eventos()
  {
    return (self::checkTipo())->eventos;
  }

  public function get_cliente()
  {
    $movimentacao = self::checkTipo();
    return $movimentacao->proposta->cliente;
  }

  public function get_movimentacao() {
   return self::checkTipo();
  }


  public function get_proposta()
  {
    $movimentacao = self::checkTipo();
    return $movimentacao->proposta;
  }

  public function get_identificador()
  {
    $movimentacao = self::checkTipo();
    return $movimentacao->bl ? ( $movimentacao->isInLote() ? $movimentacao->isInLote()->numero . ' (Lote)' : ($movimentacao->numero . ' (Captação)') ) : ($movimentacao->numero . ' (Despacho)');

    // return $movimentacao->bl ? ($movimentacao->numero . ' - ' . $movimentacao->bl) : ($movimentacao->numero . ' - ' . $movimentacao->due);
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


  private function checkTipo()
  {
    if ($this->id_despacho) :
      return new Despacho($this->id_despacho);
    elseif ($this->id_captacao) :
      return new Captacao($this->id_captacao);
    endif;
  }
}
