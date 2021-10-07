<?php
namespace App\Model\Usuario;

use App\Lib\Database\Record;
use App\Model\Acesso\UsuarioGrupoAcesso;
use App\Model\Acesso\ModuloSubUsuario;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;

class Usuario extends Record
{
    const TABLENAME = 'Usuario';

    public function get_permissoes() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_usuario', '=', $this->id_usuario));
        $repository = (new Repository(UsuarioGrupoAcesso::class))->load($criteria);
        if (count($repository) === 0)
            return [];
        $grupo_acesso = $repository[0]->grupoacesso->permissoes($this);
        return $grupo_acesso;
    }

    /**
     * Metodo para retornar os modulos que o usuario tem acesso
     * @return array retorna um array vaziou ou contendo os modulos que o usuario possue acesso
     */
    public function get_modulos_sub_acesso() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_usuario', '=', $this->id_usuario));
        return (new Repository(ModuloSubUsuario::class))->load($criteria);
    }

    /**
     * Metodo busca se o usuario tem definido permissão especifica por modulo na tabela ModuloSubUsuario
     * 
     */
    public function hasAcessModuloSub($id_modulosub) {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_usuario', '=', $this->id_usuario));
        $criteria->add(new Filter('id_modulosub', '=', $id_modulosub));
        $modulo_sub = (new Repository(ModuloSubUsuario::class))->load($criteria);
        return count($modulo_sub) > 0 ? $modulo_sub[0] : (new ModuloSubUsuario);
    }

    public function get_grupo_acesso() {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_usuario', '=', $this->id_usuario));
        $repository = (new Repository(UsuarioGrupoAcesso::class))->load($criteria);
        if (count($repository) === 0)
            return new UsuarioGrupoAcesso;
        $grupo_acesso = $repository[0]->grupoacesso;
        return $grupo_acesso; 
    }
}
