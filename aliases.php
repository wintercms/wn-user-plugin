<?php

use Winter\Storm\Support\ClassLoader;

/**
 * To allow compatibility with plugins that extend the original RainLab.User plugin, this will alias those classes to
 * use the new Winter.User classes.
 */
$aliases = [
    // Reverse alias to fix issue on PHP 7.2, see https://github.com/wintercms/wn-user-plugin/runs/2122181184
    RainLab\Notify\Classes\EventBase::class     => Winter\Notify\Classes\EventBase::class,

    // Regular aliases
    Winter\User\Plugin::class                   => RainLab\User\Plugin::class,
    Winter\User\Classes\AuthManager::class      => RainLab\User\Classes\AuthManager::class,
    Winter\User\Classes\AuthMiddleware::class   => RainLab\User\Classes\AuthMiddleware::class,
    Winter\User\Classes\UserEventBase::class    => RainLab\User\Classes\UserEventBase::class,
    Winter\User\Classes\UserRedirector::class   => RainLab\User\Classes\UserRedirector::class,
    Winter\User\Components\Account::class       => RainLab\User\Components\Account::class,
    Winter\User\Components\ResetPassword::class => RainLab\User\Components\ResetPassword::class,
    Winter\User\Components\Session::class       => RainLab\User\Components\Session::class,
    Winter\User\Controllers\Users::class        => RainLab\User\Controllers\Users::class,
    Winter\User\Controllers\UserGroups::class   => RainLab\User\Controllers\UserGroups::class,
    Winter\User\Models\User::class              => RainLab\User\Models\User::class,
    Winter\User\Models\MailBlocker::class       => RainLab\User\Models\MailBlocker::class,
    Winter\User\Models\Throttle::class          => RainLab\User\Models\Throttle::class,
    Winter\User\Models\Settings::class          => RainLab\User\Models\Settings::class,
];

app(ClassLoader::class)->addAliases($aliases);
