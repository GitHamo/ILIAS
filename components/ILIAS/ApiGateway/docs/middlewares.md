# Middlewares

## Table of Contents

* [Configuration and Application](#configuration-and-application)
* [How the Layers Work](#how-the-layers-work)
* [Creating a Custom Middleware](#creating-a-custom-middleware)
* [Applying Middlewares to Routes](#applying-middlewares-to-routes)

## Configuration and Application

This table details the purpose of each middleware and how it's typically configured and applied within the `ApiGateway` architecture.

* **Default in WebApp**: Indicates if the middleware is typically registered globally in `@src/Application/WebApp.php`, affecting all requests.
* **Default in Activity**: Indicates if the middleware is intended to be automatically included by default in every `ActivityRoute` (i.e., every activity). This reflects a future architectural decision for implicit inclusion.
* **Configurable per Route**: Indicates if the middleware can be applied to individual routes (e.g., via `Route::getMiddlewares()`), allowing for route-specific behavior.

| Middleware Name                        | Purpose                                     | Default in WebApp | Default in Activity | Configurable per Route |
| :------------------------------------- | :---------------------------------------- | :---------------- | :------------------ | :--------------------- |
| `ApiServiceStatusMiddleware`           | Checks if the API service is globally enabled. | Yes               | No                  | No                     |
| `AuthenticationMiddleware`             | Validates authentication token and attaches user data. | No                | Yes                 | Yes                    |
| `AuthorizationMiddleware`              | Checks if the user is allowed to perform the requested Activity. | No                | Yes                 | Yes                    |
| `BrowserSecurityMiddleware`            | Adds security headers to protect browser clients. | Yes               | No                  | No                     |
| `CorsMiddleware`                       | Manages cross-origin access for web browsers. | Yes               | No                  | No                     |
| `RequestRateLimitingMiddleware`    | Limits client request rates to prevent abuse. | Yes               | No                  | Yes                    |
| `RequestParameterMiddleware`         | Normalizes request parameters (JSON body + query) into a single array attribute. | Yes               | No                  | No                     |
| `JsonBodyParserMiddleware`             | Parses JSON request bodies and populates the Request's parsed body. | Yes               | No                  | No                     |
| `JsonContentNegotiationMiddleware`     | Ensures client can handle `application/json` responses. | Yes               | No                  | No                     |
| `RequestLoggingMiddleware`             | Logs request and response data.           | Yes               | No                  | No                     |
| `TransportSecurityMiddleware`          | Adds transport security headers.          | Yes               | No                  | No                     |

## How the Layers Work

Middlewares are processed in a stack, like layers. The request flows through each layer to reach the center (your code), and then the response flows back out through the same layers in reverse.

**Note on implementation:** 
Because of how the underlying framework works, you must register these layers in **reverse order** to get the correct flow. The last one you add is the first one the request hits.

### 1. Incoming Request Flow (before application logic)

* `ApiServiceStatusMiddleware` (Checks if the API is enabled)
* `RequestLoggingMiddleware` (Starts the timer and logs the request)
* `RequestRateLimitingMiddleware` (Checks if the user has exceeded their quota)
* `JsonContentNegotiationMiddleware` (Ensures the client wants JSON)
* `JsonBodyParserMiddleware` (Turns the JSON text into a PHP array)
* `RequestParameterMiddleware` (Normalizes request parameters)
* `AuthenticationMiddleware` (Identifies the user)
* `AuthorizationMiddleware` (Checks if the user is allowed to perform the requested Activity)

### 2. Application Logic

* **(The API Action runs here)**

### 3. Outgoing Response Flow (after application logic)

* `TransportSecurityMiddleware` (Adds HSTS security)
* `BrowserSecurityMiddleware` (Adds CSP and other browser protections)
* `CorsMiddleware` (Handles cross-domain access)

### Why this order?
- **Security First**: By putting security headers on the very outside, we ensure they are sent even if the server crashes or returns an error.
- **Fail Fast**: We check if the API is enabled and if the format is correct before wasting time on routing or parsing data.
- **Server Protection**: We check the rate limit before parsing the JSON body to prevent large, malicious uploads from slowing down the server.

## Creating a Custom Middleware

A middleware is a class that implements the `Psr\Http\Server\MiddlewareInterface`. It contains a single `process` method that accepts the current request and a request handler.

The `process` method must either:

1. Return a `Response` object to halt the processing chain (e.g., to return a `403 Forbidden` error).
2. Invoke `$handler->handle($request)` to pass control to the next middleware in the chain, or to the route's action if it is the last one.

The following is a basic example of a custom middleware that verifies the presence of a specific HTTP header.

```php
## Components/Vendor/MyModule/Middleware/MyCustomHeaderCheckMiddleware.php

namespace Vendor\MyModule\Middleware;

use ILIAS\HTTP\Response\ResponseFactory;
use ILIAS\HTTP\StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MyCustomHeaderCheckMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactory $responseFactory)
    {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // 1. Check for a specific header
        if (!$request->hasHeader('X-Special-Header')) {
            // 2. If the header is missing, return an error response immediately using the ResponseFactory.
            // The chain stops here.
            $response = $this->responseFactory->create();
            $response = $response->withStatus(StatusCode::HTTP_FORBIDDEN) // 403
                ->withHeader('Content-Type', 'text/plain');
            $response->getBody()->write('Forbidden: Special header is required.');
            return $response;
        }

        // 3. If the header exists, pass the request to the next middleware.
        return $handler->handle($request);
    }
}
```

## Applying Middlewares to Routes

While many middlewares are applied globally, custom middlewares or specific logic can be applied to individual routes. This is supported by all route creation methods, with the `ApiRoute` approach providing a clear example.

### How It Looks

When an `ApiRoute` is instantiated, an array of middleware class names can be passed to the `middlewares` parameter. These middlewares will execute only for this specific route.

```php
## Components/Vendor/MyModule/MyModule.php

use ILIAS\ApiGateway\Application\Routes\ApiRoute;
use ILIAS\ApiGateway\Middleware\AuthenticationMiddleware;
use ILIAS\ApiGateway\Routing\Route;
use ILIAS\HTTP\Response\ResponseFactory;
use Vendor\MyModule\Middleware\MyCustomHeaderCheckMiddleware; 

$contribute[MyCustomHeaderCheckMiddleware::class] = static fn(): MyCustomHeaderCheckMiddleware =>
    new MyCustomHeaderCheckMiddleware(
        $use[ResponseFactory::class],
    );

$contribute[Route::class] = static fn(): Route =>
    new ApiRoute(
        name: 'Secret Data',
        path: "/my-secret-data",
        method: 'GET',
        description: 'An endpoint that requires both a special header and authentication.',
        action: fn(): array => ['secret' => '42'],
        middlewares: [
            AuthenticationMiddleware::class, // Example of an existing middleware
            MyCustomHeaderCheckMiddleware::class, // Your custom middleware
        ]
    );
```

### What It Means

* **`middlewares: [AuthenticationMiddleware::class, MyCustomHeaderCheckMiddleware::class]`**: An array containing the fully qualified class names of the middlewares to apply.
* The middlewares listed are executed *in addition* to any globally registered middlewares.
* Execution occurs *after* the global middlewares but *before* the route's `action` is executed, which protects the route's core logic.
