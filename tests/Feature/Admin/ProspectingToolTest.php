<?php

namespace Tests\Feature\Admin;

use App\Models\Lead;
use App\Models\ProspectingSession;
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
        $response->assertSee('Scripts');
        $response->assertSee('Expired Script');
        $response->assertSee('FSBO');
        $response->assertSee('Edit Script Content');
        $response->assertSee('I noticed your home was listed on the market and recently expired.');
        $response->assertSee('Is it still available?');
    }

    public function test_manager_can_access_prospecting_module(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $managerIndexResponse = $this->actingAs($manager)->get(route('admin.prospecting.index'));
        $managerIndexResponse->assertOk();

        $managerSessionResponse = $this->actingAs($manager)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->postJson(route('admin.prospecting.session-state'), [
                'csv_filename' => 'manager-prospects.csv',
                'rows' => [
                    [
                        'line' => 2,
                        'owner_full_name' => 'Manager Owner',
                        'property_full_address' => '111 Manager St, Marietta, GA 30062',
                        'property_address' => '111 Manager St',
                        'property_city' => 'Marietta',
                        'property_state' => 'GA',
                        'property_zip' => '30062',
                    ],
                ],
                'current_index' => 0,
                'edits' => [
                    '0' => [
                        'phone' => '404-555-4444',
                        'email' => 'manager@example.com',
                    ],
                ],
                'saved_rows' => ['0' => true],
            ]);
        $managerSessionResponse->assertOk();
    }

    public function test_agent_can_access_prospecting_module(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $agentIndexResponse = $this->actingAs($agent)->get(route('admin.prospecting.index'));
        $agentIndexResponse->assertOk();

        $agentSessionResponse = $this->actingAs($agent)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->postJson(route('admin.prospecting.session-state'), [
                'csv_filename' => 'agent-prospects.csv',
                'rows' => [
                    [
                        'line' => 3,
                        'owner_full_name' => 'Agent Owner',
                        'property_full_address' => '222 Agent St, Marietta, GA 30062',
                        'property_address' => '222 Agent St',
                        'property_city' => 'Marietta',
                        'property_state' => 'GA',
                        'property_zip' => '30062',
                    ],
                ],
                'current_index' => 0,
                'edits' => [
                    '0' => [
                        'phone' => '404-555-5555',
                        'email' => 'agent@example.com',
                    ],
                ],
                'saved_rows' => ['0' => true],
            ]);
        $agentSessionResponse->assertOk();
    }

    public function test_all_manager_portal_roles_can_persist_current_card_index(): void
    {
        $roles = ['owner', 'admin', 'global_admin', 'manager', 'agent'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $indexResponse = $this->actingAs($user)->get(route('admin.prospecting.index'));
            $indexResponse->assertOk();

            $sessionResponse = $this->actingAs($user)
                ->withSession(['_token' => 'test-token'])
                ->withHeader('X-CSRF-TOKEN', 'test-token')
                ->postJson(route('admin.prospecting.session-state'), [
                    'csv_filename' => sprintf('%s-prospects.csv', $role),
                    'rows' => [
                        [
                            'line' => 2,
                            'owner_full_name' => ucfirst($role).' Owner One',
                            'property_full_address' => '100 Main St, Marietta, GA 30062',
                            'property_address' => '100 Main St',
                            'property_city' => 'Marietta',
                            'property_state' => 'GA',
                            'property_zip' => '30062',
                        ],
                        [
                            'line' => 3,
                            'owner_full_name' => ucfirst($role).' Owner Two',
                            'property_full_address' => '200 Main St, Marietta, GA 30062',
                            'property_address' => '200 Main St',
                            'property_city' => 'Marietta',
                            'property_state' => 'GA',
                            'property_zip' => '30062',
                        ],
                    ],
                    'current_index' => 1,
                    'edits' => [
                        '1' => [
                            'phone' => '404-555-6666',
                            'email' => sprintf('%s@example.com', $role),
                        ],
                    ],
                    'saved_rows' => ['1' => true],
                ]);

            $sessionResponse->assertOk();

            $session = ProspectingSession::query()
                ->where('account_id', $user->account_id)
                ->where('user_id', $user->id)
                ->first();

            $this->assertNotNull($session);
            $this->assertSame(1, $session->state['current_index']);
        }
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

    public function test_admin_page_restores_saved_prospecting_session_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        ProspectingSession::query()->create([
            'account_id' => $admin->account_id,
            'user_id' => $admin->id,
            'csv_filename' => 'prospects-july.csv',
            'state' => [
                'rows' => [
                    [
                        'line' => 2,
                        'owner_full_name' => 'Saved Owner',
                        'property_full_address' => '123 Saved St, Marietta, GA 30062',
                        'property_address' => '123 Saved St',
                        'property_city' => 'Marietta',
                        'property_state' => 'GA',
                        'property_zip' => '30062',
                    ],
                ],
                'current_index' => 0,
                'edits' => [
                    '0' => [
                        'phone' => '404-555-1111',
                        'email' => 'saved@example.com',
                    ],
                ],
                'saved_rows' => ['0' => true],
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.prospecting.index'));

        $response->assertOk();
        $response->assertSee('prospects-july.csv');
        $response->assertSee('Saved Owner');
        $response->assertSee('saved@example.com');
    }

    public function test_admin_can_persist_prospecting_session_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->postJson(route('admin.prospecting.session-state'), [
                'csv_filename' => 'prospects-august.csv',
                'rows' => [
                    [
                        'line' => 2,
                        'owner_full_name' => 'Persisted Owner',
                        'property_full_address' => '456 Persisted Ave, Marietta, GA 30062',
                        'property_address' => '456 Persisted Ave',
                        'property_city' => 'Marietta',
                        'property_state' => 'GA',
                        'property_zip' => '30062',
                    ],
                ],
                'current_index' => 0,
                'edits' => [
                    '0' => [
                        'phone' => '404-555-2222',
                        'email' => 'persisted@example.com',
                    ],
                ],
                'saved_rows' => ['0' => true],
            ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Prospecting session state saved.');

        $this->assertDatabaseHas('prospecting_sessions', [
            'account_id' => $admin->account_id,
            'user_id' => $admin->id,
            'csv_filename' => 'prospects-august.csv',
        ]);
    }

    public function test_admin_persists_current_card_index_in_session_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->postJson(route('admin.prospecting.session-state'), [
                'csv_filename' => 'prospects-september.csv',
                'rows' => [
                    [
                        'line' => 2,
                        'owner_full_name' => 'Owner One',
                        'property_full_address' => '111 First St, Marietta, GA 30062',
                        'property_address' => '111 First St',
                        'property_city' => 'Marietta',
                        'property_state' => 'GA',
                        'property_zip' => '30062',
                    ],
                    [
                        'line' => 3,
                        'owner_full_name' => 'Owner Two',
                        'property_full_address' => '222 Second St, Marietta, GA 30062',
                        'property_address' => '222 Second St',
                        'property_city' => 'Marietta',
                        'property_state' => 'GA',
                        'property_zip' => '30062',
                    ],
                ],
                'current_index' => 1,
                'edits' => [
                    '1' => [
                        'phone' => '404-555-3333',
                        'email' => 'owner2@example.com',
                    ],
                ],
                'saved_rows' => ['1' => true],
            ]);

        $response->assertOk();

        $session = ProspectingSession::query()
            ->where('account_id', $admin->account_id)
            ->where('user_id', $admin->id)
            ->first();

        $this->assertNotNull($session);
        $this->assertSame(1, $session->state['current_index']);
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
            'email' => 'default@prospectgoat.com',
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
