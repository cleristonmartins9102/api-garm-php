<?php
namespace App\Control\Grupodecontatonome;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\LoggerTXT;
use App\Model\Pessoa\GrupoDeContatoNome as GrupoNome;
use App\Model\Pessoa\GrupoDeContato;
use App\Model\Pessoa\GrupoContato;
use App\Model\Pessoa\Contato;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response, $id = null)
    {
        self::openTransaction();
        if ($id) {
            $criteria = new Criteria();
            $repository = new Repository('App\Model\Pessoa\GrupoDeContato');
            $criteria->add(new Filter('id_coadjuvante', '=', $id['id_coadjuvante']));
            $criteria->add(new Filter('id_adstrito', '=', $id['id_adstrito']));
            $grupos_de_contato = $repository->load($criteria);
            $criteria->clean();
            $repository = new Repository('App\Model\Pessoa\GrupoDeContatoNome');
            foreach ($grupos_de_contato as $grupo) {

                $criteria->add(new Filter('id_grupodecontatonome', '!=', $grupo->id_nome));
            }
            $grupo_nomes = $repository->load($criteria);
            foreach ($grupo_nomes as $key => $grupo_n) {
                $dataFull[] = $grupo_n->getData();
            }
        } else {
            $nomes = (new GrupoNome())->all();
            foreach ($nomes as $key => $grupo_n) {
                $dataFull[] = $grupo_n->getData();
            }
        }
        self::closeTransaction();
        return isset($dataFull) ? $dataFull : array(array('erro' => 'sem nomes cadastrado', 'nome' => 'sem nomes cadastrado'));
    }

    public function byenvolvidos(Request $request = null, Response $response = null, $envolvidos)
    {
        self::openTransaction();
        $criteria = new Criteria;
        $criteria->add(new Filter('id_adstrito', '=', $envolvidos->id_adstrito));
        $criteria->add(new Filter('id_coadjuvante', '=', $envolvidos->id_coadjuvante));
        $repository = (new Repository(GrupoDeContato::class))->load($criteria);

        $criteria->clean();
        foreach ($repository as $key => $grupo) {
            $criteria->add(new Filter('id_grupodecontatonome', '<>', $grupo->id_nome));
        }
        $repository = (new Repository(GrupoNome::class))->load($criteria);
        
        if (count($repository) === 0)
            return json_encode([]);
        foreach ($repository as $key => $grupo_nome) {
            $grupos[] = $grupo_nome->toArray();
        }

        return json_encode($grupos);
        self::closeTransaction();
    }
}
