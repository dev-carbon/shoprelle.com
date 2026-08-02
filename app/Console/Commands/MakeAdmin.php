<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

/**
 * Creates a back-office account, or promotes an existing one.
 *
 * Public registration is disabled, so this is the only way in. The account is
 * created with its email already verified: the back office sits behind the
 * `verified` middleware, and an administrator created from the console has
 * nowhere to click a verification link from.
 */
class MakeAdmin extends Command
{
    protected $signature = 'shoprelle:make-admin
                            {--email= : Email address of the administrator}
                            {--name= : Display name}
                            {--password= : Password (prompted for when omitted)}';

    protected $description = 'Create a Shoprelle back-office administrator, or promote an existing user';

    public function handle(): int
    {
        $email = $this->option('email') ?: text(
            label: 'Adresse email',
            placeholder: 'prenom@shoprelle.com',
            required: true,
        );

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            return $this->promote($existing);
        }

        $name = $this->option('name') ?: text(
            label: 'Nom affiché',
            placeholder: 'Awa Ndiaye',
            required: true,
        );

        // Passing --password leaves the secret in the shell history and in the
        // process list, so the interactive prompt stays the default path.
        $password = $this->option('password') ?: promptPassword(
            label: 'Mot de passe',
            required: true,
        );

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', Password::default()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->components->info("Administrateur créé : {$user->email}");

        if ($this->option('password')) {
            $this->components->warn(
                'Le mot de passe a été passé en ligne de commande : pensez à nettoyer l\'historique du shell.',
            );
        }

        return self::SUCCESS;
    }

    /**
     * Grant back-office access to somebody who already has an account.
     */
    private function promote(User $user): int
    {
        if ($user->isAdmin()) {
            $this->components->warn("{$user->email} est déjà administrateur.");

            return self::SUCCESS;
        }

        $user->forceFill([
            'is_admin' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $this->components->info("{$user->email} est désormais administrateur.");

        return self::SUCCESS;
    }
}
