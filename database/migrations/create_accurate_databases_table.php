<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accurate_databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('accurate_connections')->onDelete('cascade');
            $table->string('database_id');
            $table->string('alias');
            $table->string('company_name');
            $table->string('host');
            $table->text('session_id');
            $table->timestamp('session_expires_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique([
                'connection_id',
                'database_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accurate_databases');
    }
};
