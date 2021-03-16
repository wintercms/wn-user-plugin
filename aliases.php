<?php
/**
 * To allow compatibility with plugins that extend the original RainLab.User plugin, this will alias those classes to
 * use the new Winter.User classes.
 */
$aliases = [
    'RainLab\Notify\Classes\EventBase'          => Winter\Notify\Classes\EventBase::class,
    Winter\User\Plugin::class                   => 'RainLab\User\Plugin',
    Winter\User\Classes\AuthManager::class      => 'RainLab\User\Classes\AuthManager',
    Winter\User\Classes\AuthMiddleware::class   => 'RainLab\User\Classes\AuthMiddleware',
    Winter\User\Classes\UserEventBase::class    => 'RainLab\User\Classes\UserEventBase',
    Winter\User\Classes\UserRedirector::class   => 'RainLab\User\Classes\UserRedirector',
    Winter\User\Components\Account::class       => 'RainLab\User\Components\Account',
    Winter\User\Components\ResetPassword::class => 'RainLab\User\Components\ResetPassword',
    Winter\User\Components\Session::class       => 'RainLab\User\Components\Session',
    Winter\User\Controllers\Users::class        => 'RainLab\User\Controllers\Users',
    Winter\User\Controllers\UserGroups::class   => 'RainLab\User\Controllers\UserGroups',
    Winter\User\Models\User::class              => 'RainLab\User\Models\User',
    Winter\User\Models\MailBlocker::class       => 'RainLab\User\Models\MailBlocker',
    Winter\User\Models\Throttle::class          => 'RainLab\User\Models\Throttle',
    Winter\User\Models\Settings::class          => 'RainLab\User\Models\Settings',
];

foreach ($aliases as $original => $alias) {
    if (!class_exists($alias)) {
        class_alias($original, $alias);
    }
}
