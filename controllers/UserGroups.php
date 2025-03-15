<?php namespace Winter\User\Controllers;

use Flash;
use BackendMenu;
use Backend\Classes\Controller;
use Winter\User\Models\UserGroup;

/**
 * User Groups Back-end Controller
 */
class UserGroups extends Controller
{
    /**
     * @var array Extensions implemented by this controller.
     */
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

    /**
     * @var array Permissions required to view this page.
     */
    public $requiredPermissions = ['winter.users.access_groups'];
}
