# Responses

![Language](https://img.shields.io/badge/language-English-blue)

Controllers built on `oihana/php-controllers` produce their HTTP output through a small stack of focused traits. Together they let you serialize a payload to **JSON** or **CBOR**, set the right `Content-Type` and HTTP **status code**, and — when you need a consistent contract for clients — wrap the payload in a standardized **API envelope** (`status`, `code`, `message`, pagination metadata and `result`).

The traits compose cleanly:

- [`JsonTrait`](#jsontrait) — serialize data into a PSR-7 JSON response.
- [`CborTrait`](#cbortrait) — serialize data into a binary CBOR response.
- [`StatusTrait`](#statustrait) — the high-level helpers (`response()`, `status()`, `fail()`, `success()`) that negotiate the format from the client `Accept` header and build the envelope. It pulls in `JsonTrait`, `CborTrait`, `BaseUrlTrait` and `LoggerTrait`.
- [`ApiTrait`](#apitrait) — holds the controller's `api` settings array, resolved from the DI container.

All response helpers operate on a PSR-7 `ResponseInterface` and return a **new** response instance (PSR-7 messages are immutable), so always use the returned value.

## JsonTrait

`JsonTrait` manages the JSON encoding flags and produces a JSON PSR-7 response. Serialization goes through `oihana\reflect\utils\JsonSerializer`, which honours both the integer encode flags and the structural `jsonSerializeOptions` (for example `ArrayOption::REDUCE`).

### Properties

| Property | Type | Default | Role |
|----------|------|---------|------|
| `$jsonOptions` | `int` | `JsonParam::JSON_NONE` | `json_encode` bit flags (e.g. `JSON_PRETTY_PRINT`). |
| `$jsonSerializeOptions` | `array` | `[ ArrayOption::REDUCE => true ]` | Structural options passed to `JsonSerializer`. |

### `initializeJsonOptions( array $init = [], ?ContainerInterface $container = null ): static`

Resolves the encode flags and the serializer options. Lookup order:

1. `$init[ ControllerParam::JSON_OPTIONS ]` for the flags, and `$init[ ControllerParam::JSON_SERIALIZE_OPTIONS ]` for the serializer options;
2. otherwise the matching key in the PSR-11 `$container`, when present.

Invalid encode flags (those not accepted by `isValidJsonEncodeFlags()`) fall back to `JsonParam::JSON_NONE`. Returns `$this` for chaining.

### `jsonResponse( Response $response, mixed $data = null, int $status = HttpStatusCode::OK ): Response`

Encodes `$data`, writes it to the response body, sets the status and the `Content-Type: application/json` header.

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\JsonTrait;
use oihana\enums\http\HttpStatusCode;

class JsonController
{
    use JsonTrait;

    public function show( ServerRequestInterface $request, ResponseInterface $response ): ResponseInterface
    {
        // Pretty-printed, unescaped slashes
        $this->initializeJsonOptions
        ([
            ControllerParam::JSON_OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ]);

        return $this->jsonResponse( $response, [ 'foo' => 'bar' ], HttpStatusCode::OK );
    }
}
```

The body then contains `{"foo":"bar"}` (pretty-printed) and the response carries `Content-Type: application/json` with HTTP `200`.

## CborTrait

`CborTrait` mirrors `JsonTrait` but emits binary [CBOR](https://cbor.io/) through `oihana\reflect\utils\CborSerializer`. CBOR is a compact binary format well suited to high-throughput or bandwidth-sensitive APIs.

### Property

| Property | Type | Default | Role |
|----------|------|---------|------|
| `$cborSerializeOptions` | `array` | `[ ArrayOption::REDUCE => true ]` | Structural options passed to `CborSerializer`. |

### `initializeCborOptions( array $init = [], ?ContainerInterface $container = null ): static`

Resolves `$cborSerializeOptions` from `$init[ ControllerParam::CBOR_SERIALIZE_OPTIONS ]` first, then from the container key when the supplied value is empty. A non-array value keeps the current default. Returns `$this`.

### `cborResponse( Response $response, mixed $data = null, int $status = HttpStatusCode::OK ): Response`

Encodes `$data` to CBOR, replaces the response body with a fresh stream containing the bytes, and sets `Content-Type: application/cbor` together with the exact `Content-Length`. Any pending output buffer is flushed with `ob_clean()` first, so the binary payload is never polluted by stray output.

```php
use Psr\Http\Message\ResponseInterface;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\CborTrait;
use oihana\core\options\ArrayOption;
use oihana\enums\http\HttpStatusCode;

class CborController
{
    use CborTrait;

    public function export( ResponseInterface $response ): ResponseInterface
    {
        $this->initializeCborOptions
        ([
            ControllerParam::CBOR_SERIALIZE_OPTIONS => [ ArrayOption::REDUCE => false ]
        ]);

        return $this->cborResponse( $response, [ 'foo' => 'bar' ], HttpStatusCode::CREATED );
    }
}
```

The response carries `Content-Type: application/cbor`, a correct `Content-Length`, and HTTP `201`.

## StatusTrait

`StatusTrait` is the high-level entry point. It composes `JsonTrait`, `CborTrait`, `BaseUrlTrait` and `LoggerTrait`, and exposes the helpers a controller actually calls. Every helper negotiates the output format: it inspects the `Accept` header (or an explicit `$accept` argument) and dispatches to `cborResponse()` for `application/cbor` / `application/cbor-seq`, falling back to `jsonResponse()` otherwise.

### `response( Response $response, mixed $data = null, int $status = 200, ?string $accept = null ): Response`

The format-negotiating primitive. Use it when you just want to return raw `$data` in whatever format the client requested.

```php
return $this->response( $response, $data, 200, $request->getHeaderLine( 'Accept' ) );
```

### `status( ?Request $request, ?Response $response, mixed $message = '', int|string|null $code = 200, ?array $options = null, ?string $accept = null ): ?Response`

Outputs a generic status envelope. The body is built from:

- `Output::STATUS` — the status *type* derived from the code (`HttpStatusCode::getType()`);
- `Output::CODE` — the integer code;
- `Output::MESSAGE` — the message;
- any extra keys from `$options`, merged in.

Returns `null` when `$response` is `null`.

```php
return $this->status( $request, $response, 'bad request', 400 );
```

Produces (as JSON):

```json
{ "status": "error", "code": 400, "message": "bad request" }
```

### `fail( ?Request $request, ?Response $response, string|int|null $code = 400, ?string $details = null, array $options = [], ?string $accept = null ): ?Response`

A specialization of `status()` for errors. It:

- validates the code against `HttpStatusCode` (unknown codes fall back to `HttpStatusCode::DEFAULT`);
- derives the human-readable `$message` from `HttpStatusCode::getDescription( $code )`;
- when `$details` is a non-empty string, adds it under `Output::DETAILS`;
- logs the error (class, code, message and optional details) when `$this->loggable` is `true`.

```php
return $this->fail
(
    $request,
    $response,
    406,
    'fields validation failed',
    [
        'firstName' => 'firstName is required',
        'lastName'  => 'lastName must be a string'
    ]
);
```

Produces (as JSON):

```json
{
    "status": "error",
    "code": 406,
    "message": "Not Acceptable",
    "firstName": "firstName is required",
    "lastName": "lastName must be a string",
    "details": "fields validation failed"
}
```

### `success( ?Request $request, ?Response $response, mixed $data = null, ?array $init = null, ?string $accept = null ): mixed`

Wraps a successful payload in the API envelope. The envelope always starts with `status: "success"` and ends with the payload under `Output::RESULT`. The optional `$init` array enriches it with metadata — only values of the right type and range are added:

| `$init` key | Type | Envelope key |
|-------------|------|--------------|
| `Output::COUNT` | `int >= 0` | `count` |
| `Output::LIMIT` | `int` | `limit` |
| `Output::OFFSET` | `int` | `offset` |
| `Output::POSITION` | `int >= 0` | `position` |
| `Output::TOTAL` | `int >= 0` | `total` |
| `Output::URL` | `string` | `url` (defaults to `getCurrentPath()`) |
| `Output::OWNER` | `array`/`object` | `owner` |
| `Output::PARAMS` | `array` | feeds `getCurrentPath()` |
| `Output::OPTIONS` | `array` | merged into the envelope |
| `Output::STATUS` | `int` | HTTP status code (default `200`) |

When `$response` is `null`, `success()` returns `$data` unchanged — handy when a sub-controller wants the raw value rather than an HTTP response.

```php
use oihana\enums\Output;

return $this->success
(
    $request,
    $response,
    $data,
    [
        Output::COUNT  => count( $data ),
        Output::PARAMS => $request->getQueryParams()
    ]
);
```

Produces (as JSON):

```json
{
    "status": "success",
    "url": "/current/path",
    "count": 12,
    "result": [ /* ...$data... */ ]
}
```

### `successWithNewBody( ... )` and `withFreshBody( ?Response $response ): ?Response`

`withFreshBody()` returns the same response with an **empty** body stream, discarding whatever an upstream actor (a sub-controller or middleware) may already have written. `successWithNewBody()` is `success()` applied on top of a fresh body: use it when a previous call already wrote into the shared PSR-7 body and a plain `success()` would concatenate two envelopes — invalid JSON for strict parsers.

```php
// Discard an upstream write, then emit a single clean error envelope
return $this->fail( $request, $this->withFreshBody( $response ), 502, 'zitadel_sync_failed' );
```

## ApiTrait

`ApiTrait` keeps a controller's API-level settings in a single `protected array $api` property — typically values like the API name, version or public base configuration that you want consistent across controllers.

### `initializeApi( array $init = [], ?ContainerInterface $container = null ): static`

Resolves the `api` settings. The PSR-11 container takes precedence: if `$container` has `ControllerParam::API`, its value wins over `$init[ ControllerParam::API ]`. A non-array result falls back to an empty array. Returns `$this`.

```php
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ApiTrait;

class ApiController
{
    use ApiTrait;

    public function boot(): void
    {
        $this->initializeApi
        ([
            ControllerParam::API => [ 'name' => 'my-api', 'version' => 2 ]
        ]);
    }
}
```

When both an `$init` value and a container definition are present, the container value is used — letting deployment configuration override controller defaults.

## See also

- [Models](models.md) — wiring controllers to data models.
- [File responses](files.md) — download, streaming, HTTP range, ETag / 304, encryption, images.
- [Documentation index](README.md) — back to the table of contents.
