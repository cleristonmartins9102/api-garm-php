<?php

namespace App\UserCase\Helper\Fatura;

use App\Model\Servico\Predicado;
use App\UserCase\Helper\Fatura\Protocols\Calc;
use DateTime;
use Domain\Fatura\GetItem;

use function App\UserCase\Helper\calcDiasConsumo;

class CalcAppValor implements Calc{
  protected $item;
  protected float $valor_marcadoria;
  protected int $qtd;
  protected int $periodo;
  protected $itens_fatura;
  protected int $dias_consumo;

  public function __construct(GetItem $item = null, $valor_mercadoria = null, $qtd = null, $periodo = null, $itens_fatura = null, DateTime $dataInicio, DateTime $dataFinal)
  {
    $this->item = $item->get()->body;
    $this->valor_mercadoria = $valor_mercadoria;
    $this->qtd = $qtd;
    $this->periodo = $periodo;
    $this->itens_fatura = $itens_fatura;
    $this->dias_consumo = calcDiasConsumo($dataInicio, $dataFinal);
  }
  public function calc()
  {   
      if ($this->item && $this->valor_mercadoria && $this->qtd) {
          $app_valor = $this->item->appvalor;
          
          // Verificando tipo de calculo
          switch ($app_valor->nome) {
              case 'por dia':
                  switch ($this->item->unidade) {
                      // Se for porcentagem
                      case 'Moeda':
                          // Calculando
                          if ($this->item->valor === 'sc')
                              return 0;
                          
                          // echo $dias_consumo;
                          // exit();
                          $vl_item = round(($this->dias_consumo * $this->item->valor) * $this->qtd, 2);
                          return $vl_item;  
                      break;
                  }
              break;

              case 'por dia - sobre cif':
                  switch ($this->item->unidade) {
                      // Se for porcentagem
                      case 'Moeda':
                          // Calculando
                          if ($this->item->valor === 'sc')
                              return 0;
                          
                          // echo $dias_consumo;
                          // exit();
                          $vl_item = round(($this->dias_consumo * $this->item->valor) * $this->qtd, 2);
                          return $vl_item;  
                      break;

                      case '% Valor':
                          // Calculando
                          if ($this->item->valor === 'sc')
                              return 0;
                          // echo ( $valor_mercadoria * $item->valor ) / 100;
                          // print_r((($valor_mercadoria * $item->valor ) / 100 ) );
                          $vl_item = round(((( $this->valor_mercadoria * $this->item->valor ) / 100 ) ) * $this->periodo, 2);
                          return $vl_item >= 200 ? $vl_item : round(200, 2);
                      break;
                  }
              break;

              case 'Volume':
                  // Calculando por volume
                  if ($this->item->valor === 'sc') {
                      $vl_total = 0;
                  } else {
                      if (is_numeric($this->item->valor)) {
                          switch ($this->item->unidade) {
                              case 'moeda':
                                  $vl_total = $this->item->valor * $this->qtd ?? 0;
                                  break;

                              case '% Valor':
                                  $vl_total = $this->item->valor * $this->qtd ?? 0;
                                  break;
                          }
                          $vl_total = $this->item->valor * $this->qtd ?? 0;
                      }
                  }
                  return $vl_total;

              break;

              case 'Valor Excedente':
                  // calculando o excedente
                  $val_cif_divided = ($this->valor_mercadoria / $this->qtd);
                  switch ($this->item->unidade) {
                          // Se for porcentagem
                      case '% Valor':
                          if ($val_cif_divided > $this->item->valor_partir) {
                              $result = $val_cif_divided - $this->item->valor_partir;
                          } else {
                              $result = $this->item->valor_partir - $val_cif_divided;
                          }

                          $vl_item = round(((($result * $this->item->valor) / 100) * $this->qtd * ($this->periodo > 0 ? $this->periodo : 1)), 2);

                          return $vl_item;
                          break;
                  }
                  break;

                  // Calculando o excedente
                  $vl_total = ($this->item[0]->valor * $this->qtd) * ($this->periodo > 0  ?  $this->periodo : 1)  ?? 0;
                  return $vl_total;
                  break;

              case 'Valor Mercadoria':
                  // Calculando o excedente
                  $vl_total = ((($this->item->valor * $this->valor_mercadoria) / 100)) * ($this->periodo > 0  ?  $this->periodo : 1)  ?? 0;
                  return $vl_total;
                  break;

              case 'Serviço':
                  // Calculando
                  if ($this->item->valor === 'sc') {
                      $vl_total = 0;
                  } else {
                      if (is_numeric($this->item->valor)) {
                          // $vl_total = ($item->valor * $qtd) * ($periodo > 0  ?  $periodo : 1)  ?? 0;
                          $vl_total = $this->item->valor ?? 0;
                      }
                  }
                  return $vl_total;
                  break;

              case 'Contêiner':
                  // Calculando
                  if ($this->item->valor === 'sc') {
                      $vl_total = 0;
                  } else {
                      if (is_numeric($this->item->valor)) {
                          $vl_total = ($this->item->valor * $this->qtd)  ?? 0;
                      }
                  }
                  return $vl_total;
                  break;

              case 'Unidade':
                  // Calculando
                  $vl_total = ($this->item->valor * $this->qtd) * ($this->periodo > 0  ?  $this->periodo : 1) ?? 0;
                  return $vl_total;
                  break;

              case 'sobre todos os itens':
                  // Calculando
                  $valor_todos_itens = 0;
                  foreach ($this->itens_fatura as $key => $item_fatura) {
                      if ($item_fatura['id_predicado'] !== $this->item->id_predicado)
                          $valor_todos_itens += is_numeric($item_fatura['valor_item']) ? $item_fatura['valor_item'] : 0;
                  }
                  // variável que armazena o item já processado
                  array_push($this->under_itens, $item_fatura);
        

                  $vl_total = (($this->item->valor * $valor_todos_itens) / 100) ?? 0;

                  // Item do tipo imposto, calculo especifico
                  if ((new Predicado($this->item->id_predicado))->servico->nome === 'Impostos')
                       $vl_total = round(((round($valor_todos_itens, 2) / ((100 - $this->item->valor) / 100)) * ($this->item->valor / 100)) * $this->qtd, 2);
                  return $vl_total;
                  break;

              case 'sobre item de armazenagem':
                  // Calculando
                  if ($this->item->valor === 'sc') {
                      $vl_total = 0;
                  } else {
                      if (count($this->itens_fatura) === 0) {
                          if (is_numeric($this->item->valor)) {
                              $vl_total = $this->item->valor;
                          }
                      }

                      $valor_todos_itens = 0;
                      foreach ($this->itens_fatura as $key => $item_fatura) {
                          $item_fatura = (object) $item_fatura;
                          // print_r($item);exit();
                          $predicado = new Predicado($item_fatura->id_predicado);
                          if ($predicado->servico->nome === 'Armazenagem Container') {
                              $valor_todos_itens += $item_fatura->valor_item;
                          }
                      }
                      $vl_total = ((($valor_todos_itens * $this->item->valor) / 100) * $this->qtd) ?? 0;
                  }
                  return $vl_total;
                  break;

              case 'sobre item de armazenagem e seguro':
                  // Calculando
                  if ($this->item->valor === 'sc') {
                      $vl_total = 0;
                  } else {
                      if (count($this->itens_fatura) === 0) {
                          if (is_numeric($this->item->valor)) { 
                              $vl_total = $this->item->valor;
                          }
                      }

                      $valor_todos_itens = 0;
                      foreach ($this->itens_fatura as $key => $item_fatura) {
                          $item_fatura = (object) $item_fatura;
                          // print_r($item);exit();
                          $predicado = new Predicado($item_fatura->id_predicado);
                          if ($predicado->servico->nome === 'Armazenagem Container' or $predicado->servico->nome === 'Seguro Armazenagem') {
                              // if ($predicado->nome === 'Armazenagem container 1º período' or $predicado->nome === 'Seguro armazenagem') {                                    
                                  $valor_todos_itens += $item_fatura->valor_item;
                              // }
                          }
                      }
                      // echo $valor_todos_itens;exit();
                      $vl_total = ( ( ( $valor_todos_itens / $this->qtd ) * $this->item->valor) / 100 ) * $this->qtd ?? 0;
                  }

                  return $vl_total;
                  break;

              case 'sobre o valor da mercadoria':
                  // Calculando
                  if ($this->item->valor === 'sc') {
                      $vl_total = 0;
                  } else {
                      $vl_total = ($this->item->valor * $this->valor_mercadoria) / 100;
                  }

                  return $vl_total;
                  break;

              default:
                  break;
          }
      }
  }
}