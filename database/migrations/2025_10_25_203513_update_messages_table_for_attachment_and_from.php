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
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'attachments')) {
                $table->renameColumn('attachments', 'attachment');
            }
            if (!Schema::hasColumn('messages', 'from')) {
                $table->string('from')->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'attachment')) {
                $table->renameColumn('attachment', 'attachments');
            }
            if (Schema::hasColumn('messages', 'from')) {
                $table->dropColumn('from');
            }
        });
    }
};
