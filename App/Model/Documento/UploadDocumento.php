<?php
namespace App\Model\Documento;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;
use App\Lib\Database\Transaction;
use App\Model\Servico\Predicado;

class UploadDocumento extends Record
{
    const TABLENAME = "UploadDocumento";
    const MANYTOMANY = true;
}

