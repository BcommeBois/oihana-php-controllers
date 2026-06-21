# Helpers

Twenty free functions registered through composer `autoload.files`, all under the
`oihana\controllers\helpers` namespace. They are global functions, not class
methods: import each one with a `use function` statement, e.g.
`use function oihana\controllers\helpers\getParamInt;`. They cover the everyday
glue of an HTTP controller — extracting typed request parameters (query, body or
both, with dot-notation support), negotiating multilingual values, and producing
correct file responses (content headers, ETag validators, `If-None-Match` and
`Range` parsing).

```php
use function oihana\controllers\helpers\getParamInt;
use function oihana\controllers\helpers\getParamString;
```

Because they ship as plain functions, you can call them anywhere — inside a
`Controller`, a middleware, or a standalone service — without extending a base
class.

## Request parameters

These helpers read values from a PSR-7 `ServerRequestInterface`. They all support
**dot notation** for nested keys (e.g. `'user.profile.email'`) and an
`HttpParamStrategy` that selects the source: `QUERY`, `BODY` or `BOTH` (the
default, query string first then body). When a parameter is missing they return
the relevant default, unless `$throwable` is `true`, in which case
`DI\NotFoundException` is thrown.

| Signature | Returns | Role |
|-----------|---------|------|
| `getParam(?Request $request, string $name, array $default = [], ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `mixed` | Base accessor: returns the raw value found, `$default[$name]`, or `null`. |
| `getParamInt(?Request $request, string $name, array $args = [], ?int $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?int` | Casts the value to `int` when numeric, otherwise `$defaultValue`. |
| `getParamFloat(?Request $request, string $name, array $args = [], ?float $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?float` | Casts the value to `float` when numeric, otherwise `$defaultValue`. |
| `getParamBool(?Request $request, string $name, array $args = [], ?bool $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?bool` | Interprets `true/false/1/0/yes/no/on/off` via `FILTER_VALIDATE_BOOLEAN`. |
| `getParamString(?Request $request, string $name, array $args = [], ?string $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?string` | Casts the value to `string` when set, otherwise `$defaultValue`. |
| `getParamArray(?Request $request, string $name, array $args = [], ?array $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?array` | Returns the value only when it is an `array`, otherwise `$defaultValue`. |
| `getParamI18n(?Request $request, string $name, array $default = [], ?array $languages = null, ?callable $sanitize = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?array` | Reads a translations map and filters it through `filterLanguages()`. |
| `getParamNumberRange(?Request $request, string $name, int\|float $min, int\|float $max, null\|int\|float $defaultValue = null, array $args = [], ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `int\|float\|null` | Clamps a numeric value to `[$min, $max]`, otherwise `$defaultValue`. |
| `getParamIntRange(?Request $request, string $name, int $min, int $max, ?int $defaultValue = null, array $args = [], ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?int` | `getParamNumberRange()` wrapper that forces an `int` return. |
| `getParamFloatRange(?Request $request, string $name, float $min, float $max, ?float $defaultValue = null, array $args = [], ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?float` | `getParamNumberRange()` wrapper that forces a `float` return. |
| `getQueryParam(?Request $request, string $name)` | `mixed` | Reads a single value from the query string only; `null` if absent. |
| `getBodyParam(?Request $request, string $name)` | `mixed` | Reads a single value from the parsed body only; `null` if absent. |
| `getBodyParams(?Request $request, array $names = [])` | `array` | Extracts several body keys and rebuilds them, preserving dot-notation nesting. |

## HTTP / file responses

Building blocks for serving files efficiently: decorating a response with the
download headers, computing and comparing `ETag` validators, and parsing a
`Range` header into a single byte interval.

| Signature | Returns | Role |
|-----------|---------|------|
| `applyContentHeaders(Response $response, string $file, ?string $contentType = null, array $options = [], bool $defaultOn = true)` | `Response` | Adds `Content-Type` / `Content-Length` / `Content-Disposition`, toggled by `FileResponseOption` switches. |
| `computeETag(string $file, bool $weak = false, bool $hashContent = false)` | `string` | Builds a quoted `ETag` from file metadata (`mtime`-`size`) or, optionally, from `md5_file()`. |
| `etagMatches(string $header, string $etag)` | `bool` | RFC 7232 `If-None-Match` test (`*`, comma list, weak comparison) — `true` ⇒ answer `304`. |
| `parseRangeHeader(string $rangeHeader, int $fileSize)` | `array{0:int,1:int}\|false\|null` | `[start, end]` ⇒ `206`, `false` ⇒ `416`, `null` ⇒ full `200`. Single ranges only. |

## Languages

Read and filter multilingual maps (e.g. `['fr' => 'Bonjour', 'en' => 'Hello']`).

| Signature | Returns | Role |
|-----------|---------|------|
| `translate(array\|object\|null $fields, string\|null $lang = null, string\|null $default = null)` | `mixed` | Returns the value for `$lang`, the `$default` language fallback, all fields when `$lang` is `null`, or `null`. |
| `filterLanguages(mixed $fields, ?array $languages = null, ?callable $sanitize = null)` | `?array` | Keeps only `string`/`null` values for the listed languages, with an optional per-value sanitize callback; `null` on invalid/empty input. |

## Controllers

| Signature | Returns | Role |
|-----------|---------|------|
| `getController(array\|string\|null\|Controller $definition = null, ?ContainerInterface $container = null, ?Controller $default = null)` | `?Controller` | Resolves a `Controller` from an instance, an array carrying `ControllerParam::CONTROLLER`, or a PSR-11 container id; falls back to `$default`. |

## Examples

Reading typed parameters inside a controller action:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use oihana\enums\http\HttpParamStrategy;

use function oihana\controllers\helpers\getParamInt;
use function oihana\controllers\helpers\getParamBool;
use function oihana\controllers\helpers\getParamIntRange;

function index( ServerRequestInterface $request, ResponseInterface $response ): ResponseInterface
{
    // ?page=3&active=yes&limit=500
    $page   = getParamInt( $request, 'page', [], 1 );                 // 3
    $active = getParamBool( $request, 'active', [], false );          // true
    $limit  = getParamIntRange( $request, 'limit', 1, 100, 20 );      // 100 (clamped)

    // Body-only nested value: { "filter": { "status": "open" } }
    $status = getParamInt( $request, 'filter.status', [], 0, HttpParamStrategy::BODY );

    return $response;
}
```

Serving a file with conditional `ETag` handling and range support:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use oihana\enums\http\HttpHeader;

use function oihana\controllers\helpers\applyContentHeaders;
use function oihana\controllers\helpers\computeETag;
use function oihana\controllers\helpers\etagMatches;
use function oihana\controllers\helpers\parseRangeHeader;

function download( ServerRequestInterface $request, ResponseInterface $response, string $file ): ResponseInterface
{
    $etag = computeETag( $file ) ;

    // Short-circuit with 304 when the client cache is still fresh.
    if ( etagMatches( $request->getHeaderLine( HttpHeader::IF_NONE_MATCH ), $etag ) )
    {
        return $response->withStatus( 304 )->withHeader( HttpHeader::ETAG, $etag ) ;
    }

    $response = applyContentHeaders( $response, $file )->withHeader( HttpHeader::ETAG, $etag ) ;

    // Honour a single byte range, if any.
    $range = parseRangeHeader( $request->getHeaderLine( HttpHeader::RANGE ), filesize( $file ) ) ;
    if ( $range === false )
    {
        return $response->withStatus( 416 ) ; // Range Not Satisfiable
    }
    if ( is_array( $range ) )
    {
        [ $start, $end ] = $range ;
        $response = $response->withStatus( 206 ) ; // Partial Content
        // ... stream bytes $start..$end ...
    }

    return $response ;
}
```

Negotiating a multilingual value submitted by the client:

```php
use function oihana\controllers\helpers\getParamI18n;
use function oihana\controllers\helpers\translate;

// Body: { "title": { "fr": "Bonjour", "en": "Hello", "de": 42 } }
$title = getParamI18n( $request, 'title', [], [ 'fr', 'en' ] );
// [ 'fr' => 'Bonjour', 'en' => 'Hello' ]  (the non-string 'de' value is dropped)

echo translate( $title, 'en' );          // 'Hello'
echo translate( $title, 'de', 'fr' );    // 'Bonjour' (fallback language)
```

## See also

- [Parameters](params.md) — typed request-parameter extraction and the `prepare` strategies.
- [File responses](files.md) — download, streaming, HTTP range, ETag / 304.
- [Documentation index](README.md) — back to the table of contents.
