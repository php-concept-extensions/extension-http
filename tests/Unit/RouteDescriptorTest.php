<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Extensions\Http\Routing\RouteDescriptor;
use Laminas\Diactoros\Response;
use League\Route\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class RouteDescriptorTest extends TestCase
{
    private Router $router;

    private RouteDescriptor $descriptor;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/users/{id}', [$this, 'userHandler'])->setName('users.show');
        $this->descriptor = new RouteDescriptor($this->router);
    }

    public function userHandler(string $id): ResponseInterface
    {
        return new Response();
    }

    public function testFormatMethodsJoinsArrayMethods(): void
    {
        $routes = $this->descriptor->all();
        $route = $routes[0];

        $this->assertSame('GET', $this->descriptor->formatMethods($route));
    }

    public function testActionReturnsControllerMethodForArrayHandler(): void
    {
        $route = $this->descriptor->all()[0];

        $this->assertSame(self::class . '::userHandler', $this->descriptor->action($route));
    }

    public function testDescribeReturnsExpectedShape(): void
    {
        $description = $this->descriptor->describe($this->descriptor->all()[0]);

        $this->assertSame('GET', $description['method']);
        $this->assertSame('/users/{id}', $description['path']);
        $this->assertSame('users.show', $description['name']);
        $this->assertSame(self::class . '::userHandler', $description['action']);
        $this->assertIsArray($description['middleware']);
        $this->assertIsArray($description['vars']);
    }

    public function testAllReturnsRegisteredRoutes(): void
    {
        $routes = $this->descriptor->all();

        $this->assertNotEmpty($routes);
        $this->assertSame('users.show', $routes[0]->getName());
    }
}
