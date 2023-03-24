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
        Schema::create('mintable_listing_statuses', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->timestamps();
        });
        \DB::table('mintable_listing_statuses')->insert([
            ['id'=> MintableListingStatus::StatusMinted, 'name' => 'Minted'],
            ['id'=> MintableListingStatus::StatusListed, 'name' => 'Listed'],
            ['id'=> MintableListingStatus::StatusBoth, 'name' => 'Both'],
            
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mintable_listing_statuses');
    }
};
