<?php declare(strict_types=1);

namespace Acme\ToDo;

use Env;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\ServerRequestFactory;

class AppIntegrationTest extends TestCase
{
    public function testBootstrap(): void
    {
        $this->expectNotToPerformAssertions();

        App::bootstrap();
    }

    public function testRootRequest(): void
    {
        $app = App::bootstrap();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')->withHeader('accept', '*/*');
        $response = $app->handle($request);
        assertSame(200, $response->getStatusCode());
    }

    public function testContentNegotiation(): void
    {
        $app = App::bootstrap();
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')->withHeader('accept', 'text/html');
        $response = $app->handle($request);
        assertSame(406, $response->getStatusCode());
    }

    /**
     * @dataProvider authRequiredRequestProvider
     */
    public function testAuthenticationFailure(Request $request): void
    {
        $app = App::bootstrap();
        $response = $app->handle($request);
        assertSame(401, $response->getStatusCode());
    }

    /**
     * @dataProvider authRequiredRequestProvider
     */
    public function testAuthenticationSuccess(Request $request): void
    {
        Env::$options |= Env::USE_ENV_ARRAY;
        $_ENV['AUTH_USERS'] = '{ "john": "doe" }';
        $app = App::bootstrap();
        $request = $request->withHeader('Authorization', 'Basic ' . base64_encode('john:doe'));
        $response = $app->handle($request);
        assertNotSame(401, $response->getStatusCode());
    }

    /**
     * @return array[]
     */
    public function authRequiredRequestProvider(): array
    {
        $serverRequestFactory = new ServerRequestFactory();

        return [
            [ $serverRequestFactory->createServerRequest('POST', '/todos')
                                   ->withHeader('accept', '*/*') ],
            [ $serverRequestFactory->createServerRequest('PATCH', '/todos/foo')
                                   ->withHeader('accept', '*/*') ],
            [ $serverRequestFactory->createServerRequest('DELETE', '/todos/foo')
                                   ->withHeader('accept', '*/*') ],
        ];
    }
}
