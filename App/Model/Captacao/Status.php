<?php
namespace App\Model\Captacao;

use App\Lib\Database\Record;

class Status extends Record
{
    private $public;
    const TABLENAME = "CaptacaoStatus";
}
