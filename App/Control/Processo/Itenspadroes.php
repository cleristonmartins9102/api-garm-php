<?php
namespace App\Model\Processo;

use App\Mvc\Controller;

class Itenspadroes extends Controller
{
    public function byoperacoes($data) {
        echo 111;exit();
        self::openTransaction();
        print_r($data);
        self::closeTransaction();
    }
}
