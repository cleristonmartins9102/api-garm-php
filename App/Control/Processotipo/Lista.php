<?php
namespace App\Control\Processotipo;

use App\Mvc\Controller;
use App\Model\Processo\ProcessoTipo;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response, Array $param=null)
    {
      try {
        self::openTransaction();
        $object = (new ProcessoTipo())->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        foreach ($object as $idx => &$processo_status) {
          $processoArr = $processo_status->getData();
          $dataFull['items'][] = $processoArr;
        }
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return json_encode(isset($dataFull) ? $dataFull : null);
    }
    public function alldropdown(Request $request, Response $response, Array $param=null)
    {
      try {
        self::openTransaction();
        $object = (new ProcessoTipo())->all();
        foreach ($object as $idx => &$processo_tipo) {
          $dataFull[] = $processo_tipo->getData();
        }
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return isset($dataFull) ? ($dataFull) : null;
    }
}