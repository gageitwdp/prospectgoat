<?php

namespace Tests\Feature;

use App\Mail\EventSignupThankYouMail;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EventModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_page_shows_only_published_events(): void
    {
        Event::create([
            'name' => 'Published Event',
            'slug' => 'published-event',
            'location' => 'Atlanta',
            'event_time' => now()->addDays(7),
            'details' => 'Public event details.',
            'status' => 'published',
        ]);

        Event::create([
            'name' => 'Draft Event',
            'slug' => 'draft-event',
            'location' => 'Marietta',
            'event_time' => now()->addDays(14),
            'details' => 'Draft event details.',
            'status' => 'draft',
        ]);

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertSee('Published Event');
        $response->assertDontSee('Draft Event');
    }

    public function test_guest_can_submit_event_signup_and_lead_is_created(): void
    {
        Mail::fake();

        $event = Event::create([
            'name' => 'Summer Meetup',
            'slug' => 'summer-meetup',
            'location' => 'Buckhead',
            'event_time' => now()->addDays(10),
            'details' => 'Meet and greet.',
            'status' => 'published',
        ]);

        $response = $this->post(route('events.signup.store', $event->slug), [
            'first_name' => 'Jordan',
            'last_name' => 'Parker',
            'email' => 'jordan@example.com',
            'phone' => '555-0410',
            'working_with_agent' => '1',
            'agent_first_name' => 'Taylor',
            'agent_last_name' => 'Smith',
        ]);

        $response->assertRedirect(route('events.signup.show', $event->slug));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('leads', [
            'name' => 'Jordan Parker',
            'email' => 'jordan@example.com',
            'source' => 'landing_page',
            'lead_type' => 'generic_inquiry',
            'working_with_agent' => true,
        ]);

        $leadId = (int) DB::table('leads')->where('email', 'jordan@example.com')->value('id');

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'lead_id' => $leadId,
            'first_name' => 'Jordan',
            'last_name' => 'Parker',
            'working_with_agent' => true,
            'agent_first_name' => 'Taylor',
            'agent_last_name' => 'Smith',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $leadId,
            'type' => 'note',
            'description' => 'Working with an agent: Yes.',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $leadId,
            'type' => 'note',
            'description' => 'Agent on signed agreement: Taylor Smith.',
        ]);

        Mail::assertSent(EventSignupThankYouMail::class, function (EventSignupThankYouMail $mail): bool {
            return $mail->hasTo('jordan@example.com')
                && $mail->firstName === 'Jordan'
                && $mail->isWorkingWithAgent === true
                && $mail->envelope()->subject === 'Thank you for attending our open house';
        });
    }

    public function test_event_signup_sends_thank_you_email_with_non_agent_message_when_not_working_with_agent(): void
    {
        Mail::fake();

        $event = Event::create([
            'name' => 'Open House Tour',
            'slug' => 'open-house-tour',
            'location' => 'Woodstock',
            'event_time' => now()->addDays(5),
            'details' => 'Tour and Q&A.',
            'status' => 'published',
        ]);

        $response = $this->post(route('events.signup.store', $event->slug), [
            'first_name' => 'Avery',
            'last_name' => 'Lane',
            'email' => 'avery@example.com',
            'phone' => '555-1111',
            'working_with_agent' => '0',
        ]);

        $response->assertRedirect(route('events.signup.show', $event->slug));

        Mail::assertSent(EventSignupThankYouMail::class, function (EventSignupThankYouMail $mail): bool {
            if (! $mail->hasTo('avery@example.com')) {
                return false;
            }

            $rendered = $mail->render();

            return str_contains($rendered, 'If you are not signed with an agent, I would love to work with you and help make your dream a reality.');
        });
    }

    public function test_event_signup_thank_you_email_hides_non_agent_message_when_working_with_agent(): void
    {
        Mail::fake();

        $event = Event::create([
            'name' => 'Neighborhood Open House',
            'slug' => 'neighborhood-open-house',
            'location' => 'Roswell',
            'event_time' => now()->addDays(8),
            'details' => 'Neighborhood event.',
            'status' => 'published',
        ]);

        $response = $this->post(route('events.signup.store', $event->slug), [
            'first_name' => 'Chris',
            'last_name' => 'Morgan',
            'email' => 'chris@example.com',
            'phone' => '555-2222',
            'working_with_agent' => '1',
            'agent_first_name' => 'Robin',
            'agent_last_name' => 'Stone',
        ]);

        $response->assertRedirect(route('events.signup.show', $event->slug));

        Mail::assertSent(EventSignupThankYouMail::class, function (EventSignupThankYouMail $mail): bool {
            if (! $mail->hasTo('chris@example.com')) {
                return false;
            }

            $rendered = $mail->render();

            return ! str_contains($rendered, 'If you are not signed with an agent, I would love to work with you and help make your dream a reality.');
        });
    }

    public function test_event_signup_requires_agent_name_fields_when_signed_agreement_is_yes(): void
    {
        $event = Event::create([
            'name' => 'Contract Session',
            'slug' => 'contract-session',
            'location' => 'Midtown',
            'event_time' => now()->addDays(10),
            'details' => 'Contract support event.',
            'status' => 'published',
        ]);

        $response = $this->from(route('events.signup.show', $event->slug))->post(route('events.signup.store', $event->slug), [
            'first_name' => 'Jamie',
            'last_name' => 'Lopez',
            'email' => 'jamie@example.com',
            'phone' => '555-0999',
            'working_with_agent' => '1',
            'agent_first_name' => '',
            'agent_last_name' => '',
        ]);

        $response->assertRedirect(route('events.signup.show', $event->slug));
        $response->assertSessionHasErrors(['agent_first_name', 'agent_last_name']);
    }

    public function test_draft_event_signup_page_is_not_publicly_accessible(): void
    {
        $event = Event::create([
            'name' => 'Private Planning Session',
            'slug' => 'private-planning-session',
            'location' => 'Sandy Springs',
            'event_time' => now()->addDays(5),
            'details' => null,
            'status' => 'draft',
        ]);

        $response = $this->get(route('events.signup.show', $event->slug));

        $response->assertNotFound();
    }

    public function test_admin_can_create_and_edit_event_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $createResponse = $this->actingAs($admin)->post(route('admin.events.store'), [
            'name' => 'Investor Q&A',
            'slug' => 'investor-qa',
            'location' => 'Roswell',
            'event_time' => now()->addDays(12)->toDateTimeString(),
            'details' => 'Bring your questions.',
            'status' => 'draft',
        ]);

        $createResponse->assertRedirect(route('admin.events.index'));

        $event = Event::query()->where('slug', 'investor-qa')->firstOrFail();

        $updateResponse = $this->actingAs($admin)->put(route('admin.events.update', $event), [
            'name' => 'Investor Q&A Updated',
            'slug' => 'investor-qa-updated',
            'location' => 'Roswell City Hall',
            'event_time' => now()->addDays(15)->toDateTimeString(),
            'details' => 'Updated details.',
            'status' => 'published',
        ]);

        $updateResponse->assertRedirect(route('admin.events.index'));
        $updateResponse->assertSessionHas('status', 'Event updated successfully.');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'name' => 'Investor Q&A Updated',
            'slug' => 'investor-qa-updated',
            'status' => 'published',
        ]);
    }

    public function test_agent_cannot_access_admin_events_module(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);

        $response = $this->actingAs($agent)->get(route('admin.events.index'));

        $response->assertForbidden();
    }
}
