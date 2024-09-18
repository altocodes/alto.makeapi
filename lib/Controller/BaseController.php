<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Enum\HttpStatus;
use Alto\MakeApi\Exception\Http\BaseHttpException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Response;
use Bitrix\Main\Engine\ActionFilter;

class BaseController extends Controller
{
    protected HttpStatus $httpStatus = HttpStatus::SUCCESS;

    public function setHttpStatus(HttpStatus $status)
    {
        $this->httpStatus = $status;
    }

    /**
     * Установка корректного кода ответа
     * @param Response $response
     * @return void
     */
    public function finalizeResponse(Response $response)
    {
        if (!empty($response->getErrors()) && $this->httpStatus == HttpStatus::SUCCESS) {
            $this->setHttpStatus(HttpStatus::ERROR);
        }

        $response->setStatus($this->httpStatus->value);
    }

    /**
     * Обработка всех исключений, в т.ч. и модуля
     *
     * @param \Throwable $throwable
     * @return void
     */
    protected function runProcessingThrowable(\Throwable $throwable)
    {
        if ($throwable instanceof BaseHttpException) {
            $this->handleHttpResponseException($throwable);
        } else {
            $this->handleException($throwable);
        }
    }

    /**
     * Обработка ошибок модуля
     * @param BaseHttpException $exception
     * @return void
     */
    protected function handleHttpResponseException(BaseHttpException $exception)
    {
        $this->addError(new Error(
            $exception->getErrorMessage(),
            $exception->getErrorCode(),
            $exception->getErrorDetails(),
        ));
        $this->setHttpStatus($exception->getHttpStatus());
    }

    /**
     * Обработка остальных ошибок
     * @param \Throwable $e
     * @return void
     */
    public function handleException(\Throwable $e)
    {
        $this->addError(new Error(
            $e->getMessage(),
            'request_error',
        ));
        $this->setHttpStatus(HttpStatus::ERROR);
    }

    public function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\Csrf(false),
        ];
    }
}