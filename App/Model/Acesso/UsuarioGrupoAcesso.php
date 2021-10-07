<?php

namespace App\Model\Acesso;

use App\Lib\Database\Record;

class UsuarioGrupoAcesso extends Record
{
    const MANYTOMANY = 'true';
    const TABLENAME = 'UsuarioGrupoAcesso';

    public function get_grupoacesso() {
        $grupo_acesso = (new GrupoAcesso($this->id_grupoacesso));
        $grupo_acesso->id_usuario = $this->id_usuario;
        return $grupo_acesso;
    }
}
