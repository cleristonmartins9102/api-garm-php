<?php

namespace App\UserCase\Fatura;

use App\Lib\Database\ActiveRecord;
use App\Model\Fatura\Fatura;
use App\UserCase\Helper\Fatura\Protocols\Calc;
use App\UserCase\Helper\Servico\CheckIsIntoPacote;
use DateTime;
use Domain\Fatura\AddItem;
use Domain\Fatura\DefineItemName;
use Domain\Fatura\GetItem;
use Domain\Model\Response;
use Domain\Servico\GetMaster;
use stdClass;

use function App\UserCase\Helper\ok;
use function App\UserCase\Helper\serverError;

class AddServicoDeLote extends ActiveRecord implements AddItem
{
  const TABLENAME = 'FaturaItem';
  protected GetMaster $getItemMaster;
  protected GetItem $getItemPropostaByI;
  protected DefineItemName $getItemCustom;
  protected CheckIsIntoPacote $checkIsIntoPacote;
  protected Calc $calcValorUnitario;
  protected Calc $calcValorVenda;
  protected stdClass $item_processo;
  protected Fatura $fatura;

  public function __construct(
    GetMaster $getItemMaster = null,
    GetItem $getItemPropostaById = null,
    DefineItemName $getItemCustom = null,
    CheckIsIntoPacote $checkIsIntoPacote = null,
    Calc $calcValorUnitario = null,
    Calc $calcValorVenda = null,
    stdClass $item_processo = null,
    Fatura $fatura = null
  ) {
    $numargs = count(func_get_args());
    $this->getItemMaster = $getItemMaster;
    $this->getItemPropostaById = $getItemPropostaById;
    $this->getItemCustom = $getItemCustom;
    $this->checkIsIntoPacote = $checkIsIntoPacote;
    $this->calcValorUnitario = $calcValorUnitario;
    $this->calcValorVenda = $calcValorVenda;
    $this->item_processo = $item_processo;
    $this->fatura = $fatura;

  }

  public function add(): Response
  {
    try {
      $recalcular = $this->recalcular ?? 1;
      $itemProposta =  $this->getItemPropostaById->get()->body;
      $lista_pacotes_faturados = [];
      $this->id_propostapredicado = $itemProposta->id_propostapredicado;
      $this->id_processopredicado = $this->item_processo->id_processopredicado;
      $this->id_fatura = $this->fatura->id_fatura;
      $this->id_predicado = $this->getItemMaster->get()->body->id_predicado;
      $this->descricao = $this->getItemCustom->get()->body;
      $this->qtd = $this->item_processo->qtd;
      $this->valor_custo = $this->item_processo->valor_custo ?? null;
      $this->dta_inicio = $this->item_processo->dta_inicio;
      $this->dta_final = $this->item_processo->dta_final;
      $this->periodo = $this->item_processo->periodo;

      $this->id_faturaitemlegenda = $this->checkIsIntoPacote->check() 
                        ?3 // Incluso
                        : ( !$itemProposta->isLoaded()
                            ? 1 // Não em proposta
                            : ( ( $itemProposta->isLoaded() > 0 and $itemProposta->valor === 'sc' ) 
                                ? 2 // Sobre consulta
                                : null ));
      $this->valor_unit = $recalcular
      ? (!$this->checkIsIntoPacote->check() ? ($itemProposta->isLoaded()
                      ? $this->calcValorUnitario->calc() 
                      : 0) 
                  : 0) : 0;

      $this->valor_item = $recalcular
      ? (!$this->checkIsIntoPacote->check() 
          ? ($itemProposta->isLoaded()
              ? $this->calcValorVenda->calc() 
              : 0) 
          : 0) 
      : 0;
      $this->removeProperty('recalcular');
      $this->store();
      $response = new StdClass;
      $response->id_predicado = $this->id_predicado;
      $response->valor_item = $this->valor_item;
      return ok($response);
    } catch (\Error $th) {
      return serverError($th);
    }
  }
}
