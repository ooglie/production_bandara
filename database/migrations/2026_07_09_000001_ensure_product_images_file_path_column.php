<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_images')) {
            return;
        }

        // Older project migrations created this column as "path", while the
        // current model/controllers and latest DB use "file_path". Keep fresh
        // installs and migrated databases aligned before multi-image uploads run.
        if (Schema::hasColumn('product_images', 'path') && ! Schema::hasColumn('product_images', 'file_path')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->renameColumn('path', 'file_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_images')) {
            return;
        }

        if (Schema::hasColumn('product_images', 'file_path') && ! Schema::hasColumn('product_images', 'path')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->renameColumn('file_path', 'path');
            });
        }
    }
};
