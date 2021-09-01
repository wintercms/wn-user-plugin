<?php namespace Winter\User\Models;

use Event;
use Winter\Storm\Auth\Models\Throttle as ThrottleBase;

class Throttle extends ThrottleBase
{
    /**
     * @var boolean Was the user already suspended at the beginning of the database update?
     */
    private bool $wasSuspended = false;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'user_throttle';

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => User::class
    ];

    /**
     * Check if the user was already suspended before the table updates
     */
    public function beforeSave()
    {
        $this->wasSuspended = $this->user->isSuspended();
    }

    /**
     * Check if suspended state has changed and send suspend appropriate event if it has
     */
    public function afterSave()
    {
        // Only fire suspend events if the user suspended state has changed
        if (!$this->wasSuspended && $this->user->isSuspended()) {
            // User has become suspended. Fire appropriate event.
            Event::fire('winter.user.suspend', [$this->user]);
        } else if($this->wasSuspended && !$this->user->isSuspended()) {
            // User has become unsuspended. Fire appropriate event.
            Event::fire('winter.user.unsuspend', [$this->user]);
        }
    }
}
