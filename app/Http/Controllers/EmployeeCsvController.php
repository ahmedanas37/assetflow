<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeCsvController extends Controller
{
    public function template(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('import employees') ?? false, 403);

        $rows = [
            ['employee_id', 'name', 'email', 'department', 'status', 'title', 'phone', 'notes'],
            ['EMP-1001', 'Alex Morgan', 'alex.morgan@example.local', 'IT', 'active', 'Systems Engineer', '+1-555-0100', 'Onsite staff'],
        ];

        $callback = function () use ($rows): void {
            $output = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        };

        return response()->streamDownload($callback, 'employee-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="employee-import-template.csv"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
