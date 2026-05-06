<?php

namespace Tests\Unit;

use App\Domain\Maintenance\Enums\MaintenanceStatus;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Filament\Resources\MaintenanceLogResource;
use PHPUnit\Framework\TestCase;

class MaintenanceLogResourceTest extends TestCase
{
    public function test_enum_states_are_formatted_for_table_display(): void
    {
        $this->assertSame('Repair', MaintenanceLogResource::formatEnumState(MaintenanceType::Repair));
        $this->assertSame('Open', MaintenanceLogResource::formatEnumState(MaintenanceStatus::Open));
    }

    public function test_string_states_are_formatted_for_table_display(): void
    {
        $this->assertSame('Inspection', MaintenanceLogResource::formatEnumState('inspection'));
        $this->assertSame('In Progress', MaintenanceLogResource::formatEnumState('in_progress'));
        $this->assertNull(MaintenanceLogResource::formatEnumState(null));
    }
}
