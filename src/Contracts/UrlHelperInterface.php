<?php declare(strict_types=1);

namespace Concept\Extensions\Http\Contracts;

interface UrlHelperInterface
{
    /** Current request path (no scheme/host/query), e.g. `/admin/tags`. */
    public function currentUri(): string;

    /** Current absolute URL without query string. */
    public function currentUrl(): string;

    /** Current absolute URL including query string. */
    public function currentFullUrl(): string;

    /** Matched route name, or null when unavailable. */
    public function currentRoute(): ?string;

    /**
     * Whether the matched route name matches any pattern (`*` wildcards allowed).
     */
    public function routeIs(string ...$patterns): bool;
}
