# Routing

Controllers rarely live in isolation: they are wired into a [Slim](https://www.slimframework.com/)
application, generate URLs for named routes, redirect to other actions, build
links from a configurable base URL, protect forms with CSRF tokens and emit HTTP
cache headers. `oihana/php-controllers` groups these concerns into small,
single-responsibility traits that the base `Controller` composes — each one
initialised from an `init` array or resolved from a PSR-11 container.

| Trait | Role |
|---|---|
| `AppTrait` | Holds the Slim `App` instance and builds URLs from its base path. |
| `RouterTrait` | Generates URLs for **named routes** and redirects to them. |
| `RedirectsTrait` | Stores a map of named redirect targets. |
| `BaseUrlTrait` | Manages the application's base URL and builds paths from it. |
| `PathTrait` | Holds the controller's own `path` / `fullPath` / `ownerPath`. |
| `CsrfTrait` | Exposes CSRF tokens via `slim/csrf`. |
| `HttpCacheTrait` | Sets cache headers (`ETag`, `Last-Modified`, …) via `slim/http-cache`. |

Every initializer follows the same priority: the `init` array first, then the
DI container, and finally a safe default (or an exception when the dependency is
mandatory). The keys live in `oihana\controllers\enums\ControllerParam`
(`ControllerParam::APP === 'app'`, `ControllerParam::ROUTER === 'router'`, etc.),
so you never have to hardcode the magic strings.

## `AppTrait` — the Slim App reference

`oihana\controllers\traits\AppTrait` holds the Slim `App` instance and uses it to
build absolute URLs. It composes `BaseUrlTrait`, so it inherits the `$baseUrl`
property.

| Method | Signature | Returns |
|---|---|---|
| `initializeApp` | `initializeApp( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `getBasePath` | `getBasePath(): string` | The Slim base path (e.g. `/myapp`). |
| `getUrl` | `getUrl( string $path = '', array $params = [], bool $useNow = false ): string` | Full URL. |

`initializeApp()` resolves the `App` from `$init[ControllerParam::APP]` first,
then from the container (`App::class` or a custom service id), and throws a
`RuntimeException` when none is found. `getUrl()` joins `$baseUrl`, the Slim base
path and `$path`, then appends the formatted query string.

```php
use Slim\Factory\AppFactory;
use oihana\controllers\traits\AppTrait;
use oihana\controllers\enums\ControllerParam;

$app = AppFactory::create();
$app->setBasePath( '/myapp' );

$controller = new class { use AppTrait; };
$controller->initializeApp( [ ControllerParam::APP => $app ] );
$controller->baseUrl = 'https://example.com';

echo $controller->getBasePath();                          // /myapp
echo $controller->getUrl( '/users', [ 'page' => 2 ] );    // https://example.com/myapp/users?page=2
```

## `RouterTrait` — route URL generation

`oihana\controllers\traits\RouterTrait` wraps Slim's `RouteParserInterface` to
generate URLs for **named** routes and to redirect to them. It composes
`BaseUrlTrait`.

| Method | Signature | Returns |
|---|---|---|
| `initializeRouterParser` | `initializeRouterParser( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `redirectFor` | `redirectFor( Response $response, string $name, array $params = [], int $status = 302 ): Response` | Redirect response. |
| `urlFor` (protected) | `urlFor( string $routeName ): string` | `$baseUrl` + the route path. |

`initializeRouterParser()` resolves a `RouteParserInterface` from
`$init[ControllerParam::ROUTER]` or from the container, and throws a
`RuntimeException` otherwise. `urlFor()` prepends `$baseUrl` to the route path;
`redirectFor()` resolves the route then delegates to the host's
`redirectResponse()` (provided by the base `Controller`).

```php
use Slim\Interfaces\RouteParserInterface;
use Psr\Http\Message\ResponseInterface;
use oihana\controllers\Controller;
use oihana\controllers\enums\ControllerParam;

/** @var RouteParserInterface $parser  (from $app->getRouteCollector()->getRouteParser()) */
class PostController extends Controller
{
    public function save( $request, ResponseInterface $response, array $args ): ResponseInterface
    {
        // ... persist ...
        return $this->redirectFor( $response, 'post.show', [ 'id' => 42 ], 303 );
    }
}

$controller = new PostController( /* container */ );
$controller->initializeRouterParser( [ ControllerParam::ROUTER => $parser ] );
$controller->baseUrl = '/api';
// urlFor('post.show') -> '/api' . $parser->urlFor('post.show')
```

## `RedirectsTrait` — named redirect targets

`oihana\controllers\traits\RedirectsTrait` keeps a simple associative map of
redirect targets, typically used by a controller to look up where to send the
user after an action.

| Member | Signature | Role |
|---|---|---|
| `$redirects` | `public array $redirects = []` | The redirect map. |
| `initializeRedirects` | `initializeRedirects( array $init = [] ): void` | Reads `$init[ControllerParam::REDIRECTS]`. |

```php
use oihana\controllers\traits\RedirectsTrait;
use oihana\controllers\enums\ControllerParam;

$controller = new class { use RedirectsTrait; };
$controller->initializeRedirects([
    ControllerParam::REDIRECTS => [
        'success' => '/dashboard',
        'login'   => '/auth/login',
    ],
]);

$target = $controller->redirects[ 'success' ] ?? '/'; // '/dashboard'
```

## `BaseUrlTrait` / `PathTrait` — URL & path building

### `BaseUrlTrait`

`oihana\controllers\traits\BaseUrlTrait` manages the application's `$baseUrl` and
builds paths relative to it. The base URL can come from the `init` array, the DI
container, or default to an empty string.

| Method | Signature | Returns |
|---|---|---|
| `initializeBaseUrl` | `initializeBaseUrl( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `getCurrentPath` | `getCurrentPath( ?Request $request = null, array $params = [], bool $useNow = false ): string` | Current path under the base URL. |
| `getFullPath` | `getFullPath( ?array $params = null, bool $useNow = false ): string` | Base URL + query string. |
| `getPath` | `getPath( string $path = '', ?array $params = null, bool $useNow = false ): string` | Base URL + `$path` + query. |

`getCurrentPath()` uses the PSR-7 request URI when given, otherwise falls back to
`$_SERVER['REQUEST_URI']`. When `$useNow` is `true`, a `_` cache-busting
timestamp parameter is appended.

```php
use oihana\controllers\traits\BaseUrlTrait;
use oihana\controllers\enums\ControllerParam;

$controller = new class { use BaseUrlTrait; };
$controller->initializeBaseUrl( [ ControllerParam::BASE_URL => 'https://example.com' ] );

echo $controller->getPath( '/users', [ 'page' => 2 ] ); // https://example.com/users?page=2
echo $controller->getFullPath( [ 'q' => 'php' ] );       // https://example.com?q=php
```

### `PathTrait`

`oihana\controllers\traits\PathTrait` holds the controller's own path
references — useful for resource controllers nested under an owner.

| Member | Signature | Role |
|---|---|---|
| `$path` | `public string $path = ''` | The resource path. |
| `$fullPath` | `public string $fullPath` | The full path (defaults to `/` + `$path`). |
| `$ownerPath` | `public ?string $ownerPath = ''` | The owner path prefix. |
| `getFullOwnerPath` | `getFullOwnerPath( string $id ): string` | `ownerPath/$id/path`. |
| `initializePath` | `initializePath( array $init = [] ): static` | Reads `path` / `fullPath` / `ownerPath`. |

```php
use oihana\controllers\traits\PathTrait;
use oihana\controllers\enums\ControllerParam;

$controller = new class { use PathTrait; };
$controller->initializePath([
    ControllerParam::PATH       => 'photos',
    ControllerParam::OWNER_PATH => 'albums',
]);

echo $controller->path;                       // photos
echo $controller->fullPath;                   // /photos
echo $controller->getFullOwnerPath( '7' );    // albums/7/photos
```

## `CsrfTrait` — CSRF protection via `slim/csrf`

`oihana\controllers\traits\CsrfTrait` exposes a `Slim\Csrf\Guard` (provided by DI,
never instantiated here) to read and validate CSRF tokens. When the guard is not
configured, every accessor degrades gracefully (`null`, `[]` or `false`).

| Method | Signature | Returns |
|---|---|---|
| `initializeCsrf` | `initializeCsrf( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `csrfTokenName` | `csrfTokenName(): ?string` | Current token name. |
| `csrfTokenNameKey` | `csrfTokenNameKey(): ?string` | Field name for the token name. |
| `csrfTokenValue` | `csrfTokenValue(): ?string` | Current token value. |
| `csrfTokenValueKey` | `csrfTokenValueKey(): ?string` | Field name for the token value. |
| `csrfTokens` | `csrfTokens(): array<string,string>` | `[ nameKey => name, valueKey => value ]`. |
| `generateCsrfToken` | `generateCsrfToken(): array<string,string>` | Generates and stores a fresh pair. |
| `validateCsrf` | `validateCsrf( string $name, string $value ): bool` | Validates a submitted pair. |

`initializeCsrf()` reads `$init[CsrfTrait::CSRF]` (the `'csrf'` key) first, then
falls back to `$container->get(Guard::class)`. Token values are only available
once the guard middleware (or `generateCsrfToken()`) has populated the request.

```php
use Slim\Csrf\Guard;
use oihana\controllers\traits\CsrfTrait;

/** @var Guard $guard */
$controller = new class { use CsrfTrait; };
$controller->initializeCsrf( [ CsrfTrait::CSRF => $guard ] );

// In a controller rendering a form (no middleware in the pipeline):
$tokens = $controller->generateCsrfToken();   // [ 'csrf_name' => '...', 'csrf_value' => '...' ]

// On submission:
$ok = $controller->validateCsrf( $name, $value ); // bool
```

## `HttpCacheTrait` — cache headers via `slim/http-cache`

`oihana\controllers\traits\HttpCacheTrait` wraps a
`Slim\HttpCache\CacheProvider` to add cache-related headers. **You must call
`initializeHttpCache()`** — otherwise every method silently returns the response
unchanged.

| Method | Signature | Returns |
|---|---|---|
| `initializeHttpCache` | `initializeHttpCache( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `allowCache` | `allowCache( ResponseInterface $response, string $type = 'private', int\|string\|null $maxAge = null, bool $mustRevalidate = false ): ResponseInterface` | `Cache-Control`. |
| `denyCache` | `denyCache( ResponseInterface $response ): ResponseInterface` | No-store / no-cache. |
| `withEtag` | `withEtag( ResponseInterface $response, string $value, string $type = 'strong' ): ResponseInterface` | `ETag`. |
| `withExpires` | `withExpires( ResponseInterface $response, string\|int $time ): ResponseInterface` | `Expires`. |
| `withLastModified` | `withLastModified( ResponseInterface $response, int\|string $time ): ResponseInterface` | `Last-Modified`. |

`initializeHttpCache()` reads `$init[ControllerParam::HTTP_CACHE]` first, then
falls back to `$container->get(CacheProvider::class)`.

```php
use Slim\HttpCache\CacheProvider;
use Psr\Http\Message\ResponseInterface;
use oihana\controllers\traits\HttpCacheTrait;
use oihana\controllers\enums\ControllerParam;

$controller = new class { use HttpCacheTrait; };
$controller->initializeHttpCache( [ ControllerParam::HTTP_CACHE => new CacheProvider() ] );

/** @var ResponseInterface $response */
$response = $controller->allowCache( $response, 'public', 3600, true );
$response = $controller->withEtag( $response, 'abc123' );
$response = $controller->withLastModified( $response, '-1 day' );
```

## The `getController()` helper

`oihana\controllers\helpers\getController()` resolves a `Controller` from a
flexible definition — handy when wiring routes from configuration.

```php
function getController(
    array|string|null|Controller $definition = null,
    ?ContainerInterface          $container  = null,
    ?Controller                  $default    = null
): ?Controller
```

It returns the definition directly when it is already a `Controller`; reads the
`ControllerParam::CONTROLLER` (`'controller'`) key when given an array; resolves a
string service id from the container; and otherwise returns `$default`.

```php
use Psr\Container\ContainerInterface;
use function oihana\controllers\helpers\getController;

/** @var ContainerInterface $container */
$controller = getController( 'app.controllers.home', $container );

// From a route config array:
$controller = getController( [ 'controller' => HomeController::class ], $container, $fallback );
```

## See also

- [Controller](controller.md) — the base `Controller` and its composition.
- [File responses](files.md) — download, streaming, HTTP range, ETag / 304.
- [Documentation index](README.md) — the full table of contents.
