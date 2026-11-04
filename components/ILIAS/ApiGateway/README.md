# ILIAS Webservices: ApiGateway

*Note: This is a living document that will be updated as the project progresses through each phase.*

This document outlines the **ApiGateway component**, a foundational service for creating modern, modular APIs within ILIAS. It replaces the previous webservice infrastructure with a flexible system built on the [Slim Framework](https://www.slimframework.com/), designed to be the standard for future API development. This guide covers both usage for API consumers and the internal architecture for maintainers.

## Table of Contents

- [Getting Started](#getting-started)
- [Authentication](#authentication)
- [Configuration](#configuration)
- [Creating New API Endpoints](#creating-new-api-endpoints)
  - [Developing Gateway-Compliant Activities](docs/activities.md)
- [REST API Details](docs/rest/README.md)
- [Logging](#logging)
- [Middlewares](#middlewares)
- [Validation](#validation)
- [OpenAPI Specification](#openapi-specification)
- [Maintainer Guidelines](docs/maintenance.md)
- [Testing during development](docs/development.md)
- [Future Work](ROADMAP.md)

### Getting Started

Before you can use the API, you need to set it up and ensure it's running correctly.

#### Installation and Updates

- **Fresh Installation:** If you are installing ILIAS for the first time with this component, no special action is needed. The setup process will handle the component automatically.
- **Updating an Existing ILIAS Instance:** If you are adding this component to an existing ILIAS installation, you must run the update command to apply the necessary database changes:

    ```bash
    php cli/setup.php update --yes
    ```

- **After a Failed Update:** If a previous update failed, you may need to reset the failed steps before running the update command again:

    ```bash
    php cli/setup.php achieve database.resetFailedSteps --yes
    php cli/setup.php update --yes
    ```

1. **Install Dependencies:** From the ILIAS root directory, run `composer install`. This will download the necessary libraries (like the Slim Framework) for the API Gateway.

2. **Enable the API:**
    To enable the webservice, you must navigate to the ILIAS administration dashboard:
    `Administration > System settings and maintenance > Webservices`

    Under the **REST Settings** tab (or the tab for your desired protocol), check the **Active** checkbox and save the changes.

    *For more details, see the protocol-specific documentation:*
    - **[Enabling REST API](docs/rest/README.md#enable-the-api)**

    > **Recommendation:** For security reasons, it is strongly recommended to configure a strong and unique secret key before activating any webservice. Avoid using empty or weak secrets.

    *Note: For development purposes, setting `DEVMODE` to `"1"` in `client.ini.php` enables detailed error responses and logging details, but it does **not** activate the webservice itself.*

3. **Test the Setup:**
    You can verify that the API is running by sending a request to the `/ping` endpoint. Here is an example using the REST protocol:

    **Example Request (REST):**

    ```bash
    curl --location 'http://<ILIAS_BASE_URL>/rest/ping' \
    --header 'Accept: application/json'
    ```

    **Expected Response:**

    ```json
    {
        "success": true,
        "data": "pong"
    }
    ```

#### Postman Collection

For a more interactive testing experience, you can import our pre-configured [Postman Collection](docs/rest/ILIAS-postman_collection.json). It includes automated scripts for token management and examples for all core routes.

### Authentication

The ApiGateway supports various authentication methods depending on the protocol used.

For the REST API, it uses **Bearer Tokens (JWT)**. For detailed information on how to obtain and refresh tokens, please refer to the [**REST Webservice Authentication documentation**](docs/rest/authentication.md).

### Configuration

The API Gateway's behavior, particularly authentication, can be configured from the administration dashboard. These settings include the secret key, encryption and hashing algorithms, and token expiry times.

For a detailed explanation of each setting and the system impact of changing them, please refer to the [**Configuration documentation**](docs/configuration.md).

### Creating New API Endpoints

The API Gateway is extensible, allowing components to expose their functionality. There are three primary ways to create a new route. All routes are registered by contributing them in the component's `Component.php` file.

| Approach | When to use? | Parent Class |
| :--- | :--- | :--- |
| **`ApiRoute`** | Simple endpoints with logic that fits in a single function. | `ILIAS\ApiGateway\Routing\Route` |
| **Custom `Route` Class** | Complex endpoints that require their own dependencies or have detailed setup logic. | `ILIAS\ApiGateway\Routing\Route` |
| **`Activity`** | For routes tied to core domain logic. The path and HTTP method are generated **automatically**. | `ILIAS\Component\Activities\Activity` |

*For REST-specific routing details, see the [**REST Webservice Routing documentation**](docs/rest/routing.md).*

### Logging

The API Gateway leverages ILIAS's standard error reporting mechanisms.

Requests are tracked using correlation IDs that are included in both request headers and response headers. Clients may provide their own correlation ID by setting the `X-Correlation-ID` header to a valid UUID. The API Gateway will return the same value in the response headers, making it easier to trace requests across systems and logs.

#### Example request

```
GET /api/v1/users HTTP/1.1
Host: example.com
Authorization: Bearer <token>
X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000
```

#### Example response

```
HTTP/1.1 200 OK
Content-Type: application/json
X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000
```

> Note: If the provided `X-Correlation-ID` is not a valid UUID, the request will be rejected with a `400 Bad Request` response and an error indicating that the correlation ID must be a valid UUID.

**To enable logging globally in ILIAS:**
Set `enabled = "1"` under the `[log]` section in your `ilias.ini.php` file.

Once enabled, all API-related errors are logged in the usual ILIAS logs directory within the default log file.

For development and debugging purposes, activating `DEVMODE` (setting it to `"1"`) in `/data/<client_id>/client.ini.php` offers additional insights:

- Each incoming API request will be logged.
- Errors will include a full stack trace in the response payload (if supported by the protocol). **Caution:** This feature should **never** be enabled on production environments due to potential security risks and increased response sizes.
- JSON responses from the REST webservice will be pretty-printed.

*Note: Detailed logging is only enabled when both the global ILIAS logging is active and `DEVMODE` is enabled.*

### Middlewares

The API Gateway utilizes a configurable middleware pipeline to process incoming requests and outgoing responses. These middlewares handle various cross-cutting concerns suchs as authentication, security, logging, and rate limiting.

For a comprehensive overview of available middlewares, their configuration, application, and processing order, refer to the [**Middleware Documentation**](docs/middlewares.md).

### Validation

*(Documentation for input and output validation schemas will be available in a future update.)*

### OpenAPI Specification

The API Gateway provides interactive documentation for all available endpoints, powered by [Swagger UI](https://swagger.io/) (v5.10.0). It is generated dynamically from the API's endpoint mappings, providing an [OpenAPI Specification](https://www.openapis.org/) (v3.0.3) schema directly to Swagger UI.

To update this schema, run one of the following commands from the ILIAS root directory:

```bash
php cli/setup.php build
```

Alternatively, the same process can be triggered by running:

```bash
composer du
```

The interactive documentation is accessible at `<ILIAS_BASE_URL>/swagger.php`. By default, only administrators have access to this page. Public access for guests can be enabled through the ILIAS administration interface under `Administration > System Settings and maintenance > Webservices`, by checking the 'Activate Documentation' option within the REST settings.
