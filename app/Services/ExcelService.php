<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PleasanterExport;
use App\Services\MockApiService;
use Exception;

class ExcelService
{
    /**
     * Generate Excel file from third-party API data
     *
     * @param int $id The ID to use in the API URL
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
     */
    public function generateExcel($id, $filename)
    {
        try {
            // Check if we're in local environment - use mock data
            
                // Get API URL and API Key from .env
                $apiUrl = env('THIRD_PARTY_API_URL').$id.'/get';
                $apiKey = env('THIRD_PARTY_API_KEY');
                if (empty($apiKey) || empty($apiUrl)) {
                    Log::error('API configuration missing. API key or URL is empty.');
                    return null;
                }
                
                // Make API request with API key in the request body
                $response = $this->callApi($apiUrl,$apiKey);
                dd($response);
                // Check if the request was successful
                if ($response->failed()) {
                    Log::error('API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return null;
                }
                
                // Get JSON data from response
                $data = $response->json();
            
            
            // Process the data for Excel export
            $processedData = $this->processApiData($data);
          
            // Generate and return Excel file
            return PleasanterExport::export($processedData, $filename);
        } catch (Exception $e) {
            Log::error('Error generating Excel file: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return null;
        }
    }
    
    /**
     * Process API data for Excel export
     *
     * @param array $data The raw API data
     * @return array Processed data ready for Excel export
     */
    private function processApiData(array $data)
    {
        // Initialize the processed data array with headers
        $processedData = [];
        
        // If data is empty, return empty array with headers only
        if (empty($data) || !isset($data['items']) || empty($data['items'])) {
            // Default headers if no data is available
            return [['No data available']];
        }
        
        // Extract the first item to determine headers
        $firstItem = $data['items'][0];
        $headers = array_keys($firstItem);
        
        // Add headers as the first row
        $processedData[] = $headers;
        
        // Add data rows
        foreach ($data['items'] as $item) {
            $row = [];
            foreach ($headers as $header) {
                $row[] = $item[$header] ?? '';
            }
            $processedData[] = $row;
        }
        
        return $processedData;
    }
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
        dd($response);
        return $response;
    }
}