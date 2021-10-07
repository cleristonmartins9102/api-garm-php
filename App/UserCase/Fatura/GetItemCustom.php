<?php

namespace App\UserCase\Fatura;

use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Record;
use App\Lib\Database\Repository;
use Domain\Fatura\DefineItemName;
use App\Model\Servico\Predicado;
use App\UserCase\Error\ServerError;
use App\UserCase\Model\UserCaseResponse;
use Domain\Model\Response;
use Domain\Servico\GetMaster;

use function App\UserCase\Helper\ok;
use function App\UserCase\Helper\serverError;

class GetItemCustom extends Record implements DefineItemName {
  protected $id_predicado;
  protected $descricao;
  protected Predicado $predicado;
  public function __construct(GetMaster $predicado)
  {
    $this->predicado = $predicado->get()->body;
    $this->id_predicado = $this->predicado->id_predicado;
    $this->descricao = $this->predicado->descricao;
  }
  public function get(): Response {
    try {
      $custom = null;
      $criteria = new Criteria;
      $criteria->add(new Filter('id_predicado', '=', $this->id_predicado));
      $repository = new Repository('App\Model\Fatura\FaturaItemCustom');;
      if (count($repository->load($criteria)) > 0) {
          $desc = $repository->load($criteria)[0]->field;
          $desc = explode(",", $desc);
          foreach ($desc as $key => $value) {
              if (strpos($value, '[') || strpos($value, ']')) {
                  $value = trim($value);
                  $value = str_replace('[', '', $value);
                  $value = str_replace(']', '', $value);
                  $custom .= $this->predicado->{$value};
              } else {
                  $custom .= $value;
              }
          }
          $name = $this->descricao . $custom;
      } else {
          $name = $this->descricao;
      }
      return ok($name);
    } catch (\Throwable $th) {
      return serverError($th);
    }
  }
}