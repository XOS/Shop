<?php

use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'email' => 'admin@nange.cn',
            'password' => bcrypt('123456'),
            'created_at' => \Carbon\Carbon::now(),
        ]);
    }
}
