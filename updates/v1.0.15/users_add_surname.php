<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class UsersAddSurname extends Migration
{
    public function up()
    {
        Schema::table('users', function($table)
        {
            $table->string('surname')->nullable();
        });
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'surname')) {
            Schema::table('users', function($table)
            {
                $table->dropColumn('surname');
            });
        }
    }
}
