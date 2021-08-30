<?php namespace Winter\User\NotifyRules;

use Winter\User\Classes\UserEventBase;

class UserBannedEvent extends UserEventBase
{
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