<?php

declare(strict_types=1);

use Saloon\Managers\RequestManager;
use Saloon\Tests\Fixtures\Requests\UserRequest;
use Saloon\Tests\Fixtures\Requests\QueryParameterRequest;
use Saloon\Tests\Fixtures\Requests\QueryParameterConnectorRequest;
use Saloon\Tests\Fixtures\Requests\QueryParameterConnectorBlankRequest;
use Saloon\Tests\Fixtures\Requests\OverwrittenQueryParameterConnectorRequest;

test('a request with query params added sends the query params', function () {
    $request = new QueryParameterRequest();

    $request->queryParameters()->add('sort', '-created_at');

    $pendingRequest = $request->createPendingRequest();

    $query = $pendingRequest->queryParameters()->all();

    expect($query)->toHaveKey('per_page', 100); // Test Default
    expect($query)->toHaveKey('sort', '-created_at'); // Test Adding Data After
});

test('if setQuery is used, all other default query params wont be included', function () {
    $request = new QueryParameterConnectorRequest();

    $request->queryParameters()->set([
        'sort' => 'nickname',
    ]);

    $pendingRequest = $request->createPendingRequest();
    $query = $pendingRequest->queryParameters()->all();

    expect($query)->toEqual(['sort' => 'nickname']);
});

test('a connector can have query that is set', function () {
    $request = new QueryParameterConnectorRequest();

    $pendingRequest = $request->createPendingRequest();
    $query = $pendingRequest->queryParameters()->all();

    expect($query)->toHaveKey('sort', 'first_name'); // Added by connector
    expect($query)->toHaveKey('include', 'user'); // Added by request
});

test('a request query parameter can overwrite a connectors parameter', function () {
    $request = new OverwrittenQueryParameterConnectorRequest();

    $pendingRequest = $request->createPendingRequest();
    $query = $pendingRequest->queryParameters()->all();

    expect($query)->toEqual(['sort' => 'date_of_birth']);
});

test('manually overwriting query parameter in runtime can overwrite connector parameter', function () {
    $request = new QueryParameterConnectorBlankRequest();

    $request->queryParameters()->add('sort', 'custom_field');

    $pendingRequest = $request->createPendingRequest();
    $query = $pendingRequest->queryParameters()->all();

    expect($query)->toEqual(['sort' => 'custom_field']);
});

test('manually overwriting query parameter in runtime can overwrite request parameter', function () {
    $request = new QueryParameterRequest();

    $request->queryParameters()->add('per_page', 500);

    $pendingRequest = $request->createPendingRequest();
    $query = $pendingRequest->queryParameters()->all();

    expect($query)->toEqual(['per_page' => 500]);
});

test('when not sending query parameters, the query option is not set', function () {
    $request = new UserRequest();

    $pendingRequest = $request->createPendingRequest();
    $query = $pendingRequest->queryParameters()->all();

    expect($query)->toBeEmpty();
});
