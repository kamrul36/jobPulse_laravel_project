<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Insert default roles
        DB::table('roles')->insert([
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full system access', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrative access', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Candidate', 'slug' => 'candidate', 'description' => 'Job seeker user', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Employer', 'slug' => 'employer', 'description' => 'Job poster user', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
