<?php namespace Winter\User\Classes;

use App;
use Illuminate\Routing\Redirector;

class UserRedirector extends Redirector
{
    /**
     * Create a new redirect response, while putting the current URL in the session.
     *
     * @param  string  $path
     * @param  int     $status
     * @param  array   $headers
     * @param  bool    $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function guest($path, $status = 302, $headers = [], $secure = null)
    {
        $this->session->put($this->getIntendedSessionKey(), $this->generator->full());

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Create a new redirect response to the previously intended location.
     *
     * @param  string  $default
     * @param  int     $status
     * @param  array   $headers
     * @param  bool    $secure
     * @return \Illuminate\Http\RedirectResponse
     */
    public function intended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        $path = $this->session->pull($this->getIntendedSessionKey(), $default);

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * Set the intended url.
     *
     * @param  string  $url
     * @return void
     */
    public function setIntendedUrl($url)
    {
        $this->session->put($this->getIntendedSessionKey(), $url);
    }

    /**
     * Get the session key for the intended redirect
     *
     * @return string
     */
    protected function getIntendedSessionKey()
    {
        return App::runningInBackend() ? 'url.intended' : 'url.frontend.intended';
    }
}
