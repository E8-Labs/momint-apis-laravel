<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mintable_listing_tags', function (Blueprint $table) {
            $table->id();
            $table->string("tag");
            $table->unsignedBigInteger('listing_id');
            $table->foreign('listing_id')->references('id')->on('mintable_listings')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mintable_listing_tags');
    }
};
