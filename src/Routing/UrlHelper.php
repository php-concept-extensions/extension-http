<?php declare(strict_types=1);

namespace Concept\Extensions\Http\Routing;

use Concept\Core\Http\Contracts\RequestContextInterface;
use Concept\Core\Http\Request\RequestAttribute;
use Concept\Extensions\Http\Contracts\UrlHelperInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class UrlHelper implements UrlHelperInterface
{
    private const string ERR_NO_REQUEST = 'HTTP request is not available in RequestContext.';

    public function __construct(
        private readonly RequestContextInterface $requestContext,
    ) {}

    public function currentUri(): string
    {
        return $this->request()->getUri()->getPath();
    }

    public function currentUrl(): string
    {
        return (string) $this->request()->getUri()->withQuery('')->withFragment('');
    }

    public function currentFullUrl(): string
    {
        return (string) $this->request()->getUri();
    }

    public function currentRoute(): ?string
    {
        $name = $this->request()->getAttribute(RequestAttribute::ROUTE_NAME);

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function routeIs(string ...$patterns): bool
    {
        $current = $this->currentRoute();
        if ($current === null || $patterns === []) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($this->routeNameMatches($current, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function routeNameMatches(string $name, string $pattern): bool
    {
        if (!str_contains($pattern, '*')) {
            return $name === $pattern;
        }

        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';

        return preg_match($regex, $name) === 1;
    }

    private function request(): ServerRequestInterface
    {
        return $this->requestContext->current()
            ?? throw new RuntimeException(self::ERR_NO_REQUEST);
    }
}
