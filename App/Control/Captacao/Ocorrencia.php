<?php
namespace App\Control\Captacao;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Model\Captacao\CaptacaoHistorico;
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
    $historico = new CaptacaoHistorico;
    $historico->request = $request;
    $historico->response = $response;
    $historico->id_captacao = $form['id_captacao'];
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