<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class UsersRenameLoginToUsername extends Migration
{

    public function up()
    {
        Schema::table('users', function($table)
        {
            $table->renameColumn('login', 'username');
        });
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'login')) {
            Schema::table('users', function($table)
            {
                $table->renameColumn('username', 'login');
            });
        }
    }
}
