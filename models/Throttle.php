<?php namespace Winter\User\Models;

use Winter\Storm\Auth\Models\Throttle as ThrottleBase;

class Throttle extends ThrottleBase
{
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
     * Suspend the user associated with the throttle
     * @return void
     */
    public function suspend()
    {
        if (!$this->is_suspended) {
            $this->is_suspended = true;
            $this->suspended_at = $this->freshTimestamp();
            $this->save();
        }

        Event::fire('winter.user.suspend', [$this->user]);
    }
}