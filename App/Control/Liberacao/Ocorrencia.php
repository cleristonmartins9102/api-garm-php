<?php
namespace App\Control\Liberacao;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Model\Liberacao\LiberacaoHistorico;
use Slim\Http\Response;
use Slim\Http\Request;

class Ocorrencia extends Controller
{
  public function save(Request $request, Response $response, $form=null)
  {
    $result = array();
    $result['message'] = null;
    $result['status'] = 'success';
    self::openTransaction();
    $historico = new LiberacaoHistorico;
    $historico->request = $request;
    $historico->response = $response;
    $historico->id_liberacao = $form['id_liberacao'];
    $historico->ocorrencia = $form['ocorrencia'];
    $historico->tipo = 'ocacional';
    $response = $historico->store();
    self::closeTransaction();
    if (!$response) {
        $result['status'] = 'fail'; 
        $result['message'] = 'fail in the record write';
    }
    return json_encode($result);
  }
}