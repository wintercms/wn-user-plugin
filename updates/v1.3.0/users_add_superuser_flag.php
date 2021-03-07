<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class UsersAddSuperuserFlag extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            $table->boolean('is_superuser')->default(false);
        });
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'is_superuser')) {
            Schema::table('users', function($table)
            {
                $table->dropColumn('is_superuser');
            });
        }
    }
}
