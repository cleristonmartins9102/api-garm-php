<?php
namespace App\Model\Vendedor;

use App\Model\Pessoa\Individuo;
use App\Model\Proposta\Proposta;
use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Repository;
use App\Lib\Database\Filter;
/**
 *
 */
class VendedorStatus extends Record
{
  const TABLENAME = "VendedorStatus";
}
