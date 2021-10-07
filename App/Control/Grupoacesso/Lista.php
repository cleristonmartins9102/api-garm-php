<?php

namespace App\Control\Grupoacesso;

use App\Mvc\Controller;
use App\Model\Acesso\GrupoAcesso;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response) {
        self::openTransaction();
        $grupo_acesso = (new GrupoAcesso)->all();
        foreach($grupo_acesso as $key=>$grupo) {
            $grupo->membros = $grupo->membros;
            $grupo->acessos = $grupo->permissoes;
            $grupos_acesso[] = $grupo->toArray();
        }
        return $grupos_acesso ?? [];
        self::closeTransaction();
    }
    
}
