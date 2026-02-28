<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('salary')->nullable();
            $table->date('deadline')->nullable();
            $table->string('open_position')->nullable();
            $table->string('location');
            $table->integer('views')->default(0);
            $table->text('skills');
            $table->string('experience')->nullable();//disable this in production
            $table->enum('type', ['full_time', 'remote', 'part_time', 'project_basis', 'freelance']);
            $table->uuid('employer_id'); // UUID to match users.id
            $table->foreign('employer_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories');
            $table->boolean('isFeatured')->default(0);
            $table->boolean('status')->default(0);
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
