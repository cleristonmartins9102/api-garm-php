<?php

namespace App\UserCase\Helper;

use App\UserCase\Helper\UserCaseResponse;
use Domain\Model\Response;
use Error;

function serverError(Error $error): Response {
  return new UserCaseResponse(500, $error);
}

function badRequest($body): Response {
  return new UserCaseResponse(400, $body);
}

function ok($body): Response {
  return new UserCaseResponse(200, $body);
}