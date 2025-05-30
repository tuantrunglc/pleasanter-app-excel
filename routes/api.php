<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiExportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Excel Export API endpoint
// This endpoint allows downloading Excel files based on the provided ID
// POST Parameters:
//   - id: Required. The identifier for the data to export
//   - filename: Optional. The name for the downloaded file (default: 'api-data-export')
Route::post('/export/download', [ApiExportController::class, 'exportFromApi']);