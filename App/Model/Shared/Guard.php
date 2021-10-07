<?php

namespace App\Model\Shared;

use App\Lib\Database\Record;
use App\Model\Fatura\Fatura;

class Guard extends Record {
    private $message;

    public function isFaturado() {
        if ( !isset($this->id_captacao) ) {
            $this->message = [ 'message' => 'sem captação atrelada', 'result' => false ];
            return $this->message;
        }
        
        $fatura = new Fatura;
        $fatura('id_captacao', $this->id_captacao);
        
        if ( $fatura->id_fatura ) {
            $this->message = [ 'message' => $fatura, 'result' => true ];
            return $this->message;
        } 

        $this->message = [ 'message' => 'não faturado', 'result' => false ];
        return $this->message;
    }
}