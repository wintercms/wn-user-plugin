<?php namespace Winter\User\Tests;

use App;
use PluginTestCase;
use Illuminate\Foundation\AliasLoader;
use Winter\User\Models\Settings;

abstract class UserPluginTestCase extends PluginTestCase
{
    /**
     * @var array   Plugins to refresh between tests.
     */
    protected $refreshPlugins = [
        'Winter.User',
    ];

    /**
     * Perform test case set up.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // reset any modified settings
        Settings::resetDefault();

        // log out after each test
        \Winter\User\Classes\AuthManager::instance()->logout();

        // register the auth facade
        $alias = AliasLoader::getInstance();
        $alias->alias('Auth', 'Winter\User\Facades\Auth');
    
        App::singleton('user.auth', function () {
            return \Winter\User\Classes\AuthManager::instance();
        });
    }
}