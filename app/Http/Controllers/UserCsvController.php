<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class UserCsvController extends Controller
{
    public function template(): Response
    {
        abort_unless(auth()->user()?->can('import users') ?? false, 403);

        $defaultPassword = trim((string) config('assetflow.defaults.import_default_password', ''));
        $samplePassword = $defaultPassword !== '' ? $defaultPassword : '<set-password>';

        $rows = [
            ['name', 'email', 'username', 'department', 'status', 'roles', 'password'],
            ['Jane Doe', 'jane.doe@example.local', 'jdoe', 'IT', 'active', 'IT Manager', $samplePassword],
        ];

        $callback = function () use ($rows): void {
            $output = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        };

        return response()->streamDownload($callback, 'user-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
