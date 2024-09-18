<?php

namespace Alto\MakeApi\Exception\Http;

use Alto\MakeApi\Enum\HttpStatus;
use Exception;
use Throwable;

class BaseHttpException extends Exception
{

    protected HttpStatus $httpStatus = HttpStatus::IM_A_TEAPOT;
    protected string $errorCode = '';
    protected string $errorMessage = '';
    protected array $errorDetails = [];

    public function __construct($message, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Статическая обертка, для использования сокращенного синтаксиса выброса исключения.
     */
    public static function create(
        string      $errorMessage,
        ?string     $errorCode = null,
        ?array      $errorDetails = null,
        ?HttpStatus $httpStatus = null,
        ?Throwable  $previous = null
    ): self {
        $e = new static($errorMessage, 0, $previous);

        if ($errorCode) {
            $e->setErrorCode($errorCode);
        }

        if ($errorDetails) {
            $e->setErrorDetails($errorDetails);
        }

        if ($httpStatus) {
            $e->setHttpStatus($httpStatus);
        }

        return $e;
    }

    /**
     * Возвращает HTTP-код
     */
    public function getHttpStatus(): HttpStatus
    {
        return $this->httpStatus;
    }

    /**
     * Возвращает символьный код ошибки
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Возвращает человеко-понятное сообщение об ошибке
     */
    public function getErrorMessage(): string
    {
        return $this->getMessage();
    }

    /**
     * Возвращает массив содержащий детализированные данные ошибки
     */
    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    /**
     * Установить символьный код ошибки
     */
    public function setErrorCode(string $errorCode): self
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Установить пэйлоад (детализацию ошибки
     */
    public function setErrorDetails(array $errorDetails): self
    {
        $this->errorDetails = $errorDetails;

        return $this;
    }

    /**
     * Установить http-код
     */
    public function setHttpStatus(HttpStatus $code): self
    {
        $this->httpStatus = $code;

        return $this;
    }

    public function setCode(int $code): self
    {
        $this->code = $code;

        return $this;
    }
}