<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeadImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_lead_import_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.imports.leads.index'));

        $response->assertOk();
        $response->assertSee('Download template.csv');
        $response->assertSee('Accepted inquiry_type values: buyer, seller, home_value (or home value), generic_inquiry (or generic inquiry). Leave blank if unknown.');
    }

    public function test_admin_can_download_lead_template_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.imports.leads.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertSee('first_name,last_name,email,phone,inquiry_type');
    }

    public function test_admin_can_upload_csv_and_import_leads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csv = <<<CSV
first_name,last_name,email,phone,inquiry_type
Jane,Prospect,jane@example.com,555-1000,buyer
CSV;

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.leads.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('admin.imports.leads.index'));
        $response->assertSessionHas('status', 'Import complete. 1 created, 0 skipped.');

        $this->assertDatabaseHas('leads', [
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $lead = \App\Models\Lead::query()->where('email', 'jane@example.com')->firstOrFail();
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Lead imported from admin CSV upload.',
        ]);
    }

    public function test_admin_import_normalizes_home_value_inquiry_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csv = <<<CSV
first_name,last_name,email,phone,inquiry_type
Jordan,Lee,jordan@example.com,555-2000,home value
CSV;

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.leads.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('admin.imports.leads.index'));
        $response->assertSessionHas('status', 'Import complete. 1 created, 0 skipped.');

        $this->assertDatabaseHas('leads', [
            'email' => 'jordan@example.com',
            'lead_type' => 'home_value',
        ]);
    }

    public function test_admin_can_import_csv_with_blank_email_and_phone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csv = <<<CSV
first_name,last_name,email,phone,inquiry_type
Casey,NoContact,,,seller
CSV;

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.leads.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('admin.imports.leads.index'));
        $response->assertSessionHas('status', 'Import complete. 1 created, 0 skipped.');

        $this->assertDatabaseHas('leads', [
            'name' => 'Casey NoContact',
            'email' => null,
            'phone' => null,
            'lead_type' => 'seller',
        ]);
    }

    public function test_admin_can_import_csv_with_blank_inquiry_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csv = <<<CSV
first_name,last_name,email,phone,inquiry_type
Taylor,Unknown,taylor@example.com,555-5000,
CSV;

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.leads.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('admin.imports.leads.index'));
        $response->assertSessionHas('status', 'Import complete. 1 created, 0 skipped.');

        $this->assertDatabaseHas('leads', [
            'name' => 'Taylor Unknown',
            'email' => 'taylor@example.com',
            'phone' => '555-5000',
            'lead_type' => null,
        ]);
    }

    public function test_admin_can_import_csv_with_generic_inquiry_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $csv = <<<CSV
first_name,last_name,email,phone,inquiry_type
Morgan,General,morgan@example.com,555-5100,generic inquiry
CSV;

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.leads.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('admin.imports.leads.index'));
        $response->assertSessionHas('status', 'Import complete. 1 created, 0 skipped.');

        $this->assertDatabaseHas('leads', [
            'name' => 'Morgan General',
            'email' => 'morgan@example.com',
            'lead_type' => 'generic_inquiry',
        ]);
    }

    public function test_bulk_import_does_not_send_lead_confirmation_notifications(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'agent',
            'notify_on_new_lead_intake' => true,
        ]);

        $csv = <<<CSV
first_name,last_name,email,phone,inquiry_type
Import,Only,import.only@example.com,555-7000,buyer
CSV;

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);

        $response = $this->actingAs($admin)->post(route('admin.imports.leads.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('admin.imports.leads.index'));
        $response->assertSessionHas('status', 'Import complete. 1 created, 0 skipped.');

        Notification::assertNothingSent();
    }

    public function test_admin_can_export_current_leads_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $assignee = User::factory()->create([
            'role' => 'agent',
            'email' => 'assigned@example.com',
        ]);

        \App\Models\Lead::create([
            'name' => 'Export Test Lead',
            'email' => 'export@example.com',
            'phone' => '555-3000',
            'address' => '10 Export Way',
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => $assignee->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.imports.leads.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertSee('id,name,email,phone,address,lead_type,source,status,assigned_email,created_at,updated_at');
        $response->assertSee('Export Test Lead');
        $response->assertSee('assigned@example.com');
    }

    public function test_agent_cannot_access_lead_import_module(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $indexResponse = $this->actingAs($agent)->get(route('admin.imports.leads.index'));
        $indexResponse->assertForbidden();

        $templateResponse = $this->actingAs($agent)->get(route('admin.imports.leads.template'));
        $templateResponse->assertForbidden();

        $exportResponse = $this->actingAs($agent)->get(route('admin.imports.leads.export'));
        $exportResponse->assertForbidden();
    }
}
