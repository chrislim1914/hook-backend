<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableAds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->increments('idads');
            $table->string('adstitle')->nullable($value = true);;
            $table->string('adsimage')->nullable($value = true);;
            $table->string('adsspaces')->nullable($value = true);;
            $table->string('adslink')->nullable($value = true);;
            $table->string('adsstart')->nullable($value = true);;
            $table->string('adsend')->nullable($value = true);;
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
        Schema::dropIfExists('ads');
    }
}
