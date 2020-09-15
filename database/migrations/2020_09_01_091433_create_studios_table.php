<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudiosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('studios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->decimal('basic_price',13,2);
            $table->decimal('aditional_friday_price',13,2);
            $table->decimal('aditional_saturday_price',13,2);
            $table->decimal('aditional_sunday_price',13,2);
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('studios');
    }
}
