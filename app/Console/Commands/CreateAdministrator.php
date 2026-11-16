<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\History;
use Database\Seeders\FoundationSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'sarpras:admin';

    protected $description = 'Buat administrator melalui prompt password tersembunyi, tanpa password default';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Jalankan secara interaktif. Password tidak diterima sebagai argumen.');

            return self::FAILURE;
        }
        $name = $this->ask('Nama administrator');
        $email = $this->ask('Email');
        $password = $this->secret('Password (minimal 12 karakter, huruf dan angka)');
        $confirmation = $this->secret('Ulangi password');
        $v = Validator::make(['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $confirmation], ['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users,email', 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

return self::FAILURE;
        }
        $this->callSilent('db:seed', ['--class' => FoundationSeeder::class, '--force' => true]);
        DB::transaction(function () use ($name, $email, $password) {
            $u = User::create(['name' => $name, 'email' => strtolower($email), 'password' => $password, 'role_id' => Role::where('slug', 'administrator')->value('id'), 'is_active' => true]);
            History::audit($u, 'administrator.bootstrapped', $u, ['name' => $u->name]);
        });
        $this->info('Administrator dibuat. Tidak ada password yang dicetak.');

        return self::SUCCESS;
    }
}
