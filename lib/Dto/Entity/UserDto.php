<?php

namespace Alto\MakeApi\Dto\Entity;

use Alto\MakeApi\Dto\BaseDto;
use Bitrix\Main\UserTable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserDto',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'login', type: 'string', example: 'admin'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'name', type: 'string', nullable: true),
        new OA\Property(property: 'last_name', type: 'string', nullable: true),
        new OA\Property(property: 'second_name', type: 'string', nullable: true),
    ]
)]
class UserDto extends BaseDto
{
    public readonly int $id;
    public readonly string $login;
    public readonly ?string $email;
    public readonly ?string $name;
    public readonly ?string $last_name;
    public readonly ?string $second_name;

    public function __construct(
        int $id,
        string $login,
        ?string $email,
        ?string $name,
        ?string $lastName,
        ?string $secondName,
    ) {
        $this->id = $id;
        $this->login = $login;
        $this->email = $email;
        $this->name = $name;
        $this->last_name = $lastName;
        $this->second_name = $secondName;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int) $row['ID'],
            (string) ($row['LOGIN'] ?? ''),
            isset($row['EMAIL']) && $row['EMAIL'] !== '' ? (string) $row['EMAIL'] : null,
            isset($row['NAME']) && $row['NAME'] !== '' ? (string) $row['NAME'] : null,
            isset($row['LAST_NAME']) && $row['LAST_NAME'] !== '' ? (string) $row['LAST_NAME'] : null,
            isset($row['SECOND_NAME']) && $row['SECOND_NAME'] !== '' ? (string) $row['SECOND_NAME'] : null,
        );
    }

    /**
     * @param self|int|string|null|false $value ID пользователя или уже собранный DTO
     */
    public static function tryFromMixed(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }
        if ($value === null || $value === false || $value === '') {
            return null;
        }
        $id = (int) $value;
        if ($id <= 0) {
            return null;
        }
        $row = UserTable::getById($id)->fetch();
        if (!is_array($row)) {
            return null;
        }

        return self::fromArray($row);
    }
}
