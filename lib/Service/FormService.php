<?php

namespace Alto\MakeApi\Service;

use Alto\MakeApi\Dto\Response\FormSubmitResponseDto;
use Alto\MakeApi\Repository\IblockRepository;
use Alto\MakeApi\Dto\Request\FormRequestDto;
use OpenApi\Attributes as OA;

class FormService
{
    public IblockRepository $repository;

    public function __construct($code)
    {
        $this->repository = IblockRepository::factory($code);
    }

    #[OA\Schema(
        schema: "FormResponse",
        properties: [
            new OA\Property(
                property: "status",
                description: "Статус ответа",
                type: "string",
                example: "success"
            ),
            new OA\Property(
                property: "data",
                ref: "#/components/schemas/FormSubmitResponseDto",
                description: "Данные ответа",
                type: "object"
            ),
            new OA\Property(
                property: "errors",
                description: "Ошибки ответа",
                type: "array",
                example: []
            )
        ]
    )]
    public function submit(FormRequestDto $formData): FormSubmitResponseDto
    {
        try {
            $elementData = [
                'NAME' => $this->generateElementName($formData),
                'ACTIVE' => 'Y',
                'CODE' => 'form_' . $formData->form . '_' . date('Ymd_His'),
            ];

            $properties = $this->prepareProperties($formData);

            $elementId = $this->repository->addElement($elementData, $properties);

            return new FormSubmitResponseDto(
                'success',
                'Форма успешно отправлена',
                $formData->form,
                $elementId
            );

        } catch (\Exception $e) {
            return new FormSubmitResponseDto(
                'error', 
                'Ошибка при сохранении формы: ' . $e->getMessage(),
                $formData->form
            );
        }
    }

    /**
     * Генерация названия элемента
     */
    private function generateElementName(FormRequestDto $formData): string
    {
        $baseName = 'Заявка с формы "' . $formData->form . '"';
        
        if (!empty($formData->fio)) {
            return $baseName . ' от ' . $formData->fio;
        }
        
        if (!empty($formData->email)) {
            return $baseName . ' с email ' . $formData->email;
        }
        
        if (!empty($formData->phone)) {
            return $baseName . ' с телефона ' . $formData->phone;
        }
        
        return $baseName . ' от ' . date('d.m.Y H:i');
    }

    /**
     * Подготовка свойств элемента
     */
    private function prepareProperties(FormRequestDto $formData): array
    {
        $properties = [];

        $fieldToPropertyMap = [
            'fio' => 'FIO',
            'phone' => 'PHONE', 
            'email' => 'EMAIL',
            'models' => 'MODEL',
            'comment' => 'COMMENT',
            'preferred_method' => 'PREFFERED_METHOD',
            'form' => 'NAME_FORM',
            'url' => 'URL'
        ];

        foreach ($fieldToPropertyMap as $dtoField => $propertyCode) {
            if (!empty($formData->$dtoField)) {
                if ($dtoField === 'preferred_method') {
                    $enumId = $this->repository->getPropertyEnumId($propertyCode, $formData->$dtoField);
                    $properties[$propertyCode] = $enumId ?: $formData->$dtoField;
                } else {
                    $properties[$propertyCode] = $formData->$dtoField;
                }
            }
        }

        return $properties;
    }
}
