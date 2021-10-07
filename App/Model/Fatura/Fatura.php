<?php

namespace App\Model\Fatura;

use App\Lib\Database\Record;
use App\Model\Processo\Processo;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Servico\Pacote;
use App\Model\Servico\Predicado;
use App\Model\Fatura\Calculo\CalcAppValor;
use App\Model\Pessoa\Individuo;
use App\Model\Moeda\Moeda;
use App\Model\Processo\ProcessoPredicado;
use App\Model\Servico\ItemPadrao;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Lib\Tool\Register;
use App\Model\Proposta\PropostaPredicado;
use App\Model\Cext\Cext;
use App\Model\Comissionario\Comissao;
use App\Model\Documento\Upload;
use App\Model\Comissionario\Comissionario;
use App\Model\Comissionario\FaturaComissoesDesativadas;
use App\Model\Fatura\FaturaItemValorLoteIntegral;

use App\Model\Captacao\Captacao;
use App\Model\Despacho\Despacho;

use DateTime;

class Fatura extends Record
{
    const TABLENAME = 'Fatura';
    // private $valor_despesas;
    // private $valor_impostos;

    use CalcAppValor;
    use BodyMail;
    use SubjectEmail;

    public function __construct($id_fatura = null)
    {
        parent::__construct($id_fatura);
        
        // Verificando se é fatura nova, caso sim, crie um numero e defina o status para aberta
        if (!isset($this->id_fatura)) {
            
            // Pega o ultimo numero da fatura - Tratamento para caso for complementar e tiver barra na numeracao
            $numero = $this->getLastNumber();
            $barra = strpos($numero, '.');
            if ($barra) {
                $complemento = substr($numero, $barra + 1);
                $numero = substr($numero, 0, $barra);
            }

            // $this->numero = $this->getLast() + 1;
            $this->numero = ((int)$numero) + 1;
            $criteria = new Criteria();
            $criteria->add(new Filter('status', '=', 'aberta'));
            $repository = new Repository('App\Model\Fatura\FaturaStatus');
            $status = $repository->load($criteria);
            // Verificando se encontrou o status
            if (count($status) > 0) {
                // Grava o id do status
                $this->id_faturastatus = $status[0]->id_faturastatus;
            }
        } else {
            // Calculando os valores
            self::calcValotTotal();
        }
    }

    public function store(Request $request = null, Response $response = null, Register $register = null)
    {        
        $this->clean();
        $resp = parent::store();
        // Verificando o status
        if (isset($this->id_faturastatus)) {
            $fatura_status = (new FaturaStatus($this->id_faturastatus))->status;
            switch (true) {
                case ($fatura_status === 'Cancelada' || $fatura_status === 'cancelada'):
                    // Liberando a captacao ou despacho para serem faturados novamente
                    $this->movimentacao->liberarFaturamento();
                    break;

                case ($fatura_status === 'Fechada'):
                    // Alterando a data de emissão e vencimento
                    // $vencimento = ($this->processo->captacao->proposta->prazo_pagamento - 1);
                    $today = date("Y-m-d");//
                    // $vencimento = date('Y-m-d', strtotime("$today +$vencimento days"));
                    // $this->dta_vencimento = $vencimento;
                    $this->dta_emissao = $today;
                    parent::store();
                    break;

                default:
                    // Verifica se a fatura possue um processo e se ela é uma fatura nova, caso sim, ele pega a movimentação dele e adiciona o evento avisando que foi gerado fatura
                    if ($this->processo->isLote()) {
                        $this->processo->lote->addEvento('g_fatura', $this->id, $this);
                        // $this->processo->lote->addEvento('g_fatura', $this->id, $this);
                    } else {
                        $this->captacao->addEvento('g_fatura', $this->id, $this);
                    }
                    break;
            }
            // Adicionando o evento notificando que foi gerado fatura para essa movimentacao
        }
        self::calcValotTotal();
        return $resp;
    }

      /**
     * Metodo para adicionar um novo evento na captação
     * @param string $evento
     * @param string $app_forward
     * @param string $app
     */
    public function addEvento($evento = null, $app_forward = null, $app = null): void
    {
        if (!is_null($evento) && !is_null($app_forward) && !is_null($app)) {
            // select * from FaturaEvento
            // delete from FaturaEvento where 1
            $fatura_evento = new FaturaEvento;
            $fatura_evento->id_fatura = $this->id_fatura;
            $fatura_evento->id_processo = isset($app->idBase) ? ($app->idBase === 'id_processo' ? $app->id : null) : null;
            $fatura_evento->id_liberacao = isset($app->idBase) ? ($app->idBase === 'id_liberacao' ? $app->id : null) : null;
            $fatura_evento->id_forward = $app_forward;
            $fatura_evento->evento = $evento;
            $fatura_evento->store();
        }
    }

      /**
     * Metodo para pegar o evento
     * @param string $evento
     * @param string $app_forward
     * @param string $app
     */
    public function get_evento()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura));
        $repository = new Repository(FaturaEvento::class);
        $object = $repository->load($criteria);
        $eventos = [];
        foreach ($object as $key => $evento) {
            $eventos[] = $evento->toArray();
        }
        return $eventos;
    }

      /**
     * Metodo para verificar se a fatura foi enviada para o cliente
     */
    public function enviadaParaCliente()
    {
        foreach($this->evento as $evento) {
            $evento = (object) $evento;
            return $evento->evento === 'enviado_fatura';
        };
    }

      /**
     * Metodo para pegar o historico
     * @param string $evento
     * @param string $app_forward
     * @param string $app
     */
    public function get_historico()
    {
       $historico = [];
       $historico = array_merge($historico, $this->processo->historico);
       $historico = array_merge($historico, $this->captacao->liberacao->historico);
       $historico = array_merge($historico, $this->captacao->historico);
       return $historico;
    }

    /**
     * Metodo para definir que a fatura não é cheia, portanto o processo ficara liberado para edição, passivel para gerar complementar
     */
    public function notCheia() {
        $this->cheia = 'nao';
        $this->store();
        return [ 'message' => 'sucess', 'status' => true ];
    }

    /**
     * Metodo que verifica se a fatura é cheia, casos complementares
     */
    public function isCheia() {
        return $this->cheia === 'sim' ? true : false;
    }

    /**
     * Metodo verifica se a fatura possui complementar
     * @return boolean
     */
    public function hasComplementar() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_faturacheia', '=', $this->id_fatura));
        $complementar = (new Repository(FaturaComplementar::class))->load($criteria);
        return (count($complementar) > 0) ? true : false;
    }

    /**
     * Metodo para gerar fatura complementar
     */
    public function gerarComplementar() {
        if ($this->isComplementar())
            return [ 'message' => "A fatura {$this->numero} é complementar, não é possivel gerar a partir dela.", 'status' => false ];

        if ($this->status->status !== 'Fechada')
            return 'Fatura não esta fechada';
        
        $criteria = new Criteria;
        // $criteria->setProperty('limit', 1);
        // $criteria->setProperty('order', 'id_faturacomplementar desc');
        $criteria->add(new Filter('id_faturacheia', '=', $this->id_fatura));
        $faturas_complementares = (new Repository(FaturaComplementar::class))->load($criteria);   
    
        // Gerando Fatura Cheia
        $fatura_cheia = $this;

        $todos_itens_faturados = $fatura_cheia->allItens;
      
        // Define o numero do complemento da fatura, esse numero vai depois da .
        $numero_complemento = 1;

        $todos_itens_faturados_geral = $todos_itens_faturados;

        // Percorre todos as faturas complementares para pegar os itens e gerar o numero da complementar
        if (count($faturas_complementares) > 0) {
            foreach ($faturas_complementares as $key => $fat) {
                $fat = new Fatura($fat->id_faturacomplementar);

                $todos_itens_faturados = self::mergeItens($fat->allItens, $todos_itens_faturados);
                $todos_itens_faturados_geral = array_merge($todos_itens_faturados_geral, $fat->allItens);

                // Verificando se é o último item da lista, se for, então foi a última complementar
                if (($key + 1) === count($faturas_complementares))
                    $numero_complemento = substr($fat->numero, (strpos($fat->numero, '.') + 1), strlen($fat->numero)) + 1;
            }
        }


        $itens_complementares = null;


        // Clonando a fatura cheia
        $fatura_complementar = clone $this;
        $fatura_complementar->removeProperty(['itens']);
        $vencimento = ($this->processo->movimentacao->proposta->prazo_pagamento);
        $today = date("Y-m-d");
        $vencimento = date('Y-m-d', strtotime("$today +$vencimento days"));
        $fatura_complementar->dta_vencimento = $vencimento;

        $fatura_complementar->dta_emissao = date('Y-m-d');

        // Limpando os itens da fatura complementar nova
        // $fatura_complementar->deleteAllItens();
        
        $fatura_complementar->numero = $fatura_complementar->numero . '.' . $numero_complemento;
        // echo $fatura_complementar->numero;
        // exit();
        $fatura_complementar->id_faturastatus = 1;
        // print_r($numero_complemento);
        // exit();
        $fatura_complementar->store();
  
        // Pegando os itens do processo
        $itens_processo = $fatura_cheia->processo->itens;
        
        // Verificando se possue item na fatura
        if (count($todos_itens_faturados) === 0)
            return 'Sem Itens na fatura';

        $lista_pacotes_faturados = [];

        // delete from Fatura where numero='1004.1'
        foreach ($todos_itens_faturados as $key => $item) {
            $item = (object) $item;
            $item_processo = new ProcessoPredicado($item->id_processopredicado);
            $predicado = $this->processo->movimentacao->proposta->servicoById($item_processo->id_predicado, $item_processo->dimensao);
            if (is_array($predicado) && count($predicado) > 0) {
                $item_proposta = $this->processo->movimentacao->proposta->servicoById($item_processo->id_predicado, $item_processo->dimensao);
                $predicado = (new Predicado($item_proposta[0]->id_predicado));
                if ($predicado->servico->nome === 'Pacote') {
                    $lista_pacotes_faturados[] = $predicado->isPacote();
                }
            } 
           
        }
        if ( !isset($itens_processo['all']) and count($itens_processo['all']) === 0 and isset($itens_processo['all'][0]['itens']))
        return;

        $itens_processo = $itens_processo['all'][0]['itens'];
        foreach($itens_processo as $key => $item_processo) {
            $item_proposta = null;
            $processo_predicado = new ProcessoPredicado($item_processo['id_processopredicado']);
            $predicado_master = (object) self::getItemMaster($processo_predicado);
   
            $predicado_master->dimensao = $processo_predicado->dimensao;
            $predicado = $this->processo->movimentacao->proposta->servicoById($item_processo['id_predicado'], $item_processo['dimensao']);

            if (is_array($predicado) && count($predicado) > 0) {
                $item_proposta = $this->movimentacao->proposta->servicoById($item_processo['id_predicado'], $item_processo['dimensao']);
                // Não existe na proposta
            } 


            // Verificando se o item existe na lista
            $item_existe = ob_in_array_r($item_processo['id_processopredicado'], $todos_itens_faturados);
            
            // print_r($item_existe);
            // continue;
            $item_processo = (object) $item_processo;


            $fatura_item = new FaturaItem;
            $fatura_item->id_fatura = $fatura_complementar->id ?? $fatura_complementar->id_fatura;
            $fatura_item->id_faturaitemlegenda = self::isInPacote($lista_pacotes_faturados, $item_processo) 
                        ?3 // Incluso
                        : ( !is_array($item_proposta) 
                            ? 1 // Não em proposta
                            : ($item_proposta[0]->valor === 'sc' 
                                ? 2 // Sobre consulta
                                : null ));
            
            $item_existe = $item_existe ? (object) $item_existe : false;


            $processo_predicado = new ProcessoPredicado($item_processo->id_processopredicado);
            $predicado_master = (object) self::getItemMaster($processo_predicado);
            $predicado_master->dimensao = $processo_predicado->dimensao;


            // Customizando a descrição da fatura, caso exista customização
            $fatura_item_descricao = new FaturaItemCustom;
            $fatura_item_descricao->item = $predicado_master;
            $fatura_item_descricao->id_predicado = $predicado_master->id_predicado;

            // Lista de predicados que a fatura não vai recalcular a quantidade e vai manter a quantidade do item anterior "Existente"  
            $predicados_livre_qtd = [ 61, 62, 63, 64 ];
            
            // Definindo valor da mercadoria
            if ( $this->processo->isDespacho() ) {
                $valor_mercadoria = $this->processo->valor_mercadoria;
            } else {
                $valor_mercadoria = $this->movimentacao->liberacao->valor_mercadoria;
            }

            if ($item_existe) {
                // print_r($item_existe);
                // exit();
                $qtd = $this->getPropTotal('qtd', 'id_processopredicado', $item_existe->id_processopredicado, $todos_itens_faturados_geral);
                $periodo = $this->getPropTotal('periodo', 'id_processopredicado', $item_existe->id_processopredicado, $todos_itens_faturados_geral);
                    
                if (
                    $qtd >= (int)$item_processo->qtd and
                    // $item_existe->dta_inicio === $item_processo->dta_inicio and
                    ($item_existe->dta_final === $item_processo->dta_final) ? true : ((int)$qtd >= (int)$item_processo->qtd and (int)$periodo >= (int)$item_processo->periodo)
                    // $periodo == $item_processo->periodo
                )
                continue;
                    
                $fatura_item->id_predicado = $item_processo->id_predicado;
                $fatura_item->id_processopredicado = $item_processo->id_processopredicado;
                $fatura_item->descricao = $fatura_item_descricao->custom;
                $fatura_item->qtd = (in_array((int)$item_processo->id_predicado, $predicados_livre_qtd) ? $item_processo->qtd : ((int)$item_processo->qtd > 1 ? (($item_processo->qtd - $qtd) <= 0 ? 1 : $item_processo->qtd - $qtd) : $item_processo->qtd));                 
                $fatura_item->dta_inicio = strtotime($item_existe->dta_final) !== strtotime($item_processo->dta_final) ? date('Y-m-d', strtotime($item_existe->dta_final . ' +1 day')) : $item_existe->dta_inicio;
                $fatura_item->dta_final = $item_processo->dta_final;
                $fatura_item->periodo = $item_processo->periodo > 1 ? ($item_processo->periodo - $periodo <= 0 ? 1 : $item_processo->periodo - $periodo) : $item_processo->periodo;
                // $fatura_item->valor_unit = (is_array($item_proposta) ? self::valorUnitario($item_proposta[0], $this->processo->valor_mercadoria, $fatura_item->qtd, $fatura_item->periodo, $this->allItens, $this->processo->dias_consumo) : 'inp');
                // $fatura_item->valor_item = (is_array($item_proposta) ? self::calcAppValor($item_proposta[0], $this->processo->valor_mercadoria, $fatura_item->qtd, $fatura_item->periodo, $this->allItens, $this->processo->dias_consumo) : 'inp');
                $fatura_item->valor_unit = !self::isInPacote($lista_pacotes_faturados, $item_processo) ? ((is_array($item_proposta) 
                                ? self::valorUnitario($item_proposta[0], $valor_mercadoria, $fatura_item->qtd, $fatura_item->periodo, $this->allItens, $this->processo->dias_consumo) 
                                : 0))       
                            : 0;
                $fatura_item->valor_item = (!self::isInPacote($lista_pacotes_faturados, $item_processo) ? (is_array($item_proposta) 
                ? self::calcAppValor($item_proposta[0], $valor_mercadoria, $fatura_item->qtd, $fatura_item->periodo, $this->allItens, $this->processo->dias_consumo) 
                : 0) 
                  : 0);

                $fatura_item->store();
            } else {
                $fatura_item->id_predicado = $item_processo->id_predicado;
                $fatura_item->id_processopredicado = $item_processo->id_processopredicado;
                $fatura_item->descricao = $fatura_item_descricao->custom;
                $fatura_item->qtd = $item_processo->qtd;
                $fatura_item->dta_inicio = $item_processo->dta_inicio;
                $fatura_item->dta_final = $item_processo->dta_final;
                $fatura_item->periodo = $item_processo->periodo;
                // $fatura_item->valor_unit = (is_array($item_proposta) ? self::valorUnitario($item_proposta[0], $this->processo->valor_mercadoria, $fatura_item->qtd, $fatura_item->periodo, $this->allItens, $this->processo->dias_consumo) : 'inp');
                // $fatura_item->valor_item = (is_array($item_proposta) ? self::calcAppValor($item_proposta[0], $this->processo->valor_mercadoria, $fatura_item->qtd, $fatura_item->periodo, $this->allItens, $this->processo->dias_consumo) : 'inp');
                             
                $fatura_item->valor_unit = (!self::isInPacote($lista_pacotes_faturados, $item_processo) ? (is_array($item_proposta) 
                                ? self::valorUnitario($item_proposta[0], $valor_mercadoria, $item_processo->qtd, $item_processo->periodo, $this->allItens, $this->processo->dias_consumo) 
                                : 0) 
                            : 0);
                $fatura_item->valor_item = (!self::isInPacote($lista_pacotes_faturados, $item_processo) ? (is_array($item_proposta) 
                ? self::calcAppValor($item_proposta[0], $valor_mercadoria, $item_processo->qtd, $item_processo->periodo, $this->allItens, $this->processo->dias_consumo) 
                : 0) 
                  : 0);
                $fatura_item->store();
            }
            // Verificando se o item é do tipo pacote
            if (is_array($item_proposta) and $item_proposta[0]->predicado->servico->nome === 'Pacote') {
                $lista_pacotes_faturados[] = $item_proposta[0];   
            }
        }

        if (count($fatura_complementar->allItens) === 0)
            // Retorna que não vai criar a fatura 
            return ['message' => 'Sem itens novos do processo a serem cobrados.', 'status' => false];

        // Processando itens padrões da fatura
        $fatura_complementar->processarItemPadraoFatura();

        // exit();
        $fat_com = new FaturaComplementar;
        $fat_com->id_faturacheia = $fatura_cheia->id_fatura;
        $fat_com->id_faturacomplementar = $fatura_complementar->id;
        $fat_com->store();

        // Retorna que ocorreu tudo com sucesso
        return ['message' => 'sucess', 'status' => true, 'id' => $fatura_complementar->id];
    }

    public function get_captacao() {
        return new Captacao($this->id_captacao);//
    }


    public function get_status()
    {
        return new FaturaStatus($this->id_faturastatus);
    }

    public function get_processo()
    {
        return new Processo($this->id_processo);
    }

    public function get_cliente()
    {
        return new Individuo($this->id_cliente);
    }

    public function get_agentecarga()
    {
        return new Individuo($this->id_agentecarga);
    }

   
    public function calcValotTotal()
    {
        // Busca se o despachante possue comissão
        $this->comissao_despachante = self::buscaStatusComissaoDespachante();
        
        $valor_total = 0;
        $valor_custo = 0;
        foreach ($this->allItens as $key => $item) {
            // Calculando os valores dos itens
            $predicado = new Predicado($item['id_predicado']);
            // echo '<pre>';
            if ( $predicado->servico->nome === 'Desconto' ) {
                $valor_total -= ( is_numeric($item['valor_item']) ) ?  $item['valor_item'] : 0;
            } else {
                $valor_total += ( is_numeric($item['valor_item']) ) ?  $item['valor_item'] : 0;
            }
            // $valor_total += ( is_numeric($item['valor_item']) ) ?  $item['valor_item'] : 0;

            // Calculando os valores custos dos itens
            $valor_custo += is_numeric($item['valor_custo']) ?  $item['valor_custo'] : 0;
        }
        // print_r($valor_total);

        // exit();
        $this->valor = round($valor_total, 2);
        $this->valor_custo = round($valor_custo, 2);
        
        // Calculando comissoes
        self::get_comissoes();
    }

    /**
     * Metodo para recalcular a fatura
     */
    public function recalcular() {
        if ($this->status->status !== 'Fechada') {
            // Se tiver co recalculo ativado ele apaga todos os itens e processa
            if (($this->recalculo === 'sim')) {
                $this->deleteAllItens();
            } else {
                // $this->deleteAllItensCusto();
            }
            $this->processarItens($this->recalculo);
        } 
    }


    // Metodo para buscar se existe um despachante comissionario, se não existir, retorna um array vazio.
    public function get_despachante_comissionado() {
        $movimentacao = $this->movimentacao;
        if ($movimentacao instanceof Captacao or $movimentacao instanceof Despacho) {
            $despachante = $movimentacao->despachante;
            $comissionario = new Comissionario;
            $comissionario('id_comissionado', $despachante->id_individuo);
            if (isset($comissionario->id_comissionario) and !is_null($comissionario->id_comissionario)) {
                return $comissionario;
            } else {
                return [];
            }
        }
    }

    /**
     * Adicionar anexos na fatura
     * @param Upload $upload = objeto do tipo upload
     */
    public function addDocumento(Upload $upload)
    {
        if ($upload) {
            // Salvando o upload
            $upload->save();

            // Inserindo o documento na tabela CaptacaoUpload
            $faturaUpload = new FaturaDocumento;
            $faturaUpload->id_fatura = $this->id_fatura ?? $this->id;
            $faturaUpload->id_upload = $upload->id_upload;
            $faturaUpload->store();
        }
    }

    public function get_documento()
    {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura));
        $object = (new Repository(FaturaDocumento::class))->load($criteria);
        $documentos = array();

        // Verificando se encontrou documentos
        if (count($object) > 0) {
            $documentos = array();
            // Percorrendo o array com os objetos para pegar os objetos
            foreach ($object as $key => $captacao_upload_value) {
                $id_upload = $captacao_upload_value->id_upload;
                $upload = new Upload($id_upload);
                $upload->id_tipodocumento = $upload->tipo_documento->id_tipodocumento;
                $upload->tipodocumento = $upload->tipo_documento->nome;
                $upload->removeProperty(['id_bucket', 'nome_sistema', 'localizacao', 'validado']);
                $documentos[] = $upload->toArray();
            }
        }
        return $documentos;
    }

    public function deleteDocumento($id_documento = null) {
        if (is_null($id_documento)) {
            // Apagando todos os documentos da captacao antes de gravar os novos
            $faturaDocumento = new FaturaDocumento;

            $criteria = new Criteria;
            $criteria->add(new Filter('id_fatura', '=', $this->id_fatura));

            $uploads = (new Repository(FaturaDocumento::class))->load($criteria);
            if (count($uploads) > 0) {
                foreach ($uploads as $key => $documento) {
                $upload = new Upload($documento->id_upload);
                $upload->validado = 'no';
                $upload->store();
                }
            }
            $faturaDocumento->deleteByCriteria($criteria);
        }
    }



    /**
     * Metodo que apaga copia os itens do processo para a fatura
     * @param boolean $recalcular = Parametro que vai definir se é para fazer o calculo dos valore ou definir 0 em todos
     */
    public function processarItens($recalcular = 'sim')
    {   
        $this->store();

        $lote = $this->processo->isLote();
        
        if (is_array($lote)) {
            $lote_len = count($lote) === 0 ? 1 : count($lote);
        } else {
            $lote_len = 0;
        }
        $recalcular = $recalcular === 'sim';

        $this->id_fatura = $this->id;

        // Verifica se a fatura possue processo e se a fatura já é persistente no banco
        if (isset($this->id_processo) && (isset($this->id) || isset($this->id_fatura))) {
            $valor_total = 0;
            if ( !$this->processo->isDespacho() ) {
                if ( !$this->processo->isLote() ) {
                    $movimentacao = $this->movimentacao;
                    $itens_processo = $this->captacao->itensProcessoArray;
                } else {
                    $itens_processo = $this->captacao->itensProcessoArray;
                    $movimentacao = $this->captacao;
                }
            } else {
                $movimentacao = $this->movimentacao;
                $itens_processo = $this->processo->itensProcessoArray;
            }
      
            // Verificando se possue itens
            if (count($itens_processo) > 0) {
                $this->valor_mercadoria = $this->processo->isDespacho() ? $this->processo->valor_mercadoria : $this->captacao->liberacao->valor_mercadoria;
                $lista_pacotes_faturados = [];
                foreach ($itens_processo as $key => $item) {
                    if ( $item['id_predicado'] === '70' ) {
                        $it = $itens_processo[$key];
                        unset($itens_processo[$key]);
                        $itens_processo[] = $it;
                    }
                }
            
                foreach ($itens_processo as $key => $item) {
                    // Se o recalculo estiver desativado, não altera os itens da fatura.
                    if ( !$recalcular ) continue;
                
                    $item_proposta = null;
                    $processo_predicado = new ProcessoPredicado($item['id_processopredicado']);
                    $predicado_master = (object) self::getItemMaster($processo_predicado);
                    $predicado_master->dimensao = $processo_predicado->dimensao;

                    $predicado_master->descricao = ( isset($item_proposta) and count($item_proposta) > 0 ) ? $item_proposta[0]->descricao : $predicado_master->descricao;

                    $item = (object) $item;

                    // Verificando o item na proposta
                    if ($this->processo->isDespacho()) {
                        $item_proposta = $movimentacao->proposta->servicoById($item->id_predicado, $item->dimensao, $movimentacao->id_margem, $movimentacao->depot);
                    } else {
                        $item_proposta = $movimentacao->proposta->servicoById($item->id_predicado, $item->dimensao, $movimentacao->id_margem);
                    }
                    $item_fatura = new FaturaItem;
                    $item_fatura->id_propostapredicado = is_array($item_proposta) ? $item_proposta[0]->id_propostapredicado : null;
                    $item_fatura->id_processopredicado = $processo_predicado->id_processopredicado;
                    $item_fatura->id_fatura = $this->id;
                    $item_fatura->id_predicado = $predicado_master->id_predicado;

                    // Customizando a descrição da fatura, caso exista customização
                    $fatura_item_descricao = new FaturaItemCustom;
                    $fatura_item_descricao->item = $predicado_master;
                    $fatura_item_descricao->id_predicado = $predicado_master->id_predicado;
                    $dta1 = new DateTime($item->dta_inicio);
                    $dta2 = new DateTime($item->dta_final);
                    $dias_consumo = $dta1->diff($dta2)->days + 1;
                    $item_fatura->descricao = $fatura_item_descricao->custom;
                    $item_fatura->qtd = $item->qtd;
                    $item_fatura->valor_custo = $item->valor_custo ?? null;
                    $item_fatura->dta_inicio = $item->dta_inicio;
                    $item_fatura->dta_final = $item->dta_final;
                    $item_fatura->periodo = $item->periodo;
                    $item_fatura->id_faturaitemlegenda = self::isInPacote($lista_pacotes_faturados, $item) 
                        ?3 // Incluso
                        : ( !is_array($item_proposta) 
                            ? 1 // Não em proposta
                            : ($item_proposta[0]->valor === 'sc' 
                                ? 2 // Sobre consulta
                                : null ));
                                
                    $item_fatura->valor_unit = $recalcular 
                    ? (!self::isInPacote($lista_pacotes_faturados, $item) ? ((is_array($item_proposta) and count($item_proposta) > 0)
                                    ? self::valorUnitario($item_proposta[0], $this->valor_mercadoria, $item->qtd, $item->periodo, $this->allItens, $dias_consumo) 
                                    : 0) 
                                : 0) : 0;

                    // if ($item_fatura->id_predicado === '70' ) {
                    //     print_r($item);
                    //     // foreach($this->allItens as $item) {
                    //         // print_r($todos);
                    //         // exit();
                    //     // }
                    // }


                    $item_fatura->valor_item = $recalcular      
                    ? (!self::isInPacote($lista_pacotes_faturados, $item) 
                        ? ((is_array($item_proposta) and count($item_proposta) > 0)
                            ? self::calcAppValor($item_proposta[0], $this->valor_mercadoria, $item->qtd, $item->periodo, $this->allItens, $dias_consumo) 
                            : 0) 
                        : 0) 
                    : 0;
                           

                    if ( $lote ) {
                        $search = $this->processo->lote->captacoesHaveItemQtd($item);
                        $qtd_diferencial = ( $search instanceof ProcessoPredicado ) ? $search->qtd : null ;
      
                        if ( is_string($isProrate = $processo_predicado->predicado->isProrate()) )
                            $propate = $this->processo->lote->captacoesHaveContainer($movimentacao->listaContainer);

                        // Verifica o tipo de cobrança, se vai ser rateado, integral, etc...
                        switch ( $isProrate ) {
                            case 'rat':
                                if ( $propate and $this->processo->lote->captacoesHaveItem($item) ) 
                                    $item_fatura->valor_item = $this->calcPropate($item_fatura->valor_unit, $item_fatura->valor_item, $propate, $item);

                                break;

                            case 'int_e_rat':
                                $item_fatura->valor_item = $item_fatura->valor_item + $this->calcPropate($item_fatura->valor_unit, null, $propate, $item);
                                break;

                            case 'int':
                                break;
                        }
                    }
                    // Verificando se o item é do tipo pacote
                    if (is_array($item_proposta) and count($item_proposta) > 0 and $item_proposta[0]->predicado->servico->nome === 'Pacote') {
                        $lista_pacotes_faturados[] = $item_proposta[0]; 
                    }

                    $item_fatura->store(); 
                    $this->cValor($item_fatura);
                }
                return $recalcular ? self::processarItemPadraoFatura() : ['message' => '', 'status' => 1];
            } else {
                return ['message' => 'processo sem itens', 'status' => 0];
            }
        }
    }

    // function set_valor($valor) {
    //     print_r($valor);
    //     exit();
    // }

    /**
     * Metodo para calcular o rateamento do lote
     * @param numeric $val_unit 
     * @param numeric $val_item 
     * @param array $prorates Array contendo a quantidade de container e o número de processos que cada container esta compartilhado (LOTE)
     * @param FaturaItem $item 
     */
    private function calcPropate($val_unit, $val_item, array $prorates, $item=null) {
        $result = $val_item;
        // Percorrendo a lista de containeres
        foreach ( $prorates as $prorate ) {
            if ( $prorate[1] === 1) // Caso o container esteja sendo usado em apenas 1 processo, passe para o processo elemento da interação
                continue;

            if ( is_null($val_item) ) // Caso o valor do item for nulo, pegue o valor únitario e divide pela quantidade de processo compartilham e subtrai pelo valor do item 
                $result = $val_unit / $prorate[1];

            if ( !is_null($val_item) ) {
                // $result -= $val_unit;
                $result = $val_item / $prorate[1];
            }

        }
        return $result;
    }

    private function processarItemPadraoFatura()
    {
        $item_padrao_fatura = new ItemPadrao;
        $item_padrao_fatura->modulo = 'fatura';
        $item_padrao_fatura->id_operacao = $this->captacao->id;
        $item_padrao_fatura->dias_consumo = 0;
        $item_padrao_fatura->valor_mercadoria = 0;
        $servicos = $item_padrao_fatura->servByOperacao($this->movimentacao->proposta->regime);
        // print_r($servicos);
        // exit();
        foreach ($servicos as $key => $item) {
            // Varificando a classificação para verificar se o item é obrigatório estar na proposta
            $predicado = $item->predicado;
            $predicado->modulo = $item_padrao_fatura->modulo;
            $item_proposta = null;
            // Verificando se o predicado precisa estar na propósta antes de prosseguir
            if ($predicado->checkNeedInProposta) {
                // Verificando se o item existe na proposta
                if (count($this->movimentacao->proposta->servicoById($item->id_predicado, $item->dimensao)) > 0) {
                    $item_proposta = $this->movimentacao->proposta->servicoById($item->id_predicado, $item->dimensao);
                    // Não existe na proposta
                } else {
                    // Criando uma lista com todos os itens da proposta
                    $itens_proposta = $this->movimentacao->proposta->get_servico($item->dimensao);
                    foreach ($itens_proposta as $key => $item_pro) {
                        $pacote = new Pacote();
                        $pacote->id_predicado = $item_pro->id_predicado;
                        // Verificando se é um pacote
                        if (count($pacote->pacoteByPredicado) > 0) {
                            $pacote = $pacote->pacoteByPredicado;
                            if (count($pacote) > 0) {
                                $pacote[0]->id_servico = $item->id_predicado;
                                // Verificando se o item faz parte do pacote
                                if (count($pacote[0]->pacoteHasPredicado) > 0) {
                                    $item_proposta[] = $item_pro;
                                }
                            }
                        }
                    }
                };
                if (is_null($item_proposta)) {
                    return ['message' => "Serviço ({$predicado->nome}) - Dimensão({$item['dimensao']}) não oferecido na proposta", 'status' => 0];
                }
            } else {
                $item_fatura = new FaturaItem;
                $item_fatura->id_fatura = $this->id;
                $item_fatura->qtd = 1;
                $item_fatura->periodo = 0;
                $item_fatura->dta_inicio = (new Processo($this->id_processo))->dta_inicio;
                $item_fatura->dta_final = (new Processo($this->id_processo))->dta_final;
                $item_fatura->id_predicado = $item->id_predicado;
                // Customizando a descrição da fatura, caso exista customização
                $fatura_item_descricao = new FaturaItemCustom;
                $fatura_item_descricao->item = ( isset($item_proposta) and count($item_proposta) > 0 ) ? $item_proposta[0]->descricao : (new Predicado($item->id_predicado));
                // $fatura_item_descricao->item = $item;
                // print_r($fatura_item_descricao->custom);
                // exit();
                $fatura_item_descricao->id_predicado = $item->id_predicado;
                $item_fatura->descricao = $fatura_item_descricao->custom;
                if ($item_proposta) {
                    $item_fatura->id_propostapredicado = $item_proposta[0]->id_propostapredicado;
                    $item_fatura->valor_unit = self::valorUnitario($item_proposta[0], $this->captacao->liberacao->valor_mercadoria, 1, 1, $this->allItens);
                    $item_fatura->valor_item = self::calcAppValor($item_proposta[0], $this->captacao->liberacao->valor_mercadoria, 1, 1, $this->allItens);
                } else {
                    if ( $this->processo->isDespacho() ) {
                        $valor_mercadoria = $this->processo->valor_mercadoria;
                    } else {
                        $valor_mercadoria = $this->captacao->liberacao->valor_mercadoria;
                    }
                    $item_fatura->valor_unit = self::valorUnitario($item, $valor_mercadoria, 1, 1, $this->allItens);
                    $item_fatura->valor_item = self::calcAppValor($item, $valor_mercadoria, 1, 1, $this->allItens);
                }
                $item_fatura->store();
                $this->cValor($item_fatura);
                $this->store();

            }
        }
        return ['message' => '', 'status' => 1];
    }

    private function cValor(FaturaItem $item) {
        if ( $item->servico !== 'Desconto') {
            $this->valor += is_numeric($item->valor_item) ? $item->valor_item : 0;
            // print_r($item);
        }
    }

    /**
     * Metodo que apaga todos os itens da fatura
     */
    private function deleteAllItens() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura ?? $this->id));
        $this->deleteByCriteria($criteria);
        $this->itens = [];
    }


    /**
     * Metodo que apaga todos os itens com valor_item = 0 ou nulo 
     */
    private function deleteAllItensCusto() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura ?? $this->id));
        $criteria->add(new Filter('valor_item', '=', 0));
        $this->deleteByCriteria($criteria);
    }

    public function deleteByCriteria(Criteria $criteria, $aggregate = null)
    {   
        (new FaturaItem)->deleteByCriteria($criteria);
    }


    /**
     * Metodo para ativar comissoes
     * @param Vendedor | Despachante $comissionado = recebe o comissionado a ser desativado
     */
    public function ativaComissao($comissionado) {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura ?? $this->id));
        $criteria->add(new Filter('id_comissionario', '=', $comissionado->id_comissionario));
        $fatura_com = new FaturaComissoesDesativadas;
        $fatura_com->deleteByCriteria($criteria);
    }

    public function calcValorImpostoTerminal() {
        $itens = self::get_allItens();
        if (is_array($itens) and count($itens) > 0) {
            $valor_custo = 0;
            foreach ($itens as $key => $item) {
                if ($item['servico'] !== 'Impostos') {
                    $valor_custo += $item['valor_custo'];
                } else {
                    $imposto_item = new FaturaItem($item['id_faturaitem']);
                    $imposto_item->valor_custo = round(((round($valor_custo, 2) / ((100 - 14.25) / 100)) * (14.25 / 100)), 2);
                    $imposto_item->valor_custo = $item['valor_custo'];                  ;
                    $imposto_item->store();
                }
            }
        }
    }


    /**
     * Metodo para desativar comissoes
     * @param Vendedor | Despachante $comissionado = recebe o comissionado a ser desativado
     */
    public function desativaComissao($comissionado) {
        $com_desativada = new FaturaComissoesDesativadas;
        $com_desativada->id_fatura = $this->id_fatura ?? $this->id;
        $com_desativada->id_comissionario = $comissionado->id_comissionario;
        $com_desativada->store();
    }

    /**
     * Metodo para buscar se a comissão do despachante esta desativada
     */
    public function buscaStatusComissaoDespachante() {
        if (!$this->despachante_comissionado instanceof Comissionario && (!isset($this->despachante_comissionado->id_comissionario) or is_null($this->despachante_comissionado->id_comissionario)))
            return 'des_not_com';
        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura ?? $this->id));
        $criteria->add(new Filter('id_comissionario', '=', $this->despachante_comissionado->id_comissionario));
        $repository = (new Repository(FaturaComissoesDesativadas::class))->load($criteria);
        if (count($repository) === 0) 
            return true;
        return false;
    }


    /**
     * Metodo que busca as comissoes que estão desativadas
     */

    public function get_listaComissionadosDesativados() {
        $com_desativada = new FaturaComissoesDesativadas;
        $com_desativada('id_fatura', $this->id_fatura);
        if (isset($com_desativada->id_faturacomissoesdesativada))
            return $com_desativada;
        return [];
    }

    public function addItem($item = null)
    {
        if ($item) {
            // Se for antigo, instancia o objeto para ser atualizado
            $fatura_item = new FaturaItem($item['id_faturaitem'] ?? null);
            $fatura_item->id_fatura = $this->id ?? $this->id_fatura;
            foreach ($item as $key => $value) 
            {   
                if ( $key !== 'locked')
                    $fatura_item->{$key} = $value;
            }
            $fatura_item->store();

            $fatura_item_pro = new FaturaItemPro;
            $fatura_item_pro('id_faturaitem', $fatura_item->id ?? $fatura_item->id_faturaitem);
            if ( !$fatura_item_pro->isLoaded() ) {
               $fatura_item_pro = new FaturaItemPro;
               $fatura_item_pro->id_faturaitem = $fatura_item->id ?? $fatura_item->id_faturaitem;
               $fatura_item_pro->valor_custo_imposto_locked = isset($item['locked']) ? ( $item['locked'] ? 'TRUE' : 'FALSE' ) : 'FALSE';
            } else {
               $fatura_item_pro->valor_custo_imposto_locked = isset($item['locked']) ? ( $item['locked'] ? 'TRUE' : 'FALSE' ) : 'FALSE';
            }
               $fatura_item_pro->store();
        }
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

    private function calcValorTotal($valor) {

    }

    public function get_allItens()
    {
        $this->valor_imposto_c = 0;
        $this->valor_despesa_c = 0;

        $criteria = new Criteria;
        $criteria->add(new Filter('id_fatura', '=', $this->id_fatura ?? $this->id));
        $repository = new Repository('App\Model\Fatura\FaturaItem');
        // print_r($repository);

        foreach ($repository->load($criteria) as $key => $item) {
            
            $this->valor += $item->valor_item;
            $item->legenda = [ 
                'legenda' => $item->legenda->isLoaded() ? $item->legenda->legenda : null, 
                'color' =>  $item->legenda->isLoaded() ? $item->legenda->color : null
            ];
            $item->unidade = (new PropostaPredicado($item->id_propostapredicado))->unidade;
            // Verificando se o item é despesa ou imposto
            if (( $item->servico === 'impostos' || $item->servico === 'Impostos' )) {
                $this->valor_imposto_c += $item->valor_item;
            } else if ( $item->servico !== 'Desconto') {
                $this->valor_despesa_c += is_numeric($item->valor_item) ? $item->valor_item : 0;
            }

            // select * from Fatura where id_fatura=1235
            // select * from FaturaItem where id_fatura=1235
            $item->moeda = $item->moeda->moeda;
            $item->servico = $item->servico;
            $item->locked = $item->locked;
            $item->valor_item = round($item->valor_item, 2);
            $item->valor_unit = round($item->valor_unit, 2);
            $itens[] = $item->toArray();
        }
        $this->valor -= $this->valor_despesa_c;
        $this->itens = $itens ?? [];
        return $this->itens;
    }

    

    public function get_comissoes() {
        $comissao = new Comissao;
        $comissao->ativar_despachante = self::buscaStatusComissaoDespachante();
        $comissao->byFatura($this);
        $this->comissao = [ 
                'valor_comissao_total' => $comissao->get_valor_total(),
                'despachante' => $comissao->get_valor_despachante(),
                'vendedor'    => $comissao->get_valor_vendedor()
        ];
    }

    public function get_modelo()
    {
        return new FaturaModelo($this->id_faturamodelo);
    }

    public function get_valordespesa()
    {
        return $this->valor_despesas;
    }

    public function get_valorcusto()
    {   
        $this->valor_custo += $this->imposto_interno_valor;
        return $this->valor_custo;
    }

    public function get_movimentacao() {
        return is_null($this->processo->movimentacao) ? $this->captacao : $this->processo->movimentacao;
    }

    
    public function get_valor_item_imposto() {
            print_r($this);
            exit();
    }

    public function get_valorimposto()
    {
        return $this->valor_impostos;
    }


    public function get_valor_lucro() {
        $valor_lucro = ($this->valor - ($this->imposto_interno_valor + $this->valor_custo)); 
        return $this->valor_lucro = $valor_lucro;
    }

    public function get_imposto_interno() {
        return $this->custo_extra->custo_valor_interno . '%';
    }

    public function get_imposto_interno_valor() {
        return round((($this->valor * $this->custo_extra->custo_valor_interno) / 100), 2);
    }

    public function get_custo_extra()
    {
        $cext = new Cext();
        $cext->getListaByTipo('fatura', 'interno'); 
        return $cext;
    }

    public function get_margem_lucro() {
        $val_com_imposto_interno = $this->valor - (($this->valor * $this->custo_extra->custo_valor_interno) / 100);
        if ( (int) $val_com_imposto_interno === 0 ) {
           return 0;
       } else {    
           return round(($this->valor_lucro / $this->valor) * 100, 2);
       }
    }

    private function clean() {
        $this->removeProperty([
            'comissao_despachante',
            'valor_despesa_c',
            'valor_imposto_c',
            'isC',
            'valor_mercadoria'
        ]);
    }

    /**
     * Metodo que faz uma verificacao no item1 se existe o item2 nele
     */
    private function mergeItens(array $needle, array $haystack) {
        foreach ($needle as $k => $i) {
            $found = false;
            foreach ($haystack as $key => $item) {
                if (!is_null($i['id_processopredicado']) and !is_null($item['id_processopredicado'])) {
                    if (($item['id_processopredicado'] === $i['id_processopredicado'])) {
                        $found = true;
                        unset($haystack[$key]);
                        $haystack[] = $i;
                    }
                }
            }
            if (!$found)
                $haystack[] = $i;
        }
        return $haystack;
    }

    /**
     * @param $prop1 propriedade a ser somado
     * @param $prop2 propriedade a ser buscada e comparada com o valor
     * @param $value valor a ser buscado como referencia de identificacao do item
     * @param $haystack lista de itens que serão verificados e buscados os valores internos
     */
    private function getPropTotal(string $prop1, string $prop2, string $value, array $haystack) {
        $valor_total = 0;
        foreach ($haystack as $k => $item) {
            if (isset($item[$prop2]) and $item[$prop2] === $value) {
                $valor_total += $item[$prop1];
            }
        }

        return $valor_total;
    }

    public function tracking()
    {
        $fatura_tracking = new FaturaTracking;
        $fatura_tracking->id_fatura = $this->id_fatura;
        return $fatura_tracking;
    }

    public function get_modelo_nome() {
        return (new FaturaModelo($this->id_faturamodelo ?? null))->isLoaded() ? (new FaturaModelo($this->id_faturamodelo))->nome : null;
    }

    /**
     * Metodo que verifica se o item esta incluso em algum pacote que ja foi cobrado
     * @param array $lista_pacote Lista de Pacote já faturado
     * @param $item Item a ser buscado no pacote
     */
    private function isInPacote(array $lista_pacote, $item) {
        // echo $item->dimensao . '<BR>'. ($item->dimensao === 'ambos' or $item->dimensao === 'nenhum') or $pacote->dimensao === $item->dimensao . '<FINAL>';
        foreach ($lista_pacote as $pacote) {
            // echo $pacote->dimensao .  ' <br> ' . $item->dimensao . ' <final> ';
            // echo 'IGUAL: '. $pacote->dimensao === $item->dimensao;
            if (($item->dimensao === 'ambos' or $item->dimensao === 'nenhum' ) or $pacote->dimensao === $item->dimensao) {
                $pac = (new Pacote)('id_predicado', $pacote->id_predicado);
                $pac->id_servico = $item->id_predicado;
                // print_r($pac->pacoteHasPredicado);
                return (count($pac->pacoteHasPredicado) > 0 );
            }
        }
        return false;
    }

        /**
     * Metodo para pegar o body
     * @param String $body_name Nome do body a ser buscado 
     */
    public function bodymail(string $body_name) {
        return $this->body($body_name, $this);
    }

    /**
     * Metodo que verifica se a fatura é uma complementar
     */
    public function isComplementar() {
        $fat = (new FaturaComplementar)('id_faturacomplementar', $this->id ?? $this->id_fatura);
        $this->isC = !is_array($fat) ? $fat->isLoaded() : false;
        return $this->isC;
    }

}
