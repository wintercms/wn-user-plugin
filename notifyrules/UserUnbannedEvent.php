<?php namespace Winter\User\NotifyRules;

use Backend;
use Winter\User\Classes\UserEventBase;

class UserUnbannedEvent extends UserEventBase
{
    /**
     * Defines the usable parameters provided by this class.
     */
    public function defineParams()
    {
        return [
            'id' => [
                'title' => 'ID',
                'label' => "The User's ID",
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
                'label' => "A link to the user's management page"
            ],
        ];
    }

    public static function makeParamsFromEvent(array $args, $eventName = null)
    {
        $user = array_get($args, 0);

        $params = $user->getNotificationVars();
        $params['user'] = $user;
        $params['link'] = Backend::url('winter/user/users/preview/' . $user->id);

        return $params;
    }

    /**
     * Returns information about this event, including name and description.
     */
    public function eventDetails()
    {
        return [
            'name'        => 'Unbanned',
            'description' => 'A user was unbanned',
            'group'       => 'user'
        ];
    }
}
