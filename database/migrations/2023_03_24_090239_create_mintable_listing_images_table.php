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
        Schema::create('mintable_listing_images', function (Blueprint $table) {
            $table->id();
            $table->string('image_url');
            $table->string('ipfs_hash');
            $table->string('image_location')->default("");
            $table->double('image_width');
            $table->double('image_height');
            $table->double('lat')->nullable();
            $table->double('lang')->nullable();
            $table->string("image_count")->default("35589");
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
        Schema::dropIfExists('mintable_listing_images');
    }
};
