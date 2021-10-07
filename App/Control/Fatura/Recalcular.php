<?php
namespace App\Control\Fatura;

use App\Model\Processo\ProcessoPredicado;
use App\Model\Processo\Processo;
use App\Model\Fatura\Fatura;
use App\Model\Fatura\Calculo\CalcAppValor;
use \DateTime;
use Slim\Http\Response;
use Slim\Http\Request;

use App\Mvc\Controller;

class Recalcular extends Controller
{   
    use CalcAppValor;

    public function total(Request $request, Response $response, $data)
    {   
        $data = (object) $data;
        self::openTransaction();
        $fatura = new Fatura($data->id_fatura);
        // print_r($fatura);exit();
        $fatura->recalcular();
        self::closeTransaction();
        return $dataFull ?? null;
    }
}

