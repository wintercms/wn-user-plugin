<?php

class_alias(Winter\User\Plugin::class, RainLab\User\Plugin::class);

class_alias(Winter\User\Classes\AuthManager::class,      RainLab\User\Classes\AuthManager::class);
class_alias(Winter\User\Classes\AuthMiddleware::class,   RainLab\User\Classes\AuthMiddleware::class);
class_alias(Winter\User\Classes\UserEventBase::class,    RainLab\User\Classes\UserEventBase::class);
class_alias(Winter\User\Classes\UserRedirector::class,   RainLab\User\Classes\UserRedirector::class);

class_alias(Winter\User\Components\Account::class,       RainLab\User\Components\Account::class);
class_alias(Winter\User\Components\ResetPassword::class, RainLab\User\Components\ResetPassword::class);
class_alias(Winter\User\Components\Session::class,       RainLab\User\Components\Session::class);

class_alias(Winter\User\Models\User::class,              RainLab\User\Models\User::class);
class_alias(Winter\User\Models\User::class,              RainLab\User\Models\User::class);
class_alias(Winter\User\Models\MailBlocker::class,       RainLab\User\Models\MailBlocker::class);
class_alias(Winter\User\Models\Throttle::class,          RainLab\User\Models\Throttle::class);
class_alias(Winter\User\Models\Settings::class,          RainLab\User\Models\Settings::class);

class_alias(Winter\User\Controllers\Users::class,        RainLab\User\Controllers\Users::class);
class_alias(Winter\User\Controllers\UserGroups::class,   RainLab\User\Controllers\UserGroups::class);
