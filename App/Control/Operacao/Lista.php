<?php
namespace App\Control\Operacao;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Liberacao\Liberacao;
use App\Model\Liberacao\VwLiberacao;

use App\Model\Operacao\VwOperacao;
use App\Model\Captacao\Captacao;
use App\Model\Captacao\CaptacaoEvento;
use App\Model\Processo\Processo;
use App\Model\Operacao\VwOperacaoExportacao;
use App\Model\Captacao\VwCaptacaoLote;
use App\Model\Captacao\CaptacaoLote;

use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{
  public function all(Request $request, Response $response, array $param = null)
  {
    try {
      self::openTransaction();

      // $criteria = new Criteria;
      // $criteria->add(new Filter('created_at', '>=', '2020-10-01'));
      // $repo = (new Repository(Processo::class))->load($criteria);
      // foreach($repo as $processo) {
      //   $criteria->clean();
      //   $criteria->add(new Filter('id_captacao', '=', $processo->id_captacao));
      //   $criteria->add(new Filter('evento', '=', 'g_processo'));
      //   $captacao_eventos = (new Repository(CaptacaoEvento::class))->load($criteria);
      //   // print_r($captacao_eventos);
      //   if ( !$processo->isLote() && count($captacao_eventos) === 0 ) {
      //     // print_r($processo->movimentacao );
      //     $processo->movimentacao->addEvento('g_processo', $processo->id ?? $processo->id_processo, $processo);
      //   }
      // }
      $vw_operacao_captacao = (new VwOperacao)->all();
      $vw_operacao_despacho = (new VwOperacaoExportacao())->all();
      $dataFull = array();
      $dataFull['total_count'] = count($vw_operacao_captacao) + count($vw_operacao_despacho);
      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();
      $criteria = parent::criteria($param);
      
      $vw_captacao_lote = (new Repository('App\Model\Captacao\VwCaptacaoLote'))->load($criteria);
      $vw_operacao_captacao = (new Repository('App\Model\Operacao\VwOperacao'))->load($criteria);
      $vw_operacao_despacho = (new Repository('App\Model\Operacao\VwOperacaoExportacao'))->load($criteria);
     
      // $operacoes = [];
      foreach ($vw_operacao_captacao as $idx => &$operacao) {
        $liberacaoArr = $operacao->getData();
        $liberacaoArr['complementos']['documento'] = $operacao->anexo;
        $liberacaoArr['complementos']['eventos'] = (new Captacao($operacao->id_captacao))->eventos;
        // print_r((new Captacao($operacao->id_captacao))->eventos);
        $liberacaoArr['complementos']['contêiner'] = (new Captacao($operacao->id_captacao))->listacontainer;
        $liberacaoArr['complementos']['relevantes'][] = [
          'dta_atracacao' => $operacao->dta_atracacao,
          // 'dta_saida_terminal' => $operacao->dta_saida_terminal,
        ];
        $dataFull['items'][] = $liberacaoArr;
      // $operacoes = self::checkLoteFound($operacoes, $liberacaoArr);
      }
      // $dataFull['items'] = $operacoes;

      foreach($vw_captacao_lote as &$lote) {
        $lote->validate();
        $lote->id_operacao = $lote->id_captacaolote;
        $lote->id_captacao = $lote->captacao;
        $lote->cliente_nome = $lote->cliente_nome;
        $lote->container = implode('<br>', array_unique(explode(',', $lote->container)));
        $liberacaoArr = $lote->toArray();
        $liberacaoArr['complementos']['documento'] = $operacao->anexo;
        $liberacaoArr['complementos']['eventos'] = (new CaptacaoLote($lote->id_captacaolote))->eventos;
        $liberacaoArr['complementos']['contêiner'] = (new CaptacaoLote($lote->id_captacaolote))->container;
        $liberacaoArr['complementos']['relevantes'][] = [];
        $dataFull['items'][] = $liberacaoArr;
      }

      foreach ($vw_operacao_despacho as $idx => &$despacho) {
        $despacho->regime_legenda = $despacho->proposta->regime->legenda;
        $despacho->cliente = $despacho->proposta->cliente;
        $complementos = [
          'eventos' => $despacho->eventos,
          'contêiner' => null
        ];
        $despacho->container = null;
        foreach($despacho->containeres as $container) {
          $despacho->container .= $container['codigo'] . "<br>";
        }
        $despacho->complementos = $complementos;
        $dataFull['items'][] = $despacho->toArray();
      }
      self::closeTransaction();

      usort($dataFull['items'], function ($app1, $app2) {
        return $app2['created_at'] <=> $app1['created_at'];
    });

      // echo "<pre>";
      // print_r($vw_operacao_despacho);exit();
      self::closeTransaction();
    } catch (Exception $e) {
      echo $e->getMessage();
    }
    // exit();

    return isset($dataFull) ? $dataFull : null;
  }

  public function alldropdown(Request $request, Response $response)
  {
    self::openTransaction();
    $object = (new VwOperacao)->all();
    $dataFull = array();
    $dataFull['total_count'] = count($object);
    $dataFull['items'] = array();
    foreach ($object as $key => $value) {
      $captacaoArr['id_captacao'] = $value->id_captacao;
      $captacaoArr['id_proposta'] = $value->id_proposta;
      $captacaoArr['numero'] = $value->ref_gralsin;
      $captacaoArr['bl'] = $value->bl;
      $dataFull['items'][] = $captacaoArr;
    }
    self::closeTransaction();
    usort($dataFull['items'], function ($item1, $item2) {
      return $item2['id_captacao'] <=> $item1['id_captacao'];
    });
    return isset($dataFull) ? $dataFull : null;
  }

  public function byid(Request $request, Response $response, $id_liberacao = null)
  {
    try {
      self::openTransaction();
      $liberacao = new Liberacao($id_liberacao);
      // Pegando os documentos da liberacao
      $criteria = new Criteria();
      $criteria->add(new Filter('id_liberacao', '=', $id_liberacao));
      $repository = new Repository('App\Model\Liberacao\LiberacaoDocumento');
      $documentos = $repository->load($criteria);
      $object = $liberacao->toArray();
      $object['ref_importador'] = $liberacao->captacao->ref_importador;
      foreach ($documentos as $key => $documento) {
        $object['anexos'][] = ['id_tipodocumento' => $documento->id_tipodocumento, 'nome_anexo' => $documento->nomeoriginal_upload];
      }
      self::closeTransaction();
    } catch (Exception $e) {
      echo $e->getMessage();
    }
    return json_encode(isset($object) ? $object : null);
  }

  public function bystatus(Request $request, Response $response, \stdClass $statusObj = null)
  {
    self::openTransaction();
    $criteria = new Criteria;
    $criteria->add(new Filter('status', '=', 'aguardando DI/DTA'));
    $id_liberacaostatus = (new Repository('App\Model\Liberacao\LiberacaoStatus'))->load($criteria);
    $criteria->clean();
    $criteria->add(new Filter('id_liberacaostatus', '!=', $id_liberacaostatus[0]->id_liberacaostatus));
    $liberacaoObj = (new Repository('App\Model\Liberacao\VwLiberacao'))->load($criteria);
    $dataFull['total_count'] = count($liberacaoObj);
    $dataFull['items'] = array();
    foreach ($liberacaoObj as $key => $liberacao) {
      $liberacaoArr = $liberacao->getData();
      $liberacaoArr['complementos']['documento'] = $liberacao->anexo;
      $liberacaoArr['complementos']['contêiner'] = $liberacao->container;
      $liberacaoArr['complementos']['relevantes'][] = [
        'dta_atracacao' => $liberacao->dta_atracacao,
        'dta_saida_terminal' => $liberacao->dta_saida_terminal,
      ];
      $dataFull['items'][] = $liberacaoArr;
    }
    self::closeTransaction();
    print_r($dataFull);
  }

  public function filtered(Request $request, Response $response, array $filter)
  {
    try {
      self::openTransaction();
      $dataFull = array();
      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();
      $param['columns'] = (new VwLiberacao())->getColTable();
      $criteria = parent::filterColunm($filter);

      $dataFull['items'] = array();
      $dataModificated['complementos'] = array();
      
      $vw_operacao_captacao = (new Repository('App\Model\Operacao\VwOperacao'))->load($criteria);
      $vw_operacao_despacho = (new Repository('App\Model\Operacao\VwOperacaoExportacao'))->load($criteria);
      $vw_captacao_lote = (new Repository('App\Model\Captacao\VwCaptacaoLote'))->load($criteria);

      $dataFull['total_count'] = count($vw_operacao_captacao) + count($vw_operacao_despacho);

      $operacoes = [];
      // echo '<pre>';
      // print_r($vw_operacao_captacao);
      // exit();
      foreach ($vw_operacao_captacao as $idx => &$operacao) {
        $operacao->regime_legenda = $operacao->liberacao->captacao->proposta->regime->legenda;
        $liberacaoArr = $operacao->getData();
        $liberacaoArr['complementos']['documento'] = $operacao->anexo;
        $liberacaoArr['complementos']['eventos'] = (new Captacao($operacao->id_captacao))->eventos;
        $liberacaoArr['complementos']['contêiner'] = (new Captacao($operacao->id_captacao))->listacontainer;
        $liberacaoArr['complementos']['relevantes'][] = [
          'dta_atracacao' => $operacao->dta_atracacao,
          'dta_saida_terminal' => $operacao->dta_saida_terminal,
        ];
        $operacoes = self::checkLoteFound($operacoes, $liberacaoArr);
      }

      $dataFull['items'] = $operacoes;

      foreach ($vw_operacao_despacho as $idx => &$despacho) {
        $despacho->regime_legenda = $despacho->proposta->regime->legenda;
        $despacho->cliente = $despacho->proposta->cliente;
        $despacho->complementos = [ 'eventos' => $despacho->eventos];
        $despacho->complementos = [ 'conêiner' => $despacho->listacontainer];
        $dataFull['items'][] = $despacho->toArray();
      }

      foreach($vw_captacao_lote as &$lote) {
        $lote->validate();
        $lote->id_operacao = $lote->id_captacaolote;
        $lote->id_captacao = $lote->captacao;
        $lote->ref_gralsin = $lote->captacao;
        $lote->cliente_nome = $lote->cliente_nome;
        $lote->container = implode('<br>', array_unique(explode(',', $lote->container)));
        $liberacaoArr = $lote->toArray();
        // $liberacaoArr['complementos']['documento'] = $operacao->anexo;
        $liberacaoArr['complementos']['eventos'] = (new CaptacaoLote($lote->id_captacaolote))->eventos;
        $liberacaoArr['complementos']['contêiner'] = (new CaptacaoLote($lote->id_captacaolote))->container;
        $liberacaoArr['complementos']['relevantes'][] = [];
        $dataFull['items'][] = $liberacaoArr;
      }

      self::closeTransaction();
      return json_encode(isset($dataFull) ? $dataFull : null);
    } catch (Exception $e) {
      echo $e->getMessage();
    }
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
    $operacoes = json_decode($this->filtered($request, $response, $filter), true);

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
    $sheet->setTitle('Relatório de Operações');
    self::openTransaction();
    if ( isset($operacoes['items']) and count($operacoes['items']) > 0) {
        $sheet->getStyle('A1:F1')->applyFromArray($header);
        $sheet->getRowDimension('1')->setRowHeight(30);
        $sheet->setCellValue("A1", 'Cliente')->getColumnDimension('A')->setWidth(50);
        $sheet->setCellValue("B1", 'Regime')->getColumnDimension('B')->setWidth(20);
        $sheet->setCellValue("C1", 'Terminal')->getColumnDimension('C')->setWidth(30);
        $sheet->setCellValue("D1", 'Data de Atracação')->getColumnDimension('D')->setWidth(40);
        $sheet->setCellValue("E1", 'Data de Saída do Terminal')->getColumnDimension('E')->setWidth(40);
        $sheet->setCellValue("F1", 'Status')->getColumnDimension('F')->setWidth(20);
        foreach ($operacoes['items'] as $idx => $operacao) {
            $operacao_obj = (object) $operacao;
            $idx = $idx+2;
            $sheet->setCellValue("A{$idx}", $operacao_obj->cliente_nome)->getStyle("A{$idx}")->applyFromArray($row);
            $sheet->setCellValue("B{$idx}", $operacao_obj->regime_legenda)->getStyle("B{$idx}")->applyFromArray($row);
            $sheet->setCellValue("C{$idx}", $operacao_obj->terminal)->getStyle("C{$idx}")->applyFromArray($row);
            $sheet->setCellValue("D{$idx}", date('d/m/Y', strtotime($operacao_obj->dta_atracacao)))->getStyle("D{$idx}")->applyFromArray($row);
            $sheet->setCellValue("E{$idx}", date('d/m/Y', strtotime($operacao_obj->dta_saida_terminal)))->getStyle("E{$idx}")->applyFromArray($row);
            $sheet->setCellValue("F{$idx}", $operacao_obj->status)->getStyle("F{$idx}")->applyFromArray($row);
        }
        // Bordar geral
        // $sheet->getStyle("A1:F{$idx}")->applyFromArray($allRow);

        $idx = $idx+3;
        // $margem_lucro = round(($valor_lucro / $valor_total) * 100, 2) . '%';
        // $valor_total = 'R$' . number_format( $valor_total , 2, ',', '.');
        // $valor_lucro = 'R$' . number_format( $valor_lucro , 2, ',', '.');
        // $valor_custo = 'R$' . number_format( $valor_custo , 2, ',', '.');
        // $comissao_total = 'R$' . number_format( $comissao_total , 2, ',', '.');
        // $valor_imposto_interno = 'R$' . number_format( $valor_imposto_interno , 2, ',', '.');

        $sheet->getRowDimension($idx)->setRowHeight(30);
        $sheet->setCellValue("A{$idx}", 'Total de Operações:')->getStyle("A{$idx}:B{$idx}")
            ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$idx}")->applyFromArray($header);

        $sheet->getStyle("B{$idx}")
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        $sheet->setCellValue("B{$idx}", $operacoes['total_count']);
        
        // $idx = $idx + 1;
        // $sheet->getRowDimension($idx)->setRowHeight(30);
        // $sheet->setCellValue("A{$idx}", 'Valor Total:')->getStyle("A{$idx}:B{$idx}")
        //     ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        // $sheet->getStyle("A{$idx}")->applyFromArray($header);
        // $sheet->getStyle("B{$idx}")
        //     ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
        // $sheet->setCellValue("B{$idx}", $valor_total);

    } 

      // OUTPUT
      $writer = new Xlsx($spreadsheet);
  
  
      // OR FORCE DOWNLOAD
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="Relatório de Operações.xlsx"');
      header('Cache-Control: max-age=0');
      header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Cache-Control: cache, must-revalidate');
      header('Pragma: public');
      $writer->save('php://output');

      self::closeTransaction();
   }

  private function checkLoteFound($operacoes, &$operacao) {
    $operacao = (object) $operacao;
    $found = false;
    foreach ($operacoes as &$op) {
      if (( isset($op->lote) && !empty($op->lote) ) && ( isset($operacao->lote) && !empty($operacao->lote) ) && $op->lote === $operacao->lote) {
        $op->ref_gralsin .= "<br> {$operacao->ref_gralsin}";
        $op->status .= "<br> {$operacao->status}";
        $op->documento .= "<br> {$operacao->documento}";
        $op->cliente_nome .= "<br> {$operacao->cliente_nome}";
        $op->bl .= "<br> {$operacao->bl}";
        $found = true;
        break;
      } 
    }
  

    if (!$found) 
      $operacoes[] = $operacao;
    return $operacoes;
  } 
}

