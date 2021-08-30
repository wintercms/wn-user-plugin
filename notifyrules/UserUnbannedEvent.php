<?php namespace Winter\User\NotifyRules;

use Winter\User\Classes\UserEventBase;

class UserUnbannedEvent extends UserEventBase
{
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