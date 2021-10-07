<?php
namespace App\Model\Fatura\Calculo;

use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Model\Servico\ItemPadrao;
use App\Model\Captacao\Captacao;
use App\Model\Servico\PreProAppValor;
use App\Model\Servico\Predicado;
use App\Lib\Tool\Help;

abstract class CalculoItem extends Help
{
    use CalcAppValor;
    public $item = [];

    /**
     * Metodo que verifica se ultrapassou o periodo do item na proposta
     * @param Number $dias_consumo Dias consumidos durante o processo
     * @param Despacho | $operacao  Captacao Movimentação do processo
     * @param ItemPadrao $servico Item Pardão a ser verificado se ultrapassou
     * @param PredicadoMaster $predicado_master Predicado master do item
     * @param Number $qtd Quantidade de containeres
     * @param Number | String $dimensao Dimensão do container
     * @param Number $valor_mercadoria Valor da Mercadoria
     */
    public function checkPeriodo($dias_consumo = null, $operacao = null, $servico, $predicado_master, $qtd, $dimensao, $valor_mercadoria)
    {
     
        $periodo = 0;
        // Buscando item da proposta para ver o periodo
        $predicado_proposta = $this->buscarItemProposta($operacao, $dimensao, $servico);
        if (count($predicado_proposta) > 0) {
            $franquia_periodo = $predicado_proposta[0]->franquia_periodo;
        
            // Verificando dias consumo
            if (!is_null($franquia_periodo) and $dias_consumo > $franquia_periodo) {
                $periodo = (ceil($dias_consumo - $franquia_periodo));
                // Busca o item condicional
                // print_r($dias_consumo / $franquia_periodo );
                // exit();
                $id_predicado_master = $predicado_master['id_predicado'] ?? null;
                if ($id_predicado_master !== null) {
                    $criteria = new Criteria;
                    $criteria->add(new Filter('id_predicadomaster', '=', $id_predicado_master));
                    $criteria->add(new Filter('tipo', '=', 'excesso_periodo'));
                    $repository = new Repository('App\Model\Servico\ItemCondicional');
                    $item_condicional = $repository->load($criteria);
                    // Verificando se encontrou item condicional
                    if (count($item_condicional)) {
                        $item_condicional =  $item_condicional[0]->item;
                        // Buscando item condicional na proposta
                        $predicado_proposta_condicional = $this->buscarItemProposta($operacao, 40, $item_condicional);
                        if (count($predicado_proposta_condicional)) {
                            $predicado_proposta_condicional_arr = $predicado_proposta_condicional[0]->predicado->toArray();
                            $predicado_proposta_condicional_arr['dimensao'] = (string) $dimensao;
                            $predicado_proposta_condicional_arr['id_propostapredicado'] = $predicado_proposta_condicional[0]->id_propostapredicado;
                            $predicado_proposta_condicional_arr['qtd'] = $qtd;
                            $predicado_proposta_condicional_arr['dta_inicio'] = $this->item_padrao->store_date->nextDay();
                            $predicado_proposta_condicional_arr['dta_final'] = $this->item_padrao->store_date->dataFinal($this->item_padrao->dias_consumo - 1);
                            $predicado_proposta_condicional_arr['periodo'] = $periodo;
                            $predicado_proposta_condicional_arr['classificacao'] = 'periodo';
                            $this->item[] = $predicado_proposta_condicional_arr;
                           

                        }
                    }
                }
                return $periodo;
            }
        }
    }

    public function checkAdicional($predicado_master = null, Captacao $operacao = null, $valor_mercadoria, $qtd)
    {   
        // Verificando valor máximo
        // if ($vl_max != null && $valor_mercadoria > $vl_max) {
        $itemAdicional = $this->getItemAdicional($predicado_master['id_predicado']);
        if (count($itemAdicional) > 0) {
            $itemAdicional_arr = $itemAdicional[0]->item->toArray();

            // Buscando o item adicional da proposta
            $predicado_proposta = $this->buscarItemProposta($operacao, 'ambos', $itemAdicional[0]->item)[0];

            // Buscando a aplicação do valor do item
            $app_valor = new PreProAppValor($predicado_proposta->id_predproappvalor);

            // Buscando o valor do item já calculado
            $itemAdicional_arr['id_propostapredicado'] = $predicado_proposta[0]->id_propostapredicado;

            // $itemAdicional_arr['valor_item'] = $this->calcAppValor($app_valor, $predicado_proposta[0], $valor_mercadoria, $qtd);
            $itemAdicional_arr['dimensao'] = "40";
            $itemAdicional_arr['qtd'] = $qtd;
            $this->item[] = $itemAdicional_arr;
        }
        // }
    }

    public function buscarItemProposta($operacao = null, $dimensao = null, $servico = null)
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_proposta', '=', $operacao->id_proposta));
        $criteria->add(new Filter('id_predicado', '=', $servico->id_predicado));
        $criteria->add(new Filter('dimensao', '=', $dimensao));
        $repository = new Repository('App\Model\Proposta\PropostaPredicado');
        return $repository->load($criteria);
    }


    public function getItemMaster($predicado)
    {
        $criteria = new Criteria;
        // Verifica se o predicado têm um item master de maior importancia
        $criteria->add(new Filter('id_predicadoslave', '=', $predicado->id_predicado));
        $repository = new Repository('App\Model\Servico\ItemMaster');
        $predicado_master = $repository->load($criteria);
        // Verifica se encontrou
        if (count($predicado_master) > 0) {
            $predicado_m_arr = $predicado_master[0]->master;
            // Busca o predicado master e mais importante
            $predicados[] = $predicado_m_arr ?? null;
        } else {
            $predicados[] = (new Predicado($predicado->id_predicado))->toArray();
        }
        return $predicados[0];
    }

    public function getItemAdicional($id_master = null)
    {
        if ($id_master) {
            $criteria = new Criteria;
            $criteria->add(new Filter('id_predicadomaster', '=', $id_master));
            $repository = new Repository('App\Model\Servico\ItemAdicional');
            $item_adicional = $repository->load($criteria);
            return count($item_adicional) > 0 ? $item_adicional : [];
        }
    }

    /**
     * Buscar servicos padrões por regime
     * @param Regime $regime Regime da operação
     */
    public static function getItensPadroes($regime = null, $modulo = null)
    {
        if (!is_null($regime)) {
            // Busca todos os servicos master pelo id do servico recebido     
            $criteriaMod = new Criteria;
            if (!is_null($modulo)) {
                $criteriaMod->add(new Filter('nome', '=', $modulo));
                $modulo = (new Repository('App\Model\Modulo\Modulo'))->load($criteriaMod);
                $criteriaMod->clean();
                if (count($modulo) > 0) {
                    $criteriaMod->add(new Filter('id_modulo', '=', $modulo[0]->id_modulo));
                }
            }

            $criteriaMod->setProperty('order', 'prioridade asc');

            $itens = (new Repository('App\Model\Servico\ItemPadrao'))->load($criteriaMod);
            $items = [];
            
            foreach ($itens as $key => $item) {
                $predicado = (new Predicado($item->id_predicado));
                $id_regime = (int) $predicado->regime->id_regime;
                $regime->id_regime = (int) $regime->id_regime;
                if ($id_regime === $regime->id_regime or $id_regime === 3)
                    $items[] = $item;
            }
            return $items;
            // print_r($criteriaMod->dump());exit();
            // return (new Repository('App\Model\Servico\ItemPadrao'))->load($criteriaMod);
        }
    }

    public function get_itens() {
        return $this->item;
    }

}
