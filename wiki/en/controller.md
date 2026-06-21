# Controller

`oihana\controllers\Controller` is the composable base class every endpoint of
`oihana/php-controllers` extends. It is an **abstract** class wired to a
[PSR-11](https://www.php-fig.org/psr/psr-11/) container (PHP-DI's `DI\Container`):
its constructor receives the container, stores it on the public `$container`
property, and runs a chain of `initializeXxx()` calls that boot every concern the
controller needs.

Rather than being a monolith, `Controller` is *assembled* from a set of small,
single-responsibility traits. Each trait owns one feature and exposes its own
`initializeXxx()` method, so you can reuse the same building blocks in your own
classes. The trait groups, and the pages that document them, are:

- [Parameters](params.md) — typed request-parameter extraction and the `prepare` strategies.
- [Pagination](pagination.md) — `PaginationTrait`, limits and sorting.
- [Responses](responses.md) — JSON, CBOR, status and API output.
- [File responses](files.md) — download, streaming, HTTP range, ETag / 304, encryption, images.
- [Twig](twig.md) — rendering Twig views.
- [Languages](languages.md) — language negotiation and i18n helpers.
- [Routing](routing.md) — routes, redirects, base URLs, CSRF and HTTP cache.
- [Models](models.md) — wiring controllers to data models.

On top of the traits, `Controller` itself adds a handful of route helpers:

- `getAllowedMethods( ?Request $request ) : array` — the HTTP methods allowed for the current route (empty array when no request is given).
- `getRoute( ?Request $request ) : ?RouteInterface` — the Slim route bound to the request (or `null`).
- `redirectResponse( Response $response , string $url , int $status = HttpStatusCode::FOUND ) : Response` — an HTTP redirect response carrying a `Location` header (default status `302`).

It also exposes the path properties `$path`, `$fullPath`, `$ownerPath` and the
`$conditions` array.

## Building a controller

Extend `Controller`, inject a `DI\Container`, and add your handler methods. Each
handler follows the standard PSR-7 / Slim signature.

```php
use DI\Container;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\Controller;

class UserController extends Controller
{
    public function list( Request $request, Response $response, array $args ): Response
    {
        $users = [ [ 'id' => 1, 'name' => 'Ada' ] , [ 'id' => 2, 'name' => 'Alan' ] ] ;
        return $this->jsonResponse( $response, $users ) ;
    }

    public function redirectHome( Request $request, Response $response ): Response
    {
        return $this->redirectResponse( $response, '/' ) ;
    }
}

$container  = new Container() ;
$controller = new UserController( $container ) ;
```

The constructor also accepts an optional `$init` array of options whose keys come
from the `oihana\controllers\enums\ControllerParam` enumeration — for example
`ControllerParam::APP` (`'app'`), `ControllerParam::ROUTER` (`'router'`),
`ControllerParam::PATH` (`'path'`), `ControllerParam::BENCH` (`'bench'`) and
`ControllerParam::MOCK` (`'mock'`):

```php
use oihana\controllers\enums\ControllerParam;

$controller = new UserController( $container , [
    ControllerParam::PATH  => 'users' ,
    ControllerParam::BENCH => true ,
] ) ;
```

## Benchmarking

`oihana\controllers\traits\BenchTrait` measures the execution time of a handler.
It is one of the traits already composed into `Controller`, and exposes:

- `public bool $bench` — the benchmarking flag (default `false`).
- `initializeBench( bool|array $init = [] ) : static` — sets `$bench` from a boolean or from the `ControllerParam::BENCH` key of an `$init` array.
- `startBench( ?Request $request, array $args = [], ?array &$params = null ) : null|float|int` — returns the current `microtime( true )` when benchmarking is enabled (after `prepareBench()` passes), otherwise `0`.
- `endBench( null|int|float $timestamp, array &$options = [] ) : ?string` — stops the bench and returns a **human-readable duration string** built with [`oihana\core\date\humanizeDuration`](https://bcommebois.github.io/oihana-php-core). It also writes that string into `$options[ Output::TIME ]`. Returns `null` when no valid timestamp is supplied.

```php
use oihana\enums\Output;

$controller->bench = true ;

$options = [] ;
$start   = $controller->startBench( null ) ; // float microtime, or 0 if bench is off

usleep( 100000 ) ; // do the work (0.1s here)

$duration = $controller->endBench( $start , $options ) ;

// $duration is a human-readable string, also stored in $options
echo $duration ;                // e.g. "100 ms"
echo $options[ Output::TIME ] ; // same value
```

When the timestamp is missing or not positive, the bench is a no-op:

```php
$options = [] ;
$result  = $controller->endBench( null , $options ) ;

var_dump( $result ) ;  // NULL
var_dump( $options ) ; // [] — untouched
```

## Mocking

`oihana\controllers\traits\MockTrait` carries a single flag used to switch a
controller (and the models it drives) into mock mode — useful for tests and
local development where you want canned data instead of a real backend.

It provides:

- `public ?bool $mock` — the mock flag (default `null`, i.e. unset).
- `initializeMock( bool|array $init = [] ) : static` — sets `$mock` from a boolean, or from the `ControllerParam::MOCK` key of an `$init` array; defaults to `null` when neither is present.

```php
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\MockTrait;

$service = new class { use MockTrait ; } ;

$service->initializeMock( true ) ;
var_dump( $service->mock ); // bool(true)

$service->initializeMock( [ ControllerParam::MOCK => true ] ) ;
var_dump( $service->mock ); // bool(true)

$service->initializeMock() ;
var_dump( $service->mock ); // NULL — no mock key, stays unset
```

## See also

- [Parameters](params.md) — typed request-parameter extraction.
- [Responses](responses.md) — JSON, CBOR and API output.
- [Documentation index](README.md) — full table of contents.
