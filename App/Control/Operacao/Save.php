<?php
namespace App\Control\Operacao;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Captacao\Captacao;
use App\Model\Liberacao\Liberacao;
use App\Model\Liberacao\LiberacaoDocumento;
use App\Model\Documento\TipoDocumento;
use App\Model\Documento\DocDi;
use App\Model\Documento\DocDta;
use App\Model\Documento\UploadDocumento;
use App\Lib\Tool\Register;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller
{  
  public function store(Request $request, Response $response, Array $data)
  {
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    $isUpdate = false;
    self::openTransaction();
    if (isset($data['numero'])) {
        $liberacao = new Liberacao($data['id_liberacao']);
        $isUpdate = true;
    } else {
        $liberacao = new Liberacao;
    }
    $num = $liberacao->getLast() + 1;
    $id_captacao = $data['id_captacao'];
    $captacao = new Captacao($id_captacao);
    $liberacao->id_captacao = $captacao->id_captacao;
    $liberacao->documento = $data['documento'] ?? null;
    $liberacao->numero = $num;
    $liberacao->tipo_operacao = $data['tipo_operacao'] ?? null;
    $liberacao->id_liberacaostatus = $data['id_status'] ?? null;
    $liberacao->dta_recebimento_doc = isset($data['dta_recebimento_doc'])&&$data['dta_recebimento_doc']?date('Y-m-d', strtotime($data['dta_recebimento_doc'])):NULL;
    $liberacao->dta_liberacao = isset($data['dta_liberacao'])&&$data['dta_liberacao']?date('Y-m-d', strtotime($data['dta_liberacao'])):NULL;
    $liberacao->dta_saida_terminal = isset($data['dta_saida_terminal'])&&$data['dta_saida_terminal']?date('Y-m-d', strtotime($data['dta_saida_terminal'])):NULL;
    
    // Criando um registro 
    $reg = new Register;
    $reg->add('id_liberacaostatus', 'status');
    // Gravando liberacão
    $resp_save_liberacao = $liberacao->store($reg);

    // Verificando se houve alteracão e salva o historico
    self::historico($resp_save_liberacao, $liberacao);

    if ($captacao->ref_importador == null || $data['ref_importador'] !== $captacao->ref_importador) {
        $captacao->ref_importador = $data['ref_importador'];
        $resp_save_captacao = $captacao->store();        
        self::historico($resp_save_captacao, $liberacao);
        self::historico($resp_save_captacao, $captacao);
    }

    $anexos = $data['anexos'] ?? null;

    if (isset($anexos) && count($anexos[0]) == 0) {
        // Apaga todos os documentos
        $liberacao->deleteDocumentos();
    }

    // Verificando se possue documentos anexados
    if (isset($anexos) && count($anexos) > 0) {
        foreach ($anexos[0] as $key => $anexo) {
            $id_anexo = $anexo['id_upload'] ?? null;
            if (!is_string($id_anexo)) {
                // Apaga todos os documentos
                $liberacao->deleteDocumentos();

                // Instanciando liberacao documento
                $lib_documento = new LiberacaoDocumento;
                $lib_documento->id_liberacao = $liberacao->id_liberacao ?? $liberacao->id;
                $lib_documento->id_upload = $anexo['id_upload'] ?? null;
                $lib_documento->id_tipodocumento = $anexo['id_tipodocumento'];
                $tipo_documento = new TipoDocumento($anexo['id_tipodocumento']);
                $upload_documento = new UploadDocumento();
                // Verificando o tipo de documento
                switch ($tipo_documento->nome) {
                    case 'DI':
                        $doc_di = new DocDi();
                        $doc_di->identificacao = $data['documento'] ?? null;
                        $doc_di->store();
                        $lib_documento->id_docdi = $doc_di->id;
                        $lib_documento->store();
                        $upload_documento->id_upload = $anexo['id_upload'];
                        $upload_documento->id_tipodocumento = $anexo['id_tipodocumento'];
                        $resp_save = $upload_documento->store();
                        if ($resp_save['occurrences'] !== null) {
                            $liberacao->addHistorico('Adicionado documento do tipo DI');
                        }
                        break;

                    case 'DTA':
                        $doc_dta = new DocDta();
                        $doc_dta->identificacao = $data['documento'] ?? null;
                        $doc_dta->store();
                        $lib_documento->id_docdta = $doc_dta->id;
                        $lib_documento->store();
                        $upload_documento->id_upload = $anexo['id_upload'];
                        $upload_documento->id_tipodocumento = $anexo['id_tipodocumento'];
                        $resp_save = $upload_documento->store();
                        if ($resp_save['occurrences'] !== null) {
                            $liberacao->addHistorico('Adicionado documento do tipo DTA');
                        }
                        break;
                    
                    default:
                        break;
                } 
            }
        }
    } 
    
    if (!isset($data['id_liberacao'])) {
        $captacao->addEvento('g_liberacao');
    }
    self::closeTransaction();    
    return json_encode($result);
  }

  private function historico($resp_save = null, $object) {
       // Verificando se houve alteracão ou inclusão
    if ($resp_save['occurrences'] !== null) {
        foreach ($resp_save['occurrences'] as $key => $occurrence) {
            switch ($occurrence['action']) {
                case 'updated':
                    $msg = "Alterado " . $occurrence['propertie_comment'] . " de " . $occurrence['value_old'] . " para " . $occurrence['value_new'];
                    break;
                case 'added':
                    $msg = "Inserido " . $occurrence['propertie_comment'];
                    break;
                
                default:
                    break;
            }
            $object->addHistorico($msg);
        }
    }
  }
}
