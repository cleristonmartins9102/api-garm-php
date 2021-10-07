<?php

namespace App\Model\Proposta;

use App\Lib\Database\Record;

use App\Model\Pessoa\Cidade;
use App\Model\Pessoa\Estado;

class PropostaPredicadoCidade extends Record {
    const TABLENAME = 'PropostaPredicadoCidade';
    const MANYTOMANY = 'true';

    public function get_cidade() {
        return new Cidade($this->id_cidade);
    }

    public function get_estado() {
        if ( $this->cidade->isLoaded() )
            return new Estado($this->cidade->id_estado);
        return null;
    }
}