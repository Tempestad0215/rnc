<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rncs', function (Blueprint $table) {
            $table->id();
            $table->string('rnc');
            $table->string('razon_social');
            $table->string('actividad');
            $table->string('status');
            $table->string('type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rncs');
    }
};
