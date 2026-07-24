<?php

namespace Alto\MakeApi\Repository;


use Alto\MakeApi\Entity\QueryBuilder;
use Alto\MakeApi\Exception\RepositoryException;
use Alto\MakeApi\Helper\IblockHelper;
use Bitrix\Iblock\Model\Section;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class IblockSectionRepository extends QueryBuilder
{

    public function __construct(string $code)
    {
        if (!Loader::includeModule('iblock')) {
            throw new RepositoryException(Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_MODULES_NOT_INSTALL'));
        }

        $dataClass = Section::compileEntityByIblock($code);
        if (!$dataClass) {
            throw new RepositoryException(Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_COMPILATION_ERROR', ['#API_CODE#' => $code]));
        }

        if (!$entity = $dataClass::getEntity()) {
            throw new RepositoryException(Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_COMPILATION_ERROR', ['#API_CODE#' => $code]));
        }

        parent::__construct($entity);
    }

    /**
     * Получение списка разделов
     * TODO: добавить работу с пользовательскими свойствами
     * @param array $params
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getSections(array $params): array
    {
        if (!isset($params['select'])) {
            $params['select'] = ['*'];
        }

        $this->setParams($params);

        if ($params['limit']) {
            $this->setNavigation($params['limit'], $params['offset'] ?? 1);
        }

        return $this->getResult();
    }

    /**
     * Инициализация репозитория по коду
     * @param string $code
     * @return self
     * @throws RepositoryException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function factory(string $code): self
    {
        $iblock = IblockHelper::getIblockByCode($code, ['API_CODE']);
        if (!$iblock) {
            throw new RepositoryException(Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_NOT_FOUND_IBLOCK', ['#CODE#' => $code]));
        }

        if (empty($iblock['API_CODE'])) {
            throw new RepositoryException(Loc::getMessage('ALTO_MAKEAPI_REPOSITORY_EXCEPTION_INVALID_API_CODE', ['#CODE#' => $code]));
        }

        return new self($iblock['API_CODE']);
    }
}