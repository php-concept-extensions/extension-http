<?php declare(strict_types=1);

namespace Concept\Extensions\Http\Response;

use Concept\Core\Http\Contracts\RequestContextInterface;
use Concept\Extensions\Http\Contracts\ResponseFactoryInterface;
use Concept\Extensions\Http\Contracts\UrlGeneratorInterface;
use Concept\Extensions\Http\Protocol\HttpHeader;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Protocol\HttpValue;
use Concept\Extensions\Http\Protocol\UrlComponent;
use Concept\Extensions\Http\Requests\RequestAttribute;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ResponseFactory implements ResponseFactoryInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestContextInterface $requestContext,
    ) {}

    public function createResponse(int $code = HttpStatusCode::OK, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response())->withStatus($code, $reasonPhrase);
    }

    public function json(
        mixed $data,
        int $code = HttpStatusCode::OK,
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface {
        $response = $this->createResponse($code);
        $response->getBody()->write((string) json_encode($data, $jsonFlags));

        return $response->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::JSON);
    }

    public function redirect(string $url, int $status = HttpStatusCode::FOUND): ResponseInterface
    {
        return new RedirectResponse($url, $status);
    }

    public function redirectByName(
        string $urlName,
        array $parameters = [],
        int $status = HttpStatusCode::FOUND,
    ): ResponseInterface {
        return $this->redirect($this->urlGenerator->uri($urlName, $parameters), $status);
    }

    public function redirectBack(
        int $status = HttpStatusCode::FOUND,
        string $fallback = '/',
        ?ServerRequestInterface $request = null,
    ): ResponseInterface
    {
        $request ??= $this->requestContext->current();
        if ($request === null) {
            return $this->redirect($fallback, $status);
        }

        $url = $request->getHeaderLine(HttpHeader::REFERER);

        if ($url === '') {
            $url = $request->getAttribute(RequestAttribute::SAFE_BACK_URL);
        }

        $target = (is_string($url) && $url !== '' && $this->isInternalUrl($request, $url)) ? $url : $fallback;

        return new RedirectResponse($target, $status);
    }

    private function isInternalUrl(ServerRequestInterface $request, string $url): bool
    {
        $parts = parse_url($url);
        $currentHost = $request->getUri()->getHost();

        if (!isset($parts[UrlComponent::HOST])) {
            return str_starts_with($url, '/');
        }

        return $parts[UrlComponent::HOST] === $currentHost;
    }
}
