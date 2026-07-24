<?php

namespace Alto\MakeApi\Controller;

use Alto\MakeApi\Enum\HttpStatus;
use Alto\MakeApi\Exception\Http\BaseHttpException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Response;
use Bitrix\Main\Engine\ActionFilter;
use OpenApi\Attributes as OA;
use Alto\MakeApi\Exception\Http\BadRequestException;

#[OA\Info(
    version: "1.0.0",
    title: "API Documentation"
)]
#[OA\Server(
    url: "",
    description: "Текущий сайт"
)]
#[OA\Server(
    url: "https://bmt.leadget.ru/",
    description: "dev стенд"
)]
#[OA\Tag(
    name: "general",
    description: "Общие методы"
)]
class BaseController extends Controller
{
    protected HttpStatus $httpStatus = HttpStatus::SUCCESS;

    public function setHttpStatus(HttpStatus $status)
    {
        $this->httpStatus = $status;
    }

        /**
     * Проверка метода перед выполнением действия
     */
    protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
    {
        $requestMethod = $this->getRequest()->getRequestMethod();
        $actionName = $action->getName();
        
        // Определяем разрешенные методы для каждого действия
        $allowedMethods = $this->getAllowedMethods($actionName);
        
        if (!in_array($requestMethod, $allowedMethods)) {
            throw new BadRequestException("Method {$requestMethod} not allowed for this endpoint");
        }

        return parent::processBeforeAction($action);
    }

    /**
     * Определяет разрешенные HTTP методы для каждого действия
     */
    protected function getAllowedMethods(string $actionName): array
    {
        // По умолчанию для всех действий разрешаем только GET
        $defaultMethods = ['GET'];
        
        // Исключения для действий, которые должны принимать POST
        $postActions = ['submit']; // для FormController
        
        if (in_array($actionName, $postActions)) {
            return ['POST'];
        }
        
        return $defaultMethods;
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