<?php namespace Winter\User\Updates;

use Db;
use Schema;
use Winter\Storm\Database\Updates\Migration;

class RenameTables extends Migration
{
    public function up()
    {
        $from = 'rainlab_user_mail_blockers';
        $to = 'winter_user_mail_blockers';

        if (Schema::hasTable($from) && !Schema::hasTable($to)) {
            Schema::rename($from, $to);
        }

        Db::table('system_files')->where('attachment_type', 'RainLab\User\Models\User')->update(['attachment_type' => 'Winter\User\Models\User']);
    }

    public function down()
    {
        $from = 'winter_user_mail_blockers';
        $to = 'rainlab_user_mail_blockers';

        if (Schema::hasTable($from) && !Schema::hasTable($to)) {
            Schema::rename($from, $to);
        }

        Db::table('system_files')->where('attachment_type', 'Winter\User\Models\User')->update(['attachment_type' => 'RainLab\User\Models\User']);
    }
}
