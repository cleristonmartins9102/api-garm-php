<?php

namespace App\Infra\Factor;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FactorReportLine {
  protected Worksheet $sheet;
  public function __construct(Worksheet $spreadsheet) 
  {
    $this->sheet = $spreadsheet;
  }

  public function create(int $rowNumber, $data, $style) {
    $alphabetic = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','X','Z','W','Y'];
    $startRowNumber = $rowNumber;
    $highRow = 1;
    foreach ($data as $key => $value) {
      $rowNumber = $startRowNumber;
      $brPos = strpos($value, '<br>');
      if ($brPos) {
        $brValues = explode('<br>', $value);
        if (count($brValues) > $highRow) $highRow = count($brValues);
        foreach ($brValues as $brKey => $brValue) {
          $this->sheet = $this->prepareRow($this->sheet, "{$alphabetic[$key]}{$rowNumber}", $brValue, $style);
          $rowNumber++;
        }
      } else {
        $this->sheet = $this->prepareRow($this->sheet, "{$alphabetic[$key]}{$rowNumber}", $value, $style);
        $r = $rowNumber + ($highRow - 1);
        $this->sheet->mergeCells("{$alphabetic[$key]}{$rowNumber}:{$alphabetic[$key]}{$r}");
      }
    }
    return [
      'highRow' => $highRow,
      'sheet' => $this->sheet
    ];
  }

  private function prepareRow($sheet, $row, $value, $style) {
    $sheet->setCellValue("{$row}", $value)->getStyle("{$row}")->applyFromArray($style);
    return $sheet;
  }
}