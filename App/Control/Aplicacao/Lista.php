<?php

namespace App\Control\Aplicacao;

use App\Mvc\Controller;
use App\Model\Aplicacao\Aplicacao;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response)
    {
        self::openTransaction();
        $aplicacoes = (new Aplicacao())->all();
        if (count($aplicacoes) === 0)
            return [];
        foreach ($aplicacoes as $key => $aplicacao) {
            if ($aplicacao->nome !== 'administrador') {
                $aplicacao->modulos = $aplicacao->modulos;
                $apps[] = $aplicacao->toArray();
            }
        }
        usort($apps, function ($app1, $app2) {
            return $app2['nome'] <=> $app1['nome'];
        });
        return $apps;
        self::closeTransaction();
    }
}
