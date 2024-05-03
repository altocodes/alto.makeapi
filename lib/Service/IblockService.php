<?php

namespace Alto\MakeApi\Service;

use Alto\MakeApi\Dto\Iblock\ElementDto;
use Alto\MakeApi\Dto\Iblock\IblockDto;
use Alto\MakeApi\Dto\Iblock\Property\PropertyDto;
use Alto\MakeApi\Dto\ListDto;
use Alto\MakeApi\Entity\IblockEntity;
use Alto\MakeApi\Enum\HttpStatus;
use Alto\MakeApi\Exception\ServiceException;
use Alto\MakeApi\Repository\IblockRepository;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

Loader::includeModule('iblock');

/**
 *
 */
class IblockService
{

    private $repository;

    public function __construct(string $apiCode)
    {
        $this->repository = IblockRepository::factory($apiCode);
    }

    /**
     * Получение информации об инфоблоке
     *
     * @return IblockDto
     */
    public function getInfo(): IblockDto
    {
        return $this->repository->getIblock();
    }

    /**
     * Получение элементов
     *
     * @param array $filter
     * TODO: дефолтные значения заменить на константы
     * TODO: добавить выбираемые поля
     * @param int $page
     * @param int $limit
     * @return ListDto
     */
    public function getList(array $filter = [], int $page = 1, int $limit = 10): ListDto
    {
        $elements = $this->repository->getList([
            'filter' => $filter,
            'limit' => $limit,
            'offset' => $page
        ]);

        return new ListDto($page, $limit, $this->repository->getCount($filter), $elements);
    }

    /**
     * Получение информации об элементе
     * @param int $id
     * @return array|ElementDto
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getElementById(int $id): array|ElementDto
    {
        $data = $this->repository->getList(['filter' => ['ID' => $id]]);
        if (!isset($data[0])) {
            throw new ServiceException(Loc::getMessage('ALTO_STRAPI_SERVICE_EXCEPTION_ELEMENT_NOT_FOUND'), HttpStatus::NOT_FOUND->value);
        }


        return $data[0] ?? [];
    }
}