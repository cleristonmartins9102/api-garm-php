<?php
namespace App\Control\Pacote;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\Pacote;
use App\Model\Servico\PacotePredicado;
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

    try{
        self::openTransaction();
        $result = array();
        $result['message'] = null;
        $result['status'] = 'success'; 
        $pacote = new Pacote($data['id_pacote']);
        $pacote->id_predicado = $data['id_predicado'];
        // Salvando pacote
        $pacote->store();
        $pacote->deleteItem();
        if (count($data['predicados']) > 0) {
          foreach ($data['predicados'] as $key => $predicado) { 
            $pacote_predicado = new PacotePredicado;
            $pacote_predicado->id_predicado = $predicado['id_predicado'];  
            $pacote_predicado->nome = $predicado['item']; 
            if (!$pacote->addItem($pacote_predicado)) {
              $result['message'] = 'fail in the write item  ';
              $result['status'] = 'fail';
            }; 
          }
        }
        self::closeTransaction();
        return json_encode($result);
      }
      catch (Exception $e)
      {
        echo $e->getMessage();
      }
  } 
}
