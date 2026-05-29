<?php

use App\Providers\AppServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Rbac\Providers\PermissionServiceProvider::class,
];
