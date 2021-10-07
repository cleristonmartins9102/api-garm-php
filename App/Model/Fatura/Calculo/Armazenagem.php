<?php

namespace App\Model\Fatura\Calculo;

use App\Model\Captacao\Captacao;
use App\Model\Despacho\Despacho;
use App\Model\Servico\ItemPadrao;
use App\Model\Servico\Predicado;
use App\Model\Servico\PreProAppValor;

class Armazenagem extends CalculoItem
{
    private $dta_inicio;
    private $dias_consumo;
    private $qtd_container_total;
    private $valor_mercadoria;
    private $operacao;
    private $servico;
    private $periodo_total = 0;

    function __construct($modulo, $operacao = null, ItemPadrao $item_padrao, $regime)
    {
        $this->valor_mercadoria = $item_padrao->valor_mercadoria;
        $this->qtd_container_total = $operacao->qtdcontainer[20] + $operacao->qtdcontainer[40];
        $this->dias_consumo = $item_padrao->dias_consumo;
        $this->operacao = $operacao;
        $this->dta_inicio = isset($item_padrao->dta_inicio) ? date('Y-m-d', strtotime($item_padrao->dta_inicio)) : null;
        $this->item_padrao = $item_padrao;
        // Pegando os itens
        $itens = $item_padrao->getItensPadroes($regime, $item_padrao->modulo);
        foreach ($itens as $key => $item) {
            $this->servico = $item;
            $classificacao = $item->classificacao()->classificacao;
            $item_modulo = $item->modulo()->nome;
            if (method_exists($this, $classificacao)) {
                if ($item_modulo === $modulo) {
                    call_user_func(array($this, $classificacao), $operacao, $item);
                }
            } else {
                echo "Methodo não existe para a classificação $classificacao do item";
                exit();
            }
        }
    }

    public function itemComum($servico = null)
    {
        $dimensao = 'ambos';
        $id_predicado = $servico->id_predicado ?? null;
        $predicado = new Predicado($id_predicado);
        $predicado_proposta = $this->buscarItemProposta($this->operacao, $dimensao, $predicado);

        // Verificando se encontrou o item na proposta
        if (count($predicado_proposta) > 0) {
            $predicado_arr = $predicado->toArray();
            $predicado_arr['id_propostapredicado'] = $predicado_proposta[0]->id_predicado;
            // $predicado_arr['valor_item'] = $this->calcAppValor($predicado_proposta[0]->id_predproappvalor, $predicado_proposta, $this->valor_mercadoria, $this->qtd_container_total, $this->periodo_total);
            $predicado_arr['qtd'] = $this->qtd_container_total;
            $predicado_arr['dimensao'] = $dimensao;
            $predicado_arr['periodo'] = $this->periodo_total;
            $this->item[] = $predicado_arr;
        }
    }

    /**
     * Verifica os itens do tipo periodo
     * @param Despacho | Captacao $operacao a operação dos itens, pode ser um Despacho ou uma Captação.
     */
    private function periodo($operacao)
    {
        if ($operacao->qtdcontainer[20] > 0) {
            $this->container20();
        }
        $this->item_padrao->store_date->reset();
        if ($operacao->qtdcontainer[40] > 0) {
            $this->container40();
        }
        $this->item_padrao->store_date->reset();
        $this->checkExcedente();
    }

    /**
     * Verifica os itens do tipo seguro
     * @param Despacho | Captacao $operacao a operação dos itens, pode ser um Despacho ou uma Captação.
     * @param ItemPadrao $item Item a ser processado
     */
    private function seguro($operacao, $item)
    {
        $item_proposta = $this->buscarItemProposta($operacao, 'ambos', $item);
        if (isset($item->id_predicado) && !is_null($item->id_predicado) && count($item_proposta) > 0) {
            $predicado = new Predicado($item->id_predicado);
            $predicado->qtd = 1;
            $predicado->dta_inicio = $this->dta_inicio;
            $this->dias_consumo = $this->dias_consumo - 1;
            $predicado->dta_final = date('Y-m-d', strtotime($this->dta_inicio . " +{$this->dias_consumo} day"));
            $predicado->dimensao = 'ambos';
            $predicado->periodo = $this->periodo_total;
            $this->item[] = $predicado->toArray();
        }
    }

    /**
     * Verifica os itens do tipo seguro
     * @param Despacho | Captacao $operacao a operação dos itens, pode ser um Despacho ou uma Captação.
     * @param ItemPadrao $item Item a ser processado
     */
    private function imposto($operacao, $item)
    {
        if (isset($item->id_predicado) && !is_null($item->id_predicado)) {
            $this->item[] = $item;
        }
    }

    private function opentop_flatrack($operacao, $item)
    {
        $containeres = $operacao->container;
        $opentop_count = 0;

        foreach ($containeres as $key => $container) {
            $tipo = preg_replace('/[0-9]+/', '', $container->tipo);
            if ($tipo === 'OT' || $tipo === 'FR')
                $opentop_count += 1;
        }
        if ($opentop_count > 0) {
            $predicado = new Predicado($item->id_predicado);
            $predicado->qtd = $opentop_count;
            $predicado->dta_inicio = $this->dta_inicio;
            $predicado->dta_final = date('Y-m-d', strtotime($this->dta_inicio . " +{$this->dias_consumo} day"));
            $predicado->dimensao = 'ambos';
            $predicado->periodo = $this->periodo_total;
            $this->item[] = $predicado->toArray();
        }
    }

    /**
     * Metodo que verifica se a operação possue container do tipo refrigerado, caso sim ele insere o item padrão 'Energia Elétrica' 
     * @param Captacao | Despacho $operacao = Operação a ser verificada
     * @param $item = $item padrão do tipo classificacao reefer
     */
    private function reefer($operacao, $item) {
        $containeres = $operacao->container;
        $opentop_count = 0;

        foreach ($containeres as $key => $container) {
            // echo $container->tipo;
            $tipo = preg_replace('/[0-9]+/', '', $container->tipo);
            if ($tipo === 'RF')
                $opentop_count += 1;
        }
        if ($opentop_count > 0) {
            $predicado = new Predicado($item->id_predicado);
            $predicado->qtd = $opentop_count;
            $predicado->dta_inicio = $this->dta_inicio;
            $predicado->dta_final = date('Y-m-d', strtotime($this->dta_inicio . " +{$this->dias_consumo} day"));
            $predicado->dimensao = 'ambos';
            $predicado->periodo = $this->periodo_total;
            $this->item[] = $predicado->toArray();
        }

    }

    private function imo($operacao, $item_current)
    {
        if ($operacao instanceof Captacao && $operacao->imo === 'sim') {
            $valor_total_arm = 0;
            $qtd = 0;
            foreach ($this->itens as $key => $item) {
                $item = (object) $item;
                $predicado = new Predicado($item->id_predicado);
                if ($predicado->servico->nome === 'Armazenagem Container') {
                    if ($predicado->nome === 'Armazenagem container 1º período');
                        // $valor_total_arm += $item->valor_item ?? 0;
                        $qtd = $item->qtd;
                }
            }
            $predicado = new Predicado($item_current->id_predicado);
            $predicado->qtd = $qtd;
            $predicado->dta_inicio = $this->dta_inicio;
            $predicado->dta_final = date('Y-m-d', strtotime($this->dta_inicio . " +{$this->dias_consumo} day"));
            $predicado->dimensao = 'ambos';
            $predicado->periodo = 0;
            $this->item[] = $predicado->toArray();
        }
    }

    /**
     * Metodo que verifica se a liberacao é do tipo ddc
     */
    private function ddc($operacao, $item_current) {
        if ($operacao instanceof Captacao && $operacao->liberacao->tipo_operacao === 'DDC') {
            $predicado = new Predicado($item_current->id_predicado);
            $predicado_proposta = $this->buscarItemProposta($this->operacao, 'ambos', $predicado);
            if (count($predicado_proposta) === 0)
                return false;
                           
            $qtd = $operacao->qtdcontainer['20'] + $operacao->qtdcontainer['40'];
            $predicado->qtd = $qtd;
            $predicado->dta_inicio = $this->dta_inicio;
            $predicado->dta_final = date('Y-m-d', strtotime($this->dta_inicio . " +{$this->dias_consumo} day"));
            $predicado->dimensao = 'ambos';
            $predicado->periodo = 0;
            $this->item[] = $predicado->toArray();
        }
    }

    /**
     * Metodo que verifica se a liberacao é do tipo ddc
     */
    private function advalore_ambas_dimensoes($operacao, $item_current) {
        if ($operacao instanceof Despacho) {
            $predicado = new Predicado($item_current->id_predicado);
            $predicado_proposta = $this->buscarItemProposta($this->operacao, 'ambos', $predicado);

            if (count($predicado_proposta) === 0)
                return false;
                           
            $predicado->qtd = 1;
            $predicado->dta_inicio = $this->dta_inicio;
            $predicado->dta_final = date('Y-m-d', strtotime($this->dta_inicio . " +{$this->dias_consumo} day"));
            $predicado->dimensao = 'ambos';
            $predicado->periodo = 0;
            $this->item[] = $predicado->toArray();
        }
    }

    public function remocao_container($operacao, $item_current) {
        if ($operacao instanceof Captacao) {
            if ($operacao->id_terminal_atracacao === $operacao->id_terminal_redestinacao)
                return;

            $predicado = new Predicado($item_current->id_predicado);
            $predicado_proposta = $this->buscarItemProposta($this->operacao, 'ambos', $predicado);

            if (count($predicado_proposta) === 0)
                return false;            

            $qtd = $operacao->qtdcontainer['20'] + $operacao->qtdcontainer['40'];
            $predicado->qtd = $qtd;
            $predicado->dta_inicio = $this->dta_inicio;
            $predicado->dta_final = date('Y-m-d', strtotime($this->dta_inicio . " +{$this->dias_consumo} day"));
            $predicado->dimensao = 'ambos';
            $predicado->periodo = 1;
            $this->item[] = $predicado->toArray();
        }
    }

    public function recebimento_container_cheio($operacao, $item_current) {
        if ($operacao instanceof Despacho) {
            if ( !$operacao->proposta->regime_classificacao->isLoaded() ) return;
            
            if ( $operacao->proposta->regime_classificacao->classificacao === 'pré-stacking' ) {
                $predicado_master = $this->getItemMaster($this->servico);

                //Buscando o item da proposta
                $predicado_proposta = $this->buscarItemProposta($this->operacao, 'ambos', $this->servico);
                if (count($predicado_proposta) > 0) {
                    $predicado_proposta = $predicado_proposta[0];

                    // Definindo váriavel com o balor máximo da proposta
                    $vl_max = $predicado_proposta->valor_maximo ?? null;

                    $qtd = $operacao->qtdcontainer['20'] + $operacao->qtdcontainer['40'];

                    // Inserindo o valor do item master
                    $predicado_master['id_propostapredicado'] = $predicado_proposta->id_predicado;
                    // $predicado_master['valor_item'] = $predicado_proposta->valor * $qtd_container;
                    $predicado_master['dimensao'] = (string) 'ambos';
                    // echo $predicado_proposta->franquia_periodo;exit();
                    $predicado_master['dta_inicio'] = $this->item_padrao->store_date->first();
                    $this->dias_consumo--;
                    $predicado_master['dta_final'] =  date('Y-m-d', strtotime($this->dta_inicio . " +{$this->dias_consumo} day"));

                    // $predicado_master['dta_final'] = $this->item_padrao->store_date->calcDatePeriodo(((int)$predicado_proposta->franquia_periodo) - 1);
                    $predicado_master['periodo'] = 0;
                    $predicado_master['qtd'] = $qtd;
                    // $predicado_master['classificacao'] = 'periodo';
                    $this->item[] = $predicado_master;
                }
                return $this->item;      
    
            }
        }
    }

    public function retirada_container_vazio_depot($operacao, $item_current) {
        if ($operacao instanceof Despacho) {
            if ( !$operacao->proposta->regime_classificacao->isLoaded() ) return;
            if ( $operacao->proposta->regime_classificacao->classificacao === 'redex' ) {
                
                $predicado_master = $this->getItemMaster($this->servico);

                //Buscando o item da proposta
                $predicado_proposta = $this->buscarItemProposta($this->operacao, 'ambos', $this->servico);
                if (count($predicado_proposta) > 0) {
                    $predicado_proposta = $predicado_proposta[0];

                    // Definindo váriavel com o balor máximo da proposta
                    $vl_max = $predicado_proposta->valor_maximo ?? null;

                    $qtd = $operacao->qtdcontainer['20'] + $operacao->qtdcontainer['40'];
                    
                    // Inserindo o valor do item master
                    $predicado_master['id_propostapredicado'] = $predicado_proposta->id_predicado;
                    $predicado_master['dimensao'] = (string) 'ambos';
                    $predicado_master['dta_inicio'] = $this->item_padrao->store_date->first();
                    $this->dias_consumo--;
                    $predicado_master['dta_final'] = date('Y-m-d', strtotime($this->dta_inicio . " +{$this->dias_consumo} day"));

                    $predicado_master['periodo'] = 0;
                    $predicado_master['qtd'] = $qtd;
                    $this->item[] = $predicado_master;
                }
                return $this->item;
            }
        }
    }

    private function container20()
    {
        // echo date('Y-m-d', strtotime('+7 day'));
        if ($this->operacao && $this->servico && $this->valor_mercadoria) {
            $dimensao = 20;
            // Buscando item master
            $predicado_master = $this->getItemMaster($this->servico);

            //Buscando o item da proposta
            $predicado_proposta = $this->buscarItemProposta($this->operacao, $dimensao, $this->servico);
            if (count($predicado_proposta) > 0) {
                $predicado_proposta = $predicado_proposta[0];

                // Definindo váriavel com o balor máximo da proposta
                $vl_max = $predicado_proposta->valor_maximo ?? null;

                $qtd_container = $this->operacao->qtdcontainer[$dimensao];

                // Inserindo o valor do item master
                $predicado_master['id_propostapredicado'] = $predicado_proposta->id_predicado;
                // $predicado_master['valor_item'] = $predicado_proposta->valor * $qtd_container;
                $predicado_master['dimensao'] = (string) $dimensao;
                // echo $predicado_proposta->franquia_periodo;exit();
                $predicado_master['dta_inicio'] = $this->item_padrao->store_date->first();
                $predicado_master['dta_final'] = $this->dias_consumo > $predicado_proposta->franquia_periodo ? $this->item_padrao->store_date->calcDatePeriodo(((int)$predicado_proposta->franquia_periodo) - 1) : $this->item_padrao->store_date->calcDatePeriodo($this->dias_consumo - 1);

                // $predicado_master['dta_final'] = $this->item_padrao->store_date->calcDatePeriodo(((int)$predicado_proposta->franquia_periodo) - 1);
                $predicado_master['periodo'] = 1;
                $predicado_master['qtd'] = $qtd_container;
                $predicado_master['classificacao'] = 'periodo';
                $this->item[] = $predicado_master;
                $this->periodo_total = 1;
                $this->periodo_total += $this->checkPeriodo($this->dias_consumo, $this->operacao, $this->servico, $predicado_master, $qtd_container, $dimensao, $this->valor_mercadoria, $this->item_padrao->store_date->first());
            }

            return $this->item;
        }
    }

    private function container40()
    {
        if ($this->operacao && $this->servico && $this->valor_mercadoria) {
            $dimensao = 40;
            // Buscando item master
            $predicado_master = $this->getItemMaster($this->servico);

            //Buscando o item da proposta
            $predicado_proposta = $this->buscarItemProposta($this->operacao, $dimensao, $this->servico);
            if (count($predicado_proposta) > 0) {
                $predicado_proposta = $predicado_proposta[0];

                // Definindo váriavel com o balor máximo da proposta
                $vl_max = $predicado_proposta->valor_maximo ?? null;

                $qtd_container = $this->operacao->qtdcontainer[$dimensao];

                // Inserindo o valor do item master
                $predicado_master['id_propostapredicado'] = $predicado_proposta->id_predicado;
                $predicado_master['valor_item'] = $predicado_proposta->valor * $qtd_container;
                $predicado_master['dimensao'] = (string) $dimensao;
                $predicado_master['periodo'] = 1;
                $predicado_master['qtd'] = $qtd_container;
                $predicado_master['dta_inicio'] = $this->item_padrao->store_date->first();
                $predicado_master['dta_final'] = $this->item_padrao->store_date->calcDatePeriodo($predicado_proposta->franquia_periodo - 1);
                $predicado_master['classificacao'] = 'periodo';
                $this->item[] = $predicado_master;
                $this->periodo_total = 1;
                $this->periodo_total += $this->checkPeriodo($this->dias_consumo, $this->operacao, $this->servico, $predicado_master, $qtd_container, $dimensao, $this->valor_mercadoria);
            }
            return $this->item;
        }
    }

    private function checkExcedente()
    {
        $itemChecked = [];
        $dimensao = 'ambos';
        $itens_tipo_periodos = array_filter($this->item, function ($item) {
            if (isset($item['classificacao'])) : return $item['classificacao'] === 'periodo';
            else : return false;
            endif;
        });
        $itens = [];
        $idx = [];
        foreach ($itens_tipo_periodos as $key => $item) {
            if (!in_array($item['id_predicado'], $idx)) {
                $itens[] = $item;
                $idx[] = $item['id_predicado'];
            }
        }
        foreach ($itens as $key => $item) {
            $item = (object) $item;
            $id_predicadomaster = $item->id_predicado;
            $predicado = new Predicado($id_predicadomaster);
            $adicional = $predicado->adicional;
            $predicado_proposta = $this->buscarItemProposta($this->operacao, $dimensao, $adicional);
            // print_r(count($predicado_proposta) > 0 && ($this->valor_mercadoria / $this->qtd_container_total) > $predicado_proposta[0]->valor_partir);

            // Verificando se valor máximo excedeu
            if (count($predicado_proposta) > 0 && ($this->valor_mercadoria / $this->qtd_container_total) > $predicado_proposta[0]->valor_partir) {
                $excesso = ($this->valor_mercadoria / $this->qtd_container_total) - $predicado_proposta[0]->valor_partir;
                $adicional->id_propostapredicado = $predicado_proposta[0]->id_predicado;
                $adicional->dta_inicio = $item->dta_inicio;
                $adicional->dta_final = $item->dta_final;
                $adicional->dimensao = (string) $dimensao;
                $adicional->periodo = $item->periodo;
                $adicional->qtd = $this->qtd_container_total;
                $this->item[] = $adicional->toArray();
            }
        }
    }
}
