<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Evidence;
use App\Models\PoliceCase;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_seeder_creates_fictional_data_and_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(8, User::count());
        $this->assertSame(1, Complaint::count());
        $this->assertSame(1, PoliceCase::count());
        $this->assertSame(1, Evidence::count());

        $case = PoliceCase::first();
        $this->assertSame('CASE-DEMO-0001', $case->case_number);
        $this->assertSame(2, $case->statusHistories()->count());
        $this->assertSame(2, $case->assignments()->count());
        $this->assertSame(1, $case->activities()->count());
        $this->assertSame(1, $case->people()->count());
        $this->assertSame(1, $case->reports()->count());
    }
}
