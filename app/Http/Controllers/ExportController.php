<?php

namespace App\Http\Controllers;

use App\Exports\PleasanterExport;
use App\Exports\AdvancedExport;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    /**
     * Export sample data to Excel
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export()
    {
        // Sample data for export
        $data = [
            ['ID', 'Name', 'Email', 'Created At'],
            [1, 'John Doe', 'john@example.com', now()->format('Y-m-d')],
            [2, 'Jane Smith', 'jane@example.com', now()->format('Y-m-d')],
            [3, 'Bob Johnson', 'bob@example.com', now()->format('Y-m-d')],
            [4, 'Alice Brown', 'alice@example.com', now()->format('Y-m-d')],
            [5, 'Charlie Wilson', 'charlie@example.com', now()->format('Y-m-d')],
        ];

        return PleasanterExport::export($data, 'pleasanter-sample-data');
    }

    /**
     * Export sample data with advanced formatting
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function advancedExport()
    {
        // Sample data for export
        $data = [
            ['ID', 'Name', 'Email', 'Department', 'Position', 'Salary', 'Hire Date'],
            [1, 'John Doe', 'john@example.com', 'IT', 'Developer', 75000, '2022-01-15'],
            [2, 'Jane Smith', 'jane@example.com', 'HR', 'Manager', 85000, '2021-05-20'],
            [3, 'Bob Johnson', 'bob@example.com', 'Finance', 'Accountant', 65000, '2022-03-10'],
            [4, 'Alice Brown', 'alice@example.com', 'Marketing', 'Specialist', 60000, '2023-01-05'],
            [5, 'Charlie Wilson', 'charlie@example.com', 'IT', 'Team Lead', 90000, '2020-11-18'],
            [6, 'Diana Miller', 'diana@example.com', 'Sales', 'Representative', 55000, '2023-02-28'],
            [7, 'Edward Davis', 'edward@example.com', 'IT', 'DevOps Engineer', 80000, '2021-09-15'],
            [8, 'Fiona Clark', 'fiona@example.com', 'HR', 'Recruiter', 58000, '2022-07-12'],
            [9, 'George White', 'george@example.com', 'Finance', 'Analyst', 62000, '2022-04-30'],
            [10, 'Hannah Green', 'hannah@example.com', 'Marketing', 'Director', 95000, '2019-08-22'],
        ];

        return AdvancedExport::export($data, 'pleasanter-advanced-data');
    }
}