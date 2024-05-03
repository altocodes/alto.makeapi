<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Routing\RoutingConfigurator;

use Alto\MakeApi\Controller\IblockController;

Loader::includeModule('alto.makeapi');

return function (RoutingConfigurator $routes) {
    $routes->prefix('api/v1')->group(function (RoutingConfigurator $routes) {
        $routes->get('iblock/{iblock_code}', [IblockController::class, 'info']);
        $routes->get('iblock/{iblock_code}/elements', [IblockController::class, 'list']);
        $routes->get('iblock/{iblock_code}/element', [IblockController::class, 'element']);

        //$routes->post('user/registration', [UserController::class, 'registration']);
    });
};