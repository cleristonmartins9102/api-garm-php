<?php
namespace App\Control\Grupodecontato    ;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\LoggerTXT;
use App\Model\Pessoa\GrupoDeContato;
use Slim\Http\Response;
use Slim\Http\Request;

class Record extends Controller
{
    public function delete(Request $request, Response $response, array $id)
    {
        self::openTransaction();
        $grupo = new GrupoDeContato($id['id']);
        $response = $grupo->delete();
        self::closeTransaction();
        return $response;
    }
    
}


