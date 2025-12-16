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
        Schema::table('films', function (Blueprint $table) {
            $table->string('source_type')->default('TELEGRAM')->after('downloads');
            $table->text('video_url')->nullable()->after('source_type');
            $table->longText('description')->nullable()->after('title');
            $table->index('source_type');

            if (Schema::hasColumn('films', 'chat_id')) {
                $table->string('chat_id')->nullable()->change();
            }

            if (Schema::hasColumn('films', 'message_id')) {
                $table->string('message_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('films', function (Blueprint $table) {
            $table->dropIndex(['source_type']);
            $table->dropColumn('source_type');
            $table->dropColumn('video_url');
            $table->dropColumn('description');
        });
    }
};
