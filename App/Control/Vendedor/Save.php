<?php
namespace App\Control\Vendedor;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Vendedor\Vendedor;
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
    $vendedor = new Vendedor($data['id_vendedor']);
    // $vendedor->nome = $data['nome'];
    $vendedor->id_individuo = $data['id_individuo'];
    $vendedor->id_vendedorstatus = $data['id_vendedorstatus'];
    $vendedor->apelido = $data['apelido'];
    $vendedor->email = $data['email'];
    $vendedor->store();
    self::closeTransaction();
    return json_encode($result);
  }
}
