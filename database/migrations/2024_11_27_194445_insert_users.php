<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $user = new \App\Models\User();
        $user->name = 'Admin';
        $user->email = 'admin@teste.com.br';
        $user->email_verified_at = now()->getTimestamp();
        $user->password = bcrypt('12345678');
        $user->phone = '(11) 9999-9999';
        $user->save();
        $user->createToken(Str::random(60),)->plainTextToken;

        $user = new \App\Models\User();
        $user->name = 'Beltrano da Silveira';
        $user->email = 'beltrano@teste.com.br';
        $user->email_verified_at = now()->getTimestamp();
        $user->password = bcrypt('12345678');
        $user->phone = '(11) 9999-8888';
        $user->save();
        $user->createToken(Str::random(60))->plainTextToken;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('users')->truncate();
    }
};
