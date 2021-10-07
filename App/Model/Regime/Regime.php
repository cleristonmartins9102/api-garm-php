<?php
namespace App\Model\Regime;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;

class Regime extends Record
{
    const TABLENAME = 'Regime';
    
    public function searchByName() {
        $criteria = new Criteria;
        $criteria->add(new Filter('regime', '=', $this->regime));
        $repository = (new Repository(get_class()))->load($criteria);
        if (count($repository) >= 0) {
            return $repository[0];
        } 
    }
}
