<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ProjectTrackingExport
{
    /**
     * Create a project tracking Excel template
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function createTemplate($filename = '2025_案件一覧')
    {
        // Create spreadsheet
        $spreadsheet = self::createSpreadsheet($filename);
        
        // Create a temporary file
        $tempFile = storage_path('app/temp/' . $filename . '.xlsx');
        
        // Ensure the directory exists
        if (!file_exists(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }
        
        // Save the spreadsheet to the file
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        // Create response with the file
        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename . '.xlsx'
        );
        
        // Register a callback to delete the file after sending the response
        $response->deleteFileAfterSend(true);
        
        return $response;
    }
    
    /**
     * Create a project tracking Excel spreadsheet
     *
     * @param string $filename
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public static function createSpreadsheet($filename = '2025_案件一覧')
    {
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet name
        $sheet->setTitle('2025_案件一覧');
        
        // Define headers
        $headers = [
            'PRJ Type', 'Market', 'Status', '直接・間接', 'Client', 'Prj Name', 'Role', 'Division', 'ID', 'PIC',
            '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月',
            'TOTAL(h)', 'Start Plan', 'Start Actual', 'End Plan', 'End Actual'
        ];
        
        // Add headers to the first row
        foreach ($headers as $columnIndex => $header) {
            $column = self::columnLetter($columnIndex + 1);
            $sheet->setCellValue($column . '1', $header);
        }
        
        // Format header row
        $headerRange = 'A1:' . self::columnLetter(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'D3D3D3', // Light gray
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        
        // Format monthly columns with yellow background
        $monthlyColumnsStart = array_search('1月', $headers) + 1;
        $monthlyColumnsEnd = array_search('12月', $headers) + 1;
        $monthlyRange = self::columnLetter($monthlyColumnsStart) . '1:' . self::columnLetter($monthlyColumnsEnd) . '1';
        $sheet->getStyle($monthlyRange)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFFF99', // Light yellow
                ],
            ],
        ]);
        
        // Format TOTAL column with blue background
        $totalColumn = self::columnLetter(array_search('TOTAL(h)', $headers) + 1);
        $sheet->getStyle($totalColumn . '1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'B3D9FF', // Light blue
                ],
            ],
        ]);
        
        // Add 10 empty rows
        for ($row = 2; $row <= 11; $row++) {
            // Add formulas for TOTAL column
            $totalColumn = self::columnLetter(array_search('TOTAL(h)', $headers) + 1);
            $firstMonthColumn = self::columnLetter(array_search('1月', $headers) + 1);
            $lastMonthColumn = self::columnLetter(array_search('12月', $headers) + 1);
            $sheet->setCellValue($totalColumn . $row, "=SUM({$firstMonthColumn}{$row}:{$lastMonthColumn}{$row})");
        }
        
        // Set column widths
        // Narrow columns
        $narrowColumns = ['Market', 'ID', '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月', 'TOTAL(h)'];
        foreach ($narrowColumns as $header) {
            $columnIndex = array_search($header, $headers) + 1;
            $sheet->getColumnDimension(self::columnLetter($columnIndex))->setWidth(8);
        }
        
        // Medium columns
        $mediumColumns = ['PRJ Type', 'Status', '直接・間接', 'Role', 'Division'];
        foreach ($mediumColumns as $header) {
            $columnIndex = array_search($header, $headers) + 1;
            $sheet->getColumnDimension(self::columnLetter($columnIndex))->setWidth(15);
        }
        
        // Wide columns
        $wideColumns = ['Prj Name', 'Client', 'PIC'];
        foreach ($wideColumns as $header) {
            $columnIndex = array_search($header, $headers) + 1;
            $sheet->getColumnDimension(self::columnLetter($columnIndex))->setWidth(25);
        }
        
        // Date columns
        $dateColumns = ['Start Plan', 'Start Actual', 'End Plan', 'End Actual'];
        foreach ($dateColumns as $header) {
            $columnIndex = array_search($header, $headers) + 1;
            $sheet->getColumnDimension(self::columnLetter($columnIndex))->setWidth(12);
            
            // Set date format for all cells in this column
            $column = self::columnLetter($columnIndex);
            $sheet->getStyle($column . '2:' . $column . '11')->getNumberFormat()->setFormatCode('yyyy/mm/dd');
        }
        
        // Set number format for monthly columns and TOTAL
        $hourColumns = array_merge(
            array_filter($headers, function($header) {
                return preg_match('/^\d+月$/', $header);
            }),
            ['TOTAL(h)']
        );
        
        foreach ($hourColumns as $header) {
            $columnIndex = array_search($header, $headers) + 1;
            $column = self::columnLetter($columnIndex);
            $sheet->getStyle($column . '2:' . $column . '11')->getNumberFormat()->setFormatCode('0.0');
        }
        
        // Add data validation for Status column
        $statusColumn = self::columnLetter(array_search('Status', $headers) + 1);
        $statusValues = ["受注済み (Contracted)", "Project Closed"];
        self::addDropdownValidation($sheet, $statusColumn, 2, 11, $statusValues);
        
        // Add data validation for Role column
        $roleColumn = self::columnLetter(array_search('Role', $headers) + 1);
        $roleValues = ["PM", "BrSE", "DEV", "DEVL", "QAL", "QA"];
        self::addDropdownValidation($sheet, $roleColumn, 2, 11, $roleValues);
        
        // Set default value for PRJ Type column
        $prjTypeColumn = self::columnLetter(array_search('PRJ Type', $headers) + 1);
        for ($row = 2; $row <= 11; $row++) {
            $sheet->setCellValue($prjTypeColumn . $row, "Project Base");
        }
        
        // Freeze top row
        $sheet->freezePane('A2');
        
        // Add filter to header row
        $sheet->setAutoFilter($headerRange);
        
        return $spreadsheet;
    }
    
    /**
     * Add dropdown validation to a range of cells
     *
     * @param Worksheet $sheet
     * @param string $column
     * @param int $startRow
     * @param int $endRow
     * @param array $values
     * @return void
     */
    private static function addDropdownValidation(Worksheet $sheet, $column, $startRow, $endRow, $values)
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $validation = $sheet->getCell($column . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('"' . implode(',', $values) . '"');
        }
    }
    
    /**
     * Convert column number to letter
     *
     * @param int $columnNumber
     * @return string
     */
    private static function columnLetter($columnNumber)
    {
        $columnLetter = '';
        while ($columnNumber > 0) {
            $modulo = ($columnNumber - 1) % 26;
            $columnLetter = chr(65 + $modulo) . $columnLetter;
            $columnNumber = (int)(($columnNumber - $modulo) / 26);
        }
        return $columnLetter;
    }
}