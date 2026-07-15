<?php

namespace Tests\Feature;

use App\Mail\LeadInquiryConfirmationMail;
use App\Http\Controllers\LeadIntakeController;
use App\Http\Requests\StoreBuyerLeadRequest;
use App\Http\Requests\StoreSellerLeadRequest;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Notifications\LeadAssignmentChangedNotification;
use App\Notifications\NewLeadIntakeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_intake_page_is_displayed(): void
    {
        $response = $this->get(route('leads.intake'));

        $response->assertOk();
    }

    public function test_buyer_intake_page_is_displayed(): void
    {
        $response = $this->get(route('buyers.intake'));

        $response->assertOk();
    }

    public function test_seller_intake_page_is_displayed(): void
    {
        $response = $this->get(route('sellers.intake'));

        $response->assertOk();
    }

    public function test_guest_can_submit_lead_intake_and_activity_is_created(): void
    {
        Mail::fake();

        $response = $this->post(route('leads.intake.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Prospect',
            'email' => 'jane@example.com',
            'phone' => '555-0100',
            'lead_type' => 'buyer',
            'source' => 'homepage',
        ]);

        $response->assertSessionHas('lead_success');

        $this->assertDatabaseHas('leads', [
            'email' => 'jane@example.com',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $lead = Lead::query()->where('email', 'jane@example.com')->firstOrFail();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Lead submitted from homepage.',
        ]);

        Mail::assertSent(LeadInquiryConfirmationMail::class, function (LeadInquiryConfirmationMail $mail): bool {
            return $mail->hasTo('jane@example.com')
                && $mail->lead->email === 'jane@example.com'
                && $mail->template->key === 'new_lead_buyer';
        });
    }

    public function test_guest_can_submit_buyer_intake_and_store_qualification_details(): void
    {
        Mail::fake();
        $request = \Mockery::mock(StoreBuyerLeadRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'first_name' => 'Mia',
            'last_name' => 'Jordan',
            'email' => 'mia@example.com',
            'phone' => '555-0444',
            'move_timeline' => 'one_to_three_months',
            'move_if_not_found' => 'must_move',
            'price_range' => '400k_500k',
            'mortgage_preapproval_status' => 'ready_to_talk',
            'need_to_sell_current_home' => 'renting',
            'agent_relationship' => 'none',
            'purchase_reason' => 'relocating_for_work',
            'target_areas' => 'Woodstock, Canton',
            'min_bedrooms' => 3,
            'min_bathrooms' => 2.5,
            'preferred_contact_method' => 'text',
        ]);

        $response = app(LeadIntakeController::class)->storeBuyer($request);

        $this->assertSame(302, $response->getStatusCode());

        $this->assertDatabaseHas('leads', [
            'email' => 'mia@example.com',
            'source' => 'landing_page',
            'lead_type' => 'buyer',
            'move_timeline' => 'one_to_three_months',
            'move_if_not_found' => 'must_move',
            'price_range' => '400k_500k',
            'mortgage_preapproval_status' => 'ready_to_talk',
            'need_to_sell_current_home' => 'renting',
            'agent_relationship' => 'none',
            'purchase_reason' => 'relocating_for_work',
            'target_areas' => 'Woodstock, Canton',
            'min_bedrooms' => 3,
            'preferred_contact_method' => 'text',
        ]);

        $lead = Lead::query()->where('email', 'mia@example.com')->firstOrFail();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Buyer intake submitted: timeline 1-3 months, budget $400k-$500k, contact by Text message.',
        ]);

        Mail::assertSent(LeadInquiryConfirmationMail::class, function (LeadInquiryConfirmationMail $mail): bool {
            return $mail->hasTo('mia@example.com')
                && $mail->lead->email === 'mia@example.com'
                && $mail->template->key === 'new_lead_buyer_qualification';
        });
    }

    public function test_buyer_intake_requires_qualification_fields(): void
    {
        $validator = Validator::make([
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
        ], (new StoreBuyerLeadRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('first_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('last_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        $this->assertArrayHasKey('move_timeline', $validator->errors()->toArray());
        $this->assertArrayHasKey('move_if_not_found', $validator->errors()->toArray());
        $this->assertArrayHasKey('price_range', $validator->errors()->toArray());
        $this->assertArrayHasKey('mortgage_preapproval_status', $validator->errors()->toArray());
        $this->assertArrayHasKey('need_to_sell_current_home', $validator->errors()->toArray());
        $this->assertArrayHasKey('agent_relationship', $validator->errors()->toArray());
        $this->assertArrayHasKey('purchase_reason', $validator->errors()->toArray());
        $this->assertArrayHasKey('target_areas', $validator->errors()->toArray());
        $this->assertArrayHasKey('min_bedrooms', $validator->errors()->toArray());
        $this->assertArrayHasKey('min_bathrooms', $validator->errors()->toArray());
        $this->assertArrayHasKey('preferred_contact_method', $validator->errors()->toArray());
    }

    public function test_guest_can_submit_seller_intake_and_store_profile_details(): void
    {
        Mail::fake();
        $request = \Mockery::mock(StoreSellerLeadRequest::class);
        $request->shouldReceive('validated')->andReturn([
            'first_name' => 'Avery',
            'last_name' => 'Stone',
            'phone' => '555-0777',
            'email' => 'avery@example.com',
            'address' => '123 Main St, Woodstock, GA',
            'seller_timeline' => 'one_to_three_months',
            'seller_motivation' => 'relocating_for_work',
            'seller_estimated_home_value' => 'Around $480k',
            'seller_mortgage_status' => 'yes',
            'seller_needs_to_buy_another_home_after_selling' => 'yes_local',
            'seller_property_condition' => 'minor_tlc',
            'seller_major_upgrades' => 'New roof in 2023 and updated kitchen',
            'seller_agent_commitment' => 'listed',
            'seller_occupancy_status' => 'primary_residence',
            'seller_valuation_delivery_method' => 'email',
        ]);

        $response = app(LeadIntakeController::class)->storeSeller($request);

        $this->assertSame(302, $response->getStatusCode());

        $this->assertDatabaseHas('leads', [
            'email' => 'avery@example.com',
            'source' => 'landing_page',
            'lead_type' => 'seller',
            'address' => '123 Main St, Woodstock, GA',
            'seller_timeline' => 'one_to_three_months',
            'seller_motivation' => 'relocating_for_work',
            'seller_estimated_home_value' => 'Around $480k',
            'seller_mortgage_status' => 'yes',
            'seller_needs_to_buy_another_home_after_selling' => 'yes_local',
            'seller_property_condition' => 'minor_tlc',
            'seller_major_upgrades' => 'New roof in 2023 and updated kitchen',
            'seller_agent_commitment' => 'listed',
            'seller_occupancy_status' => 'primary_residence',
            'seller_valuation_delivery_method' => 'email',
        ]);

        $lead = Lead::query()->where('email', 'avery@example.com')->firstOrFail();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Seller intake submitted: timeline 1-3 months, motivation Relocating for work, valuation by Email me the report.',
        ]);

        Mail::assertSent(LeadInquiryConfirmationMail::class, function (LeadInquiryConfirmationMail $mail): bool {
            return $mail->hasTo('avery@example.com')
                && $mail->lead->email === 'avery@example.com'
                && $mail->template->key === 'new_lead_seller_profile';
        });
    }

    public function test_seller_intake_requires_profile_fields(): void
    {
        $validator = Validator::make([
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'email' => '',
            'address' => '',
        ], (new StoreSellerLeadRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('first_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('last_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
        $this->assertArrayHasKey('seller_timeline', $validator->errors()->toArray());
        $this->assertArrayHasKey('seller_motivation', $validator->errors()->toArray());
        $this->assertArrayHasKey('seller_estimated_home_value', $validator->errors()->toArray());
        $this->assertArrayHasKey('seller_mortgage_status', $validator->errors()->toArray());
        $this->assertArrayHasKey('seller_needs_to_buy_another_home_after_selling', $validator->errors()->toArray());
        $this->assertArrayHasKey('seller_property_condition', $validator->errors()->toArray());
        $this->assertArrayHasKey('seller_agent_commitment', $validator->errors()->toArray());
        $this->assertArrayHasKey('seller_occupancy_status', $validator->errors()->toArray());
        $this->assertArrayHasKey('seller_valuation_delivery_method', $validator->errors()->toArray());
    }

    public function test_email_template_resolution_falls_back_to_default_when_specific_template_is_disabled(): void
    {
        $buyerTemplate = EmailTemplate::query()->where('key', 'new_lead_buyer')->firstOrFail();
        $defaultTemplate = EmailTemplate::query()->where('key', 'new_lead_default')->firstOrFail();

        $this->assertSame('new_lead_buyer', EmailTemplate::resolveForInquiryType('buyer')->key);

        $buyerTemplate->update(['is_active' => false]);

        $resolved = EmailTemplate::resolveForInquiryType('buyer');

        $this->assertNotNull($resolved);
        $this->assertSame($defaultTemplate->id, $resolved->id);
    }

    public function test_guest_cannot_submit_lead_intake_with_missing_required_fields(): void
    {
        $response = $this->from(route('leads.intake'))->post(route('leads.intake.store'), [
            'first_name' => 'No',
            'last_name' => 'Contact',
            'email' => '',
            'phone' => '',
            'lead_type' => '',
            'source' => 'homepage',
        ]);

        $response->assertRedirect(route('leads.intake'));
        $response->assertSessionHasErrors(['email', 'phone', 'lead_type']);

        $this->assertDatabaseMissing('leads', [
            'name' => 'No Contact',
        ]);
    }

    public function test_new_lead_intake_notification_is_sent_to_opted_in_managers_only(): void
    {
        Notification::fake();

        $adminOptedIn = User::factory()->create([
            'role' => 'admin',
            'notify_on_new_lead_intake' => true,
        ]);

        $agentOptedIn = User::factory()->create([
            'role' => 'agent',
            'notify_on_new_lead_intake' => true,
        ]);

        $agentOptedOut = User::factory()->create([
            'role' => 'agent',
            'notify_on_new_lead_intake' => false,
        ]);

        $this->post(route('leads.intake.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Prospect',
            'email' => 'notify-intake@example.com',
            'phone' => '555-0101',
            'lead_type' => 'buyer',
            'source' => 'homepage',
        ])->assertSessionHas('lead_success');

        Notification::assertSentTo($adminOptedIn, NewLeadIntakeNotification::class);
        Notification::assertSentTo($agentOptedIn, NewLeadIntakeNotification::class);
        Notification::assertNotSentTo($agentOptedOut, NewLeadIntakeNotification::class);
    }

    public function test_new_lead_intake_notification_is_always_sent_to_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'notify_on_new_lead_intake' => false,
        ]);

        $this->post(route('leads.intake.store'), [
            'first_name' => 'Admin',
            'last_name' => 'Target',
            'email' => 'admin-target@example.com',
            'phone' => '555-0102',
            'lead_type' => 'buyer',
            'source' => 'homepage',
        ])->assertSessionHas('lead_success');

        Notification::assertSentTo($admin, NewLeadIntakeNotification::class);
    }

    public function test_manager_routes_require_authentication(): void
    {
        $response = $this->get(route('manager.leads.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_manager_leads_index(): void
    {
        $user = User::factory()->create(['role' => 'agent']);

        $response = $this->actingAs($user)->get(route('manager.leads.index'));

        $response->assertOk();
    }

    public function test_manager_can_view_pipeline_board(): void
    {
        $manager = User::factory()->create(['role' => 'agent']);

        Lead::create([
            'name' => 'Pipeline New',
            'email' => 'pipeline-new@example.com',
            'phone' => '555-0201',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        Lead::create([
            'name' => 'Pipeline Active',
            'email' => 'pipeline-active@example.com',
            'phone' => '555-0202',
            'address' => null,
            'lead_type' => 'seller',
            'source' => 'referral',
            'status' => 'active',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($manager)->get(route('manager.leads.pipeline'));

        $response->assertOk();
        $response->assertSee('Pipeline Board');
        $response->assertSee('Close Rate');
        $response->assertSee('Pipeline New');
        $response->assertSee('Pipeline Active');
    }

    public function test_manager_can_view_buyer_qualification_summary_on_lead_detail(): void
    {
        $manager = User::factory()->create(['role' => 'agent']);

        $lead = Lead::create([
            'name' => 'Buyer Detail Lead',
            'email' => 'detail@example.com',
            'phone' => '555-0991',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'landing_page',
            'status' => 'new',
            'assigned_to' => null,
            'working_with_agent' => false,
            'move_timeline' => 'three_to_six_months',
            'move_if_not_found' => 'continue_renting',
            'price_range' => '650k_plus',
            'mortgage_preapproval_status' => 'pre_approved',
            'need_to_sell_current_home' => 'no',
            'agent_relationship' => 'none',
            'purchase_reason' => 'first_time_homebuyer',
            'target_areas' => 'Alpharetta',
            'min_bedrooms' => 4,
            'min_bathrooms' => 3.0,
            'preferred_contact_method' => 'email',
        ]);

        $response = $this->actingAs($manager)->get(route('manager.leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Buyer Qualification');
        $response->assertSee('3-6 months');
        $response->assertSee('$650k+');
        $response->assertSee('Email');
        $response->assertSee('Alpharetta');
    }

    public function test_manager_can_view_seller_qualification_summary_on_lead_detail(): void
    {
        $manager = User::factory()->create(['role' => 'agent']);

        $lead = Lead::create([
            'name' => 'Seller Detail Lead',
            'email' => 'seller-detail@example.com',
            'phone' => '555-1991',
            'address' => '456 Oak Ave, Marietta, GA',
            'lead_type' => 'seller',
            'source' => 'landing_page',
            'status' => 'new',
            'assigned_to' => null,
            'working_with_agent' => true,
            'seller_timeline' => 'three_to_six_months',
            'seller_motivation' => 'downsizing_upgrading',
            'seller_estimated_home_value' => 'Around $650k',
            'seller_mortgage_status' => 'no',
            'seller_needs_to_buy_another_home_after_selling' => 'yes_relocating',
            'seller_property_condition' => 'excellent',
            'seller_major_upgrades' => 'New windows and a remodeled primary bath',
            'seller_agent_commitment' => 'no',
            'seller_occupancy_status' => 'rented_to_tenants',
            'seller_valuation_delivery_method' => 'text',
        ]);

        $response = $this->actingAs($manager)->get(route('manager.leads.show', $lead));

        $response->assertOk();
        $response->assertSee('Seller Qualification');
        $response->assertSee('3–6 months');
        $response->assertSee('Downsizing / upgrading');
        $response->assertSee('Around $650k');
        $response->assertSee('Text me the highlights');
        $response->assertSee('New windows and a remodeled primary bath');
    }

    public function test_pipeline_period_filter_excludes_older_leads(): void
    {
        $manager = User::factory()->create(['role' => 'agent']);

        $recentLead = Lead::create([
            'name' => 'Recent Pipeline Lead',
            'email' => 'recent-pipeline@example.com',
            'phone' => '555-0301',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $olderLead = Lead::create([
            'name' => 'Old Pipeline Lead',
            'email' => 'old-pipeline@example.com',
            'phone' => '555-0302',
            'address' => null,
            'lead_type' => 'seller',
            'source' => 'referral',
            'status' => 'contacted',
            'assigned_to' => null,
        ]);

        $olderLead->created_at = now()->subDays(120);
        $olderLead->updated_at = now()->subDays(120);
        $olderLead->save();

        $recentLead->created_at = now()->subDays(2);
        $recentLead->updated_at = now()->subDays(2);
        $recentLead->save();

        $response = $this->actingAs($manager)->get(route('manager.leads.pipeline', ['period' => '30']));

        $response->assertOk();
        $response->assertSee('Recent Pipeline Lead');
        $response->assertDontSee('Old Pipeline Lead');
    }

    public function test_manager_can_move_lead_to_next_pipeline_stage(): void
    {
        $manager = User::factory()->create(['role' => 'agent']);

        $lead = Lead::create([
            'name' => 'Stage Move Lead',
            'email' => 'stage-move@example.com',
            'phone' => '555-0221',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($manager)->patch(route('manager.leads.status.move', $lead), [
            'status' => 'contacted',
        ]);

        $response->assertSessionHas('status', 'Lead stage updated.');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'contacted',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Lead status changed from new to contacted.',
        ]);
    }

    public function test_manager_cannot_skip_pipeline_stages_with_invalid_transition(): void
    {
        $manager = User::factory()->create(['role' => 'agent']);

        $lead = Lead::create([
            'name' => 'Invalid Move Lead',
            'email' => 'invalid-move@example.com',
            'phone' => '555-0222',
            'address' => null,
            'lead_type' => 'seller',
            'source' => 'referral',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $response = $this->from(route('manager.leads.pipeline'))
            ->actingAs($manager)
            ->patch(route('manager.leads.status.move', $lead), [
                'status' => 'active',
            ]);

        $response->assertRedirect(route('manager.leads.pipeline'));
        $response->assertSessionHasErrors('status');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'new',
        ]);
    }

    public function test_manager_can_update_lead_status_and_assignment_with_history_entries(): void
    {
        $manager = User::factory()->create(['name' => 'Primary Agent', 'role' => 'agent']);
        $assignee = User::factory()->create(['name' => 'Assigned Agent', 'role' => 'agent']);

        $lead = Lead::create([
            'name' => 'John Seller',
            'email' => 'john@example.com',
            'phone' => '555-0111',
            'address' => '45 Market St',
            'lead_type' => 'seller',
            'source' => 'referral',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($manager)->put(route('manager.leads.update', $lead), [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'address' => $lead->address,
            'lead_type' => $lead->lead_type,
            'source' => $lead->source,
            'status' => 'contacted',
            'assigned_to' => $assignee->id,
        ]);

        $response->assertSessionHas('status', 'Lead updated successfully.');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'contacted',
            'assigned_to' => $assignee->id,
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Lead status changed from new to contacted.',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Lead assignment changed from Unassigned to Assigned Agent.',
        ]);
    }

    public function test_assignment_change_notification_is_sent_to_opted_in_new_assignee_only(): void
    {
        Notification::fake();

        $manager = User::factory()->create(['name' => 'Primary Agent', 'role' => 'agent']);
        $assignee = User::factory()->create([
            'name' => 'Assigned Agent',
            'role' => 'agent',
            'notify_on_lead_assignment' => true,
        ]);

        $lead = Lead::create([
            'name' => 'Assignment Notification Lead',
            'email' => 'assignment-notification@example.com',
            'phone' => '555-0140',
            'address' => '19 Main St',
            'lead_type' => 'seller',
            'source' => 'referral',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $this->actingAs($manager)->put(route('manager.leads.update', $lead), [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'address' => $lead->address,
            'lead_type' => $lead->lead_type,
            'source' => $lead->source,
            'status' => 'contacted',
            'assigned_to' => $assignee->id,
        ])->assertSessionHas('status', 'Lead updated successfully.');

        Notification::assertSentTo($assignee, LeadAssignmentChangedNotification::class, function (LeadAssignmentChangedNotification $notification): bool {
            return $notification->previousAssignee === 'Unassigned';
        });
        Notification::assertNotSentTo($manager, LeadAssignmentChangedNotification::class);
    }

    public function test_assignment_change_notification_is_not_sent_when_new_assignee_is_opted_out(): void
    {
        Notification::fake();

        $manager = User::factory()->create(['name' => 'Primary Agent', 'role' => 'agent']);
        $assignee = User::factory()->create([
            'name' => 'Assigned Agent',
            'role' => 'agent',
            'notify_on_lead_assignment' => false,
        ]);

        $lead = Lead::create([
            'name' => 'Assignment No Notification Lead',
            'email' => 'assignment-no-notification@example.com',
            'phone' => '555-0141',
            'address' => '20 Main St',
            'lead_type' => 'seller',
            'source' => 'referral',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $this->actingAs($manager)->put(route('manager.leads.update', $lead), [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'address' => $lead->address,
            'lead_type' => $lead->lead_type,
            'source' => $lead->source,
            'status' => 'contacted',
            'assigned_to' => $assignee->id,
        ])->assertSessionHas('status', 'Lead updated successfully.');

        Notification::assertNotSentTo($assignee, LeadAssignmentChangedNotification::class);
    }

    public function test_manager_can_set_generic_inquiry_type_from_backend(): void
    {
        $manager = User::factory()->create(['role' => 'agent']);

        $lead = Lead::create([
            'name' => 'Backend Type Lead',
            'email' => 'backend-type@example.com',
            'phone' => '555-9801',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($manager)->put(route('manager.leads.update', $lead), [
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'address' => $lead->address,
            'lead_type' => 'generic_inquiry',
            'source' => $lead->source,
            'status' => 'contacted',
            'assigned_to' => null,
        ]);

        $response->assertSessionHas('status', 'Lead updated successfully.');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'lead_type' => 'generic_inquiry',
            'status' => 'contacted',
        ]);
    }

    public function test_manager_can_add_lead_activity(): void
    {
        $manager = User::factory()->create(['role' => 'agent']);

        $lead = Lead::create([
            'name' => 'Buyer Lead',
            'email' => 'buyer@example.com',
            'phone' => '555-0112',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'landing_page',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($manager)->post(route('manager.leads.activities.store', $lead), [
            'type' => 'call',
            'description' => 'Initial outreach completed.',
        ]);

        $response->assertSessionHas('status', 'Lead activity added.');

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'call',
            'description' => 'Initial outreach completed.',
        ]);
    }

    public function test_manager_can_create_and_complete_task_with_history_entry(): void
    {
        $manager = User::factory()->create(['role' => 'agent']);

        $lead = Lead::create([
            'name' => 'Home Value Lead',
            'email' => 'value@example.com',
            'phone' => '555-0113',
            'address' => '77 Pine Ave',
            'lead_type' => 'home_value',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $createResponse = $this->actingAs($manager)->post(route('manager.leads.tasks.store', $lead), [
            'title' => 'Schedule valuation call',
            'due_date' => '2026-07-01',
        ]);

        $createResponse->assertSessionHas('status', 'Task created.');

        $task = Task::query()->where('lead_id', $lead->id)->firstOrFail();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Task created: Schedule valuation call.',
        ]);

        $updateResponse = $this->actingAs($manager)->patch(route('manager.leads.tasks.update', [$lead, $task]), [
            'title' => $task->title,
            'due_date' => $task->due_date->format('Y-m-d'),
            'status' => 'complete',
        ]);

        $updateResponse->assertSessionHas('status', 'Task updated.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'complete',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Task "Schedule valuation call" marked as complete.',
        ]);
    }

    public function test_admin_can_delete_lead_and_all_linked_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $lead = Lead::create([
            'name' => 'Delete Me Lead',
            'email' => 'delete-me@example.com',
            'phone' => '555-0229',
            'address' => '12 Ocean Ave',
            'lead_type' => 'seller',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $lead->activities()->create([
            'type' => 'note',
            'description' => 'Delete test activity.',
        ]);

        $lead->tasks()->create([
            'title' => 'Delete test task',
            'due_date' => '2026-07-20',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->delete(route('manager.leads.destroy', $lead));

        $response->assertRedirect(route('manager.leads.index'));
        $response->assertSessionHas('status', 'Lead moved to recycle bin.');

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id]);
        $this->assertDatabaseHas('tasks', ['lead_id' => $lead->id]);
    }

    public function test_agent_cannot_delete_lead(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $lead = Lead::create([
            'name' => 'Protected Lead',
            'email' => 'protected@example.com',
            'phone' => '555-0230',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'landing_page',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($agent)->delete(route('manager.leads.destroy', $lead));

        $response->assertForbidden();
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_admin_can_bulk_delete_leads_and_linked_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $leadOne = Lead::create([
            'name' => 'Bulk Delete One',
            'email' => 'bulk-delete-one@example.com',
            'phone' => '555-0401',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $leadTwo = Lead::create([
            'name' => 'Bulk Delete Two',
            'email' => 'bulk-delete-two@example.com',
            'phone' => '555-0402',
            'address' => null,
            'lead_type' => 'seller',
            'source' => 'referral',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $leadOne->activities()->create([
            'type' => 'note',
            'description' => 'First lead activity.',
        ]);
        $leadTwo->activities()->create([
            'type' => 'note',
            'description' => 'Second lead activity.',
        ]);

        $leadOne->tasks()->create([
            'title' => 'First task',
            'due_date' => '2026-08-01',
            'status' => 'pending',
        ]);
        $leadTwo->tasks()->create([
            'title' => 'Second task',
            'due_date' => '2026-08-02',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->delete(route('manager.leads.bulk-destroy'), [
            'lead_ids' => [$leadOne->id, $leadTwo->id],
        ]);

        $response->assertRedirect(route('manager.leads.index'));
        $response->assertSessionHas('status', '2 leads moved to recycle bin.');

        $this->assertSoftDeleted('leads', ['id' => $leadOne->id]);
        $this->assertSoftDeleted('leads', ['id' => $leadTwo->id]);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $leadOne->id]);
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $leadTwo->id]);
        $this->assertDatabaseHas('tasks', ['lead_id' => $leadOne->id]);
        $this->assertDatabaseHas('tasks', ['lead_id' => $leadTwo->id]);
    }

    public function test_agent_cannot_bulk_delete_leads(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $lead = Lead::create([
            'name' => 'No Bulk Permission',
            'email' => 'no-bulk@example.com',
            'phone' => '555-0403',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($agent)->delete(route('manager.leads.bulk-destroy'), [
            'lead_ids' => [$lead->id],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_admin_can_restore_soft_deleted_lead(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $lead = Lead::create([
            'name' => 'Restore Single Lead',
            'email' => 'restore-single@example.com',
            'phone' => '555-0404',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $lead->delete();

        $response = $this->actingAs($admin)->patch(route('manager.leads.restore', $lead->id));

        $response->assertRedirect(route('manager.leads.index', ['visibility' => 'deleted']));
        $response->assertSessionHas('status', 'Lead restored successfully.');
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'deleted_at' => null]);
    }

    public function test_admin_can_bulk_restore_soft_deleted_leads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $leadOne = Lead::create([
            'name' => 'Restore Bulk One',
            'email' => 'restore-bulk-one@example.com',
            'phone' => '555-0405',
            'address' => null,
            'lead_type' => 'buyer',
            'source' => 'homepage',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $leadTwo = Lead::create([
            'name' => 'Restore Bulk Two',
            'email' => 'restore-bulk-two@example.com',
            'phone' => '555-0406',
            'address' => null,
            'lead_type' => 'seller',
            'source' => 'referral',
            'status' => 'new',
            'assigned_to' => null,
        ]);

        $leadOne->delete();
        $leadTwo->delete();

        $response = $this->actingAs($admin)->patch(route('manager.leads.bulk-restore'), [
            'lead_ids' => [$leadOne->id, $leadTwo->id],
        ]);

        $response->assertRedirect(route('manager.leads.index', ['visibility' => 'deleted']));
        $response->assertSessionHas('status', '2 leads restored successfully.');
        $this->assertDatabaseHas('leads', ['id' => $leadOne->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('leads', ['id' => $leadTwo->id, 'deleted_at' => null]);
    }
}
