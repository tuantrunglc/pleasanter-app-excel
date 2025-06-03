<?php

namespace App\Exports;

use App\Services\MockFileService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class LeaveTrackingExport
{
    /**
     * Create a spreadsheet for leave tracking
     *
     * @param string $month Month in format 'Y-m' (e.g., '2025-04')
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet
     */
    public static function createSpreadsheet(string $month = null): Spreadsheet
    {
        // Default month: April 2025
        if (!$month) {
            $month = '2025-04';
        }
        
        // Parse month and determine date range (26th of previous month to 25th of current month)
        $monthDate = new DateTime($month . '-01');
        $monthName = $monthDate->format('M'); // Get month abbreviation (e.g., Apr)
        $year = $monthDate->format('Y');
        
        // Calculate previous month
        $prevMonthDate = clone $monthDate;
        $prevMonthDate->modify('-1 month');
        $prevMonthName = $prevMonthDate->format('M');
        
        // Set date range
        $startDate = $prevMonthDate->format('Y-m-26');
        $endDate = $monthDate->format('Y-m-25');
        
        // Create new spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet name
        $sheet->setTitle("PTO {$monthName}");
        
        // Generate date headers
        $dateColumns = self::generateDateColumns($startDate, $endDate);
        
        // Set up headers
        $headers = [
            'A' => 'No.',
            'B' => 'ID LGVN',
            'C' => 'Name',
            'D' => "Total Leave Hours ({$monthName} {$year})",
        ];
        
        // Add date columns to headers
        $colIndex = 'E';
        foreach ($dateColumns as $date => $dayInfo) {
            $headers[$colIndex] = $date . "\n" . $dayInfo['weekday'];
            $colIndex++;
        }
        
        // Add summary columns
        $summaryHeaders = [
            'Used Hours',
            'Remaining Hours',
            'Mượn phép',
            'Nghỉ không lương',
            'NOTE'
        ];
        
        foreach ($summaryHeaders as $header) {
            $headers[$colIndex] = $header;
            $colIndex++;
        }
        
        // Apply headers to sheet
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . '1', $header);
        }
        
        // Load mock data
        $mockData = self::getMockData();
        $employerData = self::loadEmployerData($mockData);
        $ptoData = self::loadPTOData($mockData);
        
        // Process data and add to sheet
        self::addDataToSheet($sheet, $employerData, $ptoData, $dateColumns, $startDate, $endDate);
        
        // Apply styling
        self::applyStyles($sheet, count($headers), count($employerData['list']));
        
        return $spreadsheet;
    }
    
    /**
     * Get data from API or fallback to mock data
     *
     * @return array
     */
    private static function getMockData(): array
    {
        $data = [];
        // Check if API calls are enabled via environment variable
        $useApiCalls = env('USE_API_CALLS', false);
        // Always use mock data in local environment
        $isLocal = App::environment('local');
        
        try {
            // API endpoints and their corresponding mock files
            $apiEndpoints = [
                'employer' => [
                    'url' => 'http://172.19.21.33:50001/items/1/index',
                    'mockFile' => 'employer.json'
                ],
                'PTO' => [
                    'url' => 'http://172.19.21.33:50001/items/27/index',
                    'mockFile' => 'PTO.json'
                ],
                'projects' => [
                    'url' => 'http://172.19.21.33:50001/items/21/index',
                    'mockFile' => 'projects.json'
                ],
                'WorkingTime' => [
                    'url' => 'http://172.19.21.33:50001/items/29/index',
                    'mockFile' => 'WorkingTime.json'
                ]
            ];
            
            // Get API key from environment
            $apiKey = env('THIRD_PARTY_API_KEY', '');
            
            foreach ($apiEndpoints as $dataType => $endpoint) {
                // Always use mock data in local environment or if API calls are disabled
                if ($isLocal || !$useApiCalls) {
                    $data[$dataType] = self::loadMockFile($endpoint['mockFile']);
                    Log::info("Using mock data for {$dataType} (API calls disabled or local environment)");
                    continue;
                }
                
                // Try to fetch from API first
                $apiData = self::callApi($endpoint['url'], $apiKey);
                
                // If API call fails, fallback to mock data
                if (empty($apiData)) {
                    Log::warning("API call failed for {$dataType}, falling back to mock data");
                    $data[$dataType] = self::loadMockFile($endpoint['mockFile']);
                } else {
                    $data[$dataType] = $apiData;
                    Log::info("Successfully fetched {$dataType} data from API");
                }
            }
            
            Log::info("Total data sources loaded: " . count($data), ['keys' => array_keys($data)]);
            return $data;
        } catch (\Exception $e) {
            Log::error('Exception while getting data: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
    
    /**
     * Load mock data from a JSON file
     *
     * @param string $filename The JSON file to load
     * @return array|null The loaded data or null on failure
     */
    private static function loadMockFile(string $filename): ?array
    {
        $path = storage_path('app/mock/data/' . $filename);
        Log::info("Loading mock file: $path");
        
        if (!file_exists($path)) {
            Log::warning("Mock file not found: $path");
            return null;
        }
        
        $jsonContent = file_get_contents($path);
        if ($jsonContent === false) {
            Log::error("Failed to read file content: $filename");
            return null;
        }
        
        $jsonData = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JSON decode error for file $filename: " . json_last_error_msg());
            return null;
        }
        
        Log::info("Successfully loaded mock file: $filename", ['size' => strlen($jsonContent)]);
        return $jsonData;
    }
    
    /**
     * Call API to get data
     *
     * @param string $url The API URL
     * @param string $apiKey The API key
     * @return array|null The API response data or null on failure
     */
    private static function callApi(string $url, string $apiKey): ?array
    {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{ApiKey:\"".$apiKey."\"}"
            ]);
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($err) {
                Log::error("cURL Error: " . $err);
                return null;
            }
            
            if ($httpCode >= 400) {
                Log::error("API returned error code: " . $httpCode);
                return null;
            }
            
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("JSON decode error: " . json_last_error_msg());
                return null;
            }
            
            return $data;
        } catch (\Exception $e) {
            Log::error('Exception in API call: ' . $e->getMessage(), [
                'exception' => $e,
                'url' => $url
            ]);
            return null;
        }
    }
    
    /**
     * Load employer data from mock data
     *
     * @param array $mockData The mock data array
     * @return array
     */
    private static function loadEmployerData(array $mockData): array
    {
        $employerData = [];
        $employerById = []; // Map by ResultId for easy lookup
        
        if (!isset($mockData['employer']) || !isset($mockData['employer']['Response']['Data'])) {
            Log::error('Invalid employer data structure in mock data');
            return [
                'list' => [],
                'by_result_id' => []
            ];
        }
        
        $employers = $mockData['employer']['Response']['Data'];
        
        foreach ($employers as $employer) {
            // Extract employee ID from ClassHash.ClassD
            $id = $employer['ClassHash']['ClassD'] ?? '';
            $resultId = $employer['ResultId'] ?? '';
            
            if (empty($id)) {
                Log::warning('Employer missing ClassHash.ClassD (ID): ' . json_encode($employer));
                continue;
            }
            
            if (empty($resultId)) {
                Log::warning('Employer missing ResultId: ' . json_encode($employer));
                continue;
            }
            
            $employerInfo = [
                'id' => $id,
                'result_id' => $resultId,
                'name' => $employer['DescriptionHash']['DescriptionA'] ?? 'Unknown',
                'used_leave' => $employer['NumHash']['NumS'] ?? 0,
                'location' => $employer['DescriptionHash']['DescriptionC'] ?? '',
                'gender' => $employer['ClassHash']['ClassF'] ?? '',
                'team' => $employer['ClassHash']['ClassT'] ?? '',
            ];
            
            $employerData[] = $employerInfo;
            $employerById[$resultId] = $employerInfo; // Store by ResultId for mapping
        }
        
        Log::info('Loaded ' . count($employerData) . ' employers from mock data');
        
        return [
            'list' => $employerData,
            'by_result_id' => $employerById
        ];
    }
    
    /**
     * Load PTO data from mock data
     *
     * @param array $mockData The mock data array
     * @return array
     */
    private static function loadPTOData(array $mockData): array
    {
        $ptoData = [];
        
        if (!isset($mockData['PTO']) || !isset($mockData['PTO']['Response']['Data'])) {
            Log::error('Invalid PTO data structure in mock data');
            return [];
        }
        
        $ptos = $mockData['PTO']['Response']['Data'];
        
        foreach ($ptos as $pto) {
            // Extract employer ResultId from ClassHash.ClassE
            $employerResultId = $pto['ClassHash']['ClassE'] ?? '';
            $leaveType = $pto['ClassHash']['ClassA'] ?? '';
            
            if (empty($employerResultId)) {
                Log::warning('PTO record missing ClassHash.ClassE (employer ResultId): ' . json_encode($pto));
                continue;
            }
            
            if (empty($pto['DateHash']['DateC'])) {
                Log::warning('PTO record missing DateC (leave date): ' . json_encode($pto));
                continue;
            }
            
            try {
                // Use DateC as the leave date
                $leaveDate = new DateTime($pto['DateHash']['DateC']);
                
                // Get leave hours from ClassB, default to 8 if not specified
                $leaveHours = isset($pto['ClassHash']['ClassB']) ? (float)$pto['ClassHash']['ClassB'] : 8;
                
                $ptoData[] = [
                    'employer_result_id' => $employerResultId,
                    'leave_date' => $leaveDate->format('Y-m-d'),
                    'leave_hours' => $leaveHours,
                    'type' => $leaveType,
                    'is_unpaid' => ($leaveType === 'Unpaid Leave')
                ];
            } catch (\Exception $e) {
                Log::error('Error processing PTO date: ' . $e->getMessage(), [
                    'pto' => $pto
                ]);
            }
        }
        
        Log::info('Loaded ' . count($ptoData) . ' PTO records from mock data');
        
        return $ptoData;
    }
    
    /**
     * Generate date columns from start date to end date
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private static function generateDateColumns(string $startDate, string $endDate): array
    {
        $dateColumns = [];
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end->modify('+1 day'));
        
        $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        foreach ($period as $date) {
            $day = $date->format('j');
            $weekday = $weekdays[$date->format('w')];
            $dateColumns[$day] = [
                'weekday' => $weekday,
                'date' => $date->format('Y-m-d'),
                'is_weekend' => in_array($weekday, ['Sat', 'Sun'])
            ];
        }
        
        return $dateColumns;
    }
    
    /**
     * Add data to the sheet
     *
     * @param Worksheet $sheet
     * @param array $employerData
     * @param array $ptoData
     * @param array $dateColumns
     * @param string $startDate
     * @param string $endDate
     * @return void
     */
    private static function addDataToSheet(
        Worksheet $sheet, 
        array $employerData, 
        array $ptoData, 
        array $dateColumns, 
        string $startDate, 
        string $endDate
    ): void {
        // Create a map of leave days for each employee
        $leaveDaysMap = [];
        $unpaidLeaveMap = [];
        
        // Get employer list and lookup map
        $employerList = $employerData['list'];
        $employerByResultId = $employerData['by_result_id'];
        
        // Process PTO data and map to employers
        foreach ($ptoData as $pto) {
            $employerResultId = $pto['employer_result_id'];
            
            // Skip if we can't find the employer
            if (!isset($employerByResultId[$employerResultId])) {
                continue;
            }
            
            $employer = $employerByResultId[$employerResultId];
            $employeeId = $employer['id'];
            
            $leaveDate = new DateTime($pto['leave_date']);
            $leaveDateStr = $leaveDate->format('Y-m-d');
            $leaveHours = $pto['leave_hours'];
            
            // Initialize employee in maps if not exists
            if (!isset($leaveDaysMap[$employeeId])) {
                $leaveDaysMap[$employeeId] = [];
            }
            
            if ($pto['is_unpaid'] && !isset($unpaidLeaveMap[$employeeId])) {
                $unpaidLeaveMap[$employeeId] = true;
            }
            
            // Only include days within our date range
            if ($leaveDateStr >= $startDate && $leaveDateStr <= $endDate) {
                // For weekdays, mark with negative leave hours
                // For weekends, don't count as leave
                $dayOfWeek = $leaveDate->format('w'); // 0 (Sunday) to 6 (Saturday)
                
                if ($dayOfWeek > 0 && $dayOfWeek < 6) { // Monday to Friday
                    // Use negative value to indicate leave hours
                    $leaveDaysMap[$employeeId][$leaveDateStr] = -$leaveHours;
                }
            }
        }
        
        // Add employee data to sheet
        $row = 2;
        foreach ($employerList as $index => $employee) {
            $employeeId = $employee['id'];
            
            // Basic employee info
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $employeeId);
            $sheet->setCellValue('C' . $row, $employee['name']);
            
            // Total leave hours up to current month (from NumS in employer data)
            $totalLeaveHours = $employee['used_leave']; // This is NumS from employer data
            $sheet->setCellValue('D' . $row, $totalLeaveHours);
            
            // Fill date columns with leave data
            $colIndex = 'E';
            $usedLeave = 0;
            
            foreach ($dateColumns as $day => $dayInfo) {
                $date = $dayInfo['date'];
                $isWeekend = $dayInfo['is_weekend'];
                
                // If it's a weekend, leave it blank
                if (!$isWeekend && isset($leaveDaysMap[$employeeId][$date])) {
                    $leaveValue = $leaveDaysMap[$employeeId][$date];
                    $sheet->setCellValue($colIndex . $row, $leaveValue);
                    $usedLeave += abs($leaveValue);
                }
                
                $colIndex++;
            }
            
            // Calculate EOM balance (remaining leave hours after subtracting used leave)
            // This is the total leave hours minus the leave hours used in the current period
            $eomBalance = $totalLeaveHours - $usedLeave;
            
            // Fill summary columns
            $sheet->setCellValue($colIndex++ . $row, $usedLeave);
            $sheet->setCellValue($colIndex++ . $row, $eomBalance);
            
            // Borrowed leave (for demonstration, we'll use a simple rule)
            $borrowedLeave = ($eomBalance < 0) ? abs($eomBalance) : 0;
            if ($borrowedLeave > 0) {
                $sheet->setCellValue($colIndex . $row, $borrowedLeave);
            }
            $colIndex++;
            
            // Unpaid leave
            if (isset($unpaidLeaveMap[$employeeId])) {
                $sheet->setCellValue($colIndex . $row, $employeeId);
                $sheet->setCellValue($colIndex + 1 . $row, $employeeId);
            }
            
            $row++;
        }
    }
    
    /**
     * Apply styles to the sheet
     *
     * @param Worksheet $sheet
     * @param int $columnCount
     * @param int $rowCount
     * @return void
     */
    private static function applyStyles(Worksheet $sheet, int $columnCount, int $rowCount): void
    {
        // Get the last column letter
        // Use PhpSpreadsheet's built-in method to convert column index to column letter
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);
        
        // Style headers
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E0E0E0',
                ],
            ],
        ]);
        
        // Set row height for header to accommodate two lines
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        // Style data cells
        $sheet->getStyle('A2:' . $lastColumn . ($rowCount + 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        
        // Right-align number cells
        $sheet->getStyle('D2:' . $lastColumn . ($rowCount + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Find column indexes for special formatting
        $dateColumnCount = 0;
        foreach ($sheet->getColumnIterator('E') as $column) {
            $cellValue = $sheet->getCell($column->getColumnIndex() . '1')->getValue();
            if (strpos($cellValue, "\n") !== false) {
                $dateColumnCount++;
            } else {
                break;
            }
        }
        
        $eomBalanceCol = chr(64 + 5 + $dateColumnCount);
        $unpaidLeaveCol = chr(64 + 5 + $dateColumnCount + 2);
        
        // Highlight EOM Balance > 20 in yellow
        for ($row = 2; $row <= $rowCount + 1; $row++) {
            $eomBalance = $sheet->getCell($eomBalanceCol . $row)->getValue();
            if ($eomBalance > 20) {
                $sheet->getStyle($eomBalanceCol . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FFFF00',
                        ],
                    ],
                ]);
            }
            
            // Highlight "Nghỉ không lương" column in red
            if ($sheet->getCell($unpaidLeaveCol . $row)->getValue()) {
                $sheet->getStyle($unpaidLeaveCol . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'FF0000',
                        ],
                    ],
                ]);
            }
        }
        
        // Freeze top row
        $sheet->freezePane('A2');
        
        // Auto-size columns
        // Use a different approach to iterate through columns
        for ($i = 1; $i <= $columnCount; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }
}