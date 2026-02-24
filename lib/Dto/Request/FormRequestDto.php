<?php

namespace Alto\MakeApi\Dto\Request;

class FormRequestDto
{
    public ?string $fio = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?array $models = null;

    public ?string $comment = null;

    public ?string $preferred_method = null;

    public string $form;

    public string $url;

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