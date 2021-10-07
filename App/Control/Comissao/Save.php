<?php
namespace App\Control\Comissionario;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Terminal\Terminal;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Model\Comissionario\Comissionario;
use App\Model\Comissionario\ComissionarioTipo;

class Save extends Controller
{
    
  public function store(Request $request, Response $response, Array $data)
  { 
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    $data = (object) $data;
      try{
        self::openTransaction();
        $comissionario = new Comissionario($data->id_comissionario ?? null);
        $comissionario->id_comissionariotipo = $data->id_comissionariotipo;
        $comissionario->id_comissionariostatus = $data->id_comissionariostatus;
        $comissionario->id_comissionado = $data->id_comissionado;
        $comissionario->id_unicob = $data->id_unicob;
        $comissionario->id_clientefatura = $data->id_clientefatura;
        $comissionario->valor_comissao = $data->valor_comissao;
        $comissionario->store();
        self::closeTransaction();
        return json_encode($result);
      }
      catch (Exception $e)
      {
        echo $e->getMessage();
      }
  } 
}
