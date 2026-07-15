<?php

namespace ChrisLorando\LaravelAccurate\Exceptions;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class AccurateApiException extends \RuntimeException
{
    public ?int $statusCode;

    public ?array $responseBody;

    public static function fromClientException(ClientException $e): self
    {
        $body = json_decode(
            $e->getResponse()->getBody()->getContents(),
            true
        );

        $message = $body['error']['message']
            ?? $body['message']
            ?? $body['error']
            ?? $e->getMessage();

        $instance = new self(
            is_string($message) ? $message : $e->getMessage(),
            $e->getResponse()->getStatusCode(),
            $e
        );

        $instance->statusCode = $e->getResponse()->getStatusCode();
        $instance->responseBody = $body;

        return $instance;
    }

    public static function fromServerException(ServerException $e): self
    {
        $body = json_decode(
            $e->getResponse()->getBody()->getContents(),
            true
        );

        $instance = new self(
            'Accurate API server error: '.$e->getMessage(),
            $e->getResponse()->getStatusCode(),
            $e
        );

        $instance->statusCode = $e->getResponse()->getStatusCode();
        $instance->responseBody = $body;

        return $instance;
    }
}
