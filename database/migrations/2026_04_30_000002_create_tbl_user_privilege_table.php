<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $usersTable = config('privilege-manager.database.users_table', 'users');
        $usersPrimaryKey = config('privilege-manager.database.users_primary_key', 'id');
        $privilegesTable = config('privilege-manager.database.privileges_table', 'tbl_user_privilege');
        $menusTable = config('privilege-manager.database.menus_table', 'tbl_menu_list');

        Schema::create($privilegesTable, function (Blueprint $table) use ($usersTable, $usersPrimaryKey, $menusTable) {
            $table->increments('idtbl_user_privilege');
            $table->unsignedInteger('tbl_user_idtbl_user');
            $table->unsignedInteger('tbl_menu_list_idtbl_menu_list');
            $table->tinyInteger('access_status')->default(1);
            $table->tinyInteger('add')->default(0);
            $table->tinyInteger('edit')->default(0);
            $table->tinyInteger('statuschange')->default(0);
            $table->tinyInteger('remove')->default(0);
            $table->tinyInteger('status')->default(1);

            $table->foreign('tbl_user_idtbl_user')
                ->references($usersPrimaryKey)
                ->on($usersTable)
                ->cascadeOnDelete();

            $table->foreign('tbl_menu_list_idtbl_menu_list')
                ->references('idtbl_menu_list')
                ->on($menusTable)
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('privilege-manager.database.privileges_table', 'tbl_user_privilege'));
    }
};
