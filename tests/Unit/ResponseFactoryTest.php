<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Core\Http\Request\RequestContext;
use Concept\Extensions\Http\Protocol\HttpHeader;
use Concept\Extensions\Http\Protocol\HttpStatusCode;
use Concept\Extensions\Http\Protocol\HttpValue;
use Concept\Extensions\Http\Response\ResponseFactory;
use Concept\Extensions\Http\Routing\UrlGenerator;
use Concept\Extensions\Http\Requests\RequestAttribute;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use League\Route\Router;
use PHPUnit\Framework\TestCase;

final class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    private RequestContext $requestContext;

    protected function setUp(): void
    {
        $router = new Router();
        $router->get('/dashboard', static fn(): null => null)->setName('dashboard');

        $this->requestContext = new RequestContext();
        $this->factory = new ResponseFactory(new UrlGenerator($router), $this->requestContext);
    }

    public function testJsonEncodesPayloadWithJsonContentType(): void
    {
        $response = $this->factory->json(['message' => 'ok'], HttpStatusCode::CREATED);

        $this->assertSame(HttpStatusCode::CREATED, $response->getStatusCode());
        $this->assertSame(HttpValue::JSON, $response->getHeaderLine(HttpHeader::CONTENT_TYPE));
        $this->assertSame('{"message":"ok"}', (string) $response->getBody());
    }

    public function testJsonSuccessWrapsData(): void
    {
        $response = $this->factory->jsonSuccess(['id' => 1]);
        $payload = json_decode((string) $response->getBody(), true);

        $this->assertSame([
            'status' => 'success',
            'code' => HttpStatusCode::OK,
            'data' => ['id' => 1],
        ], $payload);
    }

    public function testJsonErrorIncludesOptionalErrors(): void
    {
        $withoutErrors = $this->factory->jsonError('failed', HttpStatusCode::UNPROCESSABLE_ENTITY);
        $withErrors = $this->factory->jsonError('failed', HttpStatusCode::UNPROCESSABLE_ENTITY, ['email' => ['required']]);

        $this->assertSame(
            [
                'status' => 'error',
                'code' => HttpStatusCode::UNPROCESSABLE_ENTITY,
                'message' => 'failed',
            ],
            json_decode((string) $withoutErrors->getBody(), true),
        );
        $this->assertSame(
            ['email' => ['required']],
            json_decode((string) $withErrors->getBody(), true)['errors'],
        );
    }

    public function testRedirectReturnsLocationHeader(): void
    {
        $response = $this->factory->redirect('/login', HttpStatusCode::MOVED_PERMANENTLY);

        $this->assertSame(HttpStatusCode::MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testRedirectByNameUsesNamedRoute(): void
    {
        $response = $this->factory->redirectByName('dashboard');

        $this->assertSame('/dashboard', $response->getHeaderLine('Location'));
    }

    public function testRedirectBackUsesFallbackWhenRequestContextIsEmpty(): void
    {
        $response = $this->factory->redirectBack(fallback: '/home');

        $this->assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testRedirectBackUsesInternalReferer(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('https://example.com/form'))
            ->withHeader(HttpHeader::REFERER, 'https://example.com/previous');

        $response = $this->factory->redirectBack(request: $request);

        $this->assertSame('https://example.com/previous', $response->getHeaderLine('Location'));
    }

    public function testRedirectBackUsesRelativeReferer(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('https://example.com/form'))
            ->withHeader(HttpHeader::REFERER, '/previous');

        $response = $this->factory->redirectBack(request: $request);

        $this->assertSame('/previous', $response->getHeaderLine('Location'));
    }

    public function testRedirectBackFallsBackForExternalReferer(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('https://example.com/form'))
            ->withHeader(HttpHeader::REFERER, 'https://evil.example/phish');

        $response = $this->factory->redirectBack(fallback: '/safe', request: $request);

        $this->assertSame('/safe', $response->getHeaderLine('Location'));
    }

    public function testRedirectBackUsesSafeBackUrlWhenRefererMissing(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('https://example.com/form'))
            ->withAttribute(RequestAttribute::SAFE_BACK_URL, '/safe-back');

        $response = $this->factory->redirectBack(request: $request);

        $this->assertSame('/safe-back', $response->getHeaderLine('Location'));
    }

    public function testRedirectBackUsesRequestFromContextWhenRequestArgumentIsNull(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('https://example.com/form'))
            ->withHeader(HttpHeader::REFERER, '/from-context');
        $this->requestContext->set($request);

        $response = $this->factory->redirectBack();

        $this->assertSame('/from-context', $response->getHeaderLine('Location'));
    }
}
