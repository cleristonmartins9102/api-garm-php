<?php
namespace App\Control\Fatura;

use App\Mvc\Controller;
use App\Model\Fatura\Fatura;
use App\Model\Fatura\FaturaStatus;

use App\Model\Fatura\VwFatura;
use App\Lib\Database\Repository;
use Slim\Http\Response;
use Slim\Http\Request;
use App\Model\Captacao\Captacao;
use App\Model\Captacao\CaptacaoLote;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Processo\UseCase\ProcessoGetFornecedor;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class Lista extends Controller
{
    public function all(Request $request, Response $response, array $param = null) {
        try {
            self::openTransaction();
            $object = (new VwFatura())->all();
            $dataFull = array();
            $dataFull['total_count'] = count($object);
            $dataFull['items'] = array();
            $dataModificated['complementos'] = array();
            $criteria = parent::criteria($param);
            $repository = new Repository('App\Model\Fatura\VwFatura');
            $object = $repository->load($criteria);
            foreach ($object as $idx => &$fatura) {

                $fatura->isComplementar();
                $fatura->cheia = $fatura->isCheia();

                // $fatura->calcValotTotal();
                $fatura->valor = (float) $fatura->valor;
                $fatura->valor_custo = (float) $fatura->valor_custo;
                $fatura->fatura_numero = round($fatura->fatura_numero, 1);
                $fatura->complementos = ['contêiner' => null];
                
                if ($fatura->modelo_nome === 'notadeage') {
                    $fatura->cliente_nome = $fatura->cliente->nome;
                    $fatura->identificador = $fatura->cliente->identificador;
                }

                if ($fatura->modelo_nome === 'notadebtrc')
                    $fatura->cliente_nome = $fatura->cliente->nome;

                if ($fatura->modelo_nome === 'importacao' || $fatura->modelo_nome === 'exportacao') {
                    if ($fatura->processo->isLote()) {
                        $complementos['contêiner'] = (new CaptacaoLote($fatura->processo->lote->id_captacaolote))->container;
                    } else {
                        $complementos['contêiner'] = (new Captacao($fatura->processo->id_captacao))->listaContainer;
                        $fatura->proposta = $fatura->processo->captacao->proposta->numero;
                    }
                    $complementos['eventos'] = $fatura->evento;
                    $complementos['documentos'] = $fatura->documento;
                    $fatura->complementos = $complementos;

                }
                $fatura->valor_lucro = $fatura->valor_lucro;
                $fatura->margem_lucro = $fatura->margem_lucro;
                $fatura->itens = $fatura->itens;
                $fatura->imposto_interno = $fatura->imposto_interno; 
                $fatura->imposto_interno_valor = $fatura->imposto_interno_valor; 
                $dataFull['items'][] = $fatura->toArray();
            }
            self::closeTransaction();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return isset($dataFull) ? $dataFull : null;
    }
// select * from CaptacaoLoteEvento
// delete from CaptacaoLoteEvento where id_captacaolote=36
// select valor from Fatura where numero=1138
// delete from Fatura where id_processo=1113
// select id_processo from Processo where numero=1113

    public function alltotal(Request $request, Response $response, array $param = null)
    {
        try {
            self::openTransaction();
            $cri = new Criteria;
            $fatura_status = new FaturaStatus;
            $fatura_status('status', 'Cancelada');
            if (($fatura_status->isLoaded())) {
                $cri->add(new Filter('id_faturastatus', '<>', $fatura_status->id_faturastatus));
            }

            $repository = new Repository('App\Model\Fatura\VwFatura');
            $object = $repository->load($cri);
            $dataFull['total_count'] = count($object);

            $dataFull['items'] = array();
            $dataModificated['complementos'] = array();
            $criteria = parent::criteria($param);
            $criteria->add($cri);
            
            $repository = new Repository('App\Model\Fatura\VwFatura');
            $object = $repository->load($criteria);

            foreach ($object as $idx => &$fatura) {
                $fatura->fatura_numero = round($fatura->fatura_numero, 1);
                $fatura->isComplementar();
                $fatura->calcValotTotal();
                $fatura->complementos = ['contêiner' => null];

                if ($fatura->modelo_nome === 'notadeage') {
                    $fatura->cliente_nome = $fatura->cliente->nome;
                    $fatura->identificador = $fatura->cliente->identificador;
                }

                if ($fatura->modelo_nome === 'notadebtrc')
                    $fatura->cliente_nome = $fatura->cliente->nome;
                
                if ($fatura->modelo_nome === 'importacao' || $fatura->modelo_nome === 'exportacao') {
                    if ($fatura->processo->isLote()) {
                        $complementos['contêiner'] = (new CaptacaoLote($fatura->processo->lote->id_captacaolote))->container;
                    } else {
                        $complementos['contêiner'] = (new Captacao($fatura->processo->id_captacao))->listaContainer;
                    }
                    $complementos['eventos'] = $fatura->evento;
                    $complementos['documentos'] = $fatura->documento;

                    $fatura->complementos = $complementos;
                }
                $fatura->cheia = $fatura->isCheia();
                $fatura->valor_lucro = $fatura->valor_lucro;
                $fatura->margem_lucro = $fatura->margem_lucro;
                $fatura->itens = $fatura->itens;
                $fatura->imposto_interno = $fatura->imposto_interno; 
                $fatura->imposto_interno_valor = $fatura->imposto_interno_valor; 
                $dataFull['items'][] = $fatura->toArray();
            }
            self::closeTransaction();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return isset($dataFull) ? $dataFull : null;
    }

    public function byid(Request $request, Response $response, $id = null)
    {
        if ($id) {
            self::openTransaction();
            $fatura = new Fatura($id);
            if (!$fatura->isLoaded())
                return 'Fatura não encontrada';
            
            $containeres = [];
            $fatura->numero = round($fatura->numero, 1);

            if ($fatura->modelo_nome === 'notadeage') {
                $fatura->cliente_nome = $fatura->cliente->nome;
                $fatura->identificador = $fatura->cliente->identificador;
            }

            if ($fatura->modelo_nome === 'notadebtrc') {
                $fatura->cliente_nome = $fatura->cliente->nome;
                $fatura->identificador = $fatura->cliente->identificador;
            }
            
            if ($fatura->modelo_nome === 'importacao' || $fatura->modelo_nome === 'exportacao') {
                $fatura->fornecedor_nome = (new ProcessoGetFornecedor($fatura->processo->id_processo))->fornecedor_nome;
                $fatura->cliente_nome = $fatura->processo->isLote() ? $fatura->captacao->proposta->cliente->nome : ($fatura->processo->movimentacao->proposta->cliente->nome ? $fatura->processo->movimentacao->proposta->cliente->nome : $fatura->cliente->nome);//
                $fatura->cliente_cnpj = $fatura->processo->isLote() ? $fatura->captacao->proposta->cliente->identificador : $fatura->processo->movimentacao->proposta->cliente->identificador;
                $fatura->hbl = $fatura->processo->isLote() ? $fatura->captacao->bl : ($fatura->processo->movimentacao->bl ? $fatura->processo->movimentacao->bl : $fatura->hbl);
                // Verificando se é despacho
                if ( is_null($fatura->processo->id_despacho) ) {
                    // Verificando se é Lote
                    if ($fatura->processo->isLote()) {
                        $fatura->imo = $fatura->captacao->imo;
                        $fatura->tipo_documento = $fatura->captacao->liberacao->tipo_documento;
                        $fatura->documento = $fatura->captacao->liberacao->documento;
                        $fatura->captacao = "{$fatura->captacao->numero} - {$fatura->captacao->bl}";
                        $fatura->terminal_redestinacao = $fatura->captacao->terminal_redestinacao->nome;
                        $fatura->valor_mercadoria = $fatura->captacao->liberacao->valor_mercadoria;
                        $fatura->despachante_nome = $fatura->captacao->despachante->nome;
                        $fatura->proposta = $fatura->captacao->proposta->numero;
                        $fatura->ref_importador = $fatura->captacao->ref_importador;
                        foreach ((new CaptacaoLote($fatura->processo->lote->id_captacaolote))->container as $key => $container) {
                            $container = (object) $container;
                            if ($container->dimensao == 40) {
                                $containeres[] = $container;
                            } elseif ($container->dimensao == 20) {
                                $containeres[] = $container;
                            }
                        }
                    } else {
                        $movimentacao = $fatura->processo->movimentacao;
                        $fatura->documento = $fatura->captacao->liberacao->documento;
                        $fatura->tipo_documento = $fatura->captacao->liberacao->tipo_documento;
                        $fatura->captacao = "{$fatura->processo->captacao->numero} - {$fatura->processo->captacao->bl}";
                        $fatura->terminal_redestinacao = $fatura->processo->captacao->terminal_redestinacao->nome;
                        // $fatura->terminal_operacao = $fatura->processo->movimentacao->terminal->nome;
                        $fatura->valor_mercadoria = $fatura->processo->captacao->liberacao->valor_mercadoria ?? 0;
                        $fatura->despachante_nome = $fatura->processo->captacao->despachante->nome;
                        $fatura->due = $fatura->processo->captacao->due;
                        $fatura->ref_importador = $fatura->processo->movimentacao->ref_importador;
                        $fatura->valor_mercadoria = $fatura->captacao->liberacao->valor_mercadoria;

                        // $fatura->valor_mercadoria = (int) $fatura->processo->valor_mercadoria;
                        $fatura->proposta = $fatura->processo->captacao->proposta->numero;
                        foreach ($fatura->processo->movimentacao->container as $key => $container) {
                            $container = (object) $container;
                            if ($container->dimensao == 40) {
                                $containeres[] = $container->toArray();
                            } elseif ($container->dimensao == 20) {
                                $containeres[] = $container->toArray();
                            }
                        }
                    }
                } else {
                    $movimentacao = $fatura->processo->movimentacao;
                    $fatura->due = $movimentacao->due;
                    $fatura->valor_mercadoria = $fatura->processo->valor_mercadoria;
                    $fatura->despachante_nome = $movimentacao->despachante->nome;
                    $fatura->terminal_operacao = $movimentacao->terminal_operacao_nome;
                    

                    // delete from Fatura where id_fatura=469
                    // print_r($fatura);
                    // exit();
                    // echo '<pre>';
                    // print_r($movimentacao->documento);
                    // exit();
                    // $fatura->documento = $fatura->captacao->liberacao->documento;
                    // $fatura->tipo_documento = $fatura->captacao->liberacao->tipo_documento;
                    // $fatura->captacao = "{$fatura->processo->captacao->numero} - {$fatura->processo->captacao->bl}";
                    // $fatura->terminal_redestinacao = $movimentacao->captacao->terminal_redestinacao->nome;
                    // // $fatura->terminal_operacao = $fatura->processo->movimentacao->terminal->nome;
                    // $fatura->valor_mercadoria = $fatura->processo->captacao->liberacao->valor_mercadoria ?? 0;
                    // $fatura->despachante_nome = $fatura->processo->captacao->despachante->nome;
                    // $fatura->due = $fatura->processo->captacao->due;
                    // $fatura->ref_importador = $fatura->processo->movimentacao->ref_importador;
                    // $fatura->valor_mercadoria = $fatura->captacao->liberacao->valor_mercadoria;

                    // // $fatura->valor_mercadoria = (int) $fatura->processo->valor_mercadoria;
                    foreach ($fatura->processo->movimentacao->container as $key => $container) {
                        $container = (object) $container;
                        if ($container->dimensao == 40) {
                            $containeres[] = $container->toArray();
                        } elseif ($container->dimensao == 20) {
                            $containeres[] = $container->toArray();
                        }
                    }
                }
    
            }
            
            $fatura->complementos = [
                // 'notificacao' => $captacao->notificacao,
                // 'eventos' => $captacao->eventos,
                'anexos' => $fatura->documento
              ];            
            $fatura->valor = (float) $fatura->valor;
            $fatura->valor_custo = (float) $fatura->valor_custo;

            $fatura->status = $fatura->status->status;

            $fatura->margem_lucro = $fatura->margem_lucro;
            $fatura->containeres = $containeres;
            $fatura->modelo_nome = $fatura->modelo->legend;
        
            $fatura->agente_carga = $fatura->agentecarga->nome;
           
            $fatura->valor_lucro = $fatura->valor_lucro;
            $fatura->margem_lucro = $fatura->margem_lucro;
        
            $fatura->imposto_interno = $fatura->imposto_interno; 
            $fatura->imposto_interno_valor = $fatura->imposto_interno_valor; 
            self::closeTransaction();
            $fatura = $fatura->toArray();
            return $fatura;
        }
    }

    public function mail(Request $request, Response $response, $id_fatura = null)
    {
      self::openTransaction();
      $fatura = new Fatura($id_fatura);
      $criteria = new Criteria;
      $criteria->add(new Filter('nome', '=', 'fatura'));
      $repository = new Repository('App\Model\Aplicacao\Aplicacao');
      $aplicacao = $repository->load($criteria);
      $aplicacao_contato = count($aplicacao) > 0 ? $aplicacao[0]->listademodulo[0]->contatos : null;
      $contatos = $fatura->captacao->allgrupodecontato;
      self::closeTransaction();
      return isset($contatos) ? json_encode($contatos) : json_encode(array('sem contatos'));
    }
  

    /**
   * Metodo para buscar o body do email
   * @param number $id_captacao ID da Captação
   */
  public function boem(Request $request, Response $response, $data) {
    $data = (object) $data;

    if ( !isset($data->id_fatura) and is_null($data->id_fatura) or (!isset($data->bodyName) and is_null($data->bodyName) ))
      return 'Dados incompletos';

    self::openTransaction();
    $fatura = new Fatura($data->id_fatura);
    if (!$fatura->isLoaded())
      return 'Fatura não carregada';

    switch ($data->bodyName) {
      case 'envfat':
        $email['subject'] = $fatura->subEnvFatura($fatura);
        $email['body'] = $fatura->bodymail('enviarFatura');
        return $email;
        break;

      default:
        # code...
        break;
    }
    self::closeTransaction();
  }



    public function filtered(Request $request, Response $response, Array $filter)
    {
        self::openTransaction();
        $dataFull = array();
        $dataFull['items'] = array();
        $param['columns'] = (new VwFatura())->getColTable();
        $criteria = parent::filterColunm($filter);
        $repository = new Repository('App\Model\Fatura\VwFatura');
        $dataFull['total_count'] = count($repository->load(parent::filterColunm($filter, false)));
        $object = $repository->load($criteria);
        foreach ($object as $idx => &$fatura) {
            $fatura->proposta = $fatura->processo->captacao->proposta->numero;
            $complementos['eventos'] = $fatura->evento;
            $complementos['contêiner'] = [];
            $complementos['documentos'] = $fatura->documento;

            $fatura->complementos = ['contêiner' => null];
                
            if ($fatura->modelo_nome === 'notadeage') {
                $fatura->cliente_nome = $fatura->cliente->nome;
                $fatura->identificador = $fatura->cliente->identificador;
            }

            if ($fatura->modelo_nome === 'notadebtrc')
                $fatura->cliente_nome = $fatura->cliente->nome;

            if ($fatura->modelo_nome === 'importacao' || $fatura->modelo_nome === 'exportacao') {
                if ($fatura->processo->isLote()) {
                    $complementos['contêiner'] = (new CaptacaoLote($fatura->processo->lote->id_captacaolote))->container;
                } else {
                    $complementos['contêiner'] = (new Captacao($fatura->processo->id_captacao))->listaContainer;
                    $fatura->proposta = $fatura->processo->captacao->proposta->numero;
                }
                $complementos['eventos'] = $fatura->evento;
                $complementos['documentos'] = $fatura->documento;
                $fatura->complementos = $complementos;

            }


            // $fatura->complementos = $complementos;

            $dataFull['items'][] = $fatura->toArray();
        }
        self::closeTransaction();
        return json_encode($dataFull);
    }

    public function download(Request $request, Response $response, string $filter) {
        $header = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => '0B5A80',
                ],
            ],
        ];
        $row = [
            'font' => [
                'bold' => false,
                'color' => ['argb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $filter = json_decode(base64_decode($filter), true);
        $faturas = json_decode($this->filtered($request, $response, $filter), true);
        
        // CREATE A NEW SPREADSHEET + SET METADATA
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
        ->setCreator('Company_Name');
        // ->setLastModifiedBy('YOUR NAME')
        // ->setTitle('Demo Document')
        // ->setSubject('Demo Document')
        // ->setDescription('Demo Document')
        // ->setKeywords('demo php spreadsheet')
        // ->setCategory('demo php file');
        
        // NEW WORKSHEET
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Relatório de Faturas');
        self::openTransaction();
        if ( isset($faturas['items']) and count($faturas['items']) > 0) {
            $sheet->getStyle('A1:W1')->applyFromArray($header);
            $sheet->getRowDimension('1')->setRowHeight(30);
            $sheet->setCellValue("A1", 'Ref Company_Name')->getColumnDimension('A')->setWidth(50);
            $sheet->setCellValue("B1", 'Cliente')->getColumnDimension('B')->setWidth(50);
            $sheet->setCellValue("C1", 'CPNJ')->getColumnDimension('C')->setWidth(50);
            $sheet->setCellValue("D1", 'Despachante')->getColumnDimension('D')->setWidth(50);
            $sheet->setCellValue("E1", 'Fatura')->getColumnDimension('E')->setWidth(50);
            $sheet->setCellValue("F1", 'Emissão')->getColumnDimension('F')->setWidth(50);
            $sheet->setCellValue("G1", 'Vencimento')->getColumnDimension('G')->setWidth(20);
            $sheet->setCellValue("H1", 'Parceiro')->getColumnDimension('H')->setWidth(20);
            $sheet->setCellValue("I1", 'Tipo Doc')->getColumnDimension('I')->setWidth(30);
            $sheet->setCellValue("J1", 'Documento')->getColumnDimension('J')->setWidth(20);
            $sheet->setCellValue("K1", 'Venda Bruta')->getColumnDimension('K')->setWidth(30);
            $sheet->setCellValue("L1", 'Custo')->getColumnDimension('L')->setWidth(30);
            $sheet->setCellValue("M1", 'Impostos')->getColumnDimension('M')->setWidth(30);
            $sheet->setCellValue("N1", 'Lucro Liquido')->getColumnDimension('N')->setWidth(30);
            $sheet->setCellValue("O1", 'Qtd 20')->getColumnDimension('O')->setWidth(30);
            $sheet->setCellValue("P1", 'Containers 20')->getColumnDimension('P')->setWidth(30);
            $sheet->setCellValue("Q1", 'Qtd 40')->getColumnDimension('Q')->setWidth(30);
            $sheet->setCellValue("R1", 'Containers 40')->getColumnDimension('R')->setWidth(30);
            $sheet->setCellValue("S1", 'Data Início ')->getColumnDimension('S')->setWidth(30);
            $sheet->setCellValue("T1", 'Data Final ')->getColumnDimension('T')->setWidth(30);
            $sheet->setCellValue("U1", 'Valor Mercadoria ')->getColumnDimension('U')->setWidth(30);
            $sheet->setCellValue("V1", 'Vendedor')->getColumnDimension('V')->setWidth(30);
            $sheet->setCellValue("W1", 'Status')->getColumnDimension('X')->setWidth(30);


            $valor_total = 0;
            $valor_custo = 0;
            $valor_lucro = 0;
            $valor_imposto_interno = 0;
            $comissao_total = 0;
            foreach ($faturas['items'] as $idx => $fatura) {
                $fatura_obj = (object) $fatura;

                $fat = new Fatura($fatura_obj->id_fatura);
                $fat->comissoes;
                if ( isset($fat->comissao) and isset($fat->comissao['valor_comissao_total']) ) {
                    $comissao_total += $fat->comissao['valor_comissao_total'];
                }

                // echo $fat->valorimposto;
                // $criteria = new Criteria;
                // $criteria->add(new Filter('dd', '=', 1));
                // print_r($fatura_obj->fatura_numero);
                // exit();

                $valor_total += $fat->valor;
                $valor_lucro += $fat->valor_lucro;
                $valor_custo += $fatura_obj->valor_custo;
                $valor_imposto_interno += $fat->imposto_interno_valor;
                $idx = $idx+2;
                if ( $fat->captacao->isLoaded() ) {
                    $sheet->setCellValue("A{$idx}", $fat->captacao->numero)->getStyle("A{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("B{$idx}", $fatura_obj->cliente_nome)->getStyle("B{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("C{$idx}", $fat->captacao->proposta->cliente->identificador)->getStyle("C{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("D{$idx}", $fat->captacao->despachante_nome)->getStyle("D{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("E{$idx}", $fatura_obj->fatura_numero)->getStyle("E{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("F{$idx}", 
                        $fatura_obj->dta_emissao
                        ? date('d-m-Y', strtotime($fatura_obj->dta_emissao))
                        : null
                    )->getStyle("F{$idx}")->applyFromArray($row);
                    
                    $sheet->setCellValue("G{$idx}", 
                        $fatura_obj->dta_vencimento
                        ? date('d-m-Y', strtotime($fatura_obj->dta_vencimento))
                        : null
                    )->getStyle("G{$idx}")->applyFromArray($row);                
                    
                    $sheet->setCellValue("H{$idx}", $fatura_obj->terminal_primario)->getStyle("H{$idx}")->applyFromArray($row);

                    $sheet->setCellValue("I{$idx}", 
                        !is_null($fat->captacao->liberacao->tipo_documento) 
                        ? $fat->captacao->liberacao->tipo_documento
                        : null
                    )->getStyle("I{$idx}")->applyFromArray($row);

                    $sheet->setCellValue("J{$idx}", 
                        !is_null($fat->captacao->liberacao->documento) 
                        ? $fat->captacao->liberacao->documento
                        : null
                    )->getStyle("J{$idx}")->applyFromArray($row);
                    
                    $sheet->setCellValue("K{$idx}", $fat->valor)->getStyle("K{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("L{$idx}", $fatura_obj->valor_custo)->getStyle("L{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("M{$idx}", $fat->imposto_interno_valor)->getStyle("M{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("N{$idx}", $fat->valor_lucro)->getStyle("N{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("O{$idx}", $fat->captacao->qtdcontainer['20'])->getStyle("O{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("P{$idx}", $fat->captacao->container20)->getStyle("P{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("Q{$idx}", $fat->captacao->qtdcontainer['40'])->getStyle("Q{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("R{$idx}", $fat->captacao->container40)->getStyle("R{$idx}")->applyFromArray($row);

                    $sheet->setCellValue("S{$idx}", 
                        $fat->captacao->dta_atracacao
                        ? date('d-m-Y', strtotime($fat->captacao->dta_atracacao))
                        : null
                    )->getStyle("S{$idx}")->applyFromArray($row);

                    $sheet->setCellValue("T{$idx}", 
                        ( !is_null($fat->captacao->liberacao->dta_saida_terminal) )
                        ? date('d-m-Y', strtotime($fat->captacao->liberacao->dta_saida_terminal))
                        : null
                    )->getStyle("T{$idx}")->applyFromArray($row);

                    $sheet->setCellValue("U{$idx}", 
                        !is_null($fat->captacao->liberacao->valor_mercadoria) 
                        ? $fat->captacao->liberacao->valor_mercadoria
                        : null
                    )->getStyle("U{$idx}")->applyFromArray($row);

                    $sheet->setCellValue("V{$idx}", $fat->captacao->proposta->vendedor->nome)->getStyle("V{$idx}")->applyFromArray($row);
                    $sheet->setCellValue("W{$idx}", $fatura_obj->status)->getStyle("W{$idx}")->applyFromArray($row);
                }
            }/////
            // Bordar geral
            // $sheet->getStyle("A1:F{$idx}")->applyFromArray($allRow);

            $total_faturas = $idx+3;
            $margem_lucro = '0%';
            if ($valor_lucro > 0 and $valor_total > 0)
                $margem_lucro = round(($valor_lucro / $valor_total) * 100, 2) . '%';
            $valor_total = 'R$' . number_format( $valor_total , 2, ',', '.');
            $valor_lucro = 'R$' . number_format( $valor_lucro , 2, ',', '.');
            $valor_custo = 'R$' . number_format( $valor_custo , 2, ',', '.');
            $comissao_total = 'R$' . number_format( $comissao_total , 2, ',', '.');
            $valor_imposto_interno = 'R$' . number_format( $valor_imposto_interno , 2, ',', '.');

            $sheet->getRowDimension($total_faturas)->setRowHeight(30);
            $sheet->setCellValue("A{$total_faturas}", 'Total de Faturas:')->getStyle("A{$total_faturas}:B{$total_faturas}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$total_faturas}")->applyFromArray($header);

            $sheet->getStyle("B{$total_faturas}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$total_faturas}", $faturas['total_count']);
            
            $total_faturas = $total_faturas + 1;
            $sheet->getRowDimension($total_faturas)->setRowHeight(30);
            $sheet->setCellValue("A{$total_faturas}", 'Valor Total:')->getStyle("A{$total_faturas}:B{$total_faturas}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$total_faturas}")->applyFromArray($header);
            $sheet->getStyle("B{$total_faturas}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$total_faturas}", $valor_total);

            // $v_lucro = $v_total + 1;
            $total_faturas = $total_faturas + 1;
            $sheet->getRowDimension($total_faturas)->setRowHeight(30);
            $sheet->setCellValue("A{$total_faturas}", 'Valor Lucro:')->getStyle("A{$total_faturas}:B{$total_faturas}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$total_faturas}")->applyFromArray($header);
            $sheet->getStyle("B{$total_faturas}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$total_faturas}", $valor_lucro);

            $total_faturas = $total_faturas + 1;
            $sheet->getRowDimension($total_faturas)->setRowHeight(30);
            $sheet->setCellValue("A{$total_faturas}", 'Valor Custo:')->getStyle("A{$total_faturas}:B{$total_faturas}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$total_faturas}")->applyFromArray($header);
            $sheet->getStyle("B{$total_faturas}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$total_faturas}", $valor_custo);

            $total_faturas = $total_faturas + 1;
            $sheet->getRowDimension($total_faturas)->setRowHeight(30);
            $sheet->setCellValue("A{$total_faturas}", 'Imposto Interno:')->getStyle("A{$total_faturas}:B{$total_faturas}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$total_faturas}")->applyFromArray($header);
            $sheet->getStyle("B{$total_faturas}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$total_faturas}", $valor_imposto_interno);

            // $total_faturas = $total_faturas + 1;
            // $sheet->getRowDimension($total_faturas)->setRowHeight(30);
            // $sheet->setCellValue("A{$total_faturas}", 'Comissoes:')->getStyle("A{$total_faturas}:B{$total_faturas}")
            //     ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            // $sheet->getStyle("A{$total_faturas}")->applyFromArray($header);
            // $sheet->getStyle("B{$total_faturas}")
            //     ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            // $sheet->setCellValue("B{$total_faturas}", $comissao_total);

            $total_faturas = $total_faturas + 1;
            $sheet->getRowDimension($total_faturas)->setRowHeight(30);
            $sheet->setCellValue("A{$total_faturas}", 'Margem de Lucro:')->getStyle("A{$total_faturas}:B{$total_faturas}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$total_faturas}")->applyFromArray($header);
            $sheet->getStyle("B{$total_faturas}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$total_faturas}", $margem_lucro);

        } 
    
        // OUTPUT
        $writer = new Xlsx($spreadsheet);
    
    
        // OR FORCE DOWNLOAD
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Faturas Totais.xlsx"');
        header('Cache-Control: max-age=0');
        header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $writer->save('php://output');

        self::closeTransaction();
      }
}
