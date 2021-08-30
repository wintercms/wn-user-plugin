<?php namespace Winter\User\NotifyRules;

use Winter\User\Classes\UserEventBase;

class UserReactivatedEvent extends UserEventBase
{
    /**
     * Returns information about this event, including name and description.
     */
    public function eventDetails()
    {
        return [
            'name'        => 'Reactivated',
            'description' => 'A user was reactivated',
            'group'       => 'user'
        ];
    }
}