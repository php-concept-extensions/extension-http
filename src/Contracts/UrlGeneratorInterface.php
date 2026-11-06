<?php declare(strict_types=1);

namespace Concept\Extensions\Http\Contracts;

interface UrlGeneratorInterface
{
    public function base(): string;

    /**
     * @param array<string, mixed> $parameters
     */
    public function uri(string $name, array $parameters = []): string;

    /**
     * @param array<string, mixed> $parameters
     */
    public function url(string $name, array $parameters = []): string;
}
