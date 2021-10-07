<?php

namespace App\Model\Acesso;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Model\Acesso\GrupoAcessoModulo;
use App\Model\Usuario\Usuario;
use App\Model\Aplicacao\Modulo;
use App\Model\Modulo\ModuloSub;

class GrupoAcesso extends Record
{
    /**
     * Variável que vai armazenar todas as permissoes do grupo, baseando no usuário logado. 
     * Caso o usuário logado tiver permissão inferior a do grupo, Encontrada na tabela ModuloSubUsuario.
     */
    private $acessos = [];
    
    const TABLENAME = 'GrupoAcesso';

    /**
     * Metodo para retornar as permissoes que o grupo de acesso possue
     */
    public function permissoes(Usuario $usuario) {
        $modulos = [];
        $criteria = new Criteria;
        $criteria->add(new Filter('id_grupoacesso', '=', $this->id_grupoacesso));
        // Variável com o resultado com os submodulos ao qual o grupo possue permissao
        $grupos_has_modulos = (new Repository(GrupoAcessoModuloSub::class))->load($criteria);
        foreach($grupos_has_modulos as $modulo_sub) {
            $usuario_mod = $usuario->hasAcessModuloSub($modulo_sub->id_modulosub);
            $permissao = $modulo_sub->permissao()->permissao;
            if ($usuario_mod->isLoaded()) {
                $permissao = $usuario_mod->permissao()->permissao;
            }
            $modulo_sub->permissao = $permissao;
            $this->join($modulo_sub, $modulos);
        }
        return self::prepareModulo($modulos);
    }

    private function join(GrupoAcessoModuloSub $mod_sub, &$modulos) {
        $modulo_sub = new ModuloSub($mod_sub->id_modulosub);
        if ($modulo_sub->isLoaded()) {
            $modulo = $modulo_sub->modulo;
            if ($k = self::in_modulo($modulos, $modulo)) {
                $mod = $modulos[$k];
                $mod->addSub($mod_sub->modulo_sub);
            } else {
                $mod = $modulo;
                $mod->addSub($mod_sub->modulo_sub);
                $modulos[] = $modulo;
            }
        }
    }

    private function in_modulo($modulos, Modulo $modulo) {
        foreach ($modulos as $key => $mod) {
            if (isset($mod->id_modulo) and $mod->id_modulo === $modulo->id_modulo) 
                return $key;
        }
        return false;
    }

    private function prepareModulo(array $modulos = []) {
        $data = [];
        foreach ($modulos as $modulo) {
            $data[$modulo->aplicacao->nome][] = $modulo->dump();
        }
        return $data;
    }
}
