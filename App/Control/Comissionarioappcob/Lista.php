<?php

namespace App\Control\Comissionarioappcob;

use App\Mvc\Controller;
use App\Model\Comissionario\VwComissionario;
use App\Model\Comissionario\ComissionarioAppCob;
use App\Model\Imposto\Imposto;
use App\Lib\Database\Repository;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{
    public function alldropdown()
    {
        self::openTransaction();
        $appcobs = (new ComissionarioAppCob)->all();
        $dataFull['items'] = array();
        foreach ($appcobs as $key => $appcob) {
            $dataFull['items'][] = $appcob->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }
}
