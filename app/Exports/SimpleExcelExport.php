<?php

namespace App\Exports;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class SimpleExcelExport
{
    /**
     * Export data to Excel file using simple XML format
     *
     * @param array $data
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public static function export($data, $filename = 'pleasanter-export')
    {
        // Create a temporary file
        $tempFile = storage_path('app/temp/' . $filename . '.xlsx');
        
        // Ensure the directory exists
        if (!file_exists(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }
        
        // Create Excel XML content
        $xml = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
        $xml .= 'xmlns:o="urn:schemas-microsoft-com:office:office" ';
        $xml .= 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
        $xml .= 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" ';
        $xml .= 'xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml .= '<Worksheet ss:Name="Sheet1">';
        $xml .= '<Table>';
        
        // Add data rows
        foreach ($data as $row) {
            $xml .= '<Row>';
            foreach ($row as $cell) {
                $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($cell) . '</Data></Cell>';
            }
            $xml .= '</Row>';
        }
        
        $xml .= '</Table>';
        $xml .= '</Worksheet>';
        $xml .= '</Workbook>';
        
        // Save XML to file
        file_put_contents($tempFile, $xml);
        
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