<?php

namespace App\Support;

use App\Domain\People\Models\Department;
use App\Domain\People\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class EmployeeQuickCreate
{
    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function form(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255)
                ->helperText('Minimum required to issue the asset.'),
            TextInput::make('employee_id')
                ->label('Employee ID')
                ->maxLength(50),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255),
            Select::make('department_id')
                ->label('Department')
                ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload(),
            TextInput::make('title')
                ->label('Title')
                ->maxLength(150),
            TextInput::make('phone')
                ->label('Phone')
                ->maxLength(50),
            Textarea::make('notes')
                ->label('Notes')
                ->rows(2),
        ];
    }

    public static function create(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        $employeeId = trim((string) ($data['employee_id'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $departmentId = $data['department_id'] ?? null;
        $title = $data['title'] ?? null;
        $phone = $data['phone'] ?? null;
        $notes = $data['notes'] ?? null;

        $employee = null;

        if ($employeeId !== '') {
            $employee = Employee::withTrashed()->where('employee_id', $employeeId)->first();
        }

        if (! $employee && $email !== '') {
            $employee = Employee::withTrashed()->where('email', $email)->first();
        }

        if ($employee) {
            if (method_exists($employee, 'trashed') && $employee->trashed()) {
                $employee->restore();
            }

            if ($name !== '') {
                $employee->name = $name;
            }

            if ($employeeId !== '') {
                $employee->employee_id = $employeeId;
            }

            if ($email !== '' && ! Employee::withTrashed()
                ->where('email', $email)
                ->where('id', '!=', $employee->id)
                ->exists()
            ) {
                $employee->email = $email;
            }

            if ($departmentId) {
                $employee->department_id = $departmentId;
            }

            if ($title !== null && $title !== '') {
                $employee->title = $title;
            }

            if ($phone !== null && $phone !== '') {
                $employee->phone = $phone;
            }

            if ($notes !== null && $notes !== '') {
                $employee->notes = $notes;
            }

            $employee->save();

            return $employee->id;
        }

        if ($email !== '' && Employee::withTrashed()->where('email', $email)->exists()) {
            $email = '';
        }

        return Employee::create([
            'employee_id' => $employeeId !== '' ? $employeeId : null,
            'name' => $name !== '' ? $name : 'Unknown',
            'email' => $email !== '' ? $email : null,
            'department_id' => $departmentId,
            'status' => 'active',
            'title' => $title !== '' ? $title : null,
            'phone' => $phone !== '' ? $phone : null,
            'notes' => $notes !== '' ? $notes : null,
        ])->id;
    }

    /**
     * @return array<int, string>
     */
    public static function searchResults(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        return Employee::query()
            ->where(function ($query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'employee_id'])
            ->mapWithKeys(fn (Employee $employee): array => [$employee->id => self::formatLabel($employee)])
            ->all();
    }

    public static function optionLabel(int|string|null $value): ?string
    {
        if (! $value) {
            return null;
        }

        $employee = Employee::query()
            ->select(['id', 'name', 'employee_id'])
            ->find($value);

        return $employee ? self::formatLabel($employee) : null;
    }

    protected static function formatLabel(Employee $employee): string
    {
        return $employee->employee_id
            ? "{$employee->name} ({$employee->employee_id})"
            : $employee->name;
    }
}
