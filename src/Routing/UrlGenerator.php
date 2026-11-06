<?php declare(strict_types=1);

namespace Concept\Extensions\Http\Routing;

use Concept\Core\Http\Contracts\RequestContextInterface;
use Concept\Extensions\Http\Contracts\UrlGeneratorInterface;
use League\Route\Router;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class UrlGenerator implements UrlGeneratorInterface
{
    private const string ERR_NO_REQUEST = 'HTTP request is not available in RequestContext.';

    public function __construct(
        private readonly Router $router,
        private readonly RequestContextInterface $requestContext,
    ) {}

    public function base(): string
    {
        $uri = $this->request()->getUri();
        $baseUrl = $uri->getScheme() . '://' . $uri->getHost();

        $port = $uri->getPort();
        if ($port !== null && !in_array($port, [80, 443], true)) {
            $baseUrl .= ':' . $port;
        }

        return $baseUrl;
    }

    public function uri(string $name, array $parameters = []): string
    {
        return $this->router->getNamedRoute($name)->getPath($parameters);
    }

    public function url(string $name, array $parameters = []): string
    {
        return $this->build($this->base(), $this->uri($name, $parameters));
    }

    private function build(string $baseUrl, string $uri): string
    {
        return rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');
    }

    private function request(): ServerRequestInterface
    {
        return $this->requestContext->current()
            ?? throw new RuntimeException(self::ERR_NO_REQUEST);
    }
}
