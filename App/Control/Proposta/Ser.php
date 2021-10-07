<?php

namespace App\Control\Proposta;

use App\Mvc\Controller;
use Slim\Http\Request;
use Slim\Http\Response;

use App\Model\Processo\Processo;
use App\Model\Processo\ProcessoPredicado;


class Ser extends Controller
{
    public function periodo(Request $request, Response $response, $data) {
        $data = (object) $data;
        if (!is_numeric($data->id_processo) and !is_numeric($data->id_predicado))
            throw new \Exception("Valor não é númerico", 1);

        self::openTransaction();
        $processo = (new Processo($data->id_processo));
        $franquia_periodo = $processo->proposta->servico_periodo($data->id_predicado, $data->dimensao);
        $dta_inicio = new \DateTime($data->dta_entrada);
        $dta_final = new \DateTime($data->dta_final);
    
        // Resgata diferença entre as datas
        $dias_consumo = $dta_inicio->diff($dta_final)->days;


        $dias_consumo += 1;


        if (isset($franquia_periodo['valor'])) {
            if ((is_numeric($franquia_periodo['valor']) and ($dias_consumo > $franquia_periodo['valor']) and (($dias_consumo / $franquia_periodo['valor'])) > 1)) {
                $periodo =  [ 'valor' => ( ceil($dias_consumo / $franquia_periodo['valor'])) ]; 
            } else {
                if ($dias_consumo <= $franquia_periodo['valor']) {
                    $franquia_periodo['valor'] = 1;
                }
                $periodo = $franquia_periodo;
            }    
        }
            
 
        self::closeTransaction();
        return $periodo;
    }
}
