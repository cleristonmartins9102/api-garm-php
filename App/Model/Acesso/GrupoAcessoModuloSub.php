<?php

namespace App\Model\Acesso;

use App\Lib\Database\Record;
use App\Model\Acesso\Permissao;
use App\Model\Modulo\ModuloSub;

class GrupoAcessoModuloSub extends Record
{
    const TABLENAME = 'GrupoAcessoModuloSub';

    /**
     * Metodo para retornar a permissão 
     */
    public function permissao() {
        return new Permissao($this->id_permissao);
    }

    /**
     * Metodo para retornar o modulo sub com a permissão definida
     * @return ModuloSub
     */
    public function get_modulo_sub() {
        $modulo_sub = new ModuloSub($this->id_modulosub);
        $modulo_sub->removeProperty([ 'id_modulosubtipo', 'id_modulosub', 'created_at', 'id_modulo' ]);
        $modulo_sub->permissao = $this->permissao;
        return $modulo_sub;
    }
}
