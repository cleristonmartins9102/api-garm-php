<?php

namespace App\Control\Fatura;

use App\Mvc\Controller;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Captacao\Captacao;
use App\Model\Liberacao\Liberacao;
use App\Model\Fatura\Fatura;
use App\Model\Processo\Processo;
use App\Model\Processo\ProcessoPredicado;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Model\Documento\Upload;
use App\Model\Fatura\FaturaStatus;
use App\Model\Comissionario\Comissionario;

class Save extends Controller
{
    private $result;

    public function __construct()
    {
        $this->result = new \stdClass;
        $this->result->message = null;
        $this->result->status = 'success';
    }

    public function store(Request $request, Response $response, $data = null)
    {
        $isUpdate = false;
        self::openTransaction();
    
        /**
         * Verificando se existem os parametros necessários
         * Se tiver a propriedade $data['id_fatura] ou $data['modelo']
         */
        if ( !is_null($data) && (is_numeric($data) || ( isset($data['id_fatura']) && !is_null($data['id_fatura'])) || isset($data['modelo'])) ) {
            // Definindo o tipo de ação, fatura nova ou atualização
            $isUpdate = isset($data['id_fatura']) ? true : false;
        } else { // Retorna mensagem de erro, pois não é nem edição e novo cadas
            $this->result->message = 'Faltando parametros que definem o tipo de ação. ("id_fatura" ou "modelo")';
            $this->result->status = 'fail';
            // Encerrando
            return json_encode((array) $this->result);
        }

        // É atualização?
        if ($isUpdate) {
            // Instancia a fatura e 
            $fatura = new Fatura($data['id_fatura']);

            if (isset($data['recalculo'])) 
                $fatura->recalculo = $data['recalculo'] ? 'sim' : 'nao';
            
            
            // Verificando se possue anexos, caso sim ele salva.
            if (isset($data['anexos']) and $data['anexos'] > 0) {
                // Apagando todos os documentos
                $fatura->deleteDocumento();
                foreach ($data['anexos'] as $key => $anexo) {
                    $upload = new Upload($anexo['id_upload']);
                    $upload->validado = 'yes';
                    $fatura->addDocumento($upload);
                }
            }

            if (isset($data['id_status'])) {
                $status = (new FaturaStatus($data['id_status'])) ?? null;
                if ($status and $status->status === 'Fechada') {
                    $valid = $this->validate($data, $fatura);
                    if (!$valid['status']) {
                        return json_encode($valid);
                    } 
                } else  if (!$fatura->isComplementar()) {
                    if ($fatura->hasComplementar()) {
                        $resp['status'] = false;
                        $resp['message'] = 'fatura não pode ser alterado o status, pois existe complementares.';
                        return json_encode($resp);
                    }
                }
            } 

            //Verifica se têm comissao para despachante
            $comissionado_despachante = $fatura->despachante_comissionado;
            if ($comissionado_despachante instanceof Comissionario) {
                if (isset($data['comissao_despachante']) and $data['comissao_despachante']) {
                    $fatura->ativaComissao($comissionado_despachante);
                } else {
                    $fatura->desativaComissao($comissionado_despachante);
                }
            }
                   
            $modelo = $data['modelo'];
            $fatura->id_faturastatus = $data['id_status'];
            // Já definindo os valores da fatura
            $fatura->valor = $data['valor'] ?? 0;
            $fatura->valor_custo = $data['valor_custo'] ?? 0;
            $fatura->valor_lucro = $data['valor_lucro'] ?? 0;

            self::checkModelo($fatura, $modelo, $data);
        } else {
            // Fatura nova
            $fatura = new Fatura;
            
            // Verificando se a fatura esta sendo criada a partir de um processo, se for, quer dizer que o id do processo foi enviado na requisição
            if (is_numeric($data)) {
                $fatura->id_processo = $data;
                // Criando fatura por um processo
                if (!self::creteByProcesso($fatura)) { // Se teve falha na geracao dos itens
                    return json_encode((array) $this->result);
                }
            } else {
                // Já definindo os valores da fatura
                $fatura->valor = $data['valor'] ?? 0;
                $fatura->valor = $data['valor_custo'] ?? 0;

                // Definindo o modelo
                $modelo = $data['modelo'];

                // Checa o modelo da fatura antes de criar ou atualizar
                self::checkModelo($fatura, $modelo, $data);
            }
        }

        $this->result->id_fatura = $fatura->id;
        self::closeTransaction();
        return json_encode($this->result);
    }



    /**
     * Metodo para gerar fatura complementar a partir de uma cheia
     * @param $id = id da fatura cheia
     */
    public function complementar(Request $request, Response $response, $id) {
        if (is_null($id['id_fatura']))
            return;
        self::openTransaction();
        $fatura = new Fatura($id['id_fatura']);
        $resp = $fatura->gerarComplementar();
        if (isset($resp['status']) and $resp['status'] === true) {
            self::closeTransaction();
            return $resp;
        } else {
            return $resp;
        }
    }

    /**
     * Metodo para liberar o processo para que ele seja editavel e passivel de criar faturas complementares.
     */
    public function liberarcheia(Request $request, Response $response, $id) {
        if (is_null($id['id_fatura']))
            return;
        self::openTransaction();
        $fatura = new Fatura($id['id_fatura']);
        $resp = $fatura->notCheia();
        if ($resp['status'] === true) {
            self::closeTransaction();
            return $resp;
        } else {
            return $resp;
        }
    }

    /**
     * Metodo para criar a fatura a partir de um processo
     * @param Fatura $fatura Fatura já criada
     * @return boolean se a criação foi executada com sucesso
     */
    private function creteByProcesso(Fatura $fatura = null)
    {   
        $processo = new Processo($fatura->id_processo);
        // Verificando se é despacho
        if ( !$processo->isDespacho() ) {
            if ($captacoes = $processo->isLote()) {
                foreach ($captacoes as $captacao) {
                    $fatura->id_faturamodelo = $captacao->captacao->proposta->regime->id_regime;
                    $vencimento = ($captacao->captacao->proposta->prazo_pagamento);
                    $today = date("Y-m-d");
                    $vencimento = date('Y-m-d', strtotime("$today +$vencimento days"));
                    $fatura->dta_vencimento = $vencimento;

                    // Inserindo o id do processo
                    $fatura->id_processo = $fatura->id_processo;
                    $fatura->dta_emissao = date('Y-m-d');

                    $fatura->id_cliente = $captacao->captacao->proposta->cliente->id_cliente;
                    $fatura->id_captacao = $captacao->id_captacao;
                    $fatura->valor = 0;
                    $result_itens = $fatura->processarItens();
                    if (!$result_itens['status']) {
                        $this->result->status = 'fail';
                        $this->result->message = $result_itens['message'];
                        return 0;
                    }
                    $fatura = clone $fatura;
                    $fatura->numero++;
                }
            } else {
                $fatura->id_faturamodelo = $processo->movimentacao->proposta->regime->id_regime;
                $vencimento = ($processo->movimentacao->proposta->prazo_pagamento);
                $today = date("Y-m-d");
                $vencimento = date('Y-m-d', strtotime("$today +$vencimento days"));
                $fatura->dta_vencimento = $vencimento;

                // Inserindo o id do processo
                $fatura->id_processo = $fatura->id_processo;
                $fatura->dta_emissao = date('Y-m-d');
                $fatura->id_cliente = $processo->movimentacao->proposta->cliente->id_cliente;
                $fatura->id_captacao = $processo->movimentacao->id_captacao;
                $result_itens = $fatura->processarItens();
            }
        } else {
            $fatura->id_faturamodelo = $processo->movimentacao->proposta->regime->id_regime;
            $fatura->processo->movimentacao->addEvento('g_fatura', '',  '');

            $vencimento = ($processo->movimentacao->proposta->prazo_pagamento);
            $today = date("Y-m-d");
            $vencimento = date('Y-m-d', strtotime("$today +$vencimento days"));
            $fatura->dta_vencimento = $vencimento;

            // Inserindo o id do processo
            $fatura->id_processo = $fatura->id_processo;
            $fatura->dta_emissao = date('Y-m-d');
            $fatura->id_cliente = $processo->movimentacao->proposta->cliente->id_cliente;
            $result_itens = $fatura->processarItens();
        }

        // exit();//
        return 1;
    }

    /**
     * Metodo para verificar o tipo de fatura a ser criada
     * @param Fatura $fatura Fatura já criada
     */
    private function checkModelo(Fatura $fatura, String $modelo = null, array $data = null)
    {
        if (!is_null($modelo) && $data) {
            switch (true) {
                case ($modelo === 'importacao' || $modelo === 'exportacao' || $modelo === 'armazenagem'):
                    self::modeloArmazenagem($fatura, $data);
                    break;

                case ($modelo === 'notdebagencia'):
                    self::modeloAgenciamento($fatura, $data);
                    break;

                case ($modelo === 'notadebtrc'):
                    self::modeloTransporte($fatura, $data);
                    break;
            }
        }
    }

    /**
     * Criar fatura do tipo importação e exportação
     * @param Fatura $fatura Fatura já criada
     * @param Array $data Dados enviados pelo request
     */
    private function modeloArmazenagem(Fatura $fatura = null, $data = null)
    {
        if (!is_null($fatura) && $data) {
            $fatura->id_faturastatus = $data['id_status'] ?? null;
            // $fatura->valor_custo = $data['valor_custo'] ?? $data['valor_custo'];
            $fatura->nf = $data['nf'] ?? null;
            $fatura->dta_emissao = isset($data['dta_emissao']) && $data['dta_emissao'] ? date('Y-m-d', strtotime($data['dta_emissao'])) : NULL;;
            $fatura->dta_vencimento = isset($data['dta_vencimento']) && $data['dta_vencimento'] ? date('Y-m-d', strtotime($data['dta_vencimento'])) : NULL;;
            $fatura->store();
       
            self::gerarItens($fatura, $data);
        }
    }

    /**
     * Criar fatura do tipo nota de debito agenciamento
     * @param Fatura $fatura Fatura já criada
     * @param Array $data Dados enviados pelo request
     */
    private function modeloAgenciamento(Fatura $fatura = null, $data = null)
    {
        if (!is_null($fatura) && $data) {
            $fatura->id_faturamodelo = 4;
            $fatura->id_faturastatus = $data['id_status'] ?? null;
            $fatura->id_cliente = $data['id_cliente'] ?? null;
            $fatura->id_agentecarga = $data['id_agentecarga'] ?? null;
            $fatura->hbl = $data['hbl'] ?? null;
            $fatura->dta_chegada = isset($data['dta_chegada']) && $data['dta_chegada'] ? date('Y-m-d', strtotime($data['dta_chegada'])) : NULL;;
            $fatura->dta_embarque = isset($data['dta_embarque']) && $data['dta_embarque'] ? date('Y-m-d', strtotime($data['dta_embarque'])) : NULL;;
            $fatura->dta_emissao = isset($data['dta_emissao']) && $data['dta_emissao'] ? date('Y-m-d', strtotime($data['dta_emissao'])) : NULL;;
            $fatura->dta_vencimento = isset($data['dta_vencimento']) && $data['dta_vencimento'] ? date('Y-m-d', strtotime($data['dta_vencimento'])) : NULL;;
            $fatura->ref_cliente = $data['ref_cliente'] ?? null;
            $fatura->store();
            self::gerarItens($fatura, $data);
        }
    }

    /**
     * Criar fatura do tipo nota de debito agenciamento
     * @param Fatura $fatura Fatura já criada
     * @param Array $data Dados enviados pelo request
     */
    private function modeloTransporte(Fatura $fatura = null, $data = null)
    {
        if (!is_null($fatura) && $data) {
            // echo $data['id_cliente'] ?? null;exit();
            $fatura->id_faturamodelo = 3;
            $fatura->id_faturastatus = $data['id_status'] ?? null;
            $fatura->id_cliente = $data['id_cliente'] ?? null;
            $fatura->dta_emissao = isset($data['dta_emissao']) && $data['dta_emissao'] ? date('Y-m-d', strtotime($data['dta_emissao'])) : NULL;;
            $fatura->dta_vencimento = isset($data['dta_vencimento']) && $data['dta_vencimento'] ? date('Y-m-d', strtotime($data['dta_vencimento'])) : NULL;;
            $fatura->valor = $data['valor'] ?? null;
            $fatura->valor_custo = $data['valor_custo'] ?? null;
            $fatura->store();
            self::gerarItens($fatura, $data);
        }
    }

    /**
     * Processar ou reprocessar os itens da fatura
     * @param Fatura $fatura Fatura já criada
     * @param Array $data Dados enviados pelo request
     */
    private function gerarItens(Fatura $fatura = null, array $data = null)
    {
        // Itens recebidos pelo request
        if (isset($data['itens']) && count($data['itens']) > 0) {
            $itens = $data['itens'][0];

            // Processo de atualização e exclusão dos itens
            $criteria = new Criteria;
            $criteria->add(new Filter('id_fatura', '=', $fatura->id ?? $fatura->id_fatura));

            // Criando o criterio para apagar todos os itens que não foram recebidos no request
            foreach ($itens as $key => $item) {
                // Verificando se é item é antigo
                if (isset($item['id_faturaitem'])) {
                    // Adiciona o critério para não apagar esse item
                    $criteria->add(new Filter('id_faturaitem', '<>', $item['id_faturaitem']));
                }
            }
            // Apagando itens 
            $fatura->deleteByCriteria($criteria, '');

            // Atualizando os itens e cadastrando os novos
            foreach ($itens as $key => $item) {
                $fatura->addItem($item);               
            }
            $fatura->calcValorImpostoTerminal();
            // echo '<pre>';
            // print_r($fatura);
        }
    }

    private function validate($data, Fatura $fatura)
    {   
        $response = ['status' => true, 'message' => 'success'];
        if (
            isset($data['nf']) ? $data['nf'] : true and
            isset($data['id_status']) ? $data['id_status'] : null and
            isset($data['dta_vencimento']) ? $data['dta_vencimento'] : null and
            isset($data['dta_emissao']) ? $data['dta_emissao'] : NULL
        ) {
        } else {
            $response['status'] = false;
            $response['message'] = 'fatura não pode ser fechada, faltando o preenchimento de campos necessários';
        }
        return $response;
    }
}
