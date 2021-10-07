<?php
namespace App\Control\Captacao;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Captacao\Captacao;
use App\Model\Captacao\Status;
use App\Model\Captacao\CaptacaoUpload;
use App\Model\Captacao\Container;
use App\Model\Captacao\CaptacaoContainer;
use App\Model\Documento\Documento;
use App\Model\Documento\UploadDocumento;
use App\Model\Documento\TipoDocumento;
use App\Model\Documento\Upload;
use DateTime;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Lib\Tool\Register;

class Save extends Controller
{
  private $isUpdate = false;

  public function store(Request $request = null, Response $response = null, array $data)
  {
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    $valid = 1;
    //Verificando se os dados possuem todas as colunas necessarias para o banco
    try {
      self::openTransaction();
      // Verifica se a proposta possue numero, se possui então será uma atualizacao
      if (isset($data['numero']) && $data['numero'] != null) {
        // O número é o mesmo ID da captacão
        $captacao = new Captacao($data['numero']);
        // Setando que se trata de uma atualizacao
        $this->isUpdate = true;
      } else { // Nova Captacao
        $this->isUpdata = false;
        $captacao = new Captacao;
        $numCaptacao = $captacao->getLast() + 1;
        $captacao->numero = $numCaptacao;
      }

      $captacao->request = $request;
      $captacao->response = $response;

      $data['dta_atracacao'] = isset($data['dta_atracacao']) && $data['dta_atracacao'] ? date('Y-m-d', strtotime($data['dta_atracacao'])) : NULL;
      $data['dta_prevista_atracacao'] = isset($data['dta_prevista_atracacao']) && $data['dta_prevista_atracacao'] ? date('Y-m-d', strtotime($data['dta_prevista_atracacao'])) : NULL;

      //Checking if BL already exists
      $bl = isset($data['bl']) ? $data['bl'] : null;
      if (!is_null($bl) and !$this->isUpdate) {
        $criteria = new Criteria();
        $criteria->add(new Filter('bl', '=', $bl));
        $repository = (new Repository(Captacao::class))->load($criteria);
        if (count($repository) > 0 and $repository[0]->numero !== $data['numero']) {
          $result['message'] = "Já existe um cadastro com o BL {$bl}. Operação: {$repository[0]->numero}";
          $result['status'] = 'fail';
          return json_encode($result);
        }
      }

  
      $captacao->id_proposta = isset($data['id_proposta']) ? $data['id_proposta'] : null;
      $captacao->id_despachante = isset($data['id_despachante']) ? $data['id_despachante'] : null;
      $captacao->id_porto = isset($data['id_porto']) ? $data['id_porto'] : null;
      $captacao->id_agentedecarga = isset($data['id_agentedecarga']) ? $data['id_agentedecarga'] : null;
      $captacao->id_status = isset($data['id_status']) ? $data['id_status'] : null;
      $captacao->id_terminal_atracacao = isset($data['id_terminal_atracacao']) ? $data['id_terminal_atracacao'] : null;
      $captacao->id_terminal_redestinacao = isset($data['id_terminal_redestinacao']) ? $data['id_terminal_redestinacao'] : null;
      $captacao->id_transportadora = isset($data['id_transportadora']) ? $data['id_transportadora'] : null;
      $captacao->observacoes = isset($data['observacoes']) ? $data['observacoes'] : null;
      $captacao->ref_importador = isset($data['ref_importador']) ? $data['ref_importador'] : null;
      $captacao->nome_navio = isset($data['nome_navio']) ? $data['nome_navio'] : null;
      // $captacao->cntr = isset($data['cntr']) ? $data['cntr'] : null;
      $captacao->bl = $bl;
      $captacao->mbl = isset($data['mbl']) ? $data['mbl'] : null;
      $captacao->cm = isset($data['cm']) ? $data['cm'] : null;
      $captacao->ch = isset($data['ch']) ? $data['ch'] : null;
      $captacao->imo = isset($data['imo']) ? $data['imo'] : null;
      $captacao->id_margem = isset($data['id_margem']) ? $data['id_margem'] : null;
      // echo $data['dta_atracacao'] !== null;exit();
      $captacao->dta_atracacao = isset($data['dta_atracacao']) && $data['dta_atracacao'] !== null ? date('Y-m-d', strtotime($data['dta_atracacao'])) : NULL;
      $captacao->dta_prevista_atracacao = isset($data['dta_prevista_atracacao']) && $data['dta_prevista_atracacao'] !== null ? date('Y-m-d', strtotime($data['dta_prevista_atracacao'])) : NULL;
      // Definindo data de alteracao
      $captacao->updated_at = 'now()';
      $captacao->carga_perigosa = isset($data['carga_perigosa']) ? $data['carga_perigosa'] : '';
      $captacao->anvisa = isset($data['anvisa']) ? $data['anvisa'] : '';
      $captacao->mapa = isset($data['mapa']) ? $data['mapa'] : '';
      if ($data['break_bulk'] === 'sim') {
        $captacao->addBreakBulk($data['break_bulk_info']);
      }
      
      if (isset($data['id_status'])) {
        $status = (new Status($data['id_status'])) ?? null;
        if ($status and $status->status === 'Atracado') {
          $valid = $this->validate($data, $captacao);
          if (!$valid['status']) {
            return json_encode($valid);
          }
        }
      }

      // Criando um registro 
      $reg = new Register;
      $reg->add('id_status', 'status');
      $reg->add('id_despachante', 'despachante_nome');
      $reg->add('id_transportadora', 'transportadora_nome');
      $reg->add('id_terminal_redestinacao', 'terminal_redestinacao_nome');
      $reg->add('id_terminal_atracacao', 'terminal_nome');
      $reg->add('dta_atracacao', 'dta_atracacao');
      
      // Gravando captacao
      $resp_save_captacao = $captacao->store($request, $response, $reg);

      $result['id_captacao'] = $captacao->id ?? $captacao->id_captacao;
      
      if ($this->isUpdate)
        $this->historico($resp_save_captacao, $captacao);


      // Instanciando 
      $captacaoContainer = new CaptacaoContainer;
      $criteria = new Criteria;
      $criteria->add(new Filter('id_captacao', '=', $captacao->id_captacao));

      // Apagando todos os containeres da captacao antes de gravar os novos
      $captacaoContainer->deleteByCriteria($criteria, new Container);

      // Percorrendo os servicos
      if (isset($data['container']['containeres']) and count($data['container']['containeres']) > 0) {
        // Verificando se possui containeres
        $containeres = $data['container']['containeres'];
        if (count($containeres) > 0) {
          foreach ($containeres as $key => $container) {
            $containerObj = new Container;
            if ($container['codigo'] != null && $container['tipo_container'] != null) {
              $containerObj->codigo = $container['codigo'];
              $containerObj->id_containertipo = $container['tipo_container'];
              $captacao->addContainer($containerObj);
            }
          }
        }
      }

      // Apagando todos os documentos da captacao antes de gravar os novos
      $captacaoUpload = new CaptacaoUpload;

      $criteria = new Criteria;
      $criteria->add(new Filter('id_captacao', '=', $captacao->id_captacao));

      $repository = new Repository('App\Model\Captacao\CaptacaoUpload');
      $uploads = $repository->load($criteria);
      if (count($uploads) > 0) {
        foreach ($uploads as $key => $documento) {
          $upload = new Upload($documento->id_upload);
          $upload->validado = 'no';
          $upload->store($request, $response);
        }
      }

      $captacaoUpload->deleteByCriteria($criteria);

      // Verificando se tem documentos anexados
      if (isset($data['documentos'])) {
        $documentos = $data['documentos'];
        foreach ($documentos as $key => $documento) {
          if ($documento['id_tipodocumento'] != null && $documento['id_upload'] != null) {
            // Validando o arquivo para ele não ser despresado, porque a ideia é programar para excluir todos arquivos invalidos rotineiramente
            $upload = new Upload($documento['id_upload']);
            // $upload->id_tipodocumento = $documento['tipo'];
            $upload->validado = 'yes';
            $captacao->addDocumento($upload);
          }
        }
      }


      // Verificando se houve alteracão e salva o historico
      // self::historico($resp_save_liberacao, $captacao);

      self::closeTransaction();
      return json_encode($result);
    } catch (Exception $e) {
      echo $e->getMessage();
    }
  }

  private function validate($data, Captacao $captacao = null)
  {
    $response = ['status' => true, 'message' => 'success'];

    if (
      isset($data['id_proposta']) ? $data['id_proposta'] : null and
      // isset($data['id_despachante'])?$data['id_despachante'] : null and 
      isset($data['id_porto']) ? $data['id_porto'] : null and
      isset($data['id_status']) ? $data['id_status'] : null and
      isset($data['id_terminal_atracacao']) ? $data['id_terminal_atracacao'] : null and
      isset($data['id_terminal_redestinacao']) ? $data['id_terminal_redestinacao'] : null and
      // isset($data['ref_importador']) ? $data['ref_importador'] : null and
      isset($data['nome_navio']) ? $data['nome_navio'] : null and 
      (isset($data['bl']) ? $data['bl'] : null or isset($data['mbl']) ? $data['mbl'] : null) and
      // isset($data['cm']) ? $data['cm'] : null and
      // isset($data['ch']) ? $data['ch'] : null and
      isset($data['dta_atracacao']) ? $data['dta_atracacao'] : NULL and
      isset($data['dta_prevista_atracacao']) ? $data['dta_prevista_atracacao'] : NULL
    ) {
      if (isset($data['container']['containeres']) and count($data['container']['containeres']) == 0) {
        $response['status'] = false;
        $response['message'] = 'captação não pode ser atracada sem contêiner cadastrado';
      }elseif ((!isset($data['documentos']) || count($data['documentos']) === 0)) {
        $response['status'] = false;
        $response['message'] = 'captação não pode ser atracada sem documento anexado';
      }
    } else {
      $response['status'] = false;
      $response['message'] = 'captação não pode ser atracada, faltando o preenchimento de campos necessários';
    }

    if (!$captacao->iserv->isLoaded()) {
      $response['status'] = false;
      $response['message'] = 'necessário anexar o ISERV antes de atracar';
    }

    return $response;
  }

  // private function historico($resp_save, $object)
  // {
  //   // Verificando se houve alteracão ou inclusão
  //   if ($resp_save['occurrences'] !== null) {
  //     foreach ($resp_save['occurrences'] as $key => $occurrence) {
  //       switch ($occurrence['action']) {
  //         case 'updated':
  //           $msg = "Alterado " . ( $occurrence['propertie_comment'] !== '' ? $occurrence['propertie_comment'] : $occurrence['propertie']) . " de " . $occurrence['value_old'] . " para " . $occurrence['value_new'];
  //           break;
  //         case 'added':
  //           $msg = "Inserido " . ( $occurrence['propertie_comment'] !== '' ? $occurrence['propertie_comment'] : $occurrence['propertie'] );
  //           break;

  //         default:
  //           break;
  //       }
  //       $object->addHistorico($msg);
  //     }
  //   }
  // }

  private function checkIfIsUpdateValue(Captacao $captacao, $arrayData = null)
  {
    if (count($arrayData) > 0) {
      foreach ($arrayData as $key => $value) {
        // Verificando se é um array e se não é nulo
        if (!is_array($value) && !is_null($value)) {
          // Verificando se a chave exite no objeto captacao, se não existir, então é atualizacao
          if (isset($captacao->{$key})) {
            if ($captacao->{$key} != $value) {
              if (DateTime::createFromFormat('Y-m-d', $value) !== FALSE) {
                $newValue = date('d/m/Y', strtotime($value));
              } else {
                $newValue = $value;
              }
              if (DateTime::createFromFormat('Y-m-d', $captacao->{$key}) !== FALSE) {
                $oldValue = date('d/m/Y', strtotime($captacao->{$key}));
              } else {
                $oldValue = $captacao->{$key};
              }

              switch ($key) {
                case 'id_proposta':
                  $key = self::cleanName($key);
                  $oldValue = $captacao->proposta->numero;
                  $captacaoTmp = new Captacao;
                  $captacaoTmp->id_proposta = $value;
                  $newValue = $captacaoTmp->proposta->numero;
                  break;

                case 'id_despachante':
                  $key = self::cleanName($key);
                  $oldValue = $captacao->despachante->nome;
                  $captacaoTmp = new Captacao;
                  $captacaoTmp->id_despachante = $value;
                  $newValue = $captacaoTmp->despachante->nome;
                  break;

                case 'id_status':
                  $key = self::cleanName($key);
                  $oldValue = $captacao->status->status;
                  $captacaoTmp = new Captacao;
                  $captacaoTmp->id_status = $value;
                  $newValue = $captacaoTmp->status->status;
                  break;

                case 'id_terminal':
                  $key = self::cleanName($key);;
                  $oldValue = $captacao->terminal->nome;
                  $captacaoTmp = new Captacao;
                  $captacaoTmp->id_terminal = $value;
                  $newValue = $captacaoTmp->terminal->nome;
                  break;

                case 'id_porto':
                  $key = self::cleanName($key);
                  $oldValue = $captacao->porto->nome;
                  $captacaoTmp = new Captacao;
                  $captacaoTmp->id_porto = $value;
                  $newVsalue = $captacaoTmp->porto->nome;
                  break;

                default:
                  $key = self::cleanName($key);
                  break;
              }
              self::saveOcorrencia("alterado $key de " . $oldValue .  " para $newValue", $captacao);
            }
          } else {
            $nameClean = self::cleanName($key);
            self::saveOcorrencia("inserido $nameClean", $captacao);
          }
        }
      }
    }
  }

  private function saveOcorrencia($ocorrencia, Captacao $captacao)
  {
    return $captacao->addHistorico($ocorrencia);
  }

  private function cleanName($value = null)
  {
    switch ($value) {
      case 'dta_atracacao':
        $value = 'DTA Atracação';
        break;

      default:
        break;
    }
    if (preg_match('/id_/', $value)) {
      $value = preg_replace('/id_/', '', $value);
    }

    if (preg_match('/_/', $value)) {
      $value = preg_replace('/_/', ' ', $value);
    }
    return $value;
  }
}
