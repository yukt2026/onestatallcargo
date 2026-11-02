<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'pickup_name')) {
                $table->string('pickup_name')->nullable()->after('receiver_phone');
            }
            if (!Schema::hasColumn('shipments', 'pickup_phone')) {
                $table->string('pickup_phone')->nullable()->after('pickup_name');
            }
            if (!Schema::hasColumn('shipments', 'pickup_state')) {
                $table->string('pickup_state')->nullable()->after('pickup_city');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['pickup_name', 'pickup_phone', 'pickup_state']);
        });
    }
};
