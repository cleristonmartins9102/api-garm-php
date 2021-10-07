<?php
namespace App\Control\Grupodecontato    ;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\LoggerTXT;
use App\Model\Pessoa\GrupoDeContato;

class Record extends Controller
{
    public function delete($id)
    {
        self::openTransaction();
        $grupo = new GrupoDeContato($id);
        $response = $grupo->delete();
        self::closeTransaction();
        return $response;
        //return json_encode(isset($dataFull)?$dataFull:array(array('erro' => 'sem grupo cadastrado', 'nome' => 'sem grupo cadastrado')));
    }
    
}


