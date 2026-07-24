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
                'ACTIVE' => 'N',
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
        
        if (!empty($formData->name)) {
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
            'name' => 'NAME',
            'phone' => 'PHONE', 
            'email' => 'EMAIL',
            'comment' => 'COMMENT',
            'form' => 'NAME_FORM',
            'url' => 'URL'
        ];

        foreach ($fieldToPropertyMap as $dtoField => $propertyCode) {
            if (!empty($formData->$dtoField)) {
                $properties[$propertyCode] = $formData->$dtoField;
            }
        }

        $fileFieldToPropertyMap = [
            'questionnaire' => 'QUESTIONNAIRE',
        ];
        foreach ($fileFieldToPropertyMap as $dtoField => $propertyCode) {
            if (!isset($formData->$dtoField) || $formData->$dtoField === null) {
                continue;
            }
            $fileValue = $this->prepareFilePropertyForIblock($formData->$dtoField);
            if ($fileValue !== null) {
                $properties[$propertyCode] = $fileValue;
            }
        }

        return $properties;
    }

    /**
     * Приводит значение файлового поля к формату PROPERTY_VALUES для типа «Файл» (CIBlockElement::Add).
     *
     * @param mixed $value массив загрузки PHP (один или несколько файлов) либо уже нормализованный фрагмент
     * @return array|null однофайловое значение, либо ['n0' => ['VALUE' => ..., 'DESCRIPTION' => ''], ...] для нескольких
     */
    private function prepareFilePropertyForIblock(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $chunks = $this->splitPhpUploadedFiles($value);
        $valid = [];
        foreach ($chunks as $chunk) {
            if ($this->isValidUploadedFileChunk($chunk)) {
                $valid[] = $chunk;
            }
        }

        if ($valid === []) {
            return null;
        }

        if (count($valid) === 1) {
            return $valid[0];
        }

        $propertyValue = [];
        $i = 0;
        foreach ($valid as $file) {
            $propertyValue['n' . $i] = [
                'VALUE' => $file,
                'DESCRIPTION' => '',
            ];
            $i++;
        }

        return $propertyValue;
    }

    /**
     * @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    private function splitPhpUploadedFiles(array $file): array
    {
        if (!isset($file['name'])) {
            return isset($file['tmp_name']) ? [$file] : [];
        }

        if (!is_array($file['name'])) {
            return [$file];
        }

        $chunks = [];
        foreach ($file['name'] as $idx => $_name) {
            $chunks[] = [
                'name' => $file['name'][$idx],
                'type' => $file['type'][$idx] ?? '',
                'tmp_name' => $file['tmp_name'][$idx] ?? '',
                'error' => (int) ($file['error'][$idx] ?? \UPLOAD_ERR_OK),
                'size' => (int) ($file['size'][$idx] ?? 0),
            ];
        }

        return $chunks;
    }

    /**
     * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
     */
    private function isValidUploadedFileChunk(array $file): bool
    {
        $error = (int) ($file['error'] ?? \UPLOAD_ERR_NO_FILE);
        if ($error === \UPLOAD_ERR_NO_FILE) {
            return false;
        }
        if ($error !== \UPLOAD_ERR_OK) {
            return false;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        return $tmp !== '';
    }
}
