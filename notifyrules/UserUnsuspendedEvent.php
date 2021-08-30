<?php namespace Winter\User\NotifyRules;

use Winter\User\Classes\UserEventBase;

class UserUnsuspendedEvent extends UserEventBase
{
    /**
     * Returns information about this event, including name and description.
     */
    public function eventDetails()
    {
        return [
            'name'        => 'Unsuspended',
            'description' => 'A user was unsuspended',
            'group'       => 'user'
        ];
    }
}