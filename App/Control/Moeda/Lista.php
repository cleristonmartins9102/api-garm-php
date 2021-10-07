<?php
namespace App\Control\Moeda;

use App\Mvc\Controller;
use App\Model\Moeda\Moeda;
use Slim\Http\Response;
use Slim\Http\Request;

class Lista extends Controller
{
    public function alldropdown(Request $request, Response $response)
    {
      self::openTransaction();
      $object = (new Moeda)->all();
      foreach ($object as $key => $moeda) {
        $moedas[] = $moeda->toArray();
      }
      self::closeTransaction();
      return $moedas ?? null;
    }
}
