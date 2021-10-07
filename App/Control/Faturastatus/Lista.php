<?php
namespace App\Control\Faturastatus;

use App\Mvc\Controller;
use App\Model\Fatura\FaturaStatus;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function alldropdown(Request $request, Response $response, Array $param=null)
    {
      try {
        self::openTransaction();
        $object = (new FaturaStatus())->all();
        foreach ($object as $idx => &$fatura_status) {
          $faturaArr = $fatura_status->getData();
          $dataFull[] = $faturaArr;
        }
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return isset($dataFull) ? $dataFull : null;
    }
}