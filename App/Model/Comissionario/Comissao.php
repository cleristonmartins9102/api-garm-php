<?php

namespace App\Model\Comissionario;

use App\Lib\Database\Record;

use App\Model\Fatura\Fatura;
use App\Model\Captacao\Captacao;
use App\Model\Despacho\Despacho;
use App\Model\Pessoa\Individuo;

class Comissao 
{
    private $total = 0;
    private $comissao_vendedor = 0;
    private $comissao_despachante = 0;
    public $ativar_despachante;
    public $ativar_vendedor = true;


    public function byFatura($fatura) {
        if ($fatura->processo->movimentacao instanceof Captacao or $fatura->processo->movimentacao instanceof Despacho) {
            $pessoa = $fatura->processo->movimentacao->proposta->vendedor->pessoa;
            $despachante = $fatura->processo->movimentacao->despachante;
            if ($this->ativar_vendedor) 
                $this->calcVendedor($pessoa, $fatura);
            if ($this->ativar_despachante)
                $this->calcDespachante($despachante, $fatura);
        }
    }

    public function get_valor_total() {
        return $this->total;
    }

    public function get_valor_vendedor() {
        return $this->comissao_vendedor;
    }
    
    public function get_valor_despachante() {
        return $this->comissao_despachante;
    }

    private function calcVendedor(Individuo $vendedor, $fatura) 
    {   
        if (!$vendedor->isLoaded()) {
            $this->total = 0;
            return;
        }

        $comissionario = new Comissionario;
        $comissionario('id_comissionado', $vendedor->id_individuo);
        if (isset($comissionario->id_comissionario) && !is_null($comissionario->id_comissionario)) {
            if ($comissionario->unicob_unidade === 'porcentagem') {
                $valor = round((($fatura->valor * $comissionario->valor_comissao) / 100), 2);
                $this->comissao_vendedor = [ 
                    'valor' => $valor,
                    'percentual' => $comissionario->valor_comissao
                ];
            } else {
                $valor = $comissionario->valor_comissao;
                $this->comissao_vendedor = [ 
                    'valor' => $valor,
                    'moeda' => $comissionario->valor_comissao
                ];
            }  
            $this->total += $valor;
        }
    }

    private function calcDespachante(Individuo $despachante, $fatura) 
    {
        if (!$despachante->isLoaded()) {
            $this->total = 0;
            return;
        }

        $comissionario = new Comissionario;
        $comissionario('id_comissionado', $despachante->id_individuo);
        if (isset($comissionario->id_comissionario) && !is_null($comissionario->id_comissionario)) {
            if ($comissionario->unicob_unidade === 'porcentagem') {
                $valor = round((($fatura->valor * $comissionario->valor_comissao) / 100), 2);
                $this->comissao_despachante = [ 
                    'valor' => $valor,
                    'percentual' => $comissionario->valor_comissao
                ];
            } else {
                $valor = $comissionario->valor_comissao;
                $this->comissao_despachante = [ 
                    'valor' => $valor,
                    'moeda' => $comissionario->valor_comissao
                ];
            }
            $this->total += $valor;
        }
    }
 }
