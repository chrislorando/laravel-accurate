<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accurate_connections', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();

            // OAuth token
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('token_type')->default('bearer');
            $table->timestamp('expires_at')->nullable();

            // Accurate user info
            $table->string('accurate_user_id')->nullable();
            $table->string('accurate_user_name')->nullable();
            $table->string('accurate_user_nickname')->nullable();
            $table->string('accurate_user_email')->nullable();
            $table->string('accurate_user_mobile')->nullable();

            $table->json('scopes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accurate_connections');
    }
};
