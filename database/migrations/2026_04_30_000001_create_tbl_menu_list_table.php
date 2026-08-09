<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('privilege-manager.database.menus_table', 'tbl_menu_list'), function (Blueprint $table) {
            $table->increments('idtbl_menu_list');
            $table->string('menu', 255);
            $table->string('menuurl', 255)->nullable();
            $table->integer('displayorder')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamp('insertdatetime')->nullable();
            $table->timestamp('updatedatetime')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('privilege-manager.database.menus_table', 'tbl_menu_list'));
    }
};
