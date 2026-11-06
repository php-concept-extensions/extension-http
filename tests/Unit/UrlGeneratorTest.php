<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Core\Http\Request\RequestContext;
use Concept\Extensions\Http\Routing\UrlGenerator;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use League\Route\Router;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UrlGeneratorTest extends TestCase
{
    private UrlGenerator $generator;

    private RequestContext $requestContext;

    protected function setUp(): void
    {
        $router = new Router();
        $router->get('/users/{id}', static fn(): null => null)->setName('users.show');
        $router->get('/posts', static fn(): null => null)->setName('posts.index');

        $this->requestContext = new RequestContext();
        $this->generator = new UrlGenerator($router, $this->requestContext);
    }

    public function testUriReturnsNamedRoutePath(): void
    {
        $this->assertSame('/users/42', $this->generator->uri('users.show', ['id' => '42']));
        $this->assertSame('/posts', $this->generator->uri('posts.index'));
    }

    public function testBaseOmitsDefaultHttpPorts(): void
    {
        $this->requestContext->set((new ServerRequest())->withUri(new Uri('http://example.com:80/home')));
        $this->assertSame('http://example.com', $this->generator->base());

        $this->requestContext->set((new ServerRequest())->withUri(new Uri('https://example.com:443/home')));
        $this->assertSame('https://example.com', $this->generator->base());
    }

    public function testBaseIncludesNonDefaultPort(): void
    {
        $this->requestContext->set((new ServerRequest())->withUri(new Uri('https://example.com:8088/stack/log')));

        $this->assertSame('https://example.com:8088', $this->generator->base());
    }

    public function testUrlCombinesBaseAndNamedRoutePath(): void
    {
        $this->requestContext->set((new ServerRequest())->withUri(new Uri('https://example.com:8088/current')));

        $this->assertSame(
            'https://example.com:8088/users/7',
            $this->generator->url('users.show', ['id' => '7']),
        );
    }

    public function testBaseThrowsWhenRequestContextIsEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP request is not available in RequestContext.');

        $this->generator->base();
    }
}
