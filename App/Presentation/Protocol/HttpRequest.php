<?php

namespace App\Presentation\Protocol;

interface HttpRequest {
  public function getBody();
  public function getUser(): string;
  public function getAttribute(string $val): string;
}