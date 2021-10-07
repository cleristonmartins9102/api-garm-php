<?php
namespace App\Model\Captacao;

use App\Lib\Database\Record;

class Container extends Record
{
    const TABLENAME = "Container";

    public function get_tipo() {
        return (new ContainerTipo($this->id_containertipo))->tipo;
    }

    public function get_dimensao() {
        return (new ContainerTipo($this->id_containertipo))->dimensao;
    }
}
