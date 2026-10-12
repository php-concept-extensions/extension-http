<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Extensions\Http\Routing\UrlGenerator;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use League\Route\Router;
use PHPUnit\Framework\TestCase;

final class UrlGeneratorTest extends TestCase
{
    private UrlGenerator $generator;

    protected function setUp(): void
    {
        $router = new Router();
        $router->get('/users/{id}', static fn(): null => null)->setName('users.show');
        $router->get('/posts', static fn(): null => null)->setName('posts.index');

        $this->generator = new UrlGenerator($router);
    }

    public function testUriReturnsNamedRoutePath(): void
    {
        $this->assertSame('/users/42', $this->generator->uri('users.show', ['id' => '42']));
        $this->assertSame('/posts', $this->generator->uri('posts.index'));
    }

    public function testBaseOmitsDefaultHttpPorts(): void
    {
        $httpRequest = (new ServerRequest())->withUri(new Uri('http://example.com:80/home'));
        $httpsRequest = (new ServerRequest())->withUri(new Uri('https://example.com:443/home'));

        $this->assertSame('http://example.com', $this->generator->base($httpRequest));
        $this->assertSame('https://example.com', $this->generator->base($httpsRequest));
    }

    public function testBaseIncludesNonDefaultPort(): void
    {
        $request = (new ServerRequest())->withUri(new Uri('https://example.com:8088/stack/log'));

        $this->assertSame('https://example.com:8088', $this->generator->base($request));
    }

    public function testUrlCombinesBaseAndNamedRoutePath(): void
    {
        $request = (new ServerRequest())->withUri(new Uri('https://example.com:8088/current'));

        $this->assertSame(
            'https://example.com:8088/users/7',
            $this->generator->url($request, 'users.show', ['id' => '7']),
        );
    }
}
