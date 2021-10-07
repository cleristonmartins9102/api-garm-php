<?php
namespace App\Control\Vendedorstatus;

use App\Mvc\Controller;
use App\Model\Vendedor\Vendedor;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Vendedor\VendedorStatus;
use App\Model\Pessoa\Endereco;
use App\Model\Pessoa\Papel;
use App\Model\Pessoa\PessoaFisica;
use App\Model\Pessoa\PessoaJuridica;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{ 
    public function alldropdown() {
      self::openTransaction();
      $vendedor_status = (new VendedorStatus)->all();
      foreach ($vendedor_status as $key => $status) {
        $dataFull[] = $status->getData();
      }        
      self::closeTransaction();
      return isset($dataFull) ? $dataFull : null;
    }
}
