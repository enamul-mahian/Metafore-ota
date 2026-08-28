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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->string('group', 100)
                ->default('general');

            $table->string('key', 150);

            $table->longText('value')
                ->nullable();

            $table->string('type', 30)
                ->default('string');

            $table->boolean('is_public')
                ->default(false);

            $table->timestamps();

            $table->unique(
                ['group', 'key'],
                'settings_group_key_unique'
            );

            $table->index(
                'group',
                'settings_group_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};