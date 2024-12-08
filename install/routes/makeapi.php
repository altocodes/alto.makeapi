<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Routing\RoutingConfigurator;
use Bitrix\Main\ModuleManager;

use Alto\MakeApi\Controller\IblockController;
use Alto\MakeApi\Controller\IblockSectionController;
use Alto\MakeApi\Controller\ContentController;
use Alto\MakeApi\Controller\MetaController;

if (Loader::includeModule('alto.makeapi')) {

    return function (RoutingConfigurator $routes) {
        $routes->prefix('api/v1')->group(function (RoutingConfigurator $routes) {
            $routes->get('iblock/{iblock_code}', [IblockController::class, 'info']);
            $routes->get('iblock/{iblock_code}/elements', [IblockController::class, 'list']);
            $routes->get('iblock/{iblock_code}/element', [IblockController::class, 'element']);

            $routes->get('iblock/{iblock_code}/sections', [IblockSectionController::class, 'list']);
            $routes->get('iblock/{iblock_code}/section', [IblockSectionController::class, 'section']);

            $routes->get('content/{code}', [ContentController::class, 'getByCode']);
            $routes->get('content/pages/{page}', [ContentController::class, 'getByPage']);

            $routes->get('meta', [MetaController::class, 'getForPage']);

            $routes->get('version', function () {
                return ['version' => ModuleManager::getVersion('alto.makeapi')];
            });
        });
    };

}