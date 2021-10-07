<?php

namespace App\UserCase\Proposta;

use App\Model\Proposta\Proposta;
use App\UserCase\Helper\UserCaseResponse;
use Domain\Model\Response;
use Domain\Proposta\CreateNumber;

class CreateDefaultNumber implements CreateNumber
{
  protected Proposta $proposta;
  protected string $number;
  public function __construct(Proposta $proposta)
  {
    $this->proposta = $proposta;
    $this->number = $proposta->numero;
    $this->lastNumber = $proposta->getLastNum() + 1;
  }

  public function create(): Response
  {
    try {
      $year = $this->getYear();
      if ($this->proposta->tipo == 'modelo')
          return new UserCaseResponse(200, "$this->lastNumber/$year");

      $number = $this->getOnlyNumber($this->number);
      $version = $this->getVersion($this->number);
      return new UserCaseResponse(200, "$number.$version/$year");
    } catch (\Throwable $th) {
      return new UserCaseResponse(500, $th);
    }
  }

  private function getOnlyNumber(string $number): string
  {
    if (strpos($number, '.')) {
      return strstr($this->number, '.', true);
    } elseif (strpos($number, '/')) {
      return strstr($this->number, '/', true);
    }
  }

  private function getVersion(string $number): string
  {
    if (strpos($this->number, '.')) {
      $barra = strstr($number, '/', true);
      $traco = strpos($barra, '.') + 1;
      return substr($barra, $traco) + 1;
    }
    return 1;
  }

  private function getYear(): string
  {
    return date('Y');
  }
}
