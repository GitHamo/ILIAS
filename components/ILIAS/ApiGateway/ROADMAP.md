# ILIAS Webservices Roadmap

**Work in progress**

*Note: This is a living document that will be updated as the project progresses through each phase.*

## Overview

The new API uses a modular architecture centered around the `ApiGateway` component, which leverages the [Slim Framework](https://www.slimframework.com/) to handle the request lifecycle. An incoming API request comes through a single entry point, which boots the ILIAS environment and passes the request to the Slim application.

The request lifecycle uses the [Slim Framework](https://www.slimframework.com/). An incoming API request comes through a single entry point, which boots up the ILIAS environment and passes the request to the Slim application to handle.

This architecture separates concerns by having a generic `ApiGateway` component. For context on the previous design, which was centered around a more abstract 'Activities' concept, you can read the [previous concept document](https://github.com/jeph864/ILIAS/blob/11/rest/components/ILIAS/rest/README.md).

## Phases

- **Phase 1:** REST API: Concepts and Basic Objects \[[WIKI](https://docu.ilias.de/ilias.php?baseClass=ilwikihandlergui&cmdNode=16g:rq&cmdClass=ilobjwikigui&cmd=viewPage&ref_id=1357&wpg_id=8205)\]
- **Phase 2:** REST API: Authentication and Service Control \[[WIKI](https://docu.ilias.de/ilias.php?baseClass=ilwikihandlergui&cmdNode=16g:rq:16j&cmdClass=ilWikiPageGUI&cmd=preview&ref_id=1357&wpg_id=8803)\]
- **Phase 3:** Swagger & Middlewares
- **Phase 4:** Activity I/O Validation Schemas
- **Phase 5:** SOAP Webservice Integration

## Future work will add key features, including

- I/O validation schemas for request and response data.
- Enhanced error handling and reporting.

### Additional Features (optional)

- **Configuration:** Read enviormaent variables and prioritize over dashboard to faciliate CI.
