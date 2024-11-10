<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') 
            ?? throw new \Exception('Required email before launch app:create-admin example@test.com');

        $user = User::where('email', '=', $email)
            ->first()
            ?? new User(compact('email'));

        $password = Str::random(10);

        $user->fill([
            'name' => $email,
            'password' => Hash::make($password),
        ]);

        echo "$password\n";

        if($user->save())
            return 0;
        return 1;
    }
}
