<?php

namespace App\Model\Fatura;

trait SubjectEmail {    
    /**
     * Metodo para gerar o subject do email
     * @param Fatura $fatura
     * @return string
     */
    public function subEnvFatura(Fatura $fatura): string {
        $text = 'Fatura Company_Name';
        $date = date('d/m/Y', strtotime($fatura->dta_vencimento));
        return strtoupper("{$text} - Ref:{$fatura->numero} - Importador:{$fatura->captacao->proposta->cliente->nome} - Vencimento:{$date} - Ref-cliente:{$fatura->captacao->ref_importador} - {$fatura->captacao->liberacao->tipo_documento}:{$fatura->captacao->liberacao->documento}");
    }
}