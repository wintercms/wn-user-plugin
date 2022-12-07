<?php namespace Winter\User\Models;

use Event;
use Winter\Storm\Auth\Models\Throttle as ThrottleBase;

class Throttle extends ThrottleBase
{
    /**
     * @var boolean Was the user already suspended at the beginning of the database update?
     */
    private $wasSuspended = false;

    /**
     * @var boolean Was the user already banned at the beginning of the database update?
     */
    private $wasBanned = false;

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
     * Check if the user was already suspended or banned before the table updates
     */
    public function beforeUpdate()
    {
        $this->wasSuspended = $this->user->isSuspended();
        $this->wasBanned = $this->user->isBanned();
    }

    /**
     * Check if suspended state has changed and send suspend appropriate event if it has
     */
    public function afterUpdate()
    {
        // Only fire suspend events if the user suspended state has changed
        if (!$this->wasSuspended && $this->user->isSuspended()) {
            // User has become suspended. Fire appropriate event.
            Event::fire('winter.user.suspend', [$this->user]);
        } elseif ($this->wasSuspended && !$this->user->isSuspended()) {
            // User has become unsuspended. Fire appropriate event.
            Event::fire('winter.user.unsuspend', [$this->user]);
        }

        // Only fire banned events if the user banned state has changed
        if (!$this->wasBanned && $this->user->isBanned()) {
            // User has become banned. Fire appropriate event.
            Event::fire('winter.user.ban', [$this->user]);
        } elseif ($this->wasSuspended && !$this->user->isSuspended()) {
            // User has become unbanned. Fire appropriate event.
            Event::fire('winter.user.unban', [$this->user]);
        }
    }
}
