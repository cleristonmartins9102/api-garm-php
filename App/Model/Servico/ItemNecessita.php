<?php

namespace App\Model\Servico;

use App\Lib\Database\Record;

class ItemNecessita extends Record
{
    const TABLENAME = 'ItemNecessita';

    protected $item;
    // private $periodo;
    // private $dta_inicio;
    // private $dta_final;
    // private $qtd;
    private $classificacao;

    /**
     * Metodo que vai receber o item para ser verificado se existe um outro item necessário.
     * @param array $item = Item a ser verificado
     */
    public function set_item(array $item) {
        // $this('id_item', $item['id_predicado']);
        if ($this->isLoaded()) {
            $this->periodo = $item['periodo'];
            $this->dta_inicio = $item['dta_inicio'];
            $this->dta_final = $item['dta_final'];
            $this->qtd = $item['qtd'];
            $this->classificacao = $this->getClassificacao();
            $this->merge($this, true);
        }
    }


    /**
     * Metodo que une os itens semelhantes
     * @param ItemNecessita $item Item a ser unido
     * @param null $first = parametro que define se o metodo esta sendo executado pela primeira vez 
     */
    public function merge(ItemNecessita $item, $first = null) {
        if (method_exists($this, $this->classificacao))     
            \call_user_func(array($this, $this->classificacao), $item, !is_null($first) ?: null);
    }

        /**
     * Metodo para pegar a classificacao
     */
    private function getClassificacao() {
        return ((new ItemClassificacao)('id_itemclassificacao', $this->id_itemclassificacao))->classificacao;
    }

    /**
     * Item do tipo seguro, esse metodo vai somar o periodo e adicionar as datas inicial do primeiro periodo ao ultimo
     * @param ItemNecessita $item Item a ser unido
     * @param null $first = parametro que define se o metodo esta sendo executado pela primeira vez 
     */    
    private function seguro(ItemNecessita $item_join, $first = null) {
        if ($item_join->item['dimensao'] === $this->item['dimensao']) {
            if (($first)) {
                $this->qtd = -1;
                return;
            }
            // if ($item_join->id_predicado !== 61 and $item_join->id_predicado !== 62) {
            //     $this->periodo += $item_join->periodo;
            // }

            if (strtotime($this->dta_inicio) > strtotime($item_join->dta_inicio))
                $this->dta_inicio = $item_join->dta_inicio;

            if (strtotime($this->dta_final) < strtotime($item_join->dta_final))
                $this->dta_final = $item_join->dta_final;
        }
    }


    /**
     * Item do tipo periodo, esse metodo vai somar o periodo e adicionar as datas iniciais do primeiro periodo ao ultimo
     * @param ItemNecessita $item Item a ser unido
     * @param null $first = parametro que define se o metodo esta sendo executado pela primeira vez 
     */
    private function periodo(ItemNecessita $item_join, $first = null) {
        if ($item_join->item['dimensao'] === $this->item['dimensao']) {
            if (($first)) {
                // $this->qtd += $item_join->qtd;
                return;
            }
            $this->qtd += $item_join->qtd;
            $this->periodo += $item_join->periodo;
            if (strtotime($this->dta_inicio) > strtotime($item_join->dta_inicio))
                $this->dta_inicio = $item_join->dta_inicio;

            if (strtotime($this->dta_final) < strtotime($item_join->dta_final))
                $this->dta_final = $item_join->dta_final;
        }
    }

    /**
     * Item do tipo ddc, esse metodo vai comar o periodo e adicionar as datas inicial do primeiro periodo ao ultimo
     * @param ItemNecessita $item Item a ser unido
     * @param null $first = parametro que define se o metodo esta sendo executado pela primeira vez 
    */
    private function ddc(ItemNecessita $item_join, $first = null) {
        if ($item_join->item['dimensao'] === $this->item['dimensao']) {
            if (($first)) {
                $this->periodo = 1;
                return;
            }
            // $this->qtd += $item_join->qtd;//
            if (strtotime($this->dta_inicio) > strtotime($item_join->dta_inicio))
                $this->dta_inicio = $item_join->dta_inicio;

            if (strtotime($this->dta_final) < strtotime($item_join->dta_final))
                $this->dta_final = $item_join->dta_final;
        }
    }
}
