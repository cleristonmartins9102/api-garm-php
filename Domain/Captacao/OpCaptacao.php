<?php

namespace Domain\Captacao;

use Domain\Operacao;
use App\Model\Documento\Upload;
use App\Model\Captacao\CaptacaoTracking;
use App\Model\Pessoa\Individuo;
use App\Model\Porto\Porto;
use App\Model\Terminal\Terminal;

interface OpCaptacao extends Operacao {
  public function addDocumento(Upload $upload = null);
  public function addBreakBulk(array $break_bulk);
  public function get_break_bulk_info();
  public function get_historico($tipo = null);
  public function get_extrato_terminal(): Upload;
  public function get_grupodecontato();
  public function get_allgrupodecontato();
  public function get_iserv(): Upload;
  public function get_previous_dta_prevista_atracacao();
  public function liberarFaturamento();
  public function checkIfEnviadoAoTerminal();
  public function checkIfConfirmado(): array;
  public function get_cliente_cnpj();
  public function get_transportadora_nome(): Individuo;
  public function charge_rule(string $by, $value);
  public function get_porto(): Porto;
  public function get_status(): string;
  public function get_terminal_nome(): string;
  public function get_terminal_redestinacao(): Terminal;
  public function get_terminal_redestinacao_nome(): string;
  public function get_notificacao();
  public function tracking(): CaptacaoTracking;
  public function get_container20(): string;
  public function get_container40(): string;
  public function get_documento();
  public function checkIsDDC(): bool;
  public function get_liberacao();
  public function get_itensProcessoArray();
  public function isInLote();
}