# Maintenance Documentation

This guide is for developers maintaining or extending the core functionality of the `ApiGateway` component.

## Table of Contents

* [Architecture Overview](#architecture-overview)
* [Core Components & Concepts](#core-components--concepts)
* [Request Lifecycle](#request-lifecycle)
* [How to Extend the ApiGateway Core](#how-to-extend-the-apigateway-core)
* [Exceptions Handling](#how-to-handle-exceptions)

## Architecture Overview

The `ApiGateway` component provides a modern, modular architecture for building APIs in ILIAS. It is built on the [Slim Framework](https://www.slimframework.com/), which handles the underlying request-response lifecycle.

**Key Principles:**

* **Single Entry Point:** All REST API requests enter through `/rest/index.php`, which boots the ILIAS environment and hands control to the `WebApp`.
* **Dependency Injection:** Services are wired together in `ApiGateway.php` using the ILIAS component system's dependency management features (`$define`, `$contribute`, `$pull`, etc.).
* **Layered Structure:** Concern separation into `Domain`, `Application`, and `Infrastructure` layers. The `Application` layer is further organized by lifecycle roles: **Bootstrap**, **Handlers**, and **Middleware**.
* **Extensibility:** The system is designed to be extended by other components, primarily through the contribution of `Route` and `Activity` objects.

## Core Components & Concepts

### Application Orchestration (`src/Application/`)

* **`WebApp.php`**: The primary orchestrator. It coordinates the bootstrapping process and executes the Slim application.
* **Bootstrap (`src/Application/Bootstrap/`)**: Responsible for wiring domain logic into the framework *before* request handling begins.
    * `MiddlewarePipeline.php`: Configures the global middleware stack in the correct LIFO order.
    * `RouteBootstrapper.php`: Maps domain `Route` objects into Slim route mappings.
    * `ErrorHandlersSetup.php`: Configures the Slim `ErrorMiddleware`.
* **Handlers (`src/Application/Handlers/`)**: Manages the final execution phases of a request.
    * `ResponseHandlers.php`: Orchestrates the successful execution of an API action and response formatting.
    * `ErrorHandlers.php`: The application-wide safety net for failed requests.

### Core Services

* **`ApiGateway.php`**: The central service provider for the component. It defines and instantiates all core services, acting as a dependency injection container.
* **`RestAppEntryPoint.php`**: The official ILIAS entry point. Its main job is to initialize the ILIAS environment and start the `WebApp`.
* **`Webservice/`**: The "View" or "Formatting" layer. Responsible for serializing data into protocol-specific structures (e.g., JSON).
* **`Routing/`**:
    * `Route.php`: Interface defining an API endpoint.
    * `RoutesRegistry.php`: A registry that collects all contributed `Route` objects.
* **`Activity/`**: Automatically converts `ILIAS\Component\Activities\Activity` objects from other components into API routes.
* **`Auth/`**: Handles token-based authentication and user context.
* **`Configuration/`**: Provides a unified API for accessing component settings.

## Request Lifecycle

1. An HTTP request arrives at `/rest/index.php`.
2. `RestAppEntryPoint` is invoked. It initializes the ILIAS environment and runs the `WebApp`.
3. `WebAppFactory` constructs the `WebApp` and its bootstrap dependencies.
4. The `WebApp` triggers the **Bootstrap** phase:
    * `MiddlewarePipeline` applies global middlewares.
    * `ErrorHandlersSetup` registers the error handling logic.
    * `RouteBootstrapper` registers all routes from the `RoutesRegistry`.
5. The Slim application matches the incoming request to a route.
6. Route-specific middlewares (e.g., `AuthenticationMiddleware`) are executed.
7. If successful, the `ResponseHandlers` is invoked, executes the domain `Action`, and delegates response formatting to the `Webservice`.
8. If an exception occurs, the `ErrorHandlers` catches it and delegates the error structure to the `Webservice`.

## API Documentation (Swagger UI)

The component provides interactive API documentation powered by Swagger UI.

### Updating the Specification
The OpenAPI JSON is not generated on-the-fly. If you add or change API routes, you must rebuild the specification artifact using the ILIAS setup command:

```bash
php cli/setup.php build
```
This command triggers the `BuildOpenAPISpecObjective` across all components, which for `ApiGateway` gathers all registered route information and serializes it.

### Updating the UI Template
The interactive documentation is served by `resources/rest/swagger.php`, which uses a simple HTML template within the PHP file. Since the UI layout is stable, updates to this file are **rarely needed**.

**When an update is required (e.g., new Swagger UI features):**
1.  **Change Version:** Update the `$swaggerUiVersion` variable at the top of the file.
2.  **Edit Template:** If the initialization logic changes, simply copy the new configuration from the official Swagger UI documentation and paste it into the `<script>` section of the `$html` variable. 

No external build tools are required for the UI; it uses CDN-hosted assets (JS/CSS) for easy maintenance.

## How to Extend the ApiGateway Core

* **Adding a New Core Route:**
    1. Create a new class that implements `ILIAS\ApiGateway\Routing\Route`. For simple cases, extend `ILIAS\ApiGateway\Application\Routes\ApiRoute`.
    2. Instantiate and contribute the new route class within `ApiGateway.php`.

* **Adding a New Global Middleware:**
    1. Create a class that implements `Psr\Http\Server\MiddlewareInterface`.
    2. Instantiate and contribute the middleware in `ApiGateway.php`.
    3. Update `MiddlewarePipeline::apply()` to include the new middleware in the stack.

* **Adding a New Configuration Option:**
    1. Add the new setting to the `Configuration/Domain/Enum/SystemSetting.php` enum.
    2. Update `Configuration/Infrastructure/ConfigurationService.php` to expose the new setting.
    3. Update `Configuration/Admin/ilApiGatewaySettings.php` to include the setting in persistence.

## How to Handle Exceptions

The component employs a centralized error handling strategy to ensure API responses are consistent and predictable.

### Core Principles

When an error occurs, throw a semantically meaningful exception.

* **Use Specific Exceptions:** Throw specific exceptions from `src/Application/Exceptions/`, such as `InvalidTokenException` (401) or `AuthorizationException` (403).
* **Exception Translation:** When catching exceptions from external libraries or legacy services, translate them into component-specific exceptions to maintain a consistent API contract.

### The Central Error Handlers

The `Application/Handlers/ErrorHandlers.php` serves as the centralized "safety net." It catches any unhandled `Throwable`, determines the correct HTTP status, and delegates serialization to the `Webservice`.
