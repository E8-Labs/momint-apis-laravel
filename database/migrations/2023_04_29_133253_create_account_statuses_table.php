<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Auth\AccountStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        \DB::table('account_statuses')->insert([
            ['id'=> AccountStatus::StatusPending, 'name' => 'Pending'],
            ['id'=> AccountStatus::StatusActive, 'name' => 'Active'],
            ['id'=> AccountStatus::StatusDisabled, 'name' => 'Disabled'],
            ['id'=> AccountStatus::StatusDeleted, 'name' => 'Deleted'],
            
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_statuses');
    }
};
