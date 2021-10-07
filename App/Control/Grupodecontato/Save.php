<?php
namespace App\Control\Grupodecontato;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Pessoa\GrupoDeContatoNome;
use App\Model\Pessoa\GrupoDeContato;
use App\Model\Pessoa\GrupoContato;
use App\Model\Pessoa\Contato;
use App\Model\Pessoa\Individuo;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller
{

  public function store(Request $request, Response $response, array $data)
  {

    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    if (is_null($data)) {
      $result['message'] = 'Sem dados';
      $result['status'] = 'fails';
      return json_encode($result);
    }

    self::openTransaction();
    $grupo_contato = new GrupoContato;
    if (isset($data['id_grupo']) && $data['id_grupo'] != null) {
      $grupo_de_contato = new GrupoDeContato($data['id_grupo']);
    } else {
      $grupo_de_contato = new GrupoDeContato;
    }
    // Verifica se existe um grupo de contato e exclui as relacoes dele
    if (isset($grupo_de_contato->id_grupodecontato)) {
      $criteria = new Criteria;
      $criteria->add(new Filter('id_grupodecontato', '=', $grupo_de_contato->id_grupodecontato));
      $grupo_contato->deleteByCriteria($criteria);
    }

    if (isset($grupo_de_contato)) {
      $grupo_de_contato->id_coadjuvante = $data['coadjuvante'];
      $grupo_de_contato->id_adstrito = $data['adstrito'];
      $grupo_de_contato->id_nome = isset($data['nome_grupo']) ? $data['nome_grupo'] : null;
      $grupo_de_contato->updated_at = 'now()';
      $grupo_de_contato->store();
    }

    if (isset($data['contatos'])) {
      $contatos = $data['contatos'];
      if (count($contatos) > 0) {
        foreach ($contatos as $key => $contato) {
          $grupo_contato->id_contato = $contato['id_contato'];
          $grupo_contato->id_grupodecontato = $grupo_de_contato->id;
          $grupo_de_contato->addContato($grupo_contato);
        }
      }
    }
    self::closeTransaction();
    return json_encode($result);
  }
}
