<?php
namespace App\Control\Cext;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Terminal\Terminal;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Model\Cext\Cext;
use App\Model\Cext\CextTipo;

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
        $cext = new Cext();
        $cext->request = $request;
        $cext->response = $response;
        $cext_tipo = new CextTipo($data->id_cexttipo);
        $cext->id_enquadramento = $data->id_cextenquadramento;
        $cext->id_classificacao = $data->id_cextclassificacao;
        $cext->id_enquadramento = $data->id_cextenquadramento;
        $cext->id_tipo = $data->id_cexttipo;
        $cext->valor = $data->valor;

        if ($cext_tipo->tipo === 'imposto') {
          $cext->id_imposto = $data->id_custo;
        } else {

        }

        $cext->store();
        self::closeTransaction();
        return json_encode($result);
      }
      catch (Exception $e)
      {
        echo $e->getMessage();
      }
  } 
}
