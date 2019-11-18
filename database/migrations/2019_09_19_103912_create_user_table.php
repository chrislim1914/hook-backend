<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('iduser');
            $table->string('email')->unique();
            $table->string('firstname')->nullable($value = true);
            $table->string('lastname')->nullable($value = true);
            $table->string('username')->unique()->nullable($value = true);
            $table->string('password')->nullable($value = true);
            $table->string('contactno')->nullable($value = true);
            $table->longText('profile_photo')->nullable($value = true);
            $table->string('snsproviderid')->nullable($value = true);
            $table->tinyInteger('emailverify')->nullable($value = true);
            $table->longText('emailverifytoken')->nullable($value = true);
            $table->longText('resetpasswordtoken')->nullable($value = true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
