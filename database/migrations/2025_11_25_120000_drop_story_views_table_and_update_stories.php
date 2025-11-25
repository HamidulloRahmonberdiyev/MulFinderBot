<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('story_views');

        if (!Schema::hasColumn('stories', 'url')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->string('url')->nullable()->after('image');
            });
        }

        if (!Schema::hasColumn('stories', 'likes')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->unsignedBigInteger('likes')->default(0)->after('views_count');
            });
        }

        DB::table('stories')
            ->whereNull('views_count')
            ->update(['views_count' => 0]);
    }

    public function down(): void
    {
        $columnsToDrop = [];

        if (Schema::hasColumn('stories', 'likes')) {
            $columnsToDrop[] = 'likes';
        }

        if (Schema::hasColumn('stories', 'url')) {
            $columnsToDrop[] = 'url';
        }

        if (!empty($columnsToDrop)) {
            Schema::table('stories', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->index();
            $table->timestamps();

            $table->unique(['story_id', 'ip_address']);
        });
    }
};

