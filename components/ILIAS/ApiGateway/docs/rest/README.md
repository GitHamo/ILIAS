# REST API Documentation

*Note: This is a living document that will be updated as the project progresses through each phase.*

This document outlines the **REST API implementation** for the ApiGateway component. It covers both usage for API consumers and the internal architecture for maintainers.

## Table of Contents

- [Enable the API](#enable-the-api)
- [Authentication](#authentication)
- [Standard Response Format](#standard-response-format)
- [Headers](#headers)
- [Routing](routing.md)
- [General Configuration](../configuration.md)
- [Maintainer Guidelines](../maintenance.md)

### Enable the API

To enable the REST API, you must navigate to the ILIAS administration dashboard:
`Administration > System settings and maintenance > Webservices`

Under the **REST Settings** tab, check the **Active** checkbox and save the changes.

> **Recommendation:** For security reasons, it is strongly recommended to configure a strong and unique secret key before activating the service. Avoid using empty or weak secrets. See [**General Configuration**](../configuration.md) for details.

### Authentication

The REST API uses **Bearer Tokens (JWT)** for authentication. Any request to a protected endpoint must include an `Authorization` header.

```
Authorization: Bearer <your_access_token>
```

For detailed information on how to obtain and refresh tokens, please refer to the [**REST Webservice Authentication documentation**](authentication.md).

### Standard Response Format

The API returns responses in a standardized JSON format.

#### Successful Response

Successful requests will have `success` as `true` and the result of the operation in the `data` field.

```json
{
    "success": true,
    "data": {
        "user_id": 1337,
        "username": "root"
    }
}
```

#### Error Response

Failed requests will have `success` as `false` and include an `error` message.

```json
{
    "success": false,
    "error": "You are not allowed to perform this activity."
}
```

### Request Parameters

The API Gateway automatically normalizes incoming request parameters from both the URL query string and the JSON request body into a single, consolidated array of parameters, which is then made available to route handlers.

**Precedence Rule:** If a parameter key exists in both the query string and the request body, the value from the request body takes precedence (overrides the query string value).

In the case of validation failures (HTTP 422), a detailed `errors` object is provided mapping field names to their respective validation messages.

```json
{
    "success": false,
    "error": "The given data was invalid.",
    "errors": {
        "username": "Min length 3 is required.",
        "age": "Must be 18+."
    }
}
```

If both the global ILIAS logging is enabled (by setting `enabled = "1"` under `[log]` in `ilias.ini.php`) and `DEVMODE` is active (set to `"1"` in `client.ini.php`), a `stack` trace will also be included in the payload.

**Example Error Response (with Stack Trace)**

```json
{
    "success": false,
    "error": "Something went wrong.",
    "stack": [
        {
            "file": "/var/www/html/ilias/components/ILIAS/ApiGateway/src/Some/Class.php",
            "line": 123,
            "function": "someFunction",
            "class": "ILIAS\ApiGateway\Some\Class",
            "type": "->"
        },
        {
            "file": "/var/www/html/ilias/components/ILIAS/ApiGateway/src/Application/WebApp.php",
            "line": 456,
            "function": "handleRequest",
            "class": "ILIAS\ApiGateway\Application\WebApp",
            "type": "->"
        }
    ]
}
```

### Headers

The REST API uses standard HTTP headers for content negotiation and authentication.

#### Request Headers

- **`Accept`**: Should be `application/json` (or compatible). The API supports robust content negotiation including wildcards (`*/*`, `application/*`), quality factors (`q=0.5`), multiple types, and case-insensitivity. If an incompatible type is requested, a `406 Not Acceptable` error is returned.
- **`Content-Type`**: Required as `application/json` for requests containing a JSON body (e.g., `POST`, `PUT`, `PATCH`). If the payload is missing or the JSON is malformed, a `400 Bad Request` error is returned.
- **`Authorization`**: Required for protected endpoints as `Bearer <your_access_token>`.

#### Response Headers

- **`Content-Type`**: Usually `application/json`. Returns `text/plain` if the API service is globally disabled.
- **`Content-Length`**: Indicates the size of the response payload in bytes. *This is helpful for clients to verify data integrity and efficiently manage memory buffers.*

### Postman Collection

A standardized [Postman Collection](ILIAS-postman_collection.json) is available for testing the REST API.

- **Automated Auth**: Integrated scripts automatically capture and rotate Bearer tokens after login/refresh.
- **Environment Variables**: Uses `{{base_url}}`, `{{vault:ilias_username}}`, and `{{vault:ilias_password}}` variables for easy switching between environments.
