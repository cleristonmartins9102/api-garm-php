<?php
namespace App\Control\Imposto;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Terminal\Terminal;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Model\Imposto\Imposto;

class Save extends Controller
{
    
  public function store(Request $request, Response $response, Array $data)
  { 
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
      try{
        self::openTransaction();
        $data = (object) $data;
        $imposto = new Imposto();
        $imposto->request = $request;
        $imposto->response = $response;
        $imposto->nome = $data->nome;
        
        $imposto->store();
        self::closeTransaction();
        return json_encode($result);
      }
      catch (Exception $e)
      {
        echo $e->getMessage();
      }
  } 
}
