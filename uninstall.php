<?php

declare(strict_types=1);
use Agency_Pass\Role;
use Agency_Pass\UserManager;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit();

require_once __DIR__ . '/vendor/autoload.php';

Role::unregister();
UserManager::revoke_all();
