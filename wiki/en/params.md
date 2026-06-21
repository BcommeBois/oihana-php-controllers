# Parameters

HTTP controllers spend most of their time turning raw request input — query string, parsed body, route arguments — into clean, typed values they can trust. `oihana/php-controllers` gives you two complementary layers for that job:

- a family of stateless **`getParam*()` helpers** that read a single value from a PSR-7 request and coerce it to the type you ask for (`int`, `bool`, `string`, `array`, `float`, ranges, i18n maps);
- a set of composable **`Prepare*` strategies** (`prepare\Prepare*`) that encapsulate the conventions for the recurring parameters of a REST API — `lang`, `sort`, `limit`, `offset`, `filter`, `facets`, … — including validation, defaults and the bookkeeping needed to forward the value to the underlying model.

The vocabulary of parameter names is centralised in the `ControllerParam` enumeration, and the source to read from (query, body or both) is described by the `HttpParamStrategy` enumeration.

## The getParam helpers

All helpers live in the `oihana\controllers\helpers` namespace and accept a PSR-7 `ServerRequestInterface` (nullable). The typed accessors support **dot notation** for nested keys (`'user.profile.email'`) and share the same tail signature: `( ..., array $args = [], $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false )`.

| Helper | Returns | What it extracts |
|---|---|---|
| `getParam( $request, $name, $default = [], $strategy = BOTH, $throwable = false )` | `mixed` | Raw value from query and/or body according to `$strategy`. Falls back to `$default[$name]`, or throws `NotFoundException` when `$throwable`. |
| `getParamInt( $request, $name, $args = [], $defaultValue = null, ... )` | `?int` | Value cast to `int` when numeric, otherwise `$defaultValue`. |
| `getParamFloat( $request, $name, $args = [], $defaultValue = null, ... )` | `?float` | Value cast to `float` when numeric, otherwise `$defaultValue`. |
| `getParamBool( $request, $name, $args = [], $defaultValue = null, ... )` | `?bool` | Value normalised via `FILTER_VALIDATE_BOOLEAN` (`true/false`, `1/0`, `yes/no`, `on/off`). |
| `getParamString( $request, $name, $args = [], $defaultValue = null, ... )` | `?string` | Value cast to `string` when set. |
| `getParamArray( $request, $name, $args = [], $defaultValue = null, ... )` | `?array` | Value when it is an array, otherwise `$defaultValue`. |
| `getParamI18n( $request, $name, $default = [], $languages = null, $sanitize = null, ... )` | `?array` | A translations map (`['fr' => …, 'en' => …]`), filtered to `$languages` and optionally sanitised per value. |
| `getParamNumberRange( $request, $name, $min, $max, $defaultValue = null, ... )` | `int\|float\|null` | Numeric value clamped to `[$min, $max]`. |
| `getParamIntRange( $request, $name, $min, $max, $defaultValue = null, ... )` | `?int` | `getParamNumberRange()` cast to `int`. |
| `getParamFloatRange( $request, $name, $min, $max, $defaultValue = null, ... )` | `?float` | `getParamNumberRange()` cast to `float`. |
| `getQueryParam( $request, $name )` | `mixed` | A single value from the **query string only** (`$request->getQueryParams()`). |
| `getBodyParam( $request, $name )` | `mixed` | A single value from the **parsed body only** (`$request->getParsedBody()`). |
| `getBodyParams( $request, $names = [] )` | `array` | Several body values at once, reassembled into a nested associative array. |

> The dedicated [Helpers](helpers.md) page lists every helper in the library; this section focuses on the parameter ones.

### Reading typed values

```php
use oihana\enums\http\HttpParamStrategy;

use function oihana\controllers\helpers\getParamInt;
use function oihana\controllers\helpers\getParamBool;
use function oihana\controllers\helpers\getParamString;

// Query string: ?page=2&active=true&name=Alice
$page   = getParamInt   ( $request , 'page'   , [] , 1 ) ;          // 2
$active = getParamBool  ( $request , 'active' , [] , false ) ;      // true
$name   = getParamString( $request , 'name'   , [] , 'Guest' ) ;    // "Alice"

// Restrict the source to the request body only
$comment = getParamString( $request , 'comment' , [] , null , HttpParamStrategy::BODY ) ;
```

### Clamping and nested keys

```php
use function oihana\controllers\helpers\getParamIntRange;
use function oihana\controllers\helpers\getBodyParam;

// Clamp into a safe range — out-of-bounds values are pinned to min/max
$quantity = getParamIntRange( $request , 'quantity' , 1 , 10 , 5 ) ; // 10 if ?quantity=999

// Dot notation walks nested body structures
// POST body: ['geo' => ['latitude' => 42.5]]
$latitude = getBodyParam( $request , 'geo.latitude' ) ; // 42.5
```

## ParamsTrait & ParamsStrategyTrait

A controller advertises *which* parameters it understands and *where* they may come from through two small traits.

`ParamsTrait` exposes a `?array $params` definition and an `initializeParams()` method that reads it from the `ControllerParam::PARAMS` key of the construction options. This `$params` map drives convention-based behaviours such as `prepareFacets()` (see below).

`ParamsStrategyTrait` exposes a `string $paramsStrategy` (default `HttpParamStrategy::BOTH`) and `initializeParamsStrategy()`. The strategy decides whether parameters are looked up in the query string, the parsed body, or both. It accepts either a bare strategy string or an array keyed by `ParamsStrategyTrait::PARAMS_STRATEGY`, and silently ignores any value not recognised by `HttpParamStrategy::includes()`.

```php
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ParamsTrait;
use oihana\controllers\traits\ParamsStrategyTrait;
use oihana\enums\http\HttpParamStrategy;

use function oihana\controllers\helpers\getParam;

class ProductController
{
    use ParamsTrait , ParamsStrategyTrait ;

    public function __construct( array $init = [] )
    {
        $this->initializeParams( $init ) ;            // reads ControllerParam::PARAMS
        $this->initializeParamsStrategy( $init ) ;    // reads ParamsStrategyTrait::PARAMS_STRATEGY
    }

    public function read( $request )
    {
        // Resolve a value using the controller-wide strategy
        $owner = getParam( $request , ControllerParam::OWNER , [] , $this->paramsStrategy ) ;
        // ...
    }
}

$controller = new ProductController(
[
    ControllerParam::PARAMS          => [ ControllerParam::ID => ControllerParam::FACETS ] ,
    ParamsStrategyTrait::PARAMS_STRATEGY => HttpParamStrategy::QUERY ,
] ) ;
```

## Prepare strategies

The `oihana\controllers\traits\prepare\Prepare*` traits each own a single recurring parameter. They follow a consistent pattern: a `protected function prepare<Name>( ?Request $request, array $args = [], ?array &$params = null, ... )` method that

1. seeds a value from the route `$args` (a sensible default when the request is absent — handy in tests);
2. overrides it with the request value when present and valid (most read from the **query string** via `getQueryParam()`);
3. records the effective value into the `&$params` reference, so the controller can forward exactly what it resolved to the model layer;
4. returns the resolved, typed value.

`PrepareParamTrait` aggregates the common ones into a single trait you can `use` in a controller, giving you `prepareLang()`, `prepareSort()`, `prepareLimit()`, `prepareOffset()`, `prepareFilter()`, `prepareFacets()` and friends in one shot.

| Trait | Method | Role |
|---|---|---|
| `PrepareActive` | `prepareActive()` | Resolve the `active` boolean flag. |
| `PrepareBench` | `prepareBench()` | Resolve the `bench` benchmarking flag. |
| `PrepareBoolean` | `prepareBoolean()` | Generic helper to resolve any named boolean parameter. |
| `PrepareDate` | `prepareDate()` | Resolve and normalise a `date` parameter. |
| `PrepareFacets` | `prepareFacets()` | Build the `facets` definitions from query params and the `$params` map. |
| `PrepareFilter` | `prepareFilter()` | Decode the JSON `filter` query parameter into an array. |
| `PrepareGroupBy` | `prepareGroupBy()` | Resolve the `groupBy` expression. |
| `PrepareHasTotal` | `prepareHasTotal()` | Resolve the `hasTotal` flag (request a total count). |
| `PrepareIDs` | `preparedIDs()` | Resolve a list of identifiers (`ids`). |
| `PrepareInt` | `prepareInt()` | Generic helper to resolve any named integer parameter. |
| `PrepareInterval` | `prepareInterval()` | Resolve the time `interval` against allowed options. |
| `PrepareLang` | `prepareLang()` | Resolve and validate the `lang` parameter against allowed languages. |
| `PrepareLimit` | `prepareLimit()` / `prepareOffset()` | Resolve and clamp the pagination `limit` / `offset`. |
| `PrepareMargin` | `prepareMargin()` | Resolve the `margin` flag. |
| `PrepareMock` | `prepareMock()` | Resolve the `mock` flag (return mock data). |
| `PrepareOrder` | `prepareOrder()` | Resolve the sort `order` direction. |
| `PrepareQuantity` | `prepareQuantity()` | Resolve the `quantity` integer parameter. |
| `PrepareSearch` | `prepareSearch()` | Resolve the free-text `search` parameter. |
| `PrepareSkin` | `prepareSkin()` | Resolve and validate the `skin` (view variant) parameter. |
| `PrepareSort` | `prepareSort()` | Resolve the `sort` parameter, falling back to a default. |
| `PrepareTimezone` | `prepareTimezone()` | Resolve the `timezone` parameter. |
| `PrepareOrRedirectArgumentTrait` | `prepareOrRedirectArgument()` | Prepare an argument and redirect to it when possible. |

### `PrepareLang`

`prepareLang()` reads the `lang` **query** parameter (never the body), lowercases it and accepts it only if it belongs to `$this->languages`. The special value `all` resolves to `null` (no language filter). When a language is retained it is also written back into `&$params` under `ControllerParam::LANG`.

```php
use oihana\controllers\traits\prepare\PrepareLang;

class CmsController
{
    use PrepareLang ;

    public array $languages = [ 'fr' , 'en' ] ;

    public function page( $request , array $args )
    {
        $params = [] ;
        // ?lang=FR  -> "fr" ; ?lang=all -> null ; ?lang=de -> falls back to $args/default
        $lang = $this->prepareLang( $request , $args , $params ) ;
        // $params now contains [ 'lang' => 'fr' ] when a valid language was provided
    }
}
```

### `PrepareSort`

`prepareSort()` reads the `sort` query parameter; when present it is stored in `&$params` and returned as-is. When absent the method returns, in order, the `$default` argument, then `$this->sortDefault` (from `SortDefaultTrait`). The parameter name is configurable, so the same trait can drive several sort axes.

```php
use oihana\controllers\traits\prepare\PrepareSort;

class ListController
{
    use PrepareSort ;

    public string $sortDefault = 'name' ;

    public function index( $request , array $args )
    {
        $params = [] ;
        $sort = $this->prepareSort( $request , $args , $params ) ; // "price" for ?sort=price, else "name"
    }
}
```

### `PrepareLimit`

`prepareLimit()` (and its sibling `prepareOffset()`, which simply calls it with the `offset` property) reads the value from the query string and validates it as an integer **clamped** to `[$this->minLimit, $this->maxLimit]` (falling back to the pagination object, then `0..100`). When the request did not carry the value, the resolved limit comes from the controller property / pagination / `$defaultValue`. Only request-provided values are written back into `&$params`.

```php
use xyz\oihana\schema\Pagination;
use oihana\controllers\traits\prepare\PrepareLimit;

class ListController
{
    use PrepareLimit ;

    public int $limit    = 20 ;
    public int $maxLimit = 100 ;

    public function index( $request , array $args )
    {
        $params = [] ;
        $limit  = $this->prepareLimit ( $request , $args , $params ) ; // ?limit=50 -> 50 ; ?limit=999 -> 100
        $offset = $this->prepareOffset( $request , $args , $params ) ;
    }
}
```

### `PrepareFilter` & `PrepareFacets`

`prepareFilter()` expects the `filter` query parameter to be a JSON string. It validates the JSON, decodes it to an array (logging a warning and falling back to `$args` otherwise), stores the original JSON in `&$params` and returns the decoded array.

`prepareFacets()` combines two sources: the `facets` JSON query parameter, and per-parameter facet definitions declared in the controller's `$params` map (`prepareParamsFacets()`). This lets a route such as `/products?id=[12,255,300]` translate a plain query parameter into a facet definition when `ControllerParam::PARAMS => [ Prop::ID => ControllerParam::FACETS ]` is configured.

```php
use oihana\controllers\traits\prepare\PrepareFilter;

class SearchController
{
    use PrepareFilter ;

    public function index( $request , array $args )
    {
        $params = [] ;
        // ?filter={"status":"active"}  ->  [ 'status' => 'active' ]
        $filter = $this->prepareFilter( $request , $args , $params ) ;
    }
}
```

## See also

- [Pagination](pagination.md) — `limit` / `offset` and the pagination model.
- [Helpers](helpers.md) — the full catalogue of helper functions.
- [Enumerations](enums.md) — `ControllerParam`, `HttpParamStrategy` and related enums.
- Back to the [Documentation index](README.md).
