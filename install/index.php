<?php

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\SystemException;

use Alto\MakeApi\Orm\ContentTable;

Loc::loadMessages(__FILE__);

class alto_makeapi extends CModule
{
    
    public $MODULE_ID;
    public $MODULE_NAME;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;
    public string $MIN_MODULE_VERSION = '19.00.00';
    public $MODULE_GROUP_RIGHTS = 'Y';
    public string $LANG_PREFIX;
    
    private string $modDir;
    private array $versionData;
    private CMain $app;
    private $connection;

    public function __construct()
    {
        $className = get_class($this);
        $this->LANG_PREFIX = strtoupper($className);
        $this->MODULE_ID = str_replace('_', '.', $className);
        
        $this->MODULE_NAME = Loc::getMessage($this->LANG_PREFIX . "_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage($this->LANG_PREFIX . "_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage($this->LANG_PREFIX . "_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage($this->LANG_PREFIX . "_PARTNER_URI");

        $this->modDir = Application::getDocumentRoot() . "/local/modules/" . $this->MODULE_ID;
        
        require_once $this->modDir . "/install/version.php";

        $this->versionData = $arModuleVersion ?? [];

        if (isset($this->versionData['VERSION'])) {
            $this->MODULE_VERSION = $this->versionData['VERSION'];
            $this->MODULE_VERSION_DATE = $this->versionData['VERSION_DATE'];
        } else {
            $this->MODULE_VERSION = '0.0.1';
            $this->MODULE_VERSION_DATE = '2024-01-01 00:00:00';
        }

        global $APPLICATION;
        $this->app = $APPLICATION;
        $this->connection = Application::getConnection();
    }

    /**
     * Установка модуля
     */
    public function doInstall()
    {
        if (ModuleManager::isModuleInstalled($this->MODULE_ID)) {
            return;
        }

        try {
            if (!$this->isSupportedVersion()) {
                throw new NotSupportedException(Loc::getMessage($this->LANG_PREFIX . "_MODULE_NO_D7_ERROR"));
            }

            ModuleManager::registerModule($this->MODULE_ID);
            Loader::includeModule($this->MODULE_ID);
            $this->installDB();
        } catch (SystemException $e) {
            if (!$e instanceof NotSupportedException) {
                ModuleManager::unRegisterModule($this->MODULE_ID);
            }

            $this->app->ThrowException($e->getMessage());
            return false;
        }

        $this->installFiles();
    }

    /**
     * Удаление модуля
     */
    public function doUninstall()
    {
        if (!ModuleManager::isModuleInstalled($this->MODULE_ID)) {
            return;
        }

        try {
            Loader::includeModule($this->MODULE_ID);
            $this->uninstallDB();
        } catch (SystemException|LoaderException $e) {
            $this->app->ThrowException($e->getMessage());
        }

        ModuleManager::unRegisterModule($this->MODULE_ID);
        $this->uninstallFiles();
    }

    /**
     * Создание сущностей модуля
     *
     * @throws SystemException
     */
    public function installDB()
    {
        require __DIR__ . '/content.php';

        $content = new Content();
        $content->createDB();
    }

    /**
     * Удаление сущностей модуля
     *
     * @throws SystemException
     */
    public function uninstallDB()
    {
        require __DIR__ . '/content.php';

        $content = new Content();
        $content->deleteDB();
    }

    public function installFiles()
    {

    }

    public function uninstallFiles()
    {

    }

    /**
     * Проверка соответствия текущей версии ядра требованиям модуля
     *
     * @return bool
     */
    private function isSupportedVersion(): bool
    {
        return CheckVersion(ModuleManager::getVersion('main'), $this->MIN_MODULE_VERSION);
    }
}
