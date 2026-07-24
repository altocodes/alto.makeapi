<?php

namespace Alto\MakeApi\Dto\Request;

class FormRequestDto
{
    public ?string $name = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $comment = null;

    public string $form;

    public string $url;

    /** Структура как в $_FILES для multipart-поля «questionnaire» */
    public mixed $questionnaire = null;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}