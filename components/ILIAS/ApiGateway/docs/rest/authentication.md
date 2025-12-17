# REST Webservice Authentication

The REST API uses bearer tokens for authentication. API requests that require authentication must include an `Authorization` header with a valid access token.

```
Authorization: Bearer <your_access_token>
```

The following endpoints are available to obtain and refresh tokens.

## 1. Requesting an Authentication Token

This endpoint authenticates a user with their username and password and returns a new set of access and refresh tokens.

* **Endpoint:** `POST /rest/auth/login`
* **Request Body:** A JSON object containing the user's `username` and `password`.

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

## 2. Refreshing an Authentication Token

When an access token expires, a new one can be obtained by sending the `refresh_token` to this endpoint. This will issue a new token set and invalidate the old refresh token.

* **Endpoint:** `POST /rest/auth/refresh`
* **Request Body:** A JSON object containing the `refresh_token`.

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
