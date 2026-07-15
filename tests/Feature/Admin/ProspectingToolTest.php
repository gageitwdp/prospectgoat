<?php

namespace Tests\Feature\Admin;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProspectingToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_prospecting_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.prospecting.index'));

        $response->assertOk();
        $response->assertSee('Prospect Lead Import');
        $response->assertSee('BeenVerified Lookup');
    }

    public function test_agent_cannot_access_prospecting_module(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $indexResponse = $this->actingAs($agent)->get(route('admin.prospecting.index'));
        $indexResponse->assertForbidden();

        $parseResponse = $this->actingAs($agent)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.prospecting.parse-csv'), [
                '_token' => 'test-token',
                'csv_file' => UploadedFile::fake()->createWithContent('prospects.csv', 'a,b,c'),
            ]);
        $parseResponse->assertForbidden();

        $saveResponse = $this->actingAs($agent)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.prospecting.save-lead'), [
                '_token' => 'test-token',
                'owner_full_name' => 'Blocked User',
                'property_full_address' => '123 Main St, Marietta, GA 30062',
            ]);
        $saveResponse->assertForbidden();
    }

    public function test_admin_can_parse_prospecting_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csv = <<<CSV
Owner 1 Full,Property Full Address,Property Address,Property City,Property State,Property ZIP
George Bozocea,"4032 Wesley Chapel Rd, Marietta, GA 30062",4032 Wesley Chapel Rd,Marietta,GA,30062
CSV;

        $file = UploadedFile::fake()->createWithContent('prospects.csv', $csv);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.prospecting.parse-csv'), [
                '_token' => 'test-token',
                'csv_file' => $file,
            ]);

        $response->assertOk();
        $response->assertJsonPath('count', 1);
        $response->assertJsonPath('rows.0.owner_full_name', 'George Bozocea');
        $response->assertJsonPath('rows.0.property_city', 'Marietta');
        $response->assertJsonPath('rows.0.property_state', 'GA');
    }

    public function test_parse_fails_when_required_columns_are_missing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csv = <<<CSV
Owner 1 Full,Property Full Address,Property Address,Property City,Property State
George Bozocea,"4032 Wesley Chapel Rd, Marietta, GA 30062",4032 Wesley Chapel Rd,Marietta,GA
CSV;

        $file = UploadedFile::fake()->createWithContent('prospects.csv', $csv);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.prospecting.parse-csv'), [
                '_token' => 'test-token',
                'csv_file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'CSV is missing required columns: property zip');
    }

    public function test_save_lead_uses_defaults_when_phone_and_email_are_missing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->postJson(route('admin.prospecting.save-lead'), [
                'owner_full_name' => 'George Bozocea',
                'property_full_address' => '4032 Wesley Chapel Rd, Marietta, GA 30062',
                'property_address' => '4032 Wesley Chapel Rd',
                'property_city' => 'Marietta',
                'property_state' => 'GA',
                'property_zip' => '30062',
                'phone' => '',
                'email' => '',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Lead saved successfully.');

        $this->assertDatabaseHas('leads', [
            'name' => 'George Bozocea',
            'address' => '4032 Wesley Chapel Rd, Marietta, GA 30062',
            'phone' => '111-111-1111',
            'email' => 'default@lezinproperties.com',
            'lead_type' => 'generic_inquiry',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $lead = Lead::query()->where('name', 'George Bozocea')->firstOrFail();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Lead saved from admin prospecting tool CSV workflow.',
        ]);
    }

    public function test_save_lead_uses_provided_phone_and_email_when_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->postJson(route('admin.prospecting.save-lead'), [
                'owner_full_name' => 'Yingzi Ruan',
                'property_full_address' => '1265 Promontory Ln, Marietta, GA 30062',
                'property_address' => '1265 Promontory Ln',
                'property_city' => 'Marietta',
                'property_state' => 'GA',
                'property_zip' => '30062',
                'phone' => '404-555-0101',
                'email' => 'yingzi@example.com',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('leads', [
            'name' => 'Yingzi Ruan',
            'phone' => '404-555-0101',
            'email' => 'yingzi@example.com',
        ]);
    }

    public function test_duplicate_lead_is_skipped_when_name_and_address_match(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Lead::create([
            'name' => 'George Bozocea',
            'email' => 'existing@example.com',
            'phone' => '404-555-9999',
            'address' => '4032 Wesley Chapel Rd, Marietta, GA 30062',
            'lead_type' => 'generic_inquiry',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->postJson(route('admin.prospecting.save-lead'), [
                'owner_full_name' => 'George Bozocea',
                'property_full_address' => '4032 Wesley Chapel Rd, Marietta, GA 30062',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('message', 'This lead already exists and was skipped.');

        $this->assertEquals(
            1,
            Lead::query()->where('name', 'George Bozocea')->where('address', '4032 Wesley Chapel Rd, Marietta, GA 30062')->count()
        );
    }
}
