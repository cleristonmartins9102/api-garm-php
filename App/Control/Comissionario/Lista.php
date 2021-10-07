<?php

namespace App\Control\Comissionario;

use App\Mvc\Controller;
use App\Model\Comissionario\VwComissionario;
use App\Model\Comissionario\Comissionario;
use App\Model\Imposto\Imposto;
use App\Lib\Database\Repository;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{
    public function all()
    {
        self::openTransaction();
        $comissionarios = (new VwComissionario)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($comissionarios);
        $dataFull['items'] = array();
        foreach ($comissionarios as $key => $comissionario) {
            $dataFull['items'][] = $comissionario->toArray();
        }
        self::closeTransaction();
        return $dataFull;
    }

    public function byid(Request $request, Response $response, $id) {
        self::openTransaction();
        $comissionario = (new Comissionario($id['id']))->toArray();
        self::closeTransaction();
        return $comissionario;
    }

    private function getImposto($id_imposto): Imposto {
        return new Imposto($id_imposto);
    }

    public function filtered(Request $request, Response $response, Array $filter)
    {
        self::openTransaction();
        $dataFull = array();
        $dataFull['items'] = array();
        $param['columns'] = (new VwComissionario())->getColTable();
        $criteria = parent::filterColunm($filter);
        $repository = new Repository('App\Model\Comissionario\VwComissionario');
        $dataFull['total_count'] = count($repository->load(parent::filterColunm($filter, false)));
        $object = $repository->load($criteria);
        foreach ($object as $idx => &$comissionario) {
            // $fatura->proposta = $fatura->processo->captacao->proposta->numero;
            // $fatura->complementos = [ 'contêiner' => [] ];
            $dataFull['items'][] = $comissionario->toArray();
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
        $comissionarios = json_decode($this->filtered($request, $response, $filter), true);
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
        $sheet->setTitle('Relatório de Comissionários');
        self::openTransaction();
        if ( isset($comissionarios['items']) and count($comissionarios['items']) > 0) {
            $sheet->getStyle('A1:F1')->applyFromArray($header);
            $sheet->getRowDimension('1')->setRowHeight(30);
            $sheet->setCellValue("A1", 'Comissionado')->getColumnDimension('A')->setWidth(50);
            $sheet->setCellValue("B1", 'Classificação')->getColumnDimension('B')->setWidth(20);
            $sheet->setCellValue("C1", 'Unidade de Cobrança')->getColumnDimension('C')->setWidth(30);
            $sheet->setCellValue("D1", 'Valor da Comissão')->getColumnDimension('D')->setWidth(20);
            $sheet->setCellValue("E1", 'Criado em')->getColumnDimension('E')->setWidth(30);
            $sheet->setCellValue("F1", 'Data de Modificação')->getColumnDimension('F')->setWidth(30);
            $valor_total = 0;
            $valor_custo = 0;
            $valor_lucro = 0;
            foreach ($comissionarios['items'] as $idx => $comissionario) {
                $comissionario = (object) $comissionario;
                $idx = $idx+2;
                $sheet->setCellValue("A{$idx}", $comissionario->comissionado)->getStyle("A{$idx}")->applyFromArray($row);;
                $sheet->setCellValue("B{$idx}", $comissionario->tipo)->getStyle("B{$idx}")->applyFromArray($row);;
                $sheet->setCellValue("C{$idx}", $comissionario->unicob)->getStyle("C{$idx}")->applyFromArray($row);;
                $sheet->setCellValue("D{$idx}", $comissionario->valor_comissao)->getStyle("D{$idx}")->applyFromArray($row);;
                $sheet->setCellValue("E{$idx}", date('d/m/Y h:m', strtotime($comissionario->created_at)))->getStyle("E{$idx}")->applyFromArray($row);;
                $sheet->setCellValue("F{$idx}", is_null($comissionario->updated_at) ? null : date('d/m/Y h:m', strtotime($comissionario->updated_at)))->getStyle("F{$idx}")->applyFromArray($row);;
      
            }

            // Bordar geral
            // $sheet->getStyle("A1:F{$idx}")->applyFromArray($allRow);

            $idx = $idx+3;


            
            $sheet->getRowDimension($idx)->setRowHeight(30);
            $sheet->setCellValue("A{$idx}", 'Total de Comissionários:')->getStyle("A{$idx}:B{$idx}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$idx}")->applyFromArray($header);

            $sheet->getStyle("B{$idx}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$idx}", $comissionarios['total_count']);
            // $sheet->setCellValue("H{$idx}", round($valor_custo, 2));
            // $sheet->setCellValue("I{$idx}", round($valor_lucro, 2));


        } 
    
        // OUTPUT
        $writer = new Xlsx($spreadsheet);
    
        // OR FORCE DOWNLOAD
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Relatório de Comissionários.xlsx"');
        header('Cache-Control: max-age=0');
        header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $writer->save('php://output');

        self::closeTransaction();
      }
}
