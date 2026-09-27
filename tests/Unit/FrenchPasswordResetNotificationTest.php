<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class FrenchPasswordResetNotificationTest extends TestCase
{
    public function test_password_reset_email_is_fully_localized_in_french(): void
    {
        $this->app->setLocale('fr');

        $user = new User([
            'name' => 'Agent de contrôle',
            'email' => 'agent@example.test',
        ]);

        $message = (new ResetPassword('jeton-de-test'))->toMail($user);

        $this->assertSame('Réinitialisation de votre mot de passe', $message->subject);
        $this->assertSame('Réinitialiser le mot de passe', $message->actionText);
        $this->assertSame('Bonjour !', __('Hello!'));
        $this->assertSame('Cordialement,', __('Regards,'));
        $this->assertContains(
            "Vous recevez ce courriel parce qu'une demande de réinitialisation du mot de passe de votre compte a été effectuée.",
            $message->introLines
        );
        $this->assertContains(
            "Si vous n'êtes pas à l'origine de cette demande, aucune action n'est requise.",
            $message->outroLines
        );
        $this->assertSame(900, config('auth.passwords.users.throttle'));
        $this->assertSame(
            'Un lien de réinitialisation a déjà été envoyé. Veuillez attendre 15 minutes avant de demander un nouveau lien.',
            __('passwords.throttled', ['minutes' => 15])
        );
    }
}
