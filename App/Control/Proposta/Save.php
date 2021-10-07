<?php

namespace App\Control\Proposta;

use App\Mvc\Controller;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Documento\UploadDocumento;
use App\Model\Documento\Upload;
use App\Model\Proposta\PropostaPredicado;
use App\Model\Proposta\Proposta;
use App\Model\Pessoa\Cidade;
use App\UserCase\Proposta\PropostaTerminais\Add;
use App\UserCase\Proposta\PropostaTerminais\DeletePropTerminais;
use Slim\Http\Response;
use Slim\Http\Request;


class Save extends Controller
{

  public function store(Request $request, Response $response, array $data)
  {
    if (is_null($data))
      return [];
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';

    try {
      self::openTransaction();
      // Verifica se a proposta possue numero, se possui então será uma atualizacao
      if (isset($data['id_proposta']) && $data['id_proposta'] != null) {
        $proposta = new Proposta($data['id_proposta']);
      } else {
        //Proposta Nova
        $proposta = new Proposta;
        $numProposta = $proposta->getLastNum() + 1;
        $proposta->num = $proposta->getLastNum() + 1;
        $date = date('Y');
        $proposta->numero = "$numProposta/$date";
      }
      $proposta->request = $request;
      $proposta->response = $response;
      $proposta->id_coadjuvante = $data['coadjuvante'];
      $proposta->id_contato = isset($data['contato']) ? $data['contato'] : null;
      $proposta->id_regime = $data['id_regime'];
      $proposta->id_regimeclassificacao = isset($data['id_regimeclassificacao']) ? $data['id_regimeclassificacao'] : null;
      $proposta->tipo = $data['tipo'];
      $proposta->id_cliente = $data['cliente'];
      $proposta->id_qualificacao = $data['qualificacao'];
      $proposta->id_vendedor = $data['vendedor'];
      $proposta->dta_emissao = date('Y-m-d', strtotime($data['emissao']));
      $proposta->dta_validade = date('Y-m-d', strtotime($data['validade']));
      $proposta->status = $data['status'];
      $proposta->dta_aceite = date('Y-m-d', strtotime($data['data_aceite']));
      $proposta->prazo_pagamento = $data['prazo_pagamento'];
      $proposta->classificacao = $data['classificacao'];

      // Definindo data de alteracao
      $proposta->updated_at = 'now()';
      // Definindo documentos
      $uploadDocumento = new UploadDocumento;
      $criteria = new Criteria;
      $repository = new Repository('App\Model\Documento\TipoDocumento');

      // Inserindo os ids dos documentos
      if (isset($data['id_aceite']) and $data['id_aceite'] != null) {
        $proposta->id_aceite = $data['id_aceite'];
        $criteria->add(new Filter('nome', '=', 'aceite proposta'));
        $aceite = $repository->load($criteria);
        // $uploadDocumento->id_tipodocumento = $aceite[0]->id_tipodocumento;
        $uploadDocumento->id_upload = $data['id_aceite'];
        $uploadDocumento->store(); // Gravando na tabela

        // Validando o arquivo para ele não ser despressado, porque a ideia é programar para excluir todos arquivos invalidos rotineiramente
        $upload = new Upload($data['id_aceite']);
        $upload->validado = 'yes';
        $upload->store();
      } else if (isset($data['id_doc_proposta']) and $data['id_doc_proposta'] != null) {
        $proposta->id_doc_proposta = $data['id_doc_proposta'];
        $criteria->add(new Filter('nome', '=', 'proposta'));
        $aceite = $repository->load($criteria);
        // $uploadDocumento->id_tipodocumento = $aceite[0]->id_tipodocumento;
        $uploadDocumento->id_upload = $data['id_doc_proposta'];
        $uploadDocumento->store(); // Gravando na tabela    

        // Validando o arquivo para ele não ser despressado, porque a ideia é programar para excluir todos arquivos invalidos rotineiramente
        $upload = new Upload($data['id_doc_proposta']);
        $upload->validado = 'yes';
        $upload->store();
      }

      // print_r($proposta);exit();
      // Gravando proposta
      $proposta->store();


      $deleteTerminais = new DeletePropTerminais();
      $deleteTerminais->del($proposta->id ?? $proposta->id_proposta);
      if (isset($data['terminal']) and count($data['terminal']) > 0) {
        foreach($data['terminal'] as $terminal) {
          if ($terminal) {
            $addPropTerminal = (new Add())->add($proposta->id ?? $proposta->id_proposta, $terminal);
          }
        }
      }


      // Percorrendo os servicos
      if (isset($data['servicos']) and count($data['servicos']) > 0) {

        // Percorrendo os predicados para adicionar
        $criteria = new Criteria;
        foreach ($data['servicos'] as $key => $value) {
          // Instanciando PropostaPredicado
          $predicado = new PropostaPredicado($value['id_propostapredicado'] ?? null);
          $predicado->id_proposta = $proposta->id;
          $predicado->id_predicado = $value['nome'];
          $predicado->id_cliente = $data['cliente'];
          $predicado->id_vendedor = $data['vendedor'];
          $predicado->id_depot = $value['id_depot'] ?? null;
          $predicado->id_margem = $value['id_margem'];
          $predicado->descricao = $value['descricao'];
          $predicado->franquia_periodo = $value['franquia_periodo'];
          $predicado->valor_minimo = $value['valor_minimo'] ?? null;
          $predicado->valor_maximo = $value['valor_maximo'];
          $predicado->valor_partir = $value['valor_partir'];
          $predicado->dimensao = $value['dimensao'];
          $predicado->unidade = $value['unidade'];
          $predicado->valor = $value['valor'];
          $predicado->id_cidade = $value['id_cidade'] ?? null;
          $predicado->id_predproappvalor = $value['aplicacao_valor'];

          if (!is_null($cidades = $value['id_cidade']) && count($value['id_cidade']) > 0) {
            $cid = [];
            foreach ($cidades as $cidade) {
              $cid[] = new Cidade($cidade);
            }

            $predicado->addCidade($cid);
          }

          $proposta->addPredProposta($predicado);

          $criteria->add(new Filter('id_proposta', '=', $proposta->id_proposta));
          $criteria->add(new Filter('id_propostapredicado', '<>', $proposta->predicado->id ?? $predicado->id_propostapredicado));
        }

        // Apagando todos os predicados da proposta antes de gravar os novos
        (new PropostaPredicado)->deleteByCriteria($criteria);
      }

      self::closeTransaction();
      return json_encode($result);
    } catch (\Exception $e) {
      echo $e->getMessage();
    }
  }
}
