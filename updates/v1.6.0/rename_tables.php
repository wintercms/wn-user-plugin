<?php namespace Winter\User\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class RenameTables extends Migration
{
    public function up()
    {
        $from = 'rainlab_user_mail_blockers';
        $to   = 'winter_user_mail_blockers';
        if (Schema::hasTable($from)) {
            Schema::rename($from, $to);
        }
    }

    public function down()
    {
        $from = 'winter_user_mail_blockers';
        $to   = 'rainlab_user_mail_blockers';
        if (Schema::hasTable($from)) {
            Schema::rename($from, $to);
        }
    }
}
