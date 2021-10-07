<?php

namespace App\Control\Comissao;

use App\Mvc\Controller;
use App\Model\Comissionario\VwComissao;
use App\Model\Comissionario\Comissionario;
use App\Model\Imposto\Imposto;
use App\Lib\Database\Repository;
use Slim\Http\Response;
use Slim\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Lista extends Controller
{
    public function all(Request $request, Response $response, array $param)
    {
        self::openTransaction();
        $comissionarios = (new VwComissao)->all();
        $dataFull = array();
        $dataFull['total_count'] = count($comissionarios);  
        $dataFull['items'] = array();
        $criteria = parent::criteria($param);
        $repository = new Repository(VwComissao::class);
        $comissionarios = $repository->load($criteria);
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
        $param['columns'] = (new VwComissao())->getColTable();
        $criteria = parent::filterColunm($filter);
        $repository = new Repository(VwComissao::class);
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
        $comissoes = json_decode($this->filtered($request, $response, $filter), true);
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
        $sheet->setTitle('Relatório de Comissões');
        self::openTransaction();
        if ( isset($comissoes['items']) and count($comissoes['items']) > 0) {
            $sheet->getStyle('A1:J1')->applyFromArray($header);
            $sheet->getRowDimension('1')->setRowHeight(30);
            $sheet->setCellValue("A1", 'Importador')->getColumnDimension('A')->setWidth(50);
            $sheet->setCellValue("B1", 'Parceiro')->getColumnDimension('B')->setWidth(50);
            $sheet->setCellValue("C1", 'Valor Fatura')->getColumnDimension('C')->setWidth(15);
            $sheet->setCellValue("D1", 'Fatura')->getColumnDimension('D')->setWidth(15);
            $sheet->setCellValue("E1", 'Emissão')->getColumnDimension('E')->setWidth(15);
            $sheet->setCellValue("F1", 'Vencimento')->getColumnDimension('F')->setWidth(15);
            $sheet->setCellValue("G1", 'DI')->getColumnDimension('G')->setWidth(20);
            $sheet->setCellValue("H1", 'Valor Mercadoria')->getColumnDimension('H')->setWidth(30);
            $sheet->setCellValue("I1", 'CNTR')->getColumnDimension('I')->setWidth(40);
            $sheet->setCellValue("J1", 'QTD')->getColumnDimension('J')->setWidth(5);
            $valor_total = 0;
            $valor_custo = 0;
            $valor_lucro = 0;
            $qtd_cntr = 0;
            foreach ($comissoes['items'] as $idx => $comissao) {
                $comissao = (object) $comissao;
                $valor_total += $comissao->valor_comissao;
                $idx = $idx+2;
                $sheet->setCellValue("A{$idx}", $comissao->cliente)->getStyle("A{$idx}")->applyFromArray($row);
                $sheet->setCellValue("B{$idx}", $comissao->comissionado)->getStyle("B{$idx}")->applyFromArray($row);
                $sheet->setCellValue("C{$idx}", $comissao->valor_fatura)->getStyle("C{$idx}")->applyFromArray($row);
                $sheet->setCellValue("D{$idx}", $comissao->numero)->getStyle("D{$idx}")->applyFromArray($row);
                $sheet->setCellValue("E{$idx}", date('m-d-Y', strtotime($comissao->dta_emissao)))->getStyle("E{$idx}")->applyFromArray($row);
                $sheet->setCellValue("F{$idx}", date('m-d-Y', strtotime($comissao->dta_vencimento)))->getStyle("F{$idx}")->applyFromArray($row);
                $sheet->setCellValue("G{$idx}", $comissao->documento)->getStyle("G{$idx}")->applyFromArray($row);
                $sheet->setCellValue("H{$idx}", round($comissao->valor_mercadoria, 2))->getStyle("H{$idx}")->applyFromArray($row);
                $sheet->setCellValue("I{$idx}", str_replace('<br>', '/', $comissao->container))->getStyle("I{$idx}")->applyFromArray($row);
                $sheet->setCellValue("J{$idx}", $comissao->qtd_cntr)->getStyle("J{$idx}")->applyFromArray($row);
                $qtd_cntr += $comissao->qtd_cntr;
                // $sheet->setCellValue("F{$idx}", is_null($comissionario->updated_at) ? null : date('d/m/Y h:m', strtotime($comissionario->updated_at)))->getStyle("F{$idx}")->applyFromArray($row);;
            }

            // Bordar geral
            // $sheet->getStyle("A1:F{$idx}")->applyFromArray($allRow);

            $idx = $idx+3;


            
            $sheet->getRowDimension($idx)->setRowHeight(30);
            $sheet->setCellValue("A{$idx}", 'Total de Comissões:')->getStyle("A{$idx}:B{$idx}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$idx}")->applyFromArray($header);

            $sheet->getStyle("B{$idx}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$idx}", $comissoes['total_count']);
            
            $idx++; 
            $sheet->setCellValue("A{$idx}", 'Valor Comissões:')->getStyle("A{$idx}:B{$idx}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$idx}")->applyFromArray($header);

            $sheet->getStyle("B{$idx}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$idx}", round($valor_total, 2));
            $idx++; 
            $sheet->setCellValue("A{$idx}", 'QTD Contêineres:')->getStyle("A{$idx}:B{$idx}")
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$idx}")->applyFromArray($header);

            $sheet->getStyle("B{$idx}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);;
            $sheet->setCellValue("B{$idx}", $qtd_cntr);
            // $sheet->setCellValue("H{$idx}", round($valor_custo, 2));
            // $sheet->setCellValue("I{$idx}", round($valor_lucro, 2));


        } 
    
        // OUTPUT
        $writer = new Xlsx($spreadsheet);
    
        // OR FORCE DOWNLOAD
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Relatório de Comissão.xlsx"');
        header('Cache-Control: max-age=0');
        header('Expires: Fri, 11 Nov 2011 11:11:11 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $writer->save('php://output');

        self::closeTransaction();
      }
}
