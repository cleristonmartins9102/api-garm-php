<?php
namespace App\Control\Servico;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\Predicado;
use App\Model\Servico\ServicoPredicado;
use App\Model\Servico\Servico;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller
{
    
  public function store(Request $request, Response $response, Array $data)
  { 
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    $data = self::prepareBeforeSave($data);
    //Verificando se os dados possuem todas as colunas necessarias para o banco
    try{
        self::openTransaction();
        $result = array();
        $result['message'] = null;
        $result['status'] = 'success';
        $id_servico = $data['id_servico'] ?? null;
        if ($id_servico) {
          $servico = new Servico($id_servico);
        } else {
          $servico = new Servico;
        }
        $servico->nome = $data['nome'] ?? null;
        $servico->store();
                
        $predicados_antigos = [];
        $predicados_novos = [];
        foreach ($data['predicados'] as $key => $predicado) {
          if ($predicado['id_predicado']) {
            $predicados_antigos[] = $predicado;
          } else {
            $predicados_novos[] = $predicado;
          }
        }

        $servico->deletePredicado();
        
        foreach ($data['predicados'] as $key => $predicado) {
          $in_use = $predicado['in_use'] ?? null;
          if (!$in_use) {
            $predicadoObj = new Predicado();
            $predicadoObj->nome = $predicado['predicado'];    
            $predicadoObj->descricao = $predicado['descricao'];  
            $predicadoObj->regime = $predicado['regime'];
            $servico->addPredicado($predicadoObj);
          }
        }

        // foreach ($data['predicados'] as $key => $predicado) {
        //   $id_predicado = $predicado['id_predicado'] ?? null;
        //   $id_predicado = $predicado['id_predicado'] ?? null;
        //   if (is_numeric($id_predicado)) {
        //     echo $id_predicado . '<p>';
        //     $predicadoObj = new Predicado(87);
        //   } else {
        //     echo 'Sem' . '<p>';
        //     $predicadoObj = new Predicado();
        //   }

        //   // $predicadoObj->nome = $predicado['predicado'];    
        //   // $predicadoObj->descricao = $predicado['descricao'];  
        //   // $predicadoObj->regime = $predicado['regime'];
        //   // $servico->addPredicado($predicadoObj);
        // }
        // exit();
        self::closeTransaction();
        return json_encode($result);
      }
      catch (Exception $e)
      {
        echo $e->getMessage();
      }
  } 
}
