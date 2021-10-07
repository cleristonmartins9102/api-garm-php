<?php
namespace App\Control\Grupocontato    ;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\LoggerTXT;
use App\Model\Pessoa\GrupoDeContato;
use App\Model\Pessoa\IndividuoContato;
use App\Model\Pessoa\GrupoContato;
use App\Model\Pessoa\Contato;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function byid(Request $request, Response $response, array $id)
    {
        if (count($id) === 0)
            return [];
            
        self::openTransaction();
        $criteria = new Criteria;
        $criteria->add(new Filter('id_grupodecontato', '=', $id));
        $object = (new Repository('App\Model\Pessoa\GrupoContato'))->load($criteria);
        if (count($object) > 0) {
            foreach ($object as $key => $grupo_contato) {
                $dataFull = [];
                $gp_id = $grupo_contato->id_grupodecontato;
                $grupo_de_contato = new GrupoDeContato($gp_id);
                foreach ($grupo_de_contato->contatos as $i => $contato) {
                    $contato = $contato->getData();
                    $criteria->clean();
                    $criteria->add(new Filter('id_contato', '=', $contato['id_contato']));
                    $object = (new Repository('App\Model\Pessoa\IndividuoContato'))->load($criteria);
                    $contato['id_individuo'] = $object[0]->id_individuo;   
                    $dataFull[] =  $contato;
                }
            }
        }
        self::closeTransaction();
        return isset($dataFull)?$dataFull:array(array('erro' => 'sem contato cadastrado no grupo', 'nome' => 'sem contato cadastrado no grupo'));
    }
    
}


