<?php

declare(strict_types=1);

namespace MNIB\UrgentCargus\Exception;

use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use function GuzzleHttp\json_decode;
use function is_string;
use function sprintf;

class ClientException extends RuntimeException
{
    public static function fromException(GuzzleException $exception): self
    {
        $code = $exception->getResponse() !== null ? $exception->getResponse()->getStatusCode() : 0;
        $message = $exception->getMessage();

        $contents = $exception->hasResponse() ? (string)$exception->getResponse()->getBody() : '';

        if ($contents === '') {
            return new self(sprintf('Something went wrong: %s', $message));
        }

        $data = json_decode($contents, true);

        if (isset($data['message']) && $data['message'] !== '') {
            $message = $data['message'];
        } elseif (isset($data['Error']) && $data['Error'] !== '') {
            $message = $data['Error'];
        } elseif (is_string($data)) {
            $message = $data;
        }

        return new self($message, $code);
    }
}
