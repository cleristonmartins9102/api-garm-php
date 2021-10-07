<?php

namespace App\Model\Depot;

use App\Lib\Database\Record;
use App\Model\Pessoa\Individuo;
use App\Model\Margem\Margem;
use App\Model\Pessoa\Cidade;

class Depot extends Record {
    const TABLENAME = 'Depot';

    public function get_individuo() {
        return new Individuo($this->id_individuo);
    }

    public function get_margem() {
        return new Margem($this->id_margem);
    }

    public function get_cidade() {
        return new Cidade($this->id_cidade);
    }
}