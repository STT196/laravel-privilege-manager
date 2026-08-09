<?php

namespace LaravelPrivilegeManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MenuSeeder extends Seeder
{
    /**
     * Default menu records seeded on fresh install.
     *
     * IDs 1-3 match the conventional menu assignments used in the
     * SFA codebase (UserController constants MENU_USER_PRIVILEGE=1,
     * MENU_USER_TYPE=2, MENU_USER_ACCOUNT=3).
     */
    public function run(): void
    {
        $menusTable = config('privilege-manager.database.menus_table', 'tbl_menu_list');
        $now = Carbon::now();

        $menus = [
            [
                'idtbl_menu_list' => 1,
                'menu'            => 'User Privileges',
                'menuurl'         => '/users/privileges',
                'displayorder'    => 1,
                'status'          => 1,
                'insertdatetime'  => $now,
                'updatedatetime'  => $now,
            ],
            [
                'idtbl_menu_list' => 2,
                'menu'            => 'User Type',
                'menuurl'         => '/users/type',
                'displayorder'    => 2,
                'status'          => 1,
                'insertdatetime'  => $now,
                'updatedatetime'  => $now,
            ],
            [
                'idtbl_menu_list' => 3,
                'menu'            => 'User Account',
                'menuurl'         => '/users/account',
                'displayorder'    => 3,
                'status'          => 1,
                'insertdatetime'  => $now,
                'updatedatetime'  => $now,
            ],
        ];

        foreach ($menus as $menu) {
            DB::table($menusTable)->insertOrIgnore($menu);
        }
    }
}
