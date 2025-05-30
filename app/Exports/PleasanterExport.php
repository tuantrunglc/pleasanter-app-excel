<?php

namespace App\Exports;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Illuminate\Support\Facades\Storage;

class PleasanterExport
{
    /**
     * Export data to Excel file
     *
     * @param array $data
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function export($data, $filename = 'pleasanter-export')
    {
        // Check if PhpSpreadsheet is available
        if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            return self::exportWithPhpSpreadsheet($data, $filename);
        } else {
            // Use SimpleExcelExport as fallback
            return SimpleExcelExport::export($data, $filename);
        }
    }
    
    /**
     * Export data to Excel file using PhpSpreadsheet
     *
     * @param array $data
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private static function exportWithPhpSpreadsheet($data, $filename)
    {
        // Import classes dynamically to avoid errors if not available
        $spreadsheetClass = '\\PhpOffice\\PhpSpreadsheet\\Spreadsheet';
        $xlsxWriterClass = '\\PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx';
        
        // Create new Spreadsheet object
        $spreadsheet = new $spreadsheetClass();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Add data to the sheet
        $sheet->fromArray($data, null, 'A1');
        
        // Create a temporary file
        $tempFile = storage_path('app/temp/' . $filename . '.xlsx');
        
        // Ensure the directory exists
        if (!file_exists(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }
        
        // Save the spreadsheet to the file
        $writer = new $xlsxWriterClass($spreadsheet);
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
}