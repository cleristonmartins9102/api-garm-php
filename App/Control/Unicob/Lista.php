<?php
namespace App\Control\Unicob;

use App\Mvc\Controller;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use Slim\Http\Response;
use Slim\Http\Request;

use App\Model\Shared\UniCob;

class Lista extends Controller
{     
    public function alldropdown(Request $request, Response $response)
    {
          try{
            self::openTransaction();
            $unis_cobs = (new UniCob)->all();
            foreach ($unis_cobs as $key => $uni_cob) {
              $uni_cob->removeProperty([ 'created_at', 'created_by', 'unidade', 'updated_at', 'updated_by' ]);
              $dataFull[] = $uni_cob->toArray();
            }
            self::closeTransaction();
            return isset($dataFull) ? $dataFull : null;
          }
          catch (Exception $e)
          {
            echo $e->getMessage();
          }
    }

    
}
