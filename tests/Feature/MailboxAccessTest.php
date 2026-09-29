<?php

namespace Tests\Feature;

use App\Models\MobileMenuAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailboxAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_mailbox_landing_page_without_exposing_credentials(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('mailbox.index'))
            ->assertOk()
            ->assertSee('Messagerie institutionnelle')
            ->assertSee('https://mail.tresorpublic.ga/', false)
            ->assertSee('ne conserve et ne journalise jamais votre mot de passe');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('mailbox.index'))->assertRedirect(route('login'));
    }

    public function test_mobile_mailbox_route_requires_the_assignable_menu(): void
    {
        $role = Role::query()->create([
            'slug' => 'agent_mail_mobile',
            'name' => 'Agent messagerie mobile',
            'hierarchy_level' => 10,
            'active' => true,
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'role' => 'agent_mail_mobile',
        ]);

        $headers = ['User-Agent' => 'Android WebView DGCPT-Android/1.2'];

        $this->actingAs($user)
            ->withHeaders($headers)
            ->get(route('mailbox.index'))
            ->assertForbidden();

        MobileMenuAssignment::query()->create([
            'subject_type' => MobileMenuAssignment::SUBJECT_USER,
            'subject_id' => $user->id,
            'menu_key' => 'mailbox',
        ]);

        $this->actingAs($user)
            ->withHeaders($headers)
            ->get(route('mailbox.index'))
            ->assertOk()
            ->assertSee('Rester connecté')
            ->assertSee('uniquement la session sécurisée')
            ->assertSee('bouton Retour');
    }
}
