<?php
namespace App\Control\Processo;

use App\Mvc\Controller;
use App\Model\Liberacao\Liberacao;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Fatura\Fatura;
use App\Model\Processo\Processo;
use App\Model\Processo\ProcessoPredicado;
use App\Model\Despacho\Despacho;
use App\Lib\Tool\Register;
use Slim\Http\Response;
use Slim\Http\Request;

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
         * Se tiver a propriedade 
         */
        if ( !is_null($data) || (( isset($data['app']) && !is_null($data['app'])) && (isset($data['id']) && !is_null($data['id']) ) || (isset($data['id_processo']) && !is_null($data['id_processo'])))) {
            // Definindo o tipo de ação, fatura nova ou atualização
            $isUpdate = isset($data['id_processo']) ? true : false;
        } else { // Retorna mensagem de erro, pois não é nem edição e novo cadas
            $this->result->message = 'Faltando parametros que definem o tipo de ação. ("id_processo")';
            $this->result->status = 'fail';
            // Encerrando
            return json_encode((array) $this->result);
        }
         // É atualização?
         if ($isUpdate) {
            // Instancia o processo 
            $processo = new Processo($data['id_processo']);
            $processo->id_fornecedor = $data['id_fornecedor'];
            $processo->id_processostatus = $data['id_status'];
            $this->update($request, $response, $processo, $data);
            $processo->id_fornecedor = $data['id_fornecedor'];
        } else {

            // Processo novo
            $processo = new Processo;
            if (isset($data['id_fornecedor'])) $processo->id_fornecedor = $data['id_fornecedor'];

            // Verificando se o processo esta sendo criado a partir de uma operação
            if ((isset($data['regime']) && isset($data['id'])) ) {
                // Criando processo por uma operação
                $this->checkRegime($processo, $data['regime'], $data);
            }
        }
        $this->result->id_processo = $processo->id;
        // exit(); 
        self::closeTransaction();
        return json_encode((array) $this->result);
    }

    /**
     * Metodo para verificar o tipo de fatura a ser criada
     * @param Processo $fatura Fatura já criada
     * @param string | null $regime Nome do regime
     * @param Array | null $data Dados recebidos
     */
    private function checkRegime(Processo $processo, String $regime = null, array $data = null)
    {       
        if (!is_null($regime) && $data) {
            switch ($regime) {
                case 'exportacao':
                    $this->regimeExportacao($processo, $data);
                    break;

                case 'importacao':
                    $this->regimeImportacao($processo, $data);
                    break;

                default:
                    $this->update();
                    break;
            }
        }
    }

    /**
     * Criar fatura do tipo importação e exportação
     * @param Processo | null $processo Fatura já criada
     * @param Array | null $data Dados enviados pelo request
     */
    private function regimeImportacao(Processo $processo = null, $data = null)
    {
        if (!is_null($processo) && $data) {
            if (isset($data['lote']) and $data['lote'] === true) {
                $processo->id_captacaolote = $data['id'];
            } else {
                $liberacao = new Liberacao($data['id']);
                $processo->id_captacao = $liberacao->id_captacao;
            }
            $processo->store();
            self::gerarItens($processo, $data);
        }
    }
    /**
     * Criar fatura do tipo importação e exportação
     * @param Processo $fatura Fatura já criada
     * @param Array $data Dados enviados pelo request
     */
    private function regimeExportacao(Processo $processo = null, $data = null)
    {
        if (!is_null($processo) && $data) {
            $despacho = new Despacho($data['id']);
            $processo->id_despacho = $despacho->id_despacho;
            $processo->store();
            // $despacho->addEvento('g_processo', '', 'despacho');
            self::gerarItens($processo, $data);
        }
    }

    /**
     * Processar ou reprocessar os itens do processo
     * @param Processo $processo Processo já criado
     * @param Array $data Dados enviados pelo request
     */
    private function gerarItens(Processo $processo = null, array $data = null)
    {  
        // Itens recebidos pelo request
        if (isset($data['itens']) && count($data['itens']) > 0) {
            $itens = $data['itens'] ?? null;
            if ($itens != null) {
                $criteria = new Criteria;
                $criteria->add(new Filter('id_processo', '=', $processo->id_processo ?? $this->id));  
                foreach ($itens as $key => $item) {
                    $pro_predicado = new ProcessoPredicado(isset($item['id_processopredicado']) ? $item['id_processopredicado'] : null);
                    if ( !$processo->isDespacho() )
                        $pro_predicado->id_captacao = $item['id_captacao'];
                    $pro_predicado->id_processo = $processo->id;
                    $pro_predicado->id_predicado = $item['id_predicado'];
                    $pro_predicado->dta_inicio = isset($item['dta_inicio']) && $item['dta_inicio'] ? date('Y-m-d', strtotime($item['dta_inicio'])) : NULL;
                    $pro_predicado->dta_final = isset($item['dta_final']) && $item['dta_final'] ? date('Y-m-d', strtotime($item['dta_final'])) : NULL;
                    $pro_predicado->periodo = $item['periodo'];
                    $pro_predicado->qtd = $item['qtd'] ?? null;        
                    $pro_predicado->valor_item = $item['valor_item'] ?? null;
                    $pro_predicado->dimensao = $item['dimensao'] ?? null;
                    $pro_predicado->dias_consumido = $item['dias_consumido'] ?? null;
                    $pro_predicado->store();
                    $criteria->add(new Filter('id_processopredicado', '<>',  isset($item['id_processopredicado']) ? $item['id_processopredicado'] : $pro_predicado->id));
                }
                $pro_predicado->deleteByCriteria($criteria);
                // Recalculando Fatura
                if ( $lote = $processo->isLote() ) { // Verificando se é lote
                    foreach($lote as $current_lote) {
                        $fatura = new Fatura;
                        $fatura('id_captacao', $current_lote->id_captacao);
                        if (!is_null($fatura->id_fatura)) {
                            $fatura->removeProperty('valor_imposto_c');
                            $fatura->recalcular();
                        }
                    }
                } else {
                    $fatura = new Fatura;
                    $fatura('id_processo', $processo->id_processo);
                    if (!is_null($fatura->id_fatura)) {
                        $fatura->removeProperty('valor_imposto_c');
                        $fatura->recalcular();
                    }
                }
               
            }
        }
    }

    /**
     * Processar ou reprocessar os itens do processo
     * @param Processo $processo Processo já criado
     * @param Array $data Dados enviados pelo request
     */
    private function update(Request $request, Response $response, Processo $processo = null, array $data = null)
    {
        // echo '<pre>';
        // print_r($data);
        // exit();
        // Itens recebidos pelo request
        if (!is_null($processo) && !is_null($data)) {
            $processo->dta_inicio = isset($data['dta_inicio']) && $data['dta_inicio'] ? date('Y-m-d', strtotime($data['dta_inicio'])) : NULL;;
            $processo->dta_final = isset($data['dta_final']) && $data['dta_final'] ? date('Y-m-d', strtotime($data['dta_final'])) : NULL;;
            $processo->mercadoria = $data['mercadoria'] ?? null;
            $processo->valor_mercadoria = $data['valor_mercadoria'] ?? null;

            // Criando um registros
            $reg = new Register;
            $reg->add('id_processostatus', 'status');

            $resp_save_processo = $processo->store($request, $response, $reg);
            $result['id_processo'] = $processo->id;
            $processo->request = $request;
            $processo->response = $response;
            // Verificando se houve alteracão e salva o historico
            self::historico($resp_save_processo, $processo);

            self::gerarItens($processo, $data);
        }
    }
}
