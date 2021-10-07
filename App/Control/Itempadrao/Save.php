<?php
namespace App\Control\Itempadrao;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\Predicado;
use App\Model\Servico\ServicoPredicado;
use App\Model\Servico\ItemPadraoR;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller
{
    
  public function store(Request $request, Response $response, Array $data)
  { 
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    $data = (object) $data;
    //Verificando se os dados possuem todas as colunas necessarias para o banco
    try{
        self::openTransaction();
        $item_padrao = new ItemPadraoR($data->id_itempadrao ?? null);
        $item_padrao->id_predicado = $data->id_predicado;
        $item_padrao->id_predproappvalor = $data->id_predproappvalor;
        $item_padrao->id_itemclassificacao = $data->id_itemclassificacao;
        $item_padrao->id_modulo = $data->id_modulo;
        $item_padrao->id_unicob = $data->id_unicob;
        $item_padrao->valor = $data->valor;
        $item_padrao->prioridade = $data->prioridade;
        $item_padrao->store();
        self::closeTransaction();
        return json_encode($result);
      }
      catch (Exception $e)
      {
        echo $e->getMessage();
      }
  } 
}
