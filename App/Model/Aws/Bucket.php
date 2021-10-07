<?php

namespace App\Model\Aws;

use App\Lib\Database\Record;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Lib\Database\Repository;

class Bucket extends Record
{
    const TABLENAME = 'Bucket';
    private $folder;

    public function set_folderByName(string $folder_name) {
        $criteria = new Criteria;
        $criteria->add(new Filter('id_bucket', '=', $this->id_bucket));
        $criteria->add(new Filter('folder', '=', $folder_name));
        $repository = (new Repository(BucketFolder::class))->load($criteria);
        foreach ($repository as $key=>$bucket_folder) {
            $this->folder[] = $bucket_folder->folder;
        }
    }

    public function get_folder() {
        echo $this;
        exit();
        return $this->folder;
    }
}
