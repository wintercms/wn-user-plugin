<?php namespace Winter\User\NotifyRules;

use Winter\User\Classes\UserEventBase;

class UserSuspendedEvent extends UserEventBase
{
    /**
     * Returns information about this event, including name and description.
     */
    public function eventDetails()
    {
        return [
            'name'        => 'Suspended',
            'description' => 'A user was suspended',
            'group'       => 'user'
        ];
    }
}