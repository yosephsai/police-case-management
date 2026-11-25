<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintInputValidationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $phone, ?string $date = null): array
    {
        return [
            'complainant_name' => 'Fictional Complainant',
            'complainant_phone' => $phone,
            'incident_location' => 'Adama',
            'incident_description' => 'Fictional incident description.',
            'incident_date' => $date ?? now()->toDateString(),
        ];
    }

    public function test_nine_digit_phone_number_is_stored_with_the_country_code(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $this->actingAs($officer)
            ->post(route('complaints.store'), $this->payload('912345678'))
            ->assertRedirect(route('complaints.index'));

        $this->assertDatabaseHas('complaints', [
            'complainant_phone' => '+251912345678',
            'incident_date' => now()->toDateString(),
        ]);
    }

    public function test_a_full_country_code_number_is_accepted_without_duplication(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $this->actingAs($officer)
            ->post(route('complaints.store'), $this->payload('+251912345678'))
            ->assertRedirect(route('complaints.index'));

        $this->assertDatabaseHas('complaints', ['complainant_phone' => '+251912345678']);
        $this->assertDatabaseCount('complaints', 1);
    }

    public function test_invalid_phone_numbers_are_rejected(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        foreach (['91234', '9123456789', 'abc123456', '+1123456789'] as $phone) {
            $this->actingAs($officer)
                ->post(route('complaints.store'), $this->payload($phone))
                ->assertSessionHasErrors('complainant_phone');
        }

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_a_future_incident_date_is_rejected(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $this->actingAs($officer)
            ->post(route('complaints.store'), $this->payload('912345678', now()->addDay()->toDateString()))
            ->assertSessionHasErrors('incident_date');

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_the_registration_form_shows_the_fixed_country_code_and_date_limit(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $this->actingAs($officer)
            ->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('+251', false)
            ->assertSee('data-phone-digits', false)
            ->assertSee('maxlength="9"', false)
            ->assertSee('max="' . now()->toDateString() . '"', false);
    }
}
