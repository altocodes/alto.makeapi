<?php

namespace Alto\MakeApi\Service;

use Alto\MakeApi\Dto\Entity\Page\ContentDto;
use Alto\MakeApi\Exception\Http\NotFoundException;
use Alto\MakeApi\Helper\FetcherHelper;
use Alto\MakeApi\Orm\ContentTable;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Service\Catalog\CatalogService;
use Bitrix\Main\Localization\Loc;
use Alto\MakeApi\Dto\Entity\SliderDto;
use CFile;
use OpenApi\Attributes as OA;

class ContentService
{
    private string $siteId;
    private IblockRepository $sliderRepository;
    private CatalogService $catalogService;

    public function __construct(string $siteId)
    {
        $this->siteId = $siteId;
        $this->sliderRepository = IblockRepository::factory('slidermain');
        $this->catalogService = new CatalogService();
    }

    #[OA\Schema(
        schema: "MainSliderResponse",
        properties: [
            new OA\Property(
                property: "status",
                description: "Статус ответа",
                type: "string",
                example: "success"
            ),
            new OA\Property(
                property: "data",
                description: "Данные ответа",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/SliderDto")
            ),
            new OA\Property(
                property: "errors",
                description: "Ошибки ответа",
                type: "array",
                example: []
            )
        ]
    )]
    public function getMainSlider(): array
    {
        // Получаем активные слайды
        $slides = $this->sliderRepository->getElements([
            'filter' => [
                'ACTIVE' => 'Y',
            ],
            'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
        ]);

        $sliderData = [];
        foreach ($slides as $slide) {
            $sliderData[] = $this->prepareSliderItem($slide);
        }

        return $sliderData;
    }

    private function prepareSliderItem(array $slide): SliderDto
    {        
        // Если есть привязанный товар, получаем его данные
        if (!empty($slide['PROPERTIES']['product']->id)) {
            $productId = (int)$slide['PROPERTIES']['product']->id;
            $slide['product'] = $this->catalogService->getProductById($productId);
        }

        // Обработка изображения слайда
        if (!empty($slide['PREVIEW_PICTURE'])) {
            $slide['image'] = FetcherHelper::getFileById($slide['PREVIEW_PICTURE']);
        }

        return SliderDto::fromArray($slide);
    }

    public function getByCode(string $code): ContentDto
    {
        $content = ContentTable::getRow([
            'select' => ['*', 'TYPE_XML_ID' => 'TYPE.XML_ID'],
            'filter' => [
                'UF_CODE' => $code,
                'UF_SITE_ID' => [$this->siteId, ''],
            ],
            'limit' => 1,
        ]);

        if ($content) {
            return $this->prepareContent($content);
        }

        throw NotFoundException::create(Loc::getMessage('ALTO_MAKEAPI_SERVICE_EXCEPTION_CONTENT_NOT_FOUND'));
    }

    
    public function getByPage(string $page): array
    {
        $result = [];

        $contents = ContentTable::getList([
            'select' => ['*', 'TYPE_XML_ID' => 'TYPE.XML_ID'],
            'filter' => [
                'UF_PAGE' => $page,
                'UF_SITE_ID' => [$this->siteId, ''],
            ],
        ]);

        while($content = $contents->fetch()) {
            $result[] = $this->prepareContent($content);
        }

        return $result;
    }

    private function prepareContent(array $content): ContentDto
    {
        if ($content['TYPE_XML_ID'] == ContentTable::FILE_TYPE && !empty($content['UF_FILE'])) {
            $content['UF_CONTENT'] = FetcherHelper::getFileById($content['UF_FILE'])->url ?? null;
        }

        return ContentDto::fromArray($content);
    }
}