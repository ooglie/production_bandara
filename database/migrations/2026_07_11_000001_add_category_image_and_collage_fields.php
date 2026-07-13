<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'image_path')) {
                $table->string('image_path')->nullable()->after('description');
            }

            if (! Schema::hasColumn('categories', 'collage_image_path')) {
                $table->string('collage_image_path')->nullable()->after('image_path');
            }

            if (! Schema::hasColumn('categories', 'collage_updated_at')) {
                $table->timestamp('collage_updated_at')->nullable()->after('collage_image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'collage_updated_at')) {
                $table->dropColumn('collage_updated_at');
            }

            if (Schema::hasColumn('categories', 'collage_image_path')) {
                $table->dropColumn('collage_image_path');
            }

            if (Schema::hasColumn('categories', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
