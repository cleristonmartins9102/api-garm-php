<?php

namespace App\UseCase\Servico;

use App\UserCase\Error\ServerError;
use App\UserCase\Model\UserCaseResponse;
use App\UserCase\Servico\GetItemMaster;
use PHPUnit\Framework\TestCase;

class GetItemMasterTest extends TestCase
{
  //Ensure when call get method returns a response object
  public function testGet()
  {
    $responseFake = new UserCaseResponse;
    $responseFake->statusCode = 1;
    $responseFake->body = new ServerError('Server Error');
    
    $fakeGetItemMaster = $this->createMock(GetItemMaster::class);
    $fakeGetItemMaster->method('get')
                      ->willReturn($responseFake);
    $this->assertInstanceOf($fakeGetItemMaster->get(1), new UserCaseResponse);
  }
}
