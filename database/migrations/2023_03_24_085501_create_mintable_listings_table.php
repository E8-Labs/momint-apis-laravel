<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Minting\MintableListingStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mintable_listings', function (Blueprint $table) {
            $table->id();
            $table->string('listing_name');
            // $table->string('street_address')->nullable();
            $table->boolean('is_explicit_content')->default(0);
            $table->string('listing_description')->nullable();
            $table->double("listing_price")->nullable();
            $table->string('currency')->nullable();
            $table->double('royalty_percentage')->nullable();
            
            $table->unsignedBigInteger('minting_status')->default(MintableListingStatus::StatusMinted);
            $table->foreign('minting_status')->references('id')->on('mintable_listing_statuses')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mintable_listings');
    }
};
