<?php
namespace App\Control\Faturamodelo;

use App\Mvc\Controller;
use App\Model\Fatura\FaturaModelo;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function alldropdown(Request $request, Response $response, Array $param=null)
    {
      try {
        self::openTransaction();
        $object = (new FaturaModelo())->all();
        foreach ($object as $idx => &$fatura_modelo) {
          $faturaArr = $fatura_modelo->getData();
          $dataFull[] = $faturaArr;
        }
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return isset($dataFull) ? $dataFull : null;
    }
}
