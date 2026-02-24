<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Dto\Response\FormSubmitResponseDto;
use Alto\MakeApi\Service\FormService;
use Bitrix\Main\Engine\Action;
use OpenApi\Attributes as OA;
use Alto\MakeApi\Dto\Request\FormRequestDto;
use Alto\MakeApi\Enum\HttpStatus;
use Bitrix\Main\Error;
use Alto\MakeApi\Service\ValidatorService;
use MAKS\Mighty\Validator;

class FormController extends BaseController
{
    private FormService $service;

    // Конфигурация валидации для форм
    const FORM_FIELD_LABELS = [
        'fio' => 'ФИО',
        'phone' => 'Телефон',
        'email' => 'Email',
        'models' => 'Модели',
        'comment' => 'Комментарий',
        'preferred_method' => 'Способ связи',
        'form' => 'Тип формы',
        'url' => 'URL страницы',
    ];

    protected function processBeforeAction(Action $action)
    {
        $this->service = new FormService('forms');

        return parent::processBeforeAction($action);
    }

    #[OA\Post(
        path: "/api/v1/forms",
        description: "Обработка отправки различных форм с сайта. Обязательные поля: URL, FORM. Остальные поля зависят от типа формы.",
        summary: "Отправка формы в инфоблок forms",
        requestBody: new OA\RequestBody(
            description: "Данные формы",
            required: true,
            content: new OA\JsonContent(
                ref: "#/components/schemas/FormRequestDto"
            )
        ),
        tags: ["Forms"]
    )]
    // TODO: вероятно нужно вынести в валидацию
    #[OA\Schema(
        schema: "FormRequestDto",
        title: "FormRequestDto",
        description: "DTO для отправки формы. Обязательные: URL, FORM.",
        properties: [
            new OA\Property(
                property: "fio",
                description: "ФИО заявителя",
                type: "string",
                example: "Иванов Иван Иванович",
                nullable: true
            ),
            new OA\Property(
                property: "phone",
                description: "Номер телефона",
                type: "string",
                nullable: true
            ),
            new OA\Property(
                property: "email",
                description: "Email адрес",
                type: "string",
                format: "email",
                example: "ivan@example.com",
                nullable: true
            ),
            new OA\Property(
                property: "models",
                description: "ID элемента(-ов) из инфоблока 'Каталог' (связь)",
                type: "array",
                nullable: true
            ),
            new OA\Property(
                property: "comment",
                description: "Дополнительный комментарий",
                type: "string",
                example: "Интересует доставка в регионы",
                nullable: true
            ),
            new OA\Property(
                property: "preferred_method",
                description: "Предпочтительный способ связи",
                type: "string",
                enum: ["phone", "email", "whatsapp", "telegram"],
                example: "phone",
                nullable: true
            ),
            new OA\Property(
                property: "form",
                description: "Тип формы (обязательное поле)",
                type: "string",
                example: "contact_form"
            ),
            new OA\Property(
                property: "url",
                description: "URL страницы, с которой отправлена форма (обязательное поле)",
                type: "string",
                example: "/contacts"
            )
        ]
    )]
    #[OA\Response(
        response: "200",
        description: "Форма успешно отправлена",
        content: new OA\JsonContent(
            ref: "#/components/schemas/FormResponse"
        )
    )]
    #[OA\Response(
        response: "400",
        description: "Некорректные данные формы",
        content: new OA\JsonContent(
            ref: "#/components/schemas/BadRequestErrorResponse"
        )
    )]
    #[OA\Response(
        response: "500",
        description: "Ошибка сервера",
        content: new OA\JsonContent(
            ref: "#/components/schemas/InternalErrorResponse"
        )
    )]
    public function submitAction(): FormSubmitResponseDto
    {
        $fields = $this->request->getJsonList()->toArray();
    
        // Создаем специфичные правила валидации для этой формы
        $validations = $this->getFormValidations();
        
        // Передаем специфичные метки в сервис
        $validationErrors = ValidatorService::validate(
            $fields, 
            $validations, 
            self::FORM_FIELD_LABELS
        );
        
        if (!empty($validationErrors)) {
            $this->setHttpStatus(HttpStatus::BAD_REQUEST);
            foreach ($validationErrors as $error) {
                $this->addError(new Error(
                    $error['message'],
                    'validation_error',
                    ['field' => $error['field']]
                ));
            }
            return new FormSubmitResponseDto('error', 'Ошибки валидации формы');
        }
        
        $formRequest = new FormRequestDto($fields);
        return $this->service->submit($formRequest);
    }

    /**
     * Специфичные правила валидации для наших форм
     */
    private function getFormValidations(): array
    {
        return [
            'fio' => 'string',
            'phone' => 'string', 
            'email' => 'email|null',
            'models' => 'array|null',
            'comment' => 'string|null',
            'preferred_method' => 'in:phone,email,whatsapp,telegram',
            'form' => 'required&string',
            'url' => 'required&string',
        ];
    }
}