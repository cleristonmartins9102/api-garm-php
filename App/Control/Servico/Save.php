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

        $criteria = new Criteria;
        $criteria->add(new Filter('id_servico', '=', $servico->id_servico ?? $servico->id));
        foreach ($data['predicados'] as $key => $predicado) {
          // if (isset($predicado['id_predicado']) and !is_null($predicado['id_predicado'])) {
            $predicadoObj = new Predicado($predicado['id_predicado'] ?? null);
            $predicadoObj->nome = $predicado['predicado'];    
            $predicadoObj->descricao = $predicado['descricao'];  
            $predicadoObj->id_regime = $predicado['id_regime'];
            $servico->addPredicado($predicadoObj);
            $id_predicado = $predicadoObj->id ?? $predicadoObj->id_predicado;
            $criteria->add(new Filter('id_predicado', '<>', $id_predicado));
        }

        $predicado = new Predicado;
        $predicado->deleteByCriteria($criteria);
        self::closeTransaction();
        return json_encode($result);
      }

      catch (Exception $e)
      {
        echo $e->getMessage();
      }
  } 
}
