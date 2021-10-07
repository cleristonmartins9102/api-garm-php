<?php

namespace App\UserCase\Servico;

use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Record;
use App\Lib\Database\Repository;
use App\Model\Servico\Predicado;
use App\UserCase\Error\ServerError;
use Domain\Model\Response;
use Domain\Servico\GetMaster;

use function App\UserCase\Helper\ok;
use function App\UserCase\Helper\serverError;

class GetItemMaster extends Record implements GetMaster
{
  const TABLENAME = 'ItemMaster';
  protected int $id_predicado;
  public function __construct(int $id_predicado) {
    $this->id_predicado = $id_predicado;
  }
  public function get(): Response
  {
    try {
      $criteria = new Criteria;
      // Verifica se o predicado têm um item master de maior importancia
      $criteria->add(new Filter('id_predicadoslave', '=', $this->id_predicado));
      $repository = new Repository('App\Model\Servico\ItemMaster');
      $predicado_master = $repository->load($criteria);
      // Verifica se encontrou
      if (count($predicado_master) > 0) {
        $predicado_m_arr = $predicado_master[0]->master;
        // Busca o predicado master e mais importante
        $predicados[] = $predicado_m_arr ?? null;
      } else {
        $predicados[] = (new Predicado($this->id_predicado));
      }
      return ok($predicados[0]);
    } catch (\Throwable $th) {
      return serverError(new ServerError($th->getMessage()));
    }
  }
}
