<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectTrackingController;
use App\Http\Controllers\Api\LeaveExportController;

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

// Project Tracking Template API endpoint
// This endpoint generates a project tracking Excel template
// GET Parameters:
//   - filename: Optional. The name for the downloaded file (default: '2025_案件一覧')
Route::get('/project-tracking/template', [ProjectTrackingController::class, 'generateTemplate']);

// Leave Tracking Export API endpoint
// This endpoint generates a leave tracking Excel file
// GET Parameters:
//   - month: Optional. Month in format 'YYYY-MM' (default: current month)
//   The report will cover from the 26th of the previous month to the 25th of the specified month
Route::get('/export-leave-tracking', [LeaveExportController::class, 'export']);