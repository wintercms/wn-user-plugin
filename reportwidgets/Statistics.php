<?php namespace Winter\User\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Exception;
use Winter\User\Models\User;
use Db;
use Carbon\Carbon;

class Statistics extends ReportWidgetBase
{
    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'backend::lang.dashboard.widget_title_label',
                'default'           => 'winter.user::lang.reportwidgets.statistics.default_title',
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'backend::lang.dashboard.widget_title_error'
            ],
        ];
    }

    public function render()
    {
        try {
            $this->prepareVars();
        } catch (Exception $ex) {
            $this->vars['error'] = $ex->getMessage();
        }

        return $this->makePartial('widget');
    }

    public function prepareVars()
    {
        $current_data = Carbon::now();
        $yesterday = Carbon::now()->yesterday();
        $sub_month = Carbon::now()->subMonth();
        $sub_year = Carbon::now()->subYear();

        $this->vars['stats_users'] = [
            'count' => User::count(),
            'activated_count' => User::where('is_activated', true)->count(),
            'banned_count' => Db::table('user_throttle')->where('is_banned', true)->count(),

            'registr_day' => User::whereDate('created_at', $current_data)->count(),
            'registr_yesterday' => User::whereDate('created_at', '=', $yesterday)->count(),
            'registr_month' => User::whereDate('created_at', '>', $sub_month)->count(),
            'registr_year' => User::whereDate('created_at', '>', $sub_year)->count(),
            
            'active_day' => User::whereDate('last_seen', $current_data)->count(),
            'active_yesterday' => User::whereDate('last_seen', '>', $yesterday)->count(),
            'active_month' => User::whereDate('last_seen', '>', $sub_month)->count(),
            'active_year' => User::whereDate('last_seen', '>', $sub_year)->count(),
        ];
    }
}
