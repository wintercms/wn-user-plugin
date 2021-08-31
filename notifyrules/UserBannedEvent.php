<?php namespace Winter\User\NotifyRules;

use Backend;
use Winter\User\Classes\UserEventBase;

class UserBannedEvent extends UserEventBase
{
    /**
     * Defines the usable parameters provided by this class.
     */
    public function defineParams()
    {
        return [
            'id' => [
                'title' => 'Id',
                'label' => "The User's id",
            ],
            'name' => [
                'title' => 'Name',
                'label' => "User's first name",
            ],
            'surname' => [
                'title' => 'Surname',
                'label' => "User's last name",
            ],
            'email' => [
                'title' => 'Email',
                'label' => "User's email address",
            ],
            'link' => [
                'title' => 'Link',
                'label' => "A link to the user management page"
            ],
        ];
    }

    public static function makeParamsFromEvent(array $args, $eventName = null)
    {
        $user = array_get($args, 0);

        $params = $user->getNotificationVars();
        $params['user'] = $user;
        $params['link'] = Backend::url('winter/user/users/preview/'.$user->id);

        return $params;
    }

    /**
     * Returns information about this event, including name and description.
     */
    public function eventDetails()
    {
        return [
            'name'        => 'Banned',
            'description' => 'A user was banned',
            'group'       => 'user'
        ];
    }
}