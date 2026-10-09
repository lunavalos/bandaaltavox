<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adicionales que el paquete ya incluye sin costo (ej. botargas Juan Gabriel y Luis Miguel)
        Schema::create('package_included_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_addon_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['package_id', 'service_addon_id']);
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            // Concepto incluido en el paquete: se muestra en la cotización pero no suma al total
            $table->boolean('is_included')->default(false)->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('is_included');
        });

        Schema::dropIfExists('package_included_addons');
    }
};
