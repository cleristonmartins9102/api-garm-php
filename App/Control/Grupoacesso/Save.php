<?php

namespace App\Control\Grupoacesso;

use App\Mvc\Controller;
use App\Model\Acesso\GrupoAcesso;
use App\Model\Acesso\GrupoAcessoModulo;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use Slim\Http\Response;
use Slim\Http\Request;

class Save extends Controller
{
    private $result;

    public function __construct()
    {
        $this->result = new \stdClass;
        $this->result->message = null;
        $this->result->status = 'success';
    }

    public function store(Request $request, Response $response, $data) {
        if (count($data) === 0 && !isset($data['permissoes'][0])) {
            $this->result->message = 'Dados incompletos';
            $this->result->status = 'fail';
            return $this->result;
        }
        self::openTransaction();
        $data = (object) $data;
        $criteria = new Criteria;
        $criteria->add(new Filter('id_grupoacesso', '=', $data->id_grupoacesso));
        (new GrupoAcessoModulo)->deleteByCriteria($criteria);
        foreach ($data->modulos as $key=>$modulo) {            
            $mod = $modulo['id_modulo'];
            $grupo_acesso = $data->id_grupoacesso;
            $modulo_sub = $modulo['id_modulosub'];
            $permissoes = $modulo['permissoes'];
            $grupo_acesso_modulo = new GrupoAcessoModulo($grupo_acesso, $mod, $modulo_sub, $permissoes);
        }
        self::closeTransaction();

        if (is_null($grupo_acesso_modulo->result))
            return json_encode($this->result);
        
        $this->result->message = $grupo_acesso_modulo->result['message'];
        return json_encode($this->result);



    }
}
