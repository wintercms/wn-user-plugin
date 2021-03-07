<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;
use Winter\User\Models\User;

class UsersAddLastSeen extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            $table->timestamp('last_seen')->nullable();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'last_seen')) {
            Schema::table('users', function($table)
            {
                $table->dropColumn('last_seen');
            });
        }
    }
}
