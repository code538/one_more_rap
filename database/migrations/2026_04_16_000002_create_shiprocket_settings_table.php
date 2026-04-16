<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shiprocket_settings', function (Blueprint $table) {
            $table->id();

            $table->enum('mode', ['test', 'live'])->default('test');
            $table->boolean('status')->default(0); // 1 = active

            $table->string('base_url')->nullable(); // defaults to Shiprocket v2 if null

            // Shiprocket login credentials
            $table->string('test_email')->nullable();
            $table->string('test_password')->nullable();
            $table->string('live_email')->nullable();
            $table->string('live_password')->nullable();

            // Defaults used for shipment creation
            $table->string('channel_id')->nullable();
            $table->string('pickup_location')->nullable();
            $table->string('company_name')->nullable();

            $table->decimal('default_weight', 10, 2)->default(0.50);
            $table->decimal('default_length', 10, 2)->default(10.00);
            $table->decimal('default_breadth', 10, 2)->default(10.00);
            $table->decimal('default_height', 10, 2)->default(5.00);

            $table->unsignedInteger('token_cache_minutes')->default(720);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shiprocket_settings');
    }
};

