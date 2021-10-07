<?php
namespace App\Control\Faturatotal;

use App\Mvc\Controller;
use App\Model\Fatura\FaturaModelo;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Model\Fatura\Fatura;

class Lista extends Controller
{
    public function all(Request $request, Response $response, Array $param=null)
    {
      try {
        self::openTransaction();
        $object = (new Fatura())->all();
        foreach ($object as $idx => &$fatura) {
          $movimentacao = $fatura->processo->movimentacao;
          if (isset($movimentacao->id_captacao)) {
            $fatura->documento = $movimentacao->liberacao->documento . ' (DI/DTA)' ;
          } else {

          }
          // echo '<pre>';
          // print_r($fatura->processo->movimentacao);exit();
          $fatura->margem_lucro = round((($fatura->valor - $fatura->valor_custo) / $fatura->valor) * 100, 2);
          $fatura->itens = $fatura->itens;
          $dataFull[] = $fatura->toArray();
        }
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return isset($dataFull) ? $dataFull : null;
    }
}
