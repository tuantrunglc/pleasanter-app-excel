<?php

namespace App\Http\Controllers\Api;

use App\Exports\LeaveTrackingExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use DateTime;

class LeaveExportController extends Controller
{
    /**
     * Export leave tracking data to Excel
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        try {
            // Get month from request or use current month
            $month = $request->input('month');
            
            if (!$month) {
                // Default to current month
                $month = date('Y-m');
            }
            
            // Parse month to get month name for filename
            $monthDate = new DateTime($month . '-01');
            $monthName = $monthDate->format('M');
            $year = $monthDate->format('Y');
            
            // No debug code needed here
            
            // Generate Excel spreadsheet
            $spreadsheet = LeaveTrackingExport::createSpreadsheet($month);
            
            // Set filename
            $filename = "Attendance_{$monthName}_{$year}";
            
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
            $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($tempFile);
            $response->setContentDisposition(
                \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename . '.xlsx'
            );
            
            // Register a callback to delete the file after sending the response
            $response->deleteFileAfterSend(true);
            
            return $response;
        } catch (\Exception $e) {
            Log::error('Error exporting leave tracking data: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            // Return error response
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate Excel file',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}