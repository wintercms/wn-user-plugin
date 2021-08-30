<?php namespace Winter\User\NotifyRules;

use Winter\User\Classes\UserEventBase;

class UserDeactivatedEvent extends UserEventBase
{
    /**
     * Returns information about this event, including name and description.
     */
    public function eventDetails()
    {
        return [
            'name'        => 'Deactivated',
            'description' => 'A user was deactivated',
            'group'       => 'user'
        ];
    }
}