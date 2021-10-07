<?php

namespace App\Model\Captacao;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;

class VwCaptacaoLote extends Record
{
    const TABLENAME = 'VwCaptacaoLote';
    
    /**
     * Metodo para verificar se as liberações do lote estão com processo diferente de Aguardando DI/DTA
     */
    public function canItProcess() {
        $captacoes = self::listaCaptacao();
        print_r($captacoes);
        exit();
    }


    /**
     * Metodo para pegar as captações do lotel
     */
    public function get_listaCaptacao() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_captacaolote', '=', $this->id ?? $this->id_captacaolote));
        $repository = (new Repository(CaptacaoLoteCaptacao::class))->load($criteria);
        // foreach($repository as $captacao) {
        //     $captacoes[] = $captacao->toArray();
        // }
        return $repository;
    }

    /**
     * Metodo que válida se o lote pode ser processado
     */
    public function validate() {
        $this->validate = [
            'valid' => true,
            'value' => null
        ];

        // $captacoes = explode('<br>', $this->captacao);
        foreach ($this->listaCaptacao as $captacao) {
            if (is_array($captacao->captacao->liberacao)) {
                $vl = $this->validate;
                $vl['valid'] = false;
                $vl['value'] .= " ( Não é possivel gerar o processo: Captação $captacao->id_captacao não possue Liberação gerada! )";
                $this->validate = $vl;
            }
        }
        

        // Verificação por terminal de redestinação
        if (!is_null($this->terminal)) {
            $terminal = explode(',', $this->terminal);
            // exit();
            // Verificando se possue mais de um terminal nas captações do lote
            if (count($terminal) === count(array_unique($terminal))) {
                $vl = $this->validate;
                $vl['valid'] = false;
                $vl['value'] .= ' ( Não é possivel gerar o processo: Captações do lote com terminais de redestinação diferente! )';
                $this->validate = $vl;
            }
            $this->terminal = implode('<br>', array_unique(explode(',', $this->terminal)));
            $this->terminal = $this->terminal . " (Redestinacao)";
        }

        // Verificação por status
        if (!is_null($this->status)) {
            $status = explode(',', $this->status);
            // exit();
            // Verificando se possue mais de um status nas captações do lote
            if (count($status) === count(array_unique($status))) {
                $vl = $this->validate;
                $vl['valid'] = false;
                $vl['value'] .= ' ( Não é possivel gerar o processo: Liberação da captação do lote com status Aguardando DI/DTA! )';
                $this->validate = $vl;
            }
            $this->status = implode('<br>', array_unique(explode(',', $this->status)));
        }

        $dta_atracacao = explode(',', $this->dta_atracacao);
        // Verificação por dta_atracacao
        if (!is_null($this->dta_atracacao)) {
            // Verificando se possue mais de um dta_atracacao nas captações do lote
            if (count($dta_atracacao) === count(array_unique($dta_atracacao))) {
                $vl = $this->validate;
                $vl['valid'] = false;
                $vl['value'] .= '( Não é possivel gerar o processo: Captações do lote com data de atracação diferente! )';
                $this->validate = $vl;
            }
            // $this->dta_atracacao = implode('<br>', array_unique(explode(',', $this->dta_atracacao)));
        }
        $this->dta_atracacao = $dta_atracacao[0];

        if (count($lotes = $this->listaCaptacao) > 0 ) {
            foreach ($lotes as $captacao) {
                if (is_object($captacao->captacao->liberacao) and is_null($captacao->captacao->liberacao->valor_mercadoria)) {
                    // echo 111;
                    // exit();
                    $liberacao = $captacao->captacao->liberacao;
                    // print_r($liberacao);
                    $vl = $this->validate;
                    $vl['valid'] = false;
                    $vl['value'] .= "( Não é possivel gerar o processo: Liberação {$captacao->captacao->numero} não foi preenchido o valor da mercadoria! )";
                    $this->validate = $vl;
                }
            }
        }
    }
}
