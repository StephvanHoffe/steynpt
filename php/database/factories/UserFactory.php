<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\ReferralProgram;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Leden voor tests: standaard een klant met tweestapsverificatie aan en een vers wachtwoord ("geheim123!"),
 * zodat de middleware 'member' meteen toegang geeft.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $firstName = fake()->firstName();

        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('geheim123!'),
            'first_name' => $firstName,
            'last_name' => fake()->lastName(),
            'goal' => 'fitter',
            'coaching_status' => 'geen',
            'referral_code' => ReferralProgram::makeReferralCode($firstName),
            'role' => 'member',
            'password_changed_at' => now(),
            'totp_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
            'totp_enabled_at' => now(),
        ];
    }

    /** Beheerder (Steyn). */
    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    /** Nog zonder tweestapsverificatie (net geregistreerd). */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn () => ['totp_secret' => null, 'totp_enabled_at' => null]);
    }
}
