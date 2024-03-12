<?php namespace Winter\User;

use App;
use Auth;
use Event;
use Backend;
use System\Classes\PluginBase;
use System\Classes\SettingsManager;
use Illuminate\Foundation\AliasLoader;
use Winter\User\Classes\UserRedirector;
use Winter\User\Models\MailBlocker;
use Winter\Notify\Classes\Notifier;

class Plugin extends PluginBase
{
    /**
     * @var boolean Determine if this plugin should have elevated privileges.
     */
    public $elevated = true;

    public function pluginDetails()
    {
        return [
            'name'        => 'winter.user::lang.plugin.name',
            'description' => 'winter.user::lang.plugin.description',
            'author'      => 'Alexey Bobkov, Samuel Georges',
            'icon'        => 'icon-user',
            'homepage'    => 'https://github.com/wintercms/wn-user-plugin',
            'replaces'    => ['RainLab.User' => '~1.6'],
        ];
    }

    public function register()
    {
        $alias = AliasLoader::getInstance();
        $alias->alias('Auth', 'Winter\User\Facades\Auth');

        App::singleton('user.auth', function () {
            return \Winter\User\Classes\AuthManager::instance();
        });

        App::singleton('redirect', function ($app) {
            // overrides with our own extended version of Redirector to support
            // seperate url.intended session variable for frontend
            $redirector = new UserRedirector($app['url']);

            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
            if (isset($app['session.store'])) {
                $redirector->setSession($app['session.store']);
            }

            return $redirector;
        });

        /*
         * Apply user-based mail blocking
         */
        Event::listen('mailer.prepareSend', function ($mailer, $view, $message) {
            return MailBlocker::filterMessage($view, $message);
        });

        /*
         * Compatability with Winter.Notify
         */
        $this->bindNotificationEvents();
    }

    public function registerComponents()
    {
        return [
            \Winter\User\Components\Session::class       => 'session',
            \Winter\User\Components\Account::class       => 'account',
            \Winter\User\Components\ResetPassword::class => 'resetPassword'
        ];
    }

    public function registerPermissions()
    {
        return [
            'winter.users.access_users' => [
                'tab'   => 'winter.user::lang.plugin.tab',
                'label' => 'winter.user::lang.plugin.access_users'
            ],
            'winter.users.access_groups' => [
                'tab'   => 'winter.user::lang.plugin.tab',
                'label' => 'winter.user::lang.plugin.access_groups'
            ],
            'winter.users.access_settings' => [
                'tab'   => 'winter.user::lang.plugin.tab',
                'label' => 'winter.user::lang.plugin.access_settings'
            ],
            'winter.users.impersonate_user' => [
                'tab'   => 'winter.user::lang.plugin.tab',
                'label' => 'winter.user::lang.plugin.impersonate_user'
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'user' => [
                'label'       => 'winter.user::lang.users.menu_label',
                'url'         => Backend::url('winter/user/users'),
                'icon'        => 'icon-user',
                'iconSvg'     => 'plugins/winter/user/assets/images/user-icon.svg',
                'permissions' => ['winter.users.*'],
                'order'       => 500,

                'sideMenu' => [
                    'users' => [
                        'label' => 'winter.user::lang.users.menu_label',
                        'icon'        => 'icon-user',
                        'url'         => Backend::url('winter/user/users'),
                        'permissions' => ['winter.users.access_users']
                    ],
                    'usergroups' => [
                        'label'       => 'winter.user::lang.groups.menu_label',
                        'icon'        => 'icon-users-viewfinder',
                        'url'         => Backend::url('winter/user/usergroups'),
                        'permissions' => ['winter.users.access_groups']
                    ]
                ]
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'winter.user::lang.settings.menu_label',
                'description' => 'winter.user::lang.settings.menu_description',
                'category'    => SettingsManager::CATEGORY_USERS,
                'icon'        => 'icon-user-gear',
                'class'       => 'Winter\User\Models\Settings',
                'order'       => 500,
                'permissions' => ['winter.users.access_settings']
            ]
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'winter.user::mail.activate',
            'winter.user::mail.welcome',
            'winter.user::mail.restore',
            'winter.user::mail.new_user',
            'winter.user::mail.reactivate',
            'winter.user::mail.invite',
        ];
    }

    public function registerNotificationRules()
    {
        if (!class_exists(\Winter\Notify\Classes\Notifier::class)) {
            return [];
        }

        return [
            'groups' => [
                'user' => [
                    'label' => 'User',
                    'icon' => 'icon-user'
                ],
            ],
            'events' => [
               \Winter\User\NotifyRules\UserActivatedEvent::class,
               \Winter\User\NotifyRules\UserRegisteredEvent::class,
            ],
            'actions' => [],
            'conditions' => [
                \Winter\User\NotifyRules\UserAttributeCondition::class,
            ],
        ];
    }

    protected function bindNotificationEvents()
    {
        if (!class_exists(\Winter\Notify\Classes\Notifier::class)) {
            return;
        }

        Notifier::bindEvents([
            'winter.user.activate' => \Winter\User\NotifyRules\UserActivatedEvent::class,
            'winter.user.register' => \Winter\User\NotifyRules\UserRegisteredEvent::class,
        ]);

        Notifier::instance()->registerCallback(function ($manager) {
            $manager->registerGlobalParams([
                'user' => Auth::getUser()
            ]);
        });
    }
}
