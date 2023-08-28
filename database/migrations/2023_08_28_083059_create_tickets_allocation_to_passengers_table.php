<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsAllocationToPassengersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets_allocation_to_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticketId')
            ->constrained('tickets')
            ->onDelete('cascade')
            ->onUpdate('cascade');

            $table->foreignId('passengerId')
            ->constrained('passengers')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            
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
        Schema::dropIfExists('tickets_allocation_to_passengers');
    }
}
