<?php
namespace Domain;

use App\Model\Captacao\Container;
use App\Model\Pessoa\Individuo;
use App\Model\Terminal\Terminal;

interface Operacao {
  public function addContainer(Container $container = null);
  public function get_despachante(): Individuo;
  public function get_despachante_nome(): string;
  public function get_terminal(): Terminal;
  public function get_proposta();
  public function addEvento($evento = null, $app_forward = null, $app = null): void;
  public function get_eventos();
  public function get_container();
  public function get_listacontainer();
  public function get_qtdcontainer();
  public function addHistorico($ocorrencia);
}