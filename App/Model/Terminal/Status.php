<?php
namespace App\Model\Terminal;

use App\Lib\Database\Record;

class Status extends Record
{
    private $public;
    const TABLENAME = "TerminalStatus";
}
