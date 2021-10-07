<?php

namespace App\Model\Fatura\Calculo;

use App\Model\Servico\PreProAppValor;
use App\Model\Servico\Predicado;

/**
 * 
 */
trait CalcAppValor
{
    private $under_itens = [];
    /**
     * @param PropostaPredicado $item instancia de um predicado da proposta
     * @param Number $valor_marcadoria Valor da Mercadoriad
     * @param Number $qtd Quantidade    
     * @param Number $periodo Periodo
     * @param Array $itens_processo Itens do processo
     */
    public function calcAppValor($item = null, $valor_marcadoria = null, $qtd = null, $periodo = null, $itens_fatura = null, $dias_consumo = null)
    {   
        if ($item && $valor_marcadoria && $qtd) {
            $app_valor = $item->appvalor;
            
            // Verificando tipo de calculo
            switch ($app_valor->nome) {
                case 'por dia':
                    switch ($item->unidade) {
                        // Se for porcentagem
                        case 'Moeda':
                            // Calculando
                            if ($item->valor === 'sc')
                                return 0;
                            
                            // echo $dias_consumo;
                            // exit();
                            $vl_item = round(($dias_consumo * $item->valor) * $qtd, 2);
                            return $vl_item;  
                        break;
                    }
                break;

                case 'Volume':
                    // Calculando por volume
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (is_numeric($item->valor)) {
                            switch ($item->unidade) {
                                case 'moeda':
                                    $vl_total = $item->valor * $qtd ?? 0;
                                    break;

                                case '% Valor':
                                    $vl_total = $item->valor * $qtd ?? 0;
                                    break;
                            }
                            $vl_total = $item->valor * $qtd ?? 0;
                        }
                    }
                    return $vl_total;

                break;

                case 'Valor Excedente':
                    // calculando o excedente
                    $val_cif_divided = ($valor_marcadoria / $qtd);
                    switch ($item->unidade) {
                            // Se for porcentagem
                        case '% Valor':
                            if ($val_cif_divided > $item->valor_partir) {
                                $result = $val_cif_divided - $item->valor_partir;
                            } else {
                                $result = $item->valor_partir - $val_cif_divided;
                            }

                            $vl_item = round(((($result * $item->valor) / 100) * $qtd * ($periodo > 0 ? $periodo : 1)), 2);

                            return $vl_item;
                            break;
                    }
                    break;

                    // Calculando o excedente
                    $vl_total = ($item[0]->valor * $qtd) * ($periodo > 0  ?  $periodo : 1)  ?? 0;
                    return $vl_total;
                    break;

                case 'Valor Mercadoria':
                    // Calculando o excedente
                    $vl_total = ((($item->valor * $valor_marcadoria) / 100)) * ($periodo > 0  ?  $periodo : 1)  ?? 0;
                    return $vl_total;
                    break;

                case 'Serviço':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (is_numeric($item->valor)) {
                            // $vl_total = ($item->valor * $qtd) * ($periodo > 0  ?  $periodo : 1)  ?? 0;
                            $vl_total = $item->valor ?? 0;
                        }
                    }
                    return $vl_total;
                    break;

                case 'Contêiner':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (is_numeric($item->valor)) {
                            $vl_total = ($item->valor * $qtd)  ?? 0;
                        }
                    }
                    return $vl_total;
                    break;

                case 'Unidade':
                    // Calculando
                    $vl_total = ($item->valor * $qtd) * ($periodo > 0  ?  $periodo : 1) ?? 0;
                    return $vl_total;
                    break;

                case 'sobre todos os itens':                 
                    // Calculando
                    $valor_todos_itens = 0;
                    foreach ($itens_fatura as $key => $item_fatura) {
                        if ($item_fatura['id_predicado'] !== $item->id_predicado)
                            $valor_todos_itens += is_numeric($item_fatura['valor_item']) ? $item_fatura['valor_item'] : 0;
                    }
                    // variável que armazena o item já processado
                    array_push($this->under_itens, $item_fatura);
          

                    $vl_total = (($item->valor * $valor_todos_itens) / 100) ?? 0;

                    // Item do tipo imposto, calculo especifico
                    if ((new Predicado($item->id_predicado))->servico->nome === 'Impostos')
                         $vl_total = round(((round($valor_todos_itens, 2) / ((100 - $item->valor) / 100)) * ($item->valor / 100)) * $qtd, 2);
                    return $vl_total;
                    break;

                case 'sobre item de armazenagem':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (count($itens_fatura) === 0) {
                            if (is_numeric($item->valor)) {
                                $vl_total = $item->valor;
                            }
                        }

                        $valor_todos_itens = 0;
                        foreach ($itens_fatura as $key => $item_fatura) {
                            $item_fatura = (object) $item_fatura;
                            // print_r($item);exit();
                            $predicado = new Predicado($item_fatura->id_predicado);
                            if ($predicado->servico->nome === 'Armazenagem Container') {
                                $valor_todos_itens += $item_fatura->valor_item;
                            }
                        }
                        $vl_total = ((($valor_todos_itens * $item->valor) / 100) * $qtd) ?? 0;
                    }
                    return $vl_total;
                    break;

                case 'sobre item de armazenagem e seguro':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (count($itens_fatura) === 0) {
                            if (is_numeric($item->valor)) { 
                                $vl_total = $item->valor;
                            }
                        }

                        $valor_todos_itens = 0;
                        foreach ($itens_fatura as $key => $item_fatura) {
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
                        $vl_total = ( ( ( $valor_todos_itens / $qtd ) * $item->valor) / 100 ) * $qtd ?? 0;
                    }

                    return $vl_total;
                    break;

                case 'sobre o valor da mercadoria':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        $vl_total = ($item->valor * $valor_marcadoria) / 100;
                    }

                    return $vl_total;
                    break;

                default:
                    break;
            }
        }
    }

    public static function calcValor($item = null, $valor_marcadoria = null, $qtd = null, $periodo = null, $itens_fatura = null, $dias_consumo = null, $valor)
    {
        if ($item && $valor_marcadoria && $qtd) {
            $app_valor = $item->appvalor;

            // Verificando tipo de calculo
            switch ($app_valor->nome) {
                case 'por dia':
                    switch ($item->unidade) {
                        // Se for porcentagem
                        case 'Moeda':
                            // Calculando
                            $vl_item = round(($dias_consumo * $valor), 2);
                            return $vl_item;  
                        break;
                    }
                break;

                case 'Valor Excedente':
                    // calculando o excedente
                    $val_cif_divided = ($valor_marcadoria / $qtd);
                    switch ($item->unidade) {
                            // Se for porcentagem
                        case '% Valor':
                            if ($val_cif_divided > $item->valor_partir) {
                                $result = $val_cif_divided - $item->valor_partir;
                            } else {
                                $result = $item->valor_partir - $val_cif_divided;
                            }
                            $vl_item = round(((($result * $item->valor) / 100) * $qtd), 2);
                            return $vl_item;
                            break;
                    }
                    break;

                    // Calculando o excedente
                    $vl_total = ($item[0]->valor * $qtd) * ($periodo > 0  ?  $periodo : 1)  ?? 0;
                    return $vl_total;
                    break;

                case 'Valor Mercadoria':
                    // Calculando o excedente
                    $vl_total = ((($item->valor * $valor_marcadoria) / 100)) * ($periodo > 0  ?  $periodo : 1)  ?? 0;
                    return $vl_total;
                    break;

                case 'Serviço':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (is_numeric($item->valor)) {
                            $vl_total = ($item->valor * $qtd) * ($periodo > 0  ?  $periodo : 1)  ?? 0;
                        }
                    }
                    return $vl_total;
                    break;

                case 'Contêiner':
                    // Calculando
                    $vl_total = $valor * $qtd ?? 0;
                    return $vl_total;
                    break;

                case 'Unidade':
                    // Calculando
                    $vl_total = $item->valor * $qtd ?? 0;
                    return $vl_total;
                    break;

                case 'sobre todos os itens':
                    // Calculando
                    $valor_todos_itens = 0;
                    foreach ($itens_fatura as $key => $item_fatura) {
                        $valor_todos_itens += is_numeric($item_fatura['valor_item']) ? $item_fatura['valor_item'] : 0;
                    }
                    $vl_total = (($item->valor * $valor_todos_itens) / 100) ?? 0;
                    //
                    // Item do tipo imposto, calculo especifico
                    if ((new Predicado($item->id_predicado))->servico->nome === 'Impostos')
                            $vl_total = round(((round($valor_todos_itens, 2) / ((100 - $item->valor) / 100)) * ($item->valor / 100)), 2);
                    return $vl_total;
                    break;

                case 'sobre item de armazenagem':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (count($itens_fatura) === 0) {
                            if (is_numeric($item->valor)) {
                                $vl_total = $item->valor;
                            }
                        }

                        $valor_todos_itens = 0;
                        foreach ($itens_fatura as $key => $item_fatura) {
                            $item_fatura = (object) $item_fatura;
                            // print_r($item);exit();
                            $predicado = new Predicado($item_fatura->id_predicado);
                            if ($predicado->servico->nome === 'Armazenagem Container') {
                                $valor_todos_itens += $item_fatura->valor_item;
                            }
                        }
                        $vl_total = ((($valor_todos_itens * $item->valor) / 100) * $qtd) * ($periodo > 0  ?  $periodo : 1)  ?? 0;
                        return $vl_total;
                    }

                case 'sobre o valor da mercadoria':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        $vl_total = ($item->valor * $valor_marcadoria) / 100;
                    }

                    return $vl_total;
                    break;

                default:
                    break;
            }
        }
    }

    

    /**
     * @param PreProAppValor||Numeric $app_valor id da aplicação ou a instancia
     * @param PropostaPredicado $item instancia de um predicado da proposta
     */
    public static function valorUnitario($item = null, $valor_mercadoria = null, $qtd = null, $periodo = null, $itens_fatura = null)
    {

        if ($item && $valor_mercadoria && $qtd) {
            $app_valor = $item->appvalor;
           
            // Verificando tipo de calculo
            switch ($app_valor->nome) {
                case 'por dia':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (is_numeric($item->valor)) {
                            $vl_total = $item->valor ?? 0;
                        }
                    }
                    return $vl_total;

                break;

                case 'Volume':
                    // Calculando por volume
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (is_numeric($item->valor)) {
                            switch ($item->unidade) {
                                case 'moeda':
                                    $vl_total = $item->valor ?? 0;
                                    break;

                                case '% Valor':
                                    $vl_total = $item->valor ?? 0;
                                    break;
                            }
                            $vl_total = $item->valor * $qtd ?? 0;
                        }
                    }
                    return $vl_total;

                break;
                
                case 'Serviço':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (is_numeric($item->valor)) {
                            $vl_total = $item->valor ?? 0;
                        }
                    }
                    return $vl_total;
                break;

                case 'Valor Excedente':
                    $val_cif_divided = ($valor_mercadoria / $qtd);
                    switch ($item->unidade) {
                            // Se for porcentagem
                        case '% Valor':
                            if ($val_cif_divided > $item->valor_partir) {
                                $result = $val_cif_divided - $item->valor_partir;
                            } else {
                                $result = $item->valor_partir - $val_cif_divided;
                            }
                            $vl_item = round(((($result * $item->valor) / 100)), 2); 
                            return $vl_item;
                            break;
                    }
                    break;

                 
                case 'Valor Mercadoria':
                    switch ($item->unidade) {
                            // Se for porcentagem
                        case '% Valor':
                            $vl_item = round(((($item->valor * $valor_mercadoria) / 100)), 2);
                            // echo $qtd;exit();
                            return $vl_item;
                            break;
                    }

                case 'Serviço':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (is_numeric($item->valor)) {
                            $vl_total = $item->valor ?? 0;
                        }
                    }
                    return $vl_total;
                    break;

                case 'Contêiner':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        $vl_total = $item->valor ?? 0;
                    }
                    return $vl_total;
                    break;

                case 'Unidade':
                    $vl_total = $item->valor ?? 0;
                    return $vl_total;
                    break;

                case 'sobre todos os itens':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (count($itens_fatura) === 0) {
                            if (is_numeric($item->valor)) {
                                $vl_total = $item->valor;
                            }
                        }
                        $valor_todos_itens = 0;
                        foreach ($itens_fatura as $key => $item_fatura) {
                            $valor_todos_itens += is_numeric($item_fatura['valor_item']) ? $item_fatura['valor_item'] : 0;
                        }
                        $vl_total = (($item->valor * $valor_todos_itens) / 100);
                        //
                        // Item do tipo imposto, calculo especifico
                        if ((new Predicado($item->id_predicado))->servico->nome === 'Impostos')
                            $vl_total = round(((round($valor_todos_itens, 2) / ((100 - $item->valor) / 100)) * ($item->valor / 100)), 2);
                    }

                    return $vl_total;
                    break;

                case 'sobre item de armazenagem':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        if (count($itens_fatura) === 0) {
                            if (is_numeric($item->valor)) {
                                $vl_total = $item->valor;
                            }
                        }

                        $valor_todos_itens = 0;
                        foreach ($itens_fatura as $key => $item_fatura) {
                            $item_fatura = (object) $item_fatura;
                            // print_r($item);exit();
                            $predicado = new Predicado($item_fatura->id_predicado);
                            if ($predicado->servico->nome === 'Armazenagem Container') {
                                $valor_todos_itens += $item_fatura->valor_item;
                            }
                        }
                        $vl_total = (($valor_todos_itens * $item->valor) / 100) ?? 0;
                    }

                    return $vl_total;
                    break;

                case 'sobre item de armazenagem e seguro':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        switch ($item->unidade) {
                            // Se for porcentagem
                            case '% Valor':
                                $valor_todos_itens = 0;
                                foreach ($itens_fatura as $key => $item_fatura) {
                                    $item_fatura = (object) $item_fatura;
                                    // print_r($item);exit();
                                    $predicado = new Predicado($item_fatura->id_predicado);
                                    if ($predicado->servico->nome === 'Armazenagem Container' or $predicado->servico->nome === 'Seguro Armazenagem') {
                                        // if ($predicado->nome === 'Armazenagem container 1º período' or $predicado->nome === 'Seguro armazenagem') {
                                            $valor_todos_itens += $item_fatura->valor_item;
                                        // }
                                    }
                                    
                                }
              
                                $vl_total = ( ( ( $valor_todos_itens / $qtd ) * $item->valor) / 100);
                                // echo $valor_todos_itens;
                                // exit();
                                break;

                            case 'Moeda':
                                $vl_total = $vl_item->valor;
                                break;
                        }
                    }
                    return $vl_total;
                    break;
                
                case 'sobre o valor da mercadoria':
                    // Calculando
                    if ($item->valor === 'sc') {
                        $vl_total = 0;
                    } else {
                        $vl_total = ($item->valor * $valor_mercadoria) / 100;
                    }

                    return $vl_total;
                    break;


                default:
                    break;
            }
        }
    }
}
