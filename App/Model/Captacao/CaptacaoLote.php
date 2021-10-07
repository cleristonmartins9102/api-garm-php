<?php

namespace App\Model\Captacao;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Processo\ProcessoPredicado;
use App\Model\Captacao\CaptacaoLoteEvento;
use App\Model\Captacao\CaptacaoContainer;

class CaptacaoLote extends Record
{
    const TABLENAME = 'CaptacaoLote';
    private $eventos = [];

    public function __construct($id_captacaolote = null)
    {
        parent::__construct($id_captacaolote);
        
        // Verifica se a captacao lote existe
        if (!isset($this->id_captacaolote)) {
            
            // Pega o ultimo numero do lote de Captação
            $numero = $this->getLast();
            $this->numero = ((int)$numero) + 1;
        } 
    }

    /**
     * Metodo para adicionar captação no lote
     * @param Captacao $captacao Captação a ser adicionada
     */
    public function addCaptacao(Captacao $captacao) {
        // Verificando se o lote já foi gravado e possue um id
        if (!$this->id) 
            $this->store();


        // Verificando se a captação foi carregada
        if (!$captacao->isLoaded())
            return 'Captação não carregada';
        
        $lote_has_captacao = new CaptacaoLoteCaptacao;

        $lote_has_captacao->id_captacaolote = $this->id;
        $lote_has_captacao->id_captacao = $captacao->id_captacao;
        $lote_has_captacao->store();
        if ($lote_has_captacao->isLoaded())
            $captacao->addHistorico("Captação adicionada ao lote de número {$this->numero}");
    }

    /**
     * Metodo retorna a lista de containeres de modo único
     * @return array $containeres lista de containeres
     */
    public function get_container(): array {
        $containeres = [];
        foreach($this->listaCaptacao as $captacao) {
            $containeres_captacao = ((new Captacao($captacao->id_captacao))->listacontainer);
            foreach($containeres_captacao as $c_cap) {
                if (!in_array_r($c_cap['codigo'], $containeres))
                    $containeres[] = $c_cap;
            }
        }
        return $containeres;
    }

    /**
     * Metodo para pegar as captações do lotel
     */
    public function get_listaCaptacao() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacaolote', '=', $this->id));
        $repository = (new Repository(CaptacaoLoteCaptacao::class))->load($criteria);
        // foreach($repository as $captacao) {
        //     $captacoes[] = $captacao->toArray();
        // }
        return $repository;
    }

    /**
     * Metodo para pegar as captações do lotel
     */
    public function get_listaCaptacaoArray() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacaolote', '=', $this->id));
        $repository = (new Repository(CaptacaoLoteCaptacao::class))->load($criteria);
        foreach($repository as $captacao) {
            $captacoes[] = $captacao->toArray();
        }
        return $captacoes;
    }

    /**
     * Metodo para apagar 
     */
    public function cleanCaptacoes() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacaolote', '=', $this->id_captacaolote ?? $this->id));
        $lote_has_captacao = new CaptacaoLoteCaptacao;
        $lote_has_captacao->deleteByCriteria($criteria);
    }

   
    /**
     * Metodo para adicionar um novo evento no lote
     * @param string $evento
     * @param string $app_forward
     * @param string $app
     */
    public function addEvento($evento = null, $app_forward = null, $app = null): void
    {
        if (!is_null($evento) && !is_null($app_forward) && !is_null($app)) {
            $captacao_lote_evento = new CaptacaoLoteEvento;
            $captacao_lote_evento->id_captacaolote = $this->id_captacaolote;
            // $captacao_lote_evento->id_fatura = isset($app->idBase) ? ($app->idBase === 'id_fatura' ? $app->id : null) : null;
            // $captacao_lote_evento->id_processo = isset($app->idBase) ? ($app->idBase === 'id_processo' ? $app->id : null) : null;
            // $captacao_lote_evento->id_liberacao = isset($app->idBase) ? ($app->idBase === 'id_liberacao' ? $app->id : null) : null;
            // $captacao_lote_evento->id_forward = $app_forward;
            // $captacao_lote_evento->id_forward = $app_forward;
            $captacao_lote_evento->evento = $evento;
            $captacao_lote_evento->store();
        }
    }

    public function get_eventos()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacaolote', '=', $this->id));
        $repository = new Repository('App\Model\Captacao\CaptacaoLoteEvento');
        $object = $repository->load($criteria);
        $eves = [];
        foreach ($object as $key => $evento) {
            $found = false;
            foreach ($eves as $ev) {
                if ( $ev->evento === $evento->evento ) {
                    $found = true;
                }
            }
            $eves[] = $evento;
            if ( !$found )
                $this->eventos[] = $evento->toArray();
        }
        return $this->eventos;
    }

    /**
     * Metodo que verifica se todas as captacoes do lote possuem os mesmos serviços no processo já adicionados
     * @param int $item
     * @return boolen
     */
    public function captacoesHaveItem($item) {
        $response = true;
        foreach ($this->listaCaptacao as $captacao) {
            $criteria = new Criteria;
            $criteria->add(new Filter('id_processo', '=', $item->id_processo));
            $criteria->add(new Filter('id_predicado', '=', $item->id_predicado));
            $criteria->add(new Filter('dimensao', '=', $item->dimensao));
            $criteria->add(new Filter('id_captacao', '=', $captacao->id_captacao));  
            $repository = (new Repository(ProcessoPredicado::class))->load($criteria);
            if (count($repository) === 0)
                $response = false;
        }
        return $response;
    }
    
    /**
     * Metodo que verifica se todas as captacoes do lote possuem os mesmos serviços no processo já adicionados
     * @param int $item
     * @return boolen | ProcessoPredicado
     */
    public function captacoesHaveItemQtd($item) {
        foreach ($this->listaCaptacao as $captacao) {
            $criteria = new Criteria;
            $criteria->add(new Filter('id_processo', '=', $item->id_processo));
            $criteria->add(new Filter('id_predicado', '=', $item->id_predicado));
            $criteria->add(new Filter('qtd', '<', $item->qtd));
            $criteria->add(new Filter('id_captacao', '=', $captacao->id_captacao));  
            $repository = (new Repository(ProcessoPredicado::class))->load($criteria);
            if (count($repository) > 0)
                return $repository[0];

            return false;
        }
    }

    /**
     * Metodo que verifica se todas as captacoes do lote possuem os mesmos containeres
     * @param array $containeres
     * @param  $containeres
     * @return array
     */
    public function captacoesHaveContainer(array $containeres = [], $item = null):array {
        $propate = 0;
        $c = [];
        // Containeres recebidos
        // print_r($containeres);
        // exit();
        foreach ($containeres as $container) {
            $container = (object) $container;
                // Percorrendo as captacoes do lote
                // $ct = [ $container->codigo => [ 'qtd' => 0, 'propate' => 0 ] ];
                $cntr_count = 0;
                $propate = 0;
                foreach($this->listaCaptacao as $captacao) {
                    foreach ( $captacao->captacao->container as $cntr ) {
                        if ( $container->codigo === $cntr->codigo ) {
                            $propate++;
                            continue;
                        }
                    }
                }
                if ( $propate > 0 ) {
                    $cntr_count++;
                    $c[] = [ $cntr_count => $propate ];
                }
        }
        // print_r($c);
        // exit();
        return $c;
    }
}
