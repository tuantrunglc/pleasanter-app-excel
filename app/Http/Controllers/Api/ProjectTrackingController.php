<?php

namespace App\Http\Controllers\Api;

use App\Exports\ProjectTrackingExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProjectTrackingController extends Controller
{
    /**
     * Generate a project tracking Excel template
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateTemplate(Request $request)
    {
        try {
            // Get current year
            $currentYear = date('Y');
            
            // Get filename from request or use default with current year
            $filename = $request->input('filename', '【' . $currentYear . '】AGVN_ Assign List');
            
            // Get project ID from request (if provided)
            $id = $request->input('id');
            
            // Generate Excel template
            $spreadsheet = ProjectTrackingExport::createSpreadsheet($filename);
            
            // If ID is provided, populate the template with data
            $this->populateTemplateWithData($spreadsheet, $id);

            
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
            Log::error('Error generating project tracking template: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            
            // Return error response
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate Excel template',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Populate template with data from API or mock data
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @param string $id
     * @return void
     */
    private function populateTemplateWithData($spreadsheet, $id = null)
    {
        try {
            // Get API URL and key from environment variables
            $apiUrl = env('THIRD_PARTY_API_URL').$id.'/get';
            $apiKey = env('THIRD_PARTY_API_KEY');
            
            $data = null;
            
            // Try to call API first
            if (!empty($apiKey) && !empty($apiUrl)) {
                try {
                    // Make API request with API key
                    $response = $this->callApi($apiUrl, $apiKey);
                    if ($response) {
                        $data = $response;
                        Log::info('API data loaded successfully', ['data' => 'Data received']);
                    } else {
                        Log::warning('API request returned no data. Will use mock data instead.');
                    }
                } catch (\Exception $e) {
                    Log::warning('API request failed: ' . $e->getMessage() . '. Will use mock data instead.');
                }
            } else {
                Log::info('API credentials not available. Will use mock data.');
            }
            
            // If no data from API, use mock data
            if (!$data) {
                $data = $this->getMockData();
                Log::info('Mock data loaded successfully', ['data_keys' => array_keys($data)]);
            }
            
            // Populate the main spreadsheet with the data
            if ($data) {
                $this->fillSpreadsheetWithData($spreadsheet, $data);
                
                // Create and populate the master data sheet
                $this->createMasterDataSheet($spreadsheet, $data);
                
                Log::info('Spreadsheet populated with data successfully');
            } else {
                Log::warning('No data available to populate spreadsheet');
            }
        } catch (\Exception $e) {
            Log::error('Error populating template with data: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }
    
    /**
     * Create and populate the master data sheet
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @param array $data
     * @return void
     */
    private function createMasterDataSheet($spreadsheet, $data)
    {
        // Create a new worksheet
        $masterSheet = $spreadsheet->createSheet();
        $masterSheet->setTitle('マスタデータ');
        
        // Create statistics table at the top and get the number of rows it used
        $statsTableRows = $this->createEmployeeStatisticsTable($masterSheet, $data);
        
        // Add exactly 3 rows of space after the statistics table
        $startRow = $statsTableRows + 3;
        
        // Set headers for employee list
        $headers = [
            'A' => 'ID',
            'B' => 'Name',
            'C' => 'Division',
            'D' => 'Role',
            'E' => 'Role detail',
            'F' => 'Employee Status',
            'G' => 'Join Date',
            'H' => 'Quit Date',
            'I' => 'Location',
            'J' => 'Main Project',
            'K' => 'Note'
        ];
        
        foreach ($headers as $column => $header) {
            $masterSheet->setCellValue($column . $startRow, $header);
            
            // Apply header styling
            $masterSheet->getStyle($column . $startRow)->getFont()->setBold(true);
            $masterSheet->getStyle($column . $startRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('DDEBF7');
        }
        
        // Start from row after headers
        $row = $startRow + 1;
        
        // Process employee data
        if (isset($data['employer']['Response']['Data'])) {
            $employees = $data['employer']['Response']['Data'];
            
            foreach ($employees as $employee) {
                // ID = ClassD
                $masterSheet->setCellValue('A' . $row, $employee['ClassHash']['ClassD'] ?? '');
                
                // Name = DescriptionA
                $masterSheet->setCellValue('B' . $row, $employee['DescriptionHash']['DescriptionA'] ?? '');
                
                // Division = ClassT
                $masterSheet->setCellValue('C' . $row, $employee['ClassHash']['ClassT'] ?? '');
                
                // Role = ClassC
                $masterSheet->setCellValue('D' . $row, $employee['ClassHash']['ClassC'] ?? '');
                
                // Role detail = ClassC (same as Role)
                $masterSheet->setCellValue('E' . $row, $employee['ClassHash']['ClassC'] ?? '');
                
                // Employee Status = "Active"
                $masterSheet->setCellValue('F' . $row, 'Active');
                
                // Join Date = DateB
                if (isset($employee['DateHash']['DateB'])) {
                    $joinDate = new \DateTime($employee['DateHash']['DateB']);
                    $masterSheet->setCellValue('G' . $row, $joinDate->format('Y/m/d'));
                }
                
                // Quit Date - leave empty
                
                // Location = DescriptionC
                $masterSheet->setCellValue('I' . $row, $employee['DescriptionHash']['DescriptionC'] ?? '');
                
                // Main Project - leave empty
                
                // Note - add "2022/12入社" as an example
                $masterSheet->setCellValue('K' . $row, '2022/12入社');
                
                $row++;
            }
            
            // Apply borders to the employee list
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $lastRow = $row - 1;
            $masterSheet->getStyle('A' . ($startRow) . ':K' . $lastRow)->applyFromArray($borderStyle);
        }
        
        // Auto-size columns
        foreach (range('A', 'K') as $column) {
            $masterSheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
    
    /**
     * Create employee statistics table
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array $data
     * @return int Number of rows used by the statistics table
     */
    private function createEmployeeStatisticsTable($sheet, $data)
    {
        // Set title
        $sheet->setCellValue('A1', 'Active社員数');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        
        // Set headers
        $sheet->setCellValue('A3', '');
        $sheet->setCellValue('B3', 'AGV');
        $sheet->setCellValue('C3', 'AGV以外');
        
        // Apply header styling
        $sheet->getStyle('A3:C3')->getFont()->setBold(true);
        $sheet->getStyle('A3:C3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('DDEBF7');
        
        // Extract unique roles from employee data
        $uniqueRoles = [];
        $startRow = 4; // Start row for roles
        
        if (isset($data['employer']['Response']['Data'])) {
            $employees = $data['employer']['Response']['Data'];
            
            // Extract all unique roles from ClassC
            foreach ($employees as $employee) {
                $role = $employee['ClassHash']['ClassC'] ?? '';
                if (!empty($role) && !isset($uniqueRoles[$role])) {
                    $uniqueRoles[$role] = $startRow;
                    $startRow++;
                }
            }
        }
        
        // If no roles found, add some defaults
        if (empty($uniqueRoles)) {
            $uniqueRoles = [
                'Software Developer' => 4,
                'QA Engineer' => 5,
                'BrSE' => 6
            ];
        }
        
        // Initialize counters
        $roleCounts = [];
        foreach (array_keys($uniqueRoles) as $role) {
            $roleCounts[$role] = ['AGV' => 0, 'Other' => 0];
        }
        
        // Count employees by role and division
        if (isset($data['employer']['Response']['Data'])) {
            $employees = $data['employer']['Response']['Data'];
            
            foreach ($employees as $employee) {
                $role = $employee['ClassHash']['ClassC'] ?? '';
                $division = $employee['ClassHash']['ClassT'] ?? '';
                
                if (isset($roleCounts[$role])) {
                    if ($division === 'AGV') {
                        $roleCounts[$role]['AGV']++;
                    } else {
                        $roleCounts[$role]['Other']++;
                    }
                }
            }
        }
        
        // Fill in the statistics table
        $totalAGV = 0;
        $totalOther = 0;
        
        foreach ($uniqueRoles as $role => $row) {
            $sheet->setCellValue('A' . $row, $role);
            $sheet->setCellValue('B' . $row, $roleCounts[$role]['AGV']);
            $sheet->setCellValue('C' . $row, $roleCounts[$role]['Other']);
            
            $totalAGV += $roleCounts[$role]['AGV'];
            $totalOther += $roleCounts[$role]['Other'];
        }
        
        // Calculate the total row position (after the last role)
        $totalRow = $startRow;
        
        // Add total row
        $sheet->setCellValue('A' . $totalRow, 'Total');
        $sheet->setCellValue('B' . $totalRow, $totalAGV);
        $sheet->setCellValue('C' . $totalRow, $totalOther);
        $sheet->getStyle('A' . $totalRow . ':C' . $totalRow)->getFont()->setBold(true);
        
        // Apply borders to the table
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A3:C' . $totalRow)->applyFromArray($borderStyle);
        
        // Auto-size columns for the statistics table
        foreach (range('A', 'C') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Return the total number of rows used by the statistics table
        return $totalRow;
    }
    
    /**
     * Call the third-party API
     *
     * @param string $apiUrl
     * @param string $apiKey
     * @return array|null
     */
    public function callApi ($apiUrl,$apiKey){
        $curl = curl_init();
        curl_setopt_array($curl,array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{ApiKey:\"".$apiKey."\"}"
        ));
        $response = json_decode(curl_exec($curl),true);
        curl_close($curl);
        return $response;
    }
    
    /**
     * Get mock data from JSON files
     *
     * @return array
     */
    private function getMockData()
    {
        $data = [];
        
        try {
            // Read each JSON file
            $files = ['employer.json', 'projects.json', 'PTO.json', 'WorkingTime.json'];
            
            foreach ($files as $file) {
                $path = storage_path('app/mock/data/' . $file);
                Log::info("Checking mock file path: $path");
                
                if (file_exists($path)) {
                    $jsonContent = file_get_contents($path);
                    if ($jsonContent === false) {
                        Log::error("Failed to read file content: $file");
                        continue;
                    }
                    
                    $jsonData = json_decode($jsonContent, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error("JSON decode error for file $file: " . json_last_error_msg());
                        continue;
                    }
                    
                    $data[pathinfo($file, PATHINFO_FILENAME)] = $jsonData;
                    Log::info("Successfully read mock file: $file", ['size' => strlen($jsonContent)]);
                } else {
                    Log::warning("Mock file not found: $path");
                }
            }
            
            Log::info("Total mock data files loaded: " . count($data), ['keys' => array_keys($data)]);
            return $data;
        } catch (\Exception $e) {
            Log::error('Exception while reading mock data: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
    
    /**
     * Fill spreadsheet with data
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @param array $data
     * @return void
     */
    private function fillSpreadsheetWithData($spreadsheet, $data)
    {
        $sheet = $spreadsheet->getActiveSheet();
        
        // Start from row 2 (after headers)
        $row = 2;
        
        // Process projects data
        if (isset($data['projects']['Response']['Data'])) {
            $projects = $data['projects']['Response']['Data'];
            
            // Create a map of employee ResultId to employee data
            $employeeMap = [];
            if (isset($data['employer']['Response']['Data'])) {
                foreach ($data['employer']['Response']['Data'] as $employee) {
                    $resultId = $employee['ResultId'] ?? '';
                    if ($resultId) {
                        $employeeMap[$resultId] = $employee;
                    }
                }
            }
            
            foreach ($projects as $project) {
                // Get project details
                $projectTitle = $project['Title'] ?? '';
                // Remove any text in square brackets if present
                $projectTitle = preg_replace('/\[.*?\]\s*/', '', $projectTitle);
                $projectTitle = trim($projectTitle);
                
                $projectStatus = $this->mapStatus($project['Status'] ?? 0);
                
                // Get team members from ClassH
                $teamMembers = [];
                if (isset($project['ClassHash']['ClassH'])) {
                    // ClassH contains a JSON string with member IDs
                    $memberIds = json_decode($project['ClassHash']['ClassH'], true);
                    if (is_array($memberIds)) {
                        foreach ($memberIds as $memberId) {
                            if (isset($employeeMap[$memberId])) {
                                $teamMembers[] = $employeeMap[$memberId];
                            }
                        }
                    }
                }
                
                // If no team members found, add a single row with default values
                if (empty($teamMembers)) {
                    $sheet->setCellValue('A' . $row, 'Project Base');
                    $sheet->setCellValue('B' . $row, 'JP');
                    $sheet->setCellValue('C' . $row, $projectStatus);
                    $sheet->setCellValue('D' . $row, '直接');
                    $sheet->setCellValue('E' . $row, '株式会社AGEST');
                    $sheet->setCellValue('F' . $row, $projectTitle);
                    $sheet->setCellValue('G' . $row, 'DEV');
                    $sheet->setCellValue('H' . $row, '');
                    $sheet->setCellValue('I' . $row, '');
                    $sheet->setCellValue('J' . $row, '');
                    
                    // Leave date columns empty as requested
                    // X = Start Plan, Y = Start Actual, Z = End Plan, AA = End Actual
                    
                    $row++;
                } else {
                    // Add a row for each team member
                    foreach ($teamMembers as $member) {
                        // Common project data
                        $sheet->setCellValue('A' . $row, 'Project Base'); // PRJ Type - always "Project Base"
                        $sheet->setCellValue('B' . $row, 'JP'); // Market - always "JP"
                        $sheet->setCellValue('C' . $row, $projectStatus); // Status
                        $sheet->setCellValue('D' . $row, '直接'); // Direct - always "直接"
                        $sheet->setCellValue('E' . $row, '株式会社AGEST'); // Client - always "株式会社AGEST"
                        $sheet->setCellValue('F' . $row, $projectTitle); // Prj Name
                        
                        // Member-specific data
                        $role = $member['ClassHash']['ClassC'] ?? 'DEV';
                        $sheet->setCellValue('G' . $row, $role); // Role
                        
                        $division = $member['ClassHash']['ClassT'] ?? '';
                        $sheet->setCellValue('H' . $row, $division); // Division
                        
                        $employeeId = $member['ClassHash']['ClassD'] ?? '';
                        $sheet->setCellValue('I' . $row, $employeeId); // ID
                        
                        $memberName = $member['DescriptionHash']['DescriptionA'] ?? '';
                        $sheet->setCellValue('J' . $row, $memberName); // PIC
                        
                        // Leave date columns empty as requested
                        // X = Start Plan, Y = Start Actual, Z = End Plan, AA = End Actual
                        
                        // Process working time data if available
                        if (isset($data['WorkingTime'])) {
                            $this->fillWorkingTimeData($sheet, $row, $project['IssueId'] ?? 0, $data['WorkingTime']);
                        }
                        
                        $row++;
                        
                        // Check if we've reached the maximum number of rows
                        if ($row > 100) {
                            break 2; // Break out of both loops
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Fill working time data for a project
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $row
     * @param int $projectId
     * @param array $workingTimeData
     * @return void
     */
    private function fillWorkingTimeData($sheet, $row, $projectId, $workingTimeData)
    {
        // Map of month names to column indices
        $monthColumns = [
            '1' => 'K', '2' => 'L', '3' => 'M', '4' => 'N',
            '5' => 'O', '6' => 'P', '7' => 'Q', '8' => 'R',
            '9' => 'S', '10' => 'T', '11' => 'U', '12' => 'V'
        ];
        
        // Get the employee ID from the current row
        $employeeId = $sheet->getCell('I' . $row)->getValue();
        
        // Initialize monthly hours
        $monthlyHours = array_fill(1, 12, 0);
        // Process working time data for this project and employee
        if (isset($workingTimeData['Response']['Data'])) {
            foreach ($workingTimeData['Response']['Data'] as $workTime) {
                // ClassA is the ResultId of the employee, ClassB is the IssueId of the project
                $workTimeEmployeeId = isset($workTime['ClassHash']['ClassA']) ? $workTime['ClassHash']['ClassA'] : '';
                $workTimeProjectId = isset($workTime['ClassHash']['ClassB']) ? $workTime['ClassHash']['ClassB'] : '';
                
                // Get employee data from mock data
                $employeeData = $this->findEmployeeByResultId($workTimeEmployeeId);
                $workTimeEmployeeClassD = $employeeData ? ($employeeData['ClassHash']['ClassD'] ?? '') : '';
                
                // Check if this work time entry is for the current employee and project
                if ($workTimeProjectId == $projectId && $workTimeEmployeeClassD == $employeeId) {
                    // Extract month from StartTime
                    if (isset($workTime['StartTime'])) {
                        $startDate = new \DateTime($workTime['StartTime']);
                        $month = (int)$startDate->format('n'); // 1-12
                        
                        // Add work hours to the corresponding month
                        if (isset($workTime['WorkValue'])) {
                            $monthlyHours[$month] += (float)$workTime['WorkValue'];
                        }
                    }
                }
            }
        }
        
        // Fill in the monthly hours
        foreach ($monthlyHours as $month => $hours) {
            if (isset($monthColumns[$month]) && $hours > 0) {
                $sheet->setCellValue($monthColumns[$month] . $row, $hours);
            }
        }
        
        // Calculate and set the total hours (column W)
        $totalHours = array_sum($monthlyHours);
        $sheet->setCellValue('W' . $row, $totalHours);
    }
    
    /**
     * Find employee data by ResultId
     *
     * @param string $resultId
     * @return array|null
     */
    private function findEmployeeByResultId($resultId)
    {
        $data = $this->getMockData();
        
        if (isset($data['employer']['Response']['Data'])) {
            foreach ($data['employer']['Response']['Data'] as $employee) {
                if (isset($employee['ResultId']) && $employee['ResultId'] == $resultId) {
                    return $employee;
                }
            }
        }
        return null;
    }
    
    /**
     * Map status code to status text
     *
     * @param int $statusCode
     * @return string
     */
    private function mapStatus($statusCode)
    {
        $statusMap = [
            100 => 'Project Closed',
            200 => 'Project Closed',
            900 => 'Project Closed',
            // Add more mappings as needed
            0 => 'Contracted'
        ];
        
        return $statusMap[$statusCode] ?? 'Contracted';
    }
}