<?php

namespace Sammyjo20\Saloon\Http;

use Sammyjo20\Saloon\Traits\CollectsData;
use Sammyjo20\Saloon\Traits\SendsRequests;
use Sammyjo20\Saloon\Traits\CollectsConfig;
use Sammyjo20\Saloon\Traits\CollectsHeaders;
use Sammyjo20\Saloon\Traits\CollectsHandlers;
use Sammyjo20\Saloon\Helpers\ReflectionHelper;
use Sammyjo20\Saloon\Traits\HasCustomResponses;
use Sammyjo20\Saloon\Traits\CollectsQueryParams;
use Sammyjo20\Saloon\Traits\CollectsInterceptors;
use Sammyjo20\Saloon\Traits\AuthenticatesRequests;
use Sammyjo20\Saloon\Interfaces\SaloonRequestInterface;
use Sammyjo20\Saloon\Exceptions\SaloonMethodNotFoundException;
use Sammyjo20\Saloon\Exceptions\SaloonInvalidConnectorException;

abstract class SaloonRequestOld implements SaloonRequestInterface
{
    use CollectsData,
        CollectsQueryParams,
        CollectsHeaders,
        CollectsConfig,
        CollectsHandlers,
        CollectsInterceptors,
        AuthenticatesRequests,
        HasCustomResponses,
        SendsRequests;

    /**
     * Define the method that the request will use.
     *
     * @var string|null
     */
    protected ?string $method = null;

    /**
     * The connector.
     *
     * @var string|null
     */
    protected ?string $connector = null;

    /**
     * The instantiated connector instance.
     *
     * @var SaloonConnector|null
     */
    private ?SaloonConnector $loadedConnector = null;

    /**
     * Instantiate a new class with the arguments.
     *
     * @param mixed ...$arguments
     * @return SaloonRequest
     */
    public static function make(...$arguments): self
    {
        return new static(...$arguments);
    }

    /**
     * Define anything that should be added to the request
     * before it is sent.
     *
     * @param SaloonRequest $request
     * @return void
     */
    public function boot(SaloonRequest $request): void
    {
        //
    }

    /**
     * Get the method the class is using.
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        if (empty($this->method)) {
            return null;
        }

        return $this->method;
    }

    /**
     * Boot the connector
     *
     * @return void
     * @throws SaloonInvalidConnectorException
     * @throws \ReflectionException
     */
    private function bootConnector(): void
    {
        if (empty($this->connector) || ! class_exists($this->connector)) {
            throw new SaloonInvalidConnectorException;
        }

        $isValidConnector = ReflectionHelper::isSubclassOf($this->connector, SaloonConnector::class);

        if (! $isValidConnector) {
            throw new SaloonInvalidConnectorException;
        }

        $this->setConnector(new $this->connector);
    }

    /**
     * Get the connector instance. If it hasn't yet been booted, we will boot it up.
     *
     * @return SaloonConnector
     * @throws SaloonInvalidConnectorException
     */
    public function getConnector(): SaloonConnector
    {
        if (! $this->loadedConnector instanceof SaloonConnector) {
            $this->bootConnector();
        }

        return $this->loadedConnector;
    }

    /**
     * Specify a connector to use in the request.
     *
     * @param SaloonConnector $connector
     * @return $this
     */
    public function setConnector(SaloonConnector $connector): self
    {
        $this->loadedConnector = $connector;

        return $this;
    }

    /**
     * Build up the final request URL.
     *
     * @return string
     * @throws SaloonInvalidConnectorException
     */
    public function getFullRequestUrl(): string
    {
        $requestEndpoint = $this->defineEndpoint();

        if ($requestEndpoint !== '/') {
            $requestEndpoint = ltrim($requestEndpoint, '/ ');
        }

        $requiresTrailingSlash = ! empty($requestEndpoint) && $requestEndpoint !== '/';

        $baseEndpoint = rtrim($this->getConnector()->defineBaseUrl(), '/ ');
        $baseEndpoint = $requiresTrailingSlash ? $baseEndpoint . '/' : $baseEndpoint;

        return $baseEndpoint . $requestEndpoint;
    }

    /**
     * Define the endpoint for the request.
     *
     * @return string
     */
    public function defineEndpoint(): string
    {
        return '';
    }

    /**
     * Check if a trait exists on the connector.
     *
     * @param string $trait
     * @return bool
     * @throws SaloonInvalidConnectorException
     */
    public function traitExistsOnConnector(string $trait): bool
    {
        return array_key_exists($trait, class_uses($this->getConnector()));
    }

    /**
     * Dynamically proxy other methods to the connector.
     *
     * @param $method
     * @param $parameters
     * @return mixed
     * @throws SaloonMethodNotFoundException
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->getConnector(), $method) === false) {
            throw new SaloonMethodNotFoundException($method, $this->getConnector());
        }

        return $this->getConnector()->{$method}(...$parameters);
    }
}