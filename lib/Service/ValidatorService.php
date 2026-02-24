<?php

namespace Alto\MakeApi\Service;

use MAKS\Mighty\Validator as MightyValidator;
use MAKS\Mighty\Rule;
use Bitrix\Main\Localization\Loc;

class ValidatorService
{
    public static function getDefaultMessages(): array
    {
        Loc::loadMessages(__FILE__);
        
        return [
            '*' => [
                'required' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_REQUIRED'),
                'string' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_STRING'),
                'numeric' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_NUMERIC'),
                'array' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_ARRAY'),
                'email' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_EMAIL'),
                'url' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_URL'),
                'min' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_MIN'),
                'max' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_MAX'),
                'between' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_BETWEEN'),
                'in' => Loc::getMessage('ALTO_MAKEAPI_VALIDATION_IN'),
            ],
        ];
    }

    public static function validate(array $data, array $validations, array $labels, array $customMessages = []): array
    {
        $validator = new MightyValidator();
        
        $defaultMessages = self::getDefaultMessages();
        $messages = array_merge($defaultMessages, $customMessages);

        self::overrideBuiltInRules($validator, $defaultMessages['*']);

        try {
            $validator->setData($data)
                ->setValidations($validations)
                ->setLabels($labels)
                ->setMessages($messages)
                ->validate();

            if (!$validator->isOK()) {
                $errors = $validator->getErrors();
                return self::formatErrors($errors);
            }

            return [];
        } catch (\Exception $e) {
            return [
                ['field' => 'system', 'message' => 'Ошибка валидации данных']
            ];
        }
    }

    private static function overrideBuiltInRules(MightyValidator $validator, array $messages): void
    {
        $rules = $validator->getRules();
        
        foreach ($rules as $ruleName => $rule) {
            if (isset($messages[$ruleName])) {
                $newRule = new Rule([
                    '@name' => $ruleName,
                    '@arguments' => $rule->getArguments(),
                    '@callback' => $rule->getCallback(),
                    '@parameters' => $rule->getParameters(),
                    '@message' => $messages[$ruleName],
                    '@description' => $rule->getDescription()
                ]);
                
                $validator->addRule($newRule);
            }
        }
    }

    protected static function formatErrors(array $errors): array
    {
        $formattedErrors = [];
        
        foreach ($errors as $field => $result) {
            if (method_exists($result, 'getErrors')) {
                $fieldErrors = $result->getErrors();
                
                foreach ($fieldErrors as $rule => $errorMessage) {
                    $formattedErrors[] = [
                        'field' => $field,
                        'message' => $errorMessage
                    ];
                }
            }
        }
        
        return $formattedErrors;
    }
}