<?php
namespace App\Control\Vendedor;

use App\Mvc\Controller;
use App\Model\Vendedor\Vendedor;
use App\Model\Vendedor\VwVendedor;
use App\Lib\Database\Transaction;
use App\Lib\Database\Repository;
use App\Lib\Database\Criteria;
use App\Lib\Database\Filter;
use App\Model\Pessoa\Endereco;
use App\Model\Pessoa\Papel;
use App\Model\Pessoa\PessoaFisica;
use App\Model\Pessoa\PessoaJuridica;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{ 
    private $data;
    
    public function all(Request $request, Response $response)
    {
          try{
            Transaction::open('zoho');
              $vendedores = (new VwVendedor)->all();
              $dataFull = array();
              $dataFull['total_count'] = count($vendedores);
              $dataFull['items'] = array();
              foreach ($vendedores as $key => $vendedor) {
                $dataFull['items'][] = $vendedor->getData();
              }              
            Transaction::close();
            usort($dataFull['items'], function($item1, $item2){
              return $item1['nome'] <=> $item2['nome'];
            });
            return isset($dataFull) ? $dataFull : null;
          }
          catch (Exception $e)
          {
            echo $e->getMessage();
          }
    }

    public function alldropdown() {
      self::openTransaction();
      $vendedores = (new Vendedor)->all();
      foreach ($vendedores as $key => $vendedor) {
        $vendedor->nome = $vendedor->pessoa_nome;
        $dataFull['items'][] = $vendedor->getData();
      }        
      self::closeTransaction();
      return isset($dataFull) ? $dataFull : null;
    }

    public function find(Request $request, Response $response, $id = null)
    { 
      if ($id != null){
        Transaction::open('zoho');
        $object = new Individuo($id);
        Transaction::close();
        if (!empty($object->nome)){
          return json_encode($this->prepare($object));
        }else{
          return null;
        }
      }
    }

    public function byid(Request $request, Response $response, $id = null)
    { 
      if ($id != null){
        self::openTransaction();
        $vendedor = new Vendedor($id['id']);
        self::closeTransaction();
        return $vendedor->toArray();
      }
    }

    public function filtered(Request $request, Response $response, Array $filter=null)
    {
      try {
        self::openTransaction();
        $object = (new VwVendedor)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($object);
        $dataFull['items'] = array();
        $dataModificated['complementos'] = array();
        $criteria = parent::filterColunm($filter);
        $repository = new Repository('App\Model\Vendedor\VwVendedor');
        $object = $repository->load($criteria);
        foreach ($object as $idx => &$vendedor) {
          $vendedorArr = $vendedor->getData();
          // $pacoteArr['complementos']['items'] = $pacote->item;
          $dataFull['items'][] = $vendedorArr;
        }
        self::closeTransaction();
      } catch (Exception $e) {
        echo $e->getMessage();
      }
      return json_encode(isset($dataFull) ? $dataFull : null);

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
      $vendedores = json_decode($this->filtered($request, $response, $filter), true);
  
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
      $sheet->setTitle('Relatório de Vendedores');
      self::openTransaction();
      if ( isset($vendedores['items']) and count($vendedores['items']) > 0) {
          $sheet->getStyle('A1:D1')->applyFromArray($header);
          $sheet->getRowDimension('1')->setRowHeight(30);
          $sheet->setCellValue("A1", 'Nome')->getColumnDimension('A')->setWidth(30);
          $sheet->setCellValue("B1", 'Propostas Emitidas')->getColumnDimension('B')->setWidth(50);
          $sheet->setCellValue("C1", 'Propostas Ativas')->getColumnDimension('C')->setWidth(20);
          $sheet->setCellValue("D1", 'Status')->getColumnDimension('D')->setWidth(20);

          $line = 1;
          foreach ($vendedores['items'] as $idx => $vendedor) {
              $line++;
              $vendedor_obj = (object) $vendedor;
              $vendedor = new Vendedor($vendedor_obj->id_vendedor);  
              $sheet->setCellValue("A{$line}", $vendedor_obj->nome)->getStyle("A{$line}")->applyFromArray($row);
              $sheet->setCellValue("B{$line}", $vendedor->qtd_proposta)->getStyle("B{$line}")->applyFromArray($row);
              $sheet->setCellValue("C{$line}", $vendedor->qtd_proposta_ativa)->getStyle("C{$line}")->applyFromArray($row);
              $sheet->setCellValue("D{$line}", $vendedor_obj->status)->getStyle("D{$line}")->applyFromArray($row);
          }
  
          // Bordar geral
          // $sheet->getStyle("A1:F{$line}")->applyFromArray($allRow);
  
          // $margem_lucro = round(($valor_lucro / $valor_total) * 100, 2) . '%';
          // $valor_total = 'R$' . number_format( $valor_total , 2, ',', '.');
          // $valor_lucro = 'R$' . number_format( $valor_lucro , 2, ',', '.');
          // $valor_custo = 'R$' . number_format( $valor_custo , 2, ',', '.');
          // $comissao_total = 'R$' . number_format( $comissao_total , 2, ',', '.');
          // $valor_imposto_interno = 'R$' . number_format( $valor_imposto_interno , 2, ',', '.');
          $line++;
          $line++;
          $sheet->getRowDimension($line)->setRowHeight(30);
          $sheet->setCellValue("A{$line}", 'Total de Vendedores:')->getStyle("A{$line}:B{$line}")
              ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
          $sheet->getStyle("A{$line}")->applyFromArray($header);
  
          $sheet->getStyle("B{$line}")
              ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
          $sheet->setCellValue("B{$line}", $vendedores['total_count']);
  
          // $line++;
          // $line++;
          // foreach ($contagem['tipo'] as $tipo=>$qtd) {
          //   $sheet->getRowDimension($line)->setRowHeight(30);
          //   $sheet->setCellValue("A{$line}", ucfirst($tipo))->getStyle("A{$line}:B{$line}")
          //       ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
          //   $sheet->getStyle("A{$line}")->applyFromArray($header);
  
          //   $sheet->getStyle("B{$line}")
          //       ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
          //   $sheet->setCellValue("B{$line}", $qtd);
          //   $line++;
          // }
    
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
        header('Content-Disposition: attachment;filename="Relatório de Propostas.xlsx"');
        header('Cache-Control: max-age=0');
        header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $writer->save('php://output');
  
        self::closeTransaction();
  }
}
