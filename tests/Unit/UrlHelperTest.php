<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Core\Http\Request\RequestAttribute;
use Concept\Core\Http\Request\RequestContext;
use Concept\Extensions\Http\Routing\UrlHelper;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UrlHelperTest extends TestCase
{
    private UrlHelper $helper;

    private RequestContext $requestContext;

    protected function setUp(): void
    {
        $this->requestContext = new RequestContext();
        $this->helper = new UrlHelper($this->requestContext);
    }

    public function testCurrentUriUrlAndFullUrl(): void
    {
        $this->requestContext->set(
            (new ServerRequest())->withUri(new Uri('https://example.com:8088/admin/tags?q=php#top')),
        );

        $this->assertSame('/admin/tags', $this->helper->currentUri());
        $this->assertSame('https://example.com:8088/admin/tags', $this->helper->currentUrl());
        $this->assertSame('https://example.com:8088/admin/tags?q=php#top', $this->helper->currentFullUrl());
    }

    public function testCurrentRouteAndRouteIs(): void
    {
        $this->requestContext->set(
            (new ServerRequest())
                ->withUri(new Uri('https://example.com/admin/tags/edit/1'))
                ->withAttribute(RequestAttribute::ROUTE_NAME, 'admin.tags.edit'),
        );

        $this->assertSame('admin.tags.edit', $this->helper->currentRoute());
        $this->assertTrue($this->helper->routeIs('admin.tags.edit'));
        $this->assertTrue($this->helper->routeIs('admin.tags*'));
        $this->assertTrue($this->helper->routeIs('admin.posts', 'admin.tags.*'));
        $this->assertFalse($this->helper->routeIs('admin.posts*'));
        $this->assertFalse($this->helper->routeIs());
    }

    public function testRouteIsFalseWhenRouteNameMissing(): void
    {
        $this->requestContext->set((new ServerRequest())->withUri(new Uri('https://example.com/admin')));

        $this->assertNull($this->helper->currentRoute());
        $this->assertFalse($this->helper->routeIs('admin.home'));
    }

    public function testThrowsWhenRequestContextIsEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP request is not available in RequestContext.');

        $this->helper->currentUri();
    }
}
