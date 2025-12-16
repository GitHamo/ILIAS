# ILIAS Webservices: REST API

*Note: This is a living document that will be updated as the project progresses through each phase.*

## Index

- [Overview](#overview)
- [Components](#components)
- [Getting Started](#getting-started)
- [Authentication](#authentication)
- [Key Architectural Concepts](#key-architectural-concepts)
- [Future Work](#future-work)

## Overview

The new API uses a modular architecture centered around the `ApiGateway` component, which leverages the [Slim Framework](https://www.slimframework.com/) to handle the request lifecycle. An incoming API request comes through a single entry point, which boots the ILIAS environment and passes the request to the Slim application.

The request lifecycle uses the [Slim Framework](https://www.slimframework.com/). An incoming API request comes through a single entry point, which boots up the ILIAS environment and passes the request to the Slim application to handle.

This architecture separates concerns by having a generic `ApiGateway` component. For context on the previous design, which was centered around a more abstract 'Activities' concept, you can read the [previous concept document](https://github.com/jeph864/ILIAS/blob/11/rest/components/ILIAS/rest/README.md).

## Components

### 1. ApiGateway (`components/ILIAS/ApiGateway`)

This is a generic, reusable component that provides the tools to build different APIs (e.g., REST, SOAP). It acts as a wrapper and factory for the Slim application, standardizing how routes are registered and how requests and responses are handled. It integrates several core libraries via Composer, including `slim/slim`, `slim/psr7`, and `psr/log`. While it is protocol-agnostic, the primary focus of this phase is REST, with the public `/rest` endpoint serving as the entry point for all REST API routes.

## Getting Started

After checking out the branch, the first step is to install the new dependencies:

```bash
composer install
```

This will download the required libraries (like Slim) and trigger the necessary ILIAS build scripts.

- 🟡 *Admin dashboard control is not compatible with ILIAS 12+ yet so next is only available in ILIAS 11.*

- 🟡🟡 *To enable REST Webservice for ILIAS 12+, Debug mode has to be enabled. Set `DEVMODE` to "1" in your `client.ini.php` [more info](https://docu.ilias.de/ilias.php?baseClass=illmpresentationgui&ref_id=367&obj_id=42329&srcstring=1)*

To enable the REST API, the `rest_ws_enabled` setting must be activated in the ILIAS administration. The settings page can currently be accessed directly with this URL (replace `<localhost_url>` with your ILIAS instance URL):

```
<localhost_url>/ilias.php?baseClass=iladministrationgui&cmdNode=48:qn:13u&cmdClass=ilwebservicessettingsgui&cmd=showWebservicesSettings&ref_id=9
```

*Note: In a future phase, this settings page will be integrated into the ILIAS UI under `Administration > System settings and maintenance > Webservice`.*

Once enabled, you can test the setup by accessing the `/ping` endpoint:

**Example request:**
`GET /rest/ping`

```bash
curl --location 'http://<ILIAS_BASE_URL>/rest/ping' \
--header 'Accept: application/json' \
```

**Expected response:**

```json
{
    "success": true,
    "data": "Pong!"
}
```

## Authentication

The REST API uses bearer tokens for authentication. API requests that require authentication must include an `Authorization` header with a valid access token.

```
Authorization: Bearer <your_access_token>
```

The following endpoints are available to obtain and refresh tokens.

### 1. Requesting an Authentication Token

This endpoint authenticates a user with their username and password and returns a new set of access and refresh tokens.

*   **Endpoint:** `POST /rest/auth/login`
*   **Request Body:** A JSON object containing the user's `username` and `password`.

**Example Request:**

```bash
curl --location 'http://<ILIAS_BASE_URL>/rest/auth/login' \
--header 'Content-Type: application/json' \
--data '{
    "username": "your_username",
    "password": "your_password"
}'
```

**Example Successful Response:**

```json
{
    "success": true,
    "data": {
        "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "refresh_token": "def5020023e63064d77170889...",
        "expires_in": 1701384000
    }
}
```

### 2. Refreshing an Authentication Token

When an access token expires, a new one can be obtained by sending the `refresh_token` to this endpoint. This will issue a new token set and invalidate the old refresh token.

*   **Endpoint:** `POST /rest/auth/refresh`
*   **Request Body:** A JSON object containing the `refresh_token`.

**Example Request:**

```bash
curl --location 'http://<ILIAS_BASE_URL>/rest/auth/refresh' \
--header 'Content-Type: application/json' \
--data '{
    "refresh_token": "<your_refresh_token>"
}'
```

**Example Successful Response:**

```json
{
    "success": true,
    "data": {
        "access_token": "abc1234567890...",
        "refresh_token": "ghi0987654321...",
        "expires_in": 1701387600
    }
}
```

## Key Architectural Concepts

- **WebApp**: The main application orchestrator that configures and runs the Slim application. It registers routes, adds middleware, and manages the overall request-response flow.
- **Route**: A definition of an API endpoint, including its path, HTTP methods, and handler. Routes can be created manually or generated automatically from `Activity` components. In the future, the `Route` will also provide the schema for generating an OpenAPI specification.
- **Webservice**: An interface responsible for formatting the final output. The `RestWebservice` implementation, for example, serializes data and exceptions into a standardized JSON structure.
- **Payload**: A data transfer object used for passing data between route handlers and the `Webservice` formatter.
- **Activity**: A component representing a command or query that can be exposed as an API endpoint. The `ApiGateway` includes an autoloader to discover and register routes from these activities.

## Future Work

This initial phase lays the groundwork. Future work will add key features, including:

- Authentication and authorization middleware.
- I/O validation schemas for request and response data.
- Enhanced error handling and reporting.
- A discovery mechanism to automatically find and register all available `Activities` without manual intervention.
