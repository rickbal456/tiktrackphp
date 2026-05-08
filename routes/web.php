<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ConfigController;
use App\Controllers\ConversionController;
use App\Controllers\DashboardController;
use App\Controllers\DomainController;
use App\Controllers\LandingPageController;
use App\Controllers\LpController;
use App\Controllers\ProfileController;
use App\Controllers\RoleController;
use App\Core\Router;

return static function (Router $router): void {
    $a = AuthController::class;
    $router->add('GET', '/login', $a, 'showLogin');
    $router->add('POST', '/login', $a, 'login');
    $router->add('GET', '/register', $a, 'showRegister');
    $router->add('POST', '/register', $a, 'register');
    $router->add('POST', '/logout', $a, 'logout');

    $d = DashboardController::class;
    $router->add('GET', '/dashboard', $d, 'index');

    $c = ConfigController::class;
    $router->add('GET', '/config', $c, 'index');
    $router->add('POST', '/config', $c, 'store');
    $router->add('POST', '/config/update', $c, 'update');
    $router->add('POST', '/config/toggle', $c, 'toggle');
    $router->add('POST', '/config/delete', $c, 'delete');

    $cv = ConversionController::class;
    $router->add('GET', '/conversions', $cv, 'index');
    $router->add('POST', '/conversions', $cv, 'store');
    $router->add('POST', '/conversions/import', $cv, 'importCsv');
    $router->add('POST', '/conversions/send', $cv, 'sendBulk');
    $router->add('POST', '/conversions/send-all', $cv, 'sendAllPending');
    $router->add('GET', '/api/conversions', $cv, 'apiList');
    $router->add('POST', '/api/conversions', $cv, 'apiStore');
    $router->add('POST', '/api/conversions/send', $cv, 'apiSend');

    $lp = LandingPageController::class;
    $router->add('GET', '/landing-pages', $lp, 'index');
    $router->add('POST', '/landing-pages', $lp, 'store');
    $router->add('POST', '/landing-pages/update', $lp, 'update');
    $router->add('POST', '/landing-pages/delete', $lp, 'delete');

    $dm = DomainController::class;
    $router->add('GET', '/domains', $dm, 'index');
    $router->add('POST', '/domains', $dm, 'store');
    $router->add('POST', '/domains/verify', $dm, 'verify');
    $router->add('POST', '/domains/delete', $dm, 'delete');

    $p = ProfileController::class;
    $router->add('GET', '/profile', $p, 'index');
    $router->add('POST', '/profile', $p, 'update');

    $r2 = RoleController::class;
    $router->add('GET', '/roles', $r2, 'index');
    $router->add('POST', '/roles', $r2, 'store');
    $router->add('POST', '/roles/users', $r2, 'storeUser');
    $router->add('POST', '/roles/users/delete', $r2, 'deleteUser');
    $router->add('POST', '/roles/delete', $r2, 'delete');
};
