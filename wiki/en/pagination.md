# Pagination

![Language](https://img.shields.io/badge/language-English-blue)

Paginating a collection in `oihana/php-controllers` is split across three small, single-responsibility traits:

- **`PaginationTrait`** holds a typed `Pagination` definition — the application/API-wide pagination metadata (`limit`, `offset`, `page`, `numberOfPages`, …), resolved from an init array or a PSR-11 container.
- **`LimitTrait`** holds the per-controller `limit` / `offset` window and its `minLimit` / `maxLimit` bounds.
- **`SortAfterTrait`** re-orders an already-fetched result set by a nested property declared on the model.

The pagination metadata itself is the [`xyz\oihana\schema\Pagination`](https://packagist.org/packages/oihana/php-schema) schema object — a JSON-LD-friendly value object whose property names (`limit`, `maxLimit`, `minLimit`, `offset`, `page`, `numberOfPages`) are also exposed as `Pagination::LIMIT`, `Pagination::OFFSET`, … constants.

## `PaginationTrait`

`oihana\controllers\traits\PaginationTrait` gives a controller a single public property and one initializer:

| Member | Type | Role |
|---|---|---|
| `$pagination` | `?Pagination` | The resolved pagination definition (or `null`). |
| `initializePagination( array $init = [], ?ContainerInterface $container = null ): static` | — | Populate `$pagination`, return `$this` for chaining. |

`initializePagination()` resolves the definition in this order:

1. From `$init[ ControllerParam::PAGINATION ]` (the `'pagination'` key) when present.
2. Otherwise, if a container is given and `$container->has('pagination')`, from `$container->get('pagination')`.
3. If the resolved value is an **array**, it is wrapped into a `new Pagination( $array )`.
4. If it is already a `Pagination` instance, it is used as-is; anything else leaves `$pagination` at `null`.

### Passing a ready-made instance

```php
use xyz\oihana\schema\Pagination;
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\PaginationTrait;

$controller = new class { use PaginationTrait; };

$pagination = new Pagination();
$controller->initializePagination( [ ControllerParam::PAGINATION => $pagination ] );

$controller->pagination === $pagination; // true
```

### Passing a config array

When the value is an array it is hydrated into a `Pagination` object, so its keys become typed properties:

```php
use oihana\controllers\enums\ControllerParam;

$controller->initializePagination(
[
    ControllerParam::PAGINATION => [ 'limit' => 10, 'page' => 2 ],
] );

$controller->pagination->limit; // 10
$controller->pagination->page;  // 2
```

### Resolving from a PSR-11 container

If no `pagination` key is in `$init`, the definition is fetched from the container under the `'pagination'` service id. An array stored in the container is hydrated just the same:

```php
use DI\Container;

$container = new Container();
$container->set( 'pagination', [ 'limit' => 50, 'offset' => 10 ] );

$controller->initializePagination( container: $container );

$controller->pagination->limit;  // 50
$controller->pagination->offset; // 10
```

A `Pagination` instance stored in the container is returned untouched (no re-wrapping).

## `LimitTrait`

`oihana\controllers\traits\LimitTrait` carries the per-controller paging window — the `limit`/`offset` actually applied to a query — together with the bounds used to clamp a client-supplied limit.

| Property | Type | Role |
|---|---|---|
| `$limit` | `?int` | Default number of items per page. |
| `$maxLimit` | `?int` | Upper bound for `limit`. |
| `$minLimit` | `?int` | Lower bound for `limit`. |
| `$offset` | `?int` | Number of items to skip. |

`initializeLimit( array $init = [] ): static` reads each value from `$init` using the `Pagination` key constants, falling back to the **current** property value when a key is absent (so it never clobbers what is already set), and returns `$this`.

```php
use xyz\oihana\schema\Pagination;
use oihana\controllers\traits\LimitTrait;

$controller = new class { use LimitTrait; };

$controller->initializeLimit(
[
    Pagination::LIMIT     => 25,
    Pagination::MAX_LIMIT => 200,
    Pagination::MIN_LIMIT => 5,
    Pagination::OFFSET    => 50,
] );

$controller->limit;    // 25
$controller->maxLimit; // 200
$controller->minLimit; // 5
$controller->offset;   // 50
```

Because absent keys fall back to the existing value, a partial init overrides only what you pass:

```php
$controller->limit    = 10;
$controller->maxLimit = 100;

$controller->initializeLimit( [ Pagination::LIMIT => 20 ] ); // only limit changes

$controller->limit;    // 20
$controller->maxLimit; // 100 (unchanged)
```

Calling `initializeLimit()` with no arguments leaves every property as-is, and explicitly passing `null` for a key sets that property to `null`.

### How limit, offset and metadata fit together

`LimitTrait` provides the *window* (`limit`/`offset`) and the *bounds* (`minLimit`/`maxLimit`) a controller uses to build its query; `PaginationTrait` provides the *metadata* object (`Pagination`) you return alongside the page so a client knows where it stands. A controller typically composes both — clamp a client `limit` between `minLimit` and `maxLimit`, derive `offset` from the requested page, run the query, then expose a `Pagination` describing the result:

```php
use xyz\oihana\schema\Pagination;
use oihana\controllers\traits\LimitTrait;
use oihana\controllers\traits\PaginationTrait;

class ProductsController
{
    use LimitTrait, PaginationTrait;
}

$controller = new ProductsController();
$controller->initializeLimit( [ Pagination::MIN_LIMIT => 1, Pagination::MAX_LIMIT => 100 ] );

// clamp a client-supplied limit within the configured bounds
$requested = 250;
$limit     = max( $controller->minLimit, min( $requested, $controller->maxLimit ) ); // 100

$page   = 3;
$offset = ( $page - 1 ) * $limit; // 200

$meta              = new Pagination();
$meta->limit       = $limit;
$meta->offset      = $offset;
$meta->page        = $page;
```

## `SortAfterTrait`

`oihana\controllers\traits\SortAfterTrait` re-orders a result set **in memory**, after it has been fetched, according to a sort rule declared on the controller's `model`.

`sortAfter( $items )` looks at `$this->model->sortable['after']`. When that entry exists and is a dotted `"object.property"` path of exactly two segments, the items are sorted with `usort()` by comparing the nested property `$item->{$segment0}->{$segment1}` via `strcmp()`. In any other case — no model, no `after` key, or a path that is not exactly two segments — the items are returned unchanged.

```php
use oihana\controllers\traits\SortAfterTrait;

$controller = new class
{
    use SortAfterTrait;
    public ?object $model = null;
};

// the model declares a two-segment "after" sort rule
$controller->model = (object) [ 'sortable' => [ 'after' => 'group.label' ] ];

$items =
[
    (object) [ 'group' => (object) [ 'label' => 'charlie' ] ],
    (object) [ 'group' => (object) [ 'label' => 'alpha'   ] ],
    (object) [ 'group' => (object) [ 'label' => 'bravo'   ] ],
];

$sorted = $controller->sortAfter( $items );

array_map( fn( $i ) => $i->group->label, $sorted );
// [ 'alpha', 'bravo', 'charlie' ]
```

If the model is `null`, the `sortable` array has no `after` key, or `after` is not a two-segment path, `sortAfter()` returns the original array untouched:

```php
$controller->model = null;
$controller->sortAfter( $items ) === $items; // true
```

This makes `SortAfterTrait` the final step of a paginated read: fetch a page with the `limit`/`offset` from `LimitTrait`, then apply `sortAfter()` so the page is ordered by the model's declared nested key before it is serialized.

## See also

- [Parameters](params.md) — typed request-parameter extraction and the `prepare` strategies.
- [Responses](responses.md) — JSON, CBOR, status and API output.
- [Documentation index](README.md) — back to the table of contents.
