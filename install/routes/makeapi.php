<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Routing\RoutingConfigurator;
use Bitrix\Main\ModuleManager;

use Alto\MakeApi\Controller\IblockController;
use Alto\MakeApi\Controller\IblockSectionController;
use Alto\MakeApi\Controller\ContentController;
use Alto\MakeApi\Controller\MetaController;
use Alto\MakeApi\Controller\MenuController;
use Alto\MakeApi\Controller\SearchController;
use Alto\MakeApi\Controller\FormController;
use Alto\MakeApi\Controller\PageController;
use Alto\MakeApi\Controller\NotFoundController;
use Alto\MakeApi\Controller\TestController;

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php';

if (Loader::includeModule('alto.makeapi')) {

    return function (RoutingConfigurator $routes) {
        $routes->prefix('api/v1')->group(function (RoutingConfigurator $routes) {

            $routes->get('documentation', function(){
                $generator = new \OpenApi\Generator();

                $source = [
                    $_SERVER['DOCUMENT_ROOT'] . '/local/modules/',
                    $_SERVER['DOCUMENT_ROOT'] . '/local/routes/',
                ];

                echo $generator->generate($source)->toJson();
            });

            $routes->get('iblock/{iblock_code}', [IblockController::class, 'info']);
            $routes->get('iblock/{iblock_code}/elements', [IblockController::class, 'list']);
            $routes->get('iblock/{iblock_code}/element', [IblockController::class, 'element']);
            $routes->get('iblock/{iblock_code}/tags', [IblockController::class, 'tags']);

            $routes->get('iblock/{iblock_code}/sections', [IblockSectionController::class, 'list']);
            $routes->get('iblock/{iblock_code}/section', [IblockSectionController::class, 'section']);

            $routes->get('content/{code}', [ContentController::class, 'getByCode']);
            $routes->get('content/pages/{page}', [ContentController::class, 'getByPage']);

            $routes->get('page/{code}', [PageController::class, 'getByCode']);

            $routes->get('meta', [MetaController::class, 'getForPage']);
            $routes->get('menu', [MenuController::class, 'get']);

            $routes->get('search', [SearchController::class, 'search']);

            $routes->post('forms', [FormController::class, 'submit']);

            $routes->get('version', function () {
                return ['version' => ModuleManager::getVersion('alto.makeapi')];
            });

            $routes->get('test', [TestController::class, 'run']);

            $routes->any('{any}', [NotFoundController::class, 'notFound'])->where('any', '.*');
        });
    };

}
