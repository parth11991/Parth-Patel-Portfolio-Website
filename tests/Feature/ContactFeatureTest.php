<?php

namespace Tests\Feature;

use App\Filament\Pages\ContactInbox;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ContactFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_failure_does_not_reject_a_saved_message(): void
    {
        Exceptions::fake();
        SiteSetting::factory()->create(['contact_recipient_email' => 'owner@example.com']);
        $failure = new RuntimeException('Mail transport unavailable');
        Notification::shouldReceive('send')->once()->andThrow($failure);

        $this->post(route('contact.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('contact.status', 'sent');

        $this->assertDatabaseCount('contact_messages', 1);
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception === $failure);
    }

    public function test_persistence_failure_returns_an_error_without_notification(): void
    {
        Exceptions::fake();
        Notification::fake();
        $failure = new RuntimeException('Database unavailable');
        Event::listen('eloquent.creating: '.ContactMessage::class, function () use ($failure): void {
            throw $failure;
        });

        $this->post(route('contact.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHasErrors('contact')
            ->assertSessionMissing('contact.status');

        $this->assertDatabaseCount('contact_messages', 0);
        Notification::assertNothingSent();
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception === $failure);
    }

    public function test_guest_can_submit_valid_contact_message_and_notification_is_logged_to_configured_recipient(): void
    {
        config(['mail.default' => 'log-safe']);

        Notification::fake();
        SiteSetting::factory()->create(['contact_recipient_email' => 'owner@example.com']);

        $response = $this->post(route('contact.store'), $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHas('contact.status', 'sent');

        $message = ContactMessage::query()->sole();

        $this->assertSame('Ada Lovelace', $message->name);
        $this->assertSame('ada@example.com', $message->email);
        $this->assertSame('Project inquiry', $message->subject);
        $this->assertNull($message->read_at);
        $this->assertSame('log-safe', config('mail.default'));
        $this->assertSame('log', config('mail.mailers.log-safe.transport'));

        Notification::assertSentOnDemand(ContactMessageReceived::class, function (
            ContactMessageReceived $notification,
            array $channels,
            AnonymousNotifiable $notifiable,
        ) use ($message): bool {
            return $channels === ['mail']
                && $notifiable->routeNotificationFor('mail') === 'owner@example.com'
                && $notification->message->is($message);
        });
    }

    public function test_valid_contact_message_without_recipient_is_persisted_without_notification(): void
    {
        Notification::fake();
        SiteSetting::factory()->create(['contact_recipient_email' => null]);

        $this->post(route('contact.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('contact.status', 'sent');

        $this->assertDatabaseCount('contact_messages', 1);
        Notification::assertNothingSent();
    }

    public function test_contact_form_validates_required_format_and_length_rules(): void
    {
        Notification::fake();
        SiteSetting::factory()->create(['contact_recipient_email' => 'owner@example.com']);

        $response = $this->from('/#contact')->post(route('contact.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => str_repeat('s', 161),
            'message' => 'short',
            'company' => '',
        ]);

        $response->assertRedirect('/#contact');
        $response->assertSessionHasErrors([
            'name',
            'email',
            'subject',
            'message',
        ]);

        $this->assertDatabaseCount('contact_messages', 0);
        Notification::assertNothingSent();
    }

    public function test_contact_honeypot_rejects_spam_without_persistence_or_notification(): void
    {
        Notification::fake();
        SiteSetting::factory()->create(['contact_recipient_email' => 'owner@example.com']);

        $this->post(route('contact.store'), [
            ...$this->validPayload(),
            'company' => 'Spam Co',
        ])->assertSessionHasErrors(['company']);

        $this->assertDatabaseCount('contact_messages', 0);
        Notification::assertNothingSent();
    }

    public function test_contact_route_is_throttled(): void
    {
        Notification::fake();
        SiteSetting::factory()->create(['contact_recipient_email' => null]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('contact.store'), $this->validPayload())
                ->assertRedirect();
        }

        $this->post(route('contact.store'), $this->validPayload())
            ->assertTooManyRequests();

        $this->assertDatabaseCount('contact_messages', 5);
    }

    public function test_contact_throttle_cannot_be_bypassed_by_rotating_email_addresses(): void
    {
        Notification::fake();
        SiteSetting::factory()->create(['contact_recipient_email' => null]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('contact.store'), [
                ...$this->validPayload(),
                'email' => "sender{$attempt}@example.com",
            ])->assertRedirect();
        }

        $this->post(route('contact.store'), [
            ...$this->validPayload(),
            'email' => 'sender6@example.com',
        ])->assertTooManyRequests();

        $this->assertDatabaseCount('contact_messages', 5);
    }

    public function test_admin_can_list_view_mark_read_unread_and_delete_contact_messages(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $message = ContactMessage::factory()->create([
            'name' => 'Unread Sender',
            'subject' => 'Unread Subject',
        ]);

        $this->get('/admin/contact-inbox')->assertOk();
        $this->assertSame('1', ContactInbox::getNavigationBadge());

        Livewire::test(ContactInbox::class)
            ->assertCanSeeTableRecords([$message])
            ->assertTableActionExists('view', record: $message)
            ->assertTableActionDoesNotExist('edit', record: $message)
            ->callTableAction('markRead', $message)
            ->assertNotified();

        $this->assertNotNull($message->refresh()->read_at);
        $this->assertNull(ContactInbox::getNavigationBadge());

        Livewire::test(ContactInbox::class)
            ->assertTableActionHidden('markRead', $message)
            ->assertTableActionVisible('markUnread', $message)
            ->callTableAction('markUnread', $message)
            ->assertNotified();

        $this->assertNull($message->refresh()->read_at);

        Livewire::test(ContactInbox::class)
            ->callTableAction('delete', $message)
            ->assertNotified();

        $this->assertModelMissing($message);
    }

    public function test_contact_inbox_requires_admin_authorization(): void
    {
        $this->get('/admin/contact-inbox')->assertRedirect('/admin/login');

        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/contact-inbox')->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'subject' => 'Project inquiry',
            'message' => 'I would like to discuss a Laravel and Vue project.',
            'company' => '',
        ];
    }
}
