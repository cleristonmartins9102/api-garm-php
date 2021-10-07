<?php
namespace App\Control\Fatura;

use App\Model\Processo\ProcessoPredicado;
use App\Model\Processo\Processo;
use App\Model\Fatura\Fatura;
use App\Model\Fatura\Calculo\CalcAppValor;
use \DateTime;

use App\Mvc\Controller;

class Calc extends Controller
{   
    use CalcAppValor;

    public function item($data)
    {
        if (!isset($data->id_processopredicado) && !isset($data->valor) && !isset($data->qtd) && !isset($data->periodo))
            return 'Faltando campo necessário!';

        self::openTransaction();
        $processo_predicado = new ProcessoPredicado($data->id_processopredicado);
        $processo = new Processo($processo_predicado->id_processo);
        $proposta_predicado = $processo->movimentacao->proposta->servicoById($processo_predicado->id_predicado, $processo_predicado->dimensao);
        if (count($proposta_predicado) === 0)
            return 'Serviço não encontrado na proposta';
        
        $valor_mercadoria = $processo->valor_mercadoria;


        $valor = CalcAppValor::calcValor($proposta_predicado[0], $valor_mercadoria, $data->qtd, $data->periodo, null, $processo->dias_consumo, $data->valor);
        return $valor;
        self::closeTransaction();
        return $dataFull ?? null;
    }

    public function total($data) {
        if (isset($data->valorTotal) && isset($data->valorCusto) && isset($data->id_fatura)) {
            self::openTransaction();
            $data->valorTotal = round($data->valorTotal, 2);
            $data->valorCusto = round($data->valorCusto, 2);
            $fatura = new Fatura($data->id_fatura);
            $fatura->valor = $data->valorTotal;
            $fatura->valor_custo = $data->valorCusto;

            return json_encode([ 
                'margem_lucro' => $fatura->margem_lucro,
                'valor_lucro' => $fatura->valor_lucro,
                'valor_custo' => $fatura->imposto_interno_valor 
            ]);

            self::closeTransaction();
        }
    }
}

