<?php
namespace App\Lib\Database;

use Slim\Http\Response;
use Slim\Http\Request;

interface RecordInterface
{
    public function fromArray($data);
    public function toArray();
    public function store(Request $request = null, Response $response = null);
    public function load($id);
    public function delete($id = NULL);
}
