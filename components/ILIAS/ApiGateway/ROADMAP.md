# ILIAS Webservices Roadmap

**Work in progress**

*Note: This is a living document that will be updated as the project progresses through each phase.*

## Overview

The new API uses a modular architecture centered around the `ApiGateway` component, which leverages the [Slim Framework](https://www.slimframework.com/) to handle the request lifecycle. An incoming API request comes through a single entry point, which boots the ILIAS environment and passes the request to the Slim application.

The request lifecycle uses the [Slim Framework](https://www.slimframework.com/). An incoming API request comes through a single entry point, which boots up the ILIAS environment and passes the request to the Slim application to handle.

This architecture separates concerns by having a generic `ApiGateway` component. For context on the previous design, which was centered around a more abstract 'Activities' concept, you can read the [previous concept document](https://github.com/jeph864/ILIAS/blob/11/rest/components/ILIAS/rest/README.md).

## Phases

- **Phase 1:** REST API: Concepts and Basic Objects \[[WIKI](https://docu.ilias.de/go/wiki/wpage_8205_1357)\]
- **Phase 2:** REST API: Authentication and Service Control \[[WIKI](https://docu.ilias.de/go/wiki/wpage_8803_1357)\]
- **Phase 3:** REST API: Middlewares, Documentation and Error Handling \[[WIKI](https://docu.ilias.de/go/wiki/wpage_8825_1357)\]
- **Phase 4:** Activity I/O Validation Schemas \[[WIKI](https://docu.ilias.de/go/wiki/wpage_8860_1357)\]
- **Phase 5:** SOAP Webservice Integration

## Known Issues

- ...

## Backlog

- **Implementation Note:** Use a `ClientIdentifier` (pseudonymized hash of IP + UserAgent) to track clients consistently across logging and rate limiting without storing PII.
  ```php
  // Example logic for ClientIdentifier:
  $fingerprint = hash_hmac(
      algo: $authConfig->getHashAlgo(),
      data: $ip . '|' . $userAgent,
      key: $authConfig->getSecretKey()
  );
  ```
- **Configuration:** Read environment variables and prioritize over dashboard to facilitate CI. (optional)
- **Decouple from Slim Framework:** Move framework-specific code to its own area (`Http/Infrastructure/Slim`). This makes it easier to update the framework or swap it out entirely without breaking our core logic.
  - Create a generic base `HttpException`.
  - Use "adapters" for the `WebApp` and `RouteBootstrapper` so they don't depend directly on Slim.
- ...