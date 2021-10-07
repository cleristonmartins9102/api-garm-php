<?php
namespace App\Control\Predicado;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Servico\Predicado;
use App\Model\Servico\Servico;
use App\Model\Servico\VwPredicado;
use App\Model\Regime\Regime;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function all(Request $request, Response $response, Array $param=null)
    {
      if ($param == null) {
        $param = array( 'sort' => 'nome',
                        'order' => 'asc'
        );
      }
      self::openTransaction();
      $object = (new VwPredicado)->all();
      $dataFull = array();
      $dataFull['total_count'] = count($object);
      $dataFull['items'] = array();
      $criteria = parent::criteria($param);
      $repository = new Repository('App\Model\Servico\VwPredicado');
      $object = $repository->load($criteria);
      if (count($object) > 0){
        foreach ($object as $key => $predicado) {
          $dataFull['items'][] = $predicado->toArray();
        }
        usort($dataFull['items'], function ($item1, $item2) {
          return $item1['nome'] <=> $item2['nome'];
        });
      }
      self::closeTransaction();
      return $dataFull ?? [];
    }

    public function byregime(Request $request, Response $response, array $regime = [ 'regime' => 'importacao' ]) {
      if (is_null($regime))
        return [];
        
      self::openTransaction();
      if ( is_scalar($regime['regime']) ) {
        $regime['regime'] = [ 
          'id_regime' => !( (int) $regime['regime'] ) 
            ? (new Regime)('regime', $regime['regime'])->id_regime 
            : $regime['regime'] 
        ];  
      }

      $regime = (object) $regime['regime'];
      // echo '<pre>';
      // print_r($regime);
      // exit();
      // if ( )
      // $regime = (object) $regime['regime'];
      $predicado = new Predicado;

      $predicado->id_regime = $regime->id_regime;

      // exit();
      foreach ($predicado->allByRegime() as $key => $predicado) {
        $predicado->servico = $predicado->servico->nome;
        $predicado->regime = $predicado->regime->regime;
        $predicados[] = $predicado->toArray();
      };
      usort($predicados, function ($item1, $item2) {
        return $item1['nome'] <=> $item2['nome'];
      });
      self::closeTransaction();
      return $predicados ?? [];
    }

    public function bynome(Request $request, Response $response, $nome = null)
    { 
      if ($nome != null){
        self::openTransaction();
        $criteria = new Criteria;
        $criteria->add(new Filter('nome', '=', $nome));
        $repository = new Repository('App\Model\Servico\Predicado');
        // print_r($criteria->dump());exit();
        $object = $repository->load($criteria);
        self::closeTransaction();
        if (!empty($object)){
          return json_encode($this->prepare($object));
        }else{
          return null;
        }
      }
    }

    public function byservico(Request $request, Response $response, $id_servico = null)
    { 
      self::openTransaction();
      $criteria = new Criteria();
      $criteria->add(new Filter('id_servico', '=', $id_servico));
      $repository = new Repository('App\Model\Servico\Predicado');
      $predicados = $repository->load($criteria);
      $object = (new Predicado)->all();
      $dataFull = array();
      $dataFull['total_count'] = count($object);
      $dataFull['items'] = array();
      self::closeTransaction();
    }

    public function byid(Request $request, Response $response, $id = null)
    { 
      if (is_null($id))
        return [];
      self::openTransaction();
      $object = new Predicado($id['id']);
      $dataFull = array();
      $dataFull['items'] = $object->toArray();
      return $dataFull;
      self::closeTransaction();
    }

    public function filtered(Request $request, Response $response, Array $filter=null)
    {
      try {
        // Modifica o tipo de busca para contain a palavra, e não palavra exata
        $filter['comparation'] = 'contains';
        self::openTransaction();
        $dataFull = array();
        $dataFull['items'] = array();
        $dataModificated['complementos'] = array();
        $filter['columns'] = (new Predicado())->getColTable(); 
        $criteria = parent::filterColunm($filter);
        $repository = new Repository('App\Model\Servico\Predicado');
        $object = $repository->load($criteria);
        $dataFull['total_count'] = count($object);
        $objectFull=[];
        foreach ($object as $key => $predicado) {
          $predicado->regime = $predicado->regime->legenda;
          $predicado->servico = $predicado->servico->nome;
          $dataFull['items'][] = $predicado->toArray();
        }
        return $dataFull?json_encode($dataFull) : null;
      }catch(\Exception $e) {

      }
    }
}
