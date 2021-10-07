<?php
namespace App\Model\Servico;

use App\Lib\Database\Record;
use App\Model\Captacao\Captacao;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Fatura\Calculo\Armazenagem;
use App\Lib\Tool\Condition;
use App\Lib\Tool\Conditioner;
use App\Model\Despacho\Despacho;
use App\Model\Regime\Regime;
use App\Model\Fatura\Calculo\CalculoItem;
use App\Model\Aplicacao\Modulo;
use App\Lib\Tool\StoreDate;

/**
 * Essa classe é para em casos de processos do tipo armazenagem, pegar os itens do tipo armazenagem periodo e lancalos
 */
class ItemPadrao extends CalculoItem
{
    const TABLENAME = 'ItemPadrao';

    // Pega os servicos masters baseados pela proposta da movimentação 
    public function servByOperacao(Regime $regime = null)
    {   
        if (isset($this->regime) && is_null($regime)) {
            $regime = new Regime;
            $regime->regime = $this->regime;
            $regime = $regime->searchByName();
        }

        if (isset($this->dta_inicio) && !is_null($this->dta_inicio)) {
            $this->store_date = new StoreDate;
            $this->store_date->add($this->dta_inicio);
        }

        // $itens_padroes = $this->getItensPadroes($regime, $this->modulo);

        if (isset($regime->id_regime) && !is_null($regime->id_regime)) {
            // Verificando o regime da operação
            switch ($regime->regime) {
                case 'exportacao':
                    $operacao = new Despacho($this->id_operacao);
                    self::armazenagem($operacao, $this, $regime);
                    break;

                case 'importacao':
                    $operacao = new Captacao($this->id_operacao);
                    self::armazenagem($operacao, $this, $regime);

                    break;

                default:
                    echo 'Sem regime!';
                    exit();
                    break;
            }
        }
        return $this->item;
    }

    public function classificacao() {
        return new ItemClassificacao($this->id_itemclassificacao);
    }

    public function modulo() {
        return new Modulo($this->id_modulo);
    }

    public function get_appvalor() {
        return new PreProAppValor($this->id_predproappvalor);
    }

    /**
     * Processa itens de armazenagem importacao e exportação
     * @param Depacho | Captacao $operacao Operação a ser processado os itens
     * @param Array $itens Lista contêndo os itens padrões localizados
     * @param Number $valor_mercadoria Valor da Mercadoria
     * @param Number $dias_consumo Dias consumidos na operação
     */
    private function armazenagem($operacao, $item_padrao, $regime)
    {
        $armazenagem = new Armazenagem($this->modulo, $operacao, $item_padrao, $regime);
        $this->item = $armazenagem->get_itens();
    }

    public function get_predicado()
    {
        return new Predicado($this->id_predicado);
    }
    
}
