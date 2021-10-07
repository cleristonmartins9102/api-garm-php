<?php
namespace App\Control\Liberacao;

use App\Mvc\Controller;
use App\Model\Pessoa\Individuo;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Liberacao\Liberacao;
use App\Model\Liberacao\VwLiberacao;
use App\Model\Liberacao\LiberacaoDocumento;
use App\Model\Liberacao\LiberacaoStatus;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{
    public function all(Request $request, Response $response, Array $param=null)
    {
      try {
        self::openTransaction();
        $object = (new VwLiberacao)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        $dataModificated['complementos'] = array();
  
        $criteria = parent::criteria($param);
        $repository = new Repository('App\Model\Liberacao\VwLiberacao');
        $object = $repository->load($criteria);
        foreach ($object as $idx => &$liberacao) {
          $liberacao->proposta = $liberacao->captacao->proposta->numero;
          $liberacaoArr = $liberacao->getData();
          $liberacaoArr['complementos']['documento'] = $liberacao->anexo;
          $liberacaoArr['complementos']['contêiner'] = $liberacao->container;
          // echo '<pre>';
          // print_r($liberacao->captacao->eventos);
          $liberacaoArr['complementos']['eventos'] = array_merge($liberacao->captacao->eventos, $liberacao->eventos);
          $liberacaoArr['complementos']['relevantes'][] = [
            'dta_atracacao' => $liberacao->dta_atracacao,
            'dta_saida_terminal' => $liberacao->dta_saida_terminal,
          ];
          $dataFull['items'][] = $liberacaoArr;
        }
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return isset($dataFull) ? $dataFull : null;
    }
    // delete from Fatura where 1
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
        $liberacao->ref_importador = $liberacao->captacao->ref_importador;
        $liberacao->isInLote = true;
        if (!$liberacao->captacao->isInLote())
          $liberacao->isInLote = false;
        
        $liberacao->locked = false;

        if ( $liberacao->isFaturado()['result'] )
          $liberacao->locked = ( $liberacao->isFaturado()['message']->enviadaParaCliente() and $liberacao->isFaturado()['message']->status->status !== 'Cancelada' );

        foreach ($documentos as $key => $documento) {
          $anexos[] = [ 
            'id_tipodocumento' => $documento->tipo_documento, 
            'nome_anexo' => $documento->nomeoriginal_upload ];
        }
        $liberacao->anexos = $anexos ?? [];
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return json_encode($liberacao->toArray());
    }

    public function mail(Request $request, Response $response, $id_liberacao = null)
    {
      self::openTransaction();
      $liberacao = new Liberacao($id_liberacao);
      $criteria = new Criteria;
      $criteria->add(new Filter('nome', '=', 'liberacao'));
      $repository = new Repository('App\Model\Aplicacao\Aplicacao');
      $aplicacao = $repository->load($criteria);
      $aplicacao_contato = ( count($aplicacao) > 0 and count($aplicacao[0]->listademodulo) > 0 ) ? $aplicacao[0]->listademodulo[0]->contatos : null;
      
      // $contatos_do_grupo = $liberacao->captacao->proposta->grupodecontato->contatos;
      // $contatos = $liberacao->captacao->proposta->allgrupodecontato;
      $contatos = $liberacao->captacao->grupodecontato;

      // if (count($contatos_do_grupo) > 0) {
      //   foreach ($contatos_do_grupo as $key => $contato) {
      //     $contatos[] = $contato->toArray();
      //   }
      // }
      // if ($aplicacao_contato)
      //   $contatos[] = $aplicacao_contato->toArray();
      
      self::closeTransaction();
      return isset($contatos) ? json_encode($contatos) : json_encode(array('sem contatos'));
    }
  

    public function bystatus(Request $request, Response $response, \stdClass $statusObj = null) {
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

     /**
   * Metodo para buscar o body do email
   * @param number $id_liberacao ID da Liberação
   */
    public function boem(Request $request, Response $response, $data) {
      $data = (object) $data;

      if ( !isset($data->id_liberacao) and is_null($data->id_liberacao) or (!isset($data->bodyName) and is_null($data->bodyName) ))
        return 'Dados incompletos';

      self::openTransaction();
      $liberacao = new Liberacao($data->id_liberacao);
      if (!$liberacao->isLoaded())
        return 'Liberação não carregada';

      switch ($data->bodyName) {
        case 'soldidta':
          $email['subject'] = $liberacao->subSolDiDta($liberacao);
          $email['body'] = $liberacao->bodymail('bodySolDiDta');
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
      try {
        self::openTransaction();
        $dataFull = array();
        $dataFull['items'] = array();
        $dataModificated['complementos'] = array();
        $param['columns'] = (new VwLiberacao())->getColTable();
        $criteria = parent::filterColunm($filter);
        $repository = new Repository('App\Model\Liberacao\VwLiberacao');
        $object = $repository->load($criteria);
        $dataFull['total_count'] = count($object);

        foreach ($object as $idx => $liberacao) {
          $liberacaoArr = $liberacao->getData();
          $liberacaoArr['complementos']['eventos'] = $liberacao->captacao->eventos;
          $liberacaoArr['complementos']['documento'] = $liberacao->anexo;
          $liberacaoArr['complementos']['contêiner'] = $liberacao->container;
          $liberacaoArr['complementos']['relevantes'][] = [
            'dta_atracacao' => $liberacao->dta_atracacao,
            'dta_saida_terminal' => $liberacao->dta_saida_terminal,
          ];
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
      $liberacoes = json_decode($this->filtered($request, $response, $filter), true);

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
      $sheet->setTitle('Relatório de Liberações');
      self::openTransaction();
      if ( isset($liberacoes['items']) and count($liberacoes['items']) > 0) {
          $sheet->getStyle('A1:M1')->applyFromArray($header);
          $sheet->getRowDimension('1')->setRowHeight(30);
          $sheet->setCellValue("A1", 'Ref. Company_Name')->getColumnDimension('A')->setWidth(20);
          $sheet->setCellValue("B1", 'Ref. Importador')->getColumnDimension('B')->setWidth(20);
          $sheet->setCellValue("C1", 'Importador')->getColumnDimension('C')->setWidth(80);
          $sheet->setCellValue("D1", 'Despachante')->getColumnDimension('D')->setWidth(80);
          $sheet->setCellValue("E1", 'BL')->getColumnDimension('E')->setWidth(20);
          $sheet->setCellValue("F1", 'CNTR')->getColumnDimension('F')->setWidth(20);
          $sheet->setCellValue("G1", 'Data Atracação')->getColumnDimension('G')->setWidth(20);
          $sheet->setCellValue("H1", 'Terminal')->getColumnDimension('H')->setWidth(30);
          $sheet->setCellValue("I1", 'DI/DTA')->getColumnDimension('I')->setWidth(10);
          $sheet->setCellValue("J1", 'Data Recebimento')->getColumnDimension('J')->setWidth(20);
          $sheet->setCellValue("K1", 'Data Liberação')->getColumnDimension('K')->setWidth(20);
          $sheet->setCellValue("L1", 'Data Saída Terminal')->getColumnDimension('L')->setWidth(20);
          $sheet->setCellValue("M1", 'Observações')->getColumnDimension('M')->setWidth(20);
          $valor_total = 0;
          $valor_custo = 0;
          $valor_lucro = 0;
          $qtd_cntr = 0;
          foreach ($liberacoes['items'] as $idx => $liberacao) {
              $liberacao = (object) $liberacao;
              $lib = new Liberacao($liberacao->id_liberacao);
              $historico = '';
              $hist = $lib->get_historico('ocacional');
              foreach ($hist as $id => $his) {
                $his = (object) $his;
                $ocorrencia = $his->ocorrencia;
                if ( $id > 0 )
                  $historico .= "/";

                $historico .= $ocorrencia;
              }

          
              $idx = $idx+2;
              $sheet->setCellValue("A{$idx}", $liberacao->ref_gralsin)->getStyle("A{$idx}")->applyFromArray($row);
              $sheet->setCellValue("B{$idx}", $liberacao->ref_importador)->getStyle("B{$idx}")->applyFromArray($row);
              $sheet->setCellValue("C{$idx}", $liberacao->importador_nome)->getStyle("C{$idx}")->applyFromArray($row);
              $sheet->setCellValue("D{$idx}", $lib->captacao->despachante->nome)->getStyle("D{$idx}")->applyFromArray($row);
              $sheet->setCellValue("E{$idx}", $liberacao->bl)->getStyle("E{$idx}")->applyFromArray($row);
              $sheet->setCellValue("F{$idx}", str_replace('<br>', '/', $liberacao->container))->getStyle("F{$idx}")->applyFromArray($row);
              $sheet->setCellValue("G{$idx}",
                $liberacao->dta_atracacao
                  ? date('d-m-Y', strtotime($liberacao->dta_atracacao))
                  : null
              )->getStyle("G{$idx}")->applyFromArray($row);

              $sheet->setCellValue("H{$idx}", $liberacao->terminal_redestinacao)->getStyle("H{$idx}")->applyFromArray($row);
              $sheet->setCellValue("I{$idx}", $liberacao->documento)->getStyle("I{$idx}")->applyFromArray($row);
              $sheet->setCellValue("J{$idx}", 
                $liberacao->dta_recebimento_doc 
                  ? date('d-m-Y',  strtotime($liberacao->dta_recebimento_doc))
                  : null
              )->getStyle("J{$idx}")->applyFromArray($row);

              $sheet->setCellValue("K{$idx}",
                $lib->dta_liberacao 
                  ? date('d-m-Y',  strtotime($lib->dta_liberacao))
                  : null
              )->getStyle("K{$idx}")->applyFromArray($row);
              
              $sheet->setCellValue("L{$idx}",
                $liberacao->dta_saida_terminal 
                ? date('d-m-Y',  strtotime($liberacao->dta_saida_terminal))
                : null
              )->getStyle("L{$idx}")->applyFromArray($row);
              
              if ($lib->id_captacao === '4672') {
                // echo $historico;
                // exit();
              }
              $sheet->setCellValue("M{$idx}", $historico)->getStyle("M{$idx}")->applyFromArray($row);
              // $qtd_cntr += $comissao->qtd_cntr;
              // $sheet->setCellValue("F{$idx}", is_null($comissionario->updated_at) ? null : date('d/m/Y h:m', strtotime($comissionario->updated_at)))->getStyle("F{$idx}")->applyFromArray($row);;
          }
          // exit();
          // Bordar geral
          // $sheet->getStyle("A1:F{$idx}")->applyFromArray($allRow);

          $idx = $idx+3;


          
          $sheet->getRowDimension($idx)->setRowHeight(30);
          $sheet->setCellValue("A{$idx}", 'Total de Liberações:')->getStyle("A{$idx}:B{$idx}")
              ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
          $sheet->getStyle("A{$idx}")->applyFromArray($header);

          $sheet->getStyle("B{$idx}")
              ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
          $sheet->setCellValue("B{$idx}", $liberacoes['total_count']);
          // $sheet->setCellValue("H{$idx}", round($valor_custo, 2));
          // $sheet->setCellValue("I{$idx}", round($valor_lucro, 2));


      } 
  
      // OUTPUT
      $writer = new Xlsx($spreadsheet);
  
      // OR FORCE DOWNLOAD
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="Relatório de Liberação.xlsx"');
      header('Cache-Control: max-age=0');
      header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
      header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Cache-Control: cache, must-revalidate');
      header('Pragma: public');
      $writer->save('php://output');

      self::closeTransaction();
    }
}