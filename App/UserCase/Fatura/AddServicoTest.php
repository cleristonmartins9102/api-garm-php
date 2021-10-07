<?php

namespace App\UserCase\Fatura;

use App\UserCase\Model\UserCaseResponse;
use PHPUnit\Framework\TestCase;

class AddServicoTest extends TestCase
{
  //Ensure when call start method returns a boolean value
  public function testAdd()
  {
    $mock = $this->getMockBuilder(AddServico::class)
      ->onlyMethods(['add'])
      ->getMock();

    $mock->expects($this->at(0))
         ->method('add')
         ->with($this->identicalTo(new UserCaseResponse));

    // $mock->expects($this->at(1))
    //      ->method('add')
    //      ->with(1, 'Id proposta predicado');

    // $mock->expects($this->at(2))
    //      ->method('add')
    //      ->with(1, 'Id processo predicado');

    // $mock->expects($this->at(3))
    //      ->method('add')
    //      ->with(1, 'Id predicado');


    $mock->add(2,1,1,1);

    $this->
  }
}
