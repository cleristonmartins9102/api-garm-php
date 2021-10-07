<?php

namespace App\Presentation\Controller\Proposta;

use App\Lib\Database\Transaction;
use App\Model\Proposta\Proposta;
use App\Presentation\Protocol\Controller;
use App\Presentation\Protocol\HttpRequest;
use App\Presentation\Protocol\HttpResponse;
use App\UserCase\Proposta\CreateRenovationNumber;
use App\UserCase\Proposta\CreateVersionNumber;
use App\UserCase\Proposta\VersionarProposta;
use Error;

use function App\Presentation\Http\ok;
use function App\Presentation\Http\serverError;

class Versionar implements Controller {
  public function handle(HttpRequest $request, HttpResponse $response): HttpResponse {
    Transaction::open('zoho');
    $proposta = new Proposta($request->getBody()->id_proposta);
    $createNumber = new CreateVersionNumber($proposta);
    $response = (new VersionarProposta($proposta, $createNumber))->create();
    Transaction::close();
    if ($response->statusCode === 200) {
      return ok($response->getBody());
    } else {
      return serverError($response->body);
    }
  }
}