<?php

namespace App\Model\Captacao;

use App\Model\Captacao\Captacao;

trait SubjectEmail {    
    /**
     * Metodo para gerar o subject do email
     * @param Captacao $captacao
     * @return string
     */
    public function subSolicitarBl(Captacao $captacao): string {
        $text = 'Solicitação de BL';
        return "{$text} - Ref:{$captacao->ref_importador} - Importador:{$captacao->proposta->cliente->nome}";
    }

    /**
     * Metodo para gerar o subject do email - Solicitar BL
     * @param Captacao $captacao
     * @return string
     */
    public function subSolicitarCE(Captacao $captacao): string {
        $text = 'Solicitação de CE Master';
        return "{$text} - Ref:{$captacao->ref_importador} - Importador:{$captacao->proposta->cliente->nome}";
    }

    /**
     * Metodo para gerar o subject do email - Solicitar BL
     * @param Captacao $captacao
     * @return string
     */
    public function subConfCliente(Captacao $captacao): string {
        $text = 'Cadastro Solicitado ao Terminal';
        return "{$text} - Ref:{$captacao->ref_importador} - Importador:{$captacao->proposta->cliente->nome}";
    }

    /**
     * Metodo para gerar o subject do email - Solicitar BL
     * @param Captacao $captacao
     * @return string
     */
    public function subConfRecBL(Captacao $captacao): string {
        $text = 'Confirmação de Recebimento';
        return "{$text} - Ref:{$captacao->ref_importador} - Importador:{$captacao->proposta->cliente->nome} - BL:{$captacao->bl}";
    }
    
    /**
     * Metodo para gerar o subject do email - Solicitar BL
     * @param Captacao $captacao
     * @return string
     */
    public function subAltDtaAtracacao(Captacao $captacao): string {
        $text = 'Alterado data de atracação';
        return "{$text} - Ref:{$captacao->ref_importador} - Importador:{$captacao->proposta->cliente->nome} - BL:{$captacao->bl}";
    }

    /**
     * Metodo para gerar o subject do email - Confirmação de atracação
     * @param Captacao $captacao
     * @return string
     */
    public function subConfAtracacao(Captacao $captacao): string {
        $text = 'Confirmação de atracação';
        return "{$text} - Ref:{$captacao->ref_importador} - Importador:{$captacao->proposta->cliente->nome} - BL:{$captacao->bl}";
    }

    /**
     * Metodo para gerar o subject do email - Presença de Carga
     * @param Captacao $captacao
     * @return string
     */
    public function subPresencaDeCarga(Captacao $captacao): string {
        $text = 'Presença de Carga';
        return "{$text} - Ref:{$captacao->ref_importador} - Importador:{$captacao->proposta->cliente->nome} - BL:{$captacao->bl}";
    }
}