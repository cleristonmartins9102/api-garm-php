<?php

namespace App\Model\Liberacao;

trait SubjectEmail {    
    /**
     * Metodo para gerar o subject do email
     * @param Liberacao $liberacao
     * @return string
     */
    public function subSolDiDta(Liberacao $liberacao): string {
        $text = 'Solicitação de DI/DTA';
        $ref = !is_null($liberacao->captacao->ref_importador) ? "- Ref:{$liberacao->captacao->ref_importador}" : null;
        $bl = !is_null($liberacao->captacao->bl) ? "- BL:{$liberacao->captacao->bl}" : null;
        return "{$text} {$ref} {$bl}";
    }
}