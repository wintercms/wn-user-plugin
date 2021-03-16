<?php namespace Winter\User\NotifyRules;

use Winter\User\Classes\UserEventBase;

class UserActivatedEvent extends UserEventBase
{
    /**
     * Returns information about this event, including name and description.
     */
    public function eventDetails()
    {
        return [
            'name'        => 'Activated',
            'description' => 'A user is activated',
            'group'       => 'user'
        ];
    }
}
