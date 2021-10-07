<?php
namespace App\Control\Predicado;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\Predicado;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller
{  
  public function store(Request $request, Response $response, Array $data)
  {
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    self::openTransaction();
    if (isset($data['id_predicado'])) {
      $predicado = new Predicado($data['id_predicado']);
    } else {
      $predicado = new Predicado;
    }
    $predicado->nome = $data['predicado'] ?? null;
    $predicado->id_regime = $data['id_regime'] ?? null;
    $predicado->descricao = $data['descricao'] ?? null;
    $predicado->id_servico = $data['id_servico'] ?? null;
    $predicado->store();
    self::closeTransaction();
    return json_encode($result);
  }
}
