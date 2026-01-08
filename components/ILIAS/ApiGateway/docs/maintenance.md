
# Maintenance Documentation

This guide is for developers maintaining or extending the core functionality of the `ApiGateway` component.

## Table of Contents

* [Architecture Overview](#architecture-overview)
* [Core Components & Concepts](#core-components--concepts)
* [Request Lifecycle](#request-lifecycle)
* [How to Extend the ApiGateway Core](#how-to-extend-the-apigateway-core)

## Architecture Overview

The `ApiGateway` component provides a modern, modular architecture for building APIs in ILIAS. It is built on the [Slim Framework](https://www.slimframework.com/), which handles the underlying request-response lifecycle.

**Key Principles:**

* **Single Entry Point:** All REST API requests enter through `/rest/index.php`, which boots the ILIAS environment and hands control to the `WebApp`.
* **Dependency Injection:** Services are wired together in `ApiGateway.php` using the ILIAS component system's dependency management features (`$define`, `$contribute`, `$pull`, etc.).
* **Layered Structure:** The component follows principles of layered architecture, separating concerns into `Domain`, `Application`, and `Infrastructure` layers, although this is a work in progress.
* **Extensibility:** The system is designed to be extended by other components, primarily through the contribution of `Route` and `Activity` objects.

## Core Components & Concepts

* **`ApiGateway.php`**: The central service provider for the component. It defines and instantiates all core services, middlewares, and routes, acting as a dependency injection container.
* **`RestAppEntryPoint.php`**: The official ILIAS entry point. Its main job is to initialize the ILIAS environment and start the `WebApp`.
* **`Application/WebApp.php`**: The primary orchestrator. It configures the Slim application, registers all routes from the `RoutesRegistry`, adds global middlewares, and starts the request handling process.
* **`Webservice/`**: This is the "View" or "Formatting" layer.
  * `RestWebservice.php` is responsible for serializing data into the final JSON response structure for both successful and failed requests.
* **`Routing/`**:
  * `Route.php`: An interface that defines what an API endpoint is (path, methods, handler).
  * `RoutesRegistry.php`: A singleton that collects all `Route` objects contributed by different components and ensures there are no duplicates.
  * `RoutesAutoloader.php`: Discovers and loads all `Route` objects contributed to the component system.
* **`Activity/`**:
  * `ActivityRoutesAutoloader.php`: Discovers all `ILIAS\Component\Activities\Activity` objects from other components and automatically converts them into API routes.
  * `ActivityNamespace.php`: Contains the logic for auto-generating a URL path from an `Activity`'s class name.
* **`Auth/`**:
  * `AuthenticationMiddleware.php`: A global middleware that intercepts incoming requests, extracts the bearer token, and uses the `Authentication` service to validate it.
  * `Service/Authentication.php`: The domain service containing the core logic for creating, validating, and refreshing tokens.
  * `Repository/`: Defines interfaces for data persistence (`UserRepository`, `RefreshTokenRepository`), with concrete implementations in the `Infrastructure` directory.
* **`Configuration/`**:
  * `ConfigurationService.php`: Provides a unified API for accessing configuration values, abstracting whether the source is an admin setting, `client.ini.php`, or a constant.
  * `ilApiGatewaySettings.php` & `classes/class.ilObjApiGatewayGUI.php`: The legacy implementation of the administration panel UI for managing settings.

## Request Lifecycle

1. An HTTP request arrives at `/rest/index.php`.
2. `RestAppEntryPoint` is invoked. It initializes the ILIAS environment via `AllModernComponents` and then runs the `WebApp`.
3. `WebAppFactory` constructs the `WebApp` instance, injecting all its dependencies.
    * During this process, `RoutesAutoloader` and `ActivityRoutesAutoloader` run, discovering all available routes and registering them in the `RoutesRegistry`.
4. The `WebApp` configures the Slim application with global middlewares (like the `ErrorMiddleware`) and all the routes from the registry.
5. The Slim application's routing middleware matches the incoming request to a registered route.
6. Route-specific middlewares (e.g., `AuthenticationMiddleware`) are executed.
7. If all middlewares pass, the `ResponseHandler` is invoked. It calls the route's specific handler logic (its `__invoke` method).
8. The return value from the route handler is wrapped in a `Payload` object and passed to `RestWebservice` to be formatted into a standard JSON response.
9. If at any point an exception is thrown, the `ErrorHandler` catches it and uses `RestWebservice` to format a standard JSON error response.

## How to Extend the ApiGateway Core

* **Adding a New Core Route:**
    1. Create a new class that implements `ILIAS\ApiGateway\Routing\Route`. For simple cases, extend `ILIAS\ApiGateway\Routes\ApiRoute`.
    2. Instantiate and contribute the new route class within `ApiGateway.php`.

* **Adding a New Global Middleware:**
    1. Create a class that implements `Psr\Http\Server\MiddlewareInterface`.
    2. Instantiate and contribute the middleware in `ApiGateway.php`.
    3. Add the middleware to the `WebApp`'s middleware stack, typically in `WebApp::registerMiddlewares()`.

* **Adding a New Configuration Option:**
    1. Add the new setting to the `Configuration/Domain/Enum/SystemSetting.php` enum.
    2. Update `Configuration/Infrastructure/ConfigurationService.php` to expose the new setting.
    3. Update `Configuration/ilApiGatewaySettings.php` and `classes/class.ilObjApiGatewayGUI.php` to include the new setting in the administration UI.
