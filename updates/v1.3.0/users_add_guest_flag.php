<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class UsersAddGuestFlag extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            $table->boolean('is_guest')->default(false);
        });
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'is_guest')) {
            Schema::table('users', function($table)
            {
                $table->dropColumn('is_guest');
            });
        }
    }
}
