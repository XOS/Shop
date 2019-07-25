<?php
use Illuminate\Support\Facades\Schema; use Illuminate\Database\Schema\Blueprint; use Illuminate\Database\Migrations\Migration; class CreateUsersTable extends Migration { public function up() { Schema::create('users', function (Blueprint $sp232d91) { $sp232d91->increments('id'); $sp232d91->string('email', 100)->unique(); $sp232d91->string('mobile')->nullable(); $sp232d91->string('password', 100); $sp232d91->integer('m_paid')->default(0); $sp232d91->integer('m_frozen')->default(0); $sp232d91->integer('m_all')->default(0); $sp232d91->rememberToken(); $sp232d91->timestamps(); }); } public function down() { Schema::dropIfExists('users'); } }