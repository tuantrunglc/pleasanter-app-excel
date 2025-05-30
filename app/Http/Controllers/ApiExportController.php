<?php

namespace App\Http\Controllers;

use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiExportController extends Controller
{
    protected $excelService;
    
    /**
     * Constructor
     *
     * @param ExcelService $excelService
     */
    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
    }
    
    /**
     * Export data from API to Excel
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportFromApi(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'id' => 'required|string',
        ]);
        
        // Get parameters from request
        $id = $request->input('id');
        $filename = $request->input('filename', 'api-data-export');
        
        // Generate Excel file
        $response = $this->excelService->generateExcel($id, $filename);
        
        // Check if Excel generation was successful
        if ($response) {
            return $response;
        }
        
        // If there was an error, return JSON error response
        return response()->json(['error' => 'Failed to generate Excel file'], 500);
    }
}