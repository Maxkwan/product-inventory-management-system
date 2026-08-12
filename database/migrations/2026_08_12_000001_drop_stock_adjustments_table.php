<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }

    public function down(): void
    {
        // The removed stock-adjustment feature is intentionally not recreated.
    }
};
