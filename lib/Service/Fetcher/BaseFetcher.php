<?php

namespace Alto\MakeApi\Service\Fetcher;

use Alto\MakeApi\Dto\BaseDto;
use Bitrix\Main\Application;

abstract class BaseFetcher
{
    protected string $host;
    protected array $file;

    public function __construct(array $file)
    {
        $this->host = $this->getHost();
        $this->file = $file;
    }

    protected function getHost()
    {
        $server = Application::getInstance()->getContext()->getServer();
        $protocol = $server->get('HTTPS') ? 'https' : 'http';
        return $protocol . '://' . $server->getHttpHost();
    }

    abstract public function get(): ?BaseDto;
}