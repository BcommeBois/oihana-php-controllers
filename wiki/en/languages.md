# Languages

Controllers often serve multilingual content: a single resource carries several
translations (`['fr' => 'Bonjour', 'en' => 'Hello']`), and the client decides
which one(s) it wants. `oihana/php-controllers` keeps this concern small and
explicit:

- a **trait**, `LanguagesTrait`, that records the set of language codes a
  controller supports;
- three **i18n helpers** — `translate()`, `filterLanguages()` and
  `getParamI18n()` — that pick, filter and read translations, either from a plain
  array/object or directly from a PSR-7 request.

None of these helpers parse the `Accept-Language` header for you: negotiation
here means *"keep only the languages this controller allows"*, driven by an
explicit list of codes rather than by HTTP content negotiation.

## LanguagesTrait

`oihana\controllers\traits\LanguagesTrait` gives a controller a single public
property and one initializer:

| Member | Type | Role |
|---|---|---|
| `$languages` | `array<string>` | The valid language codes the controller supports. Defaults to `[]`. |
| `LanguagesTrait::LANGUAGES` | `string` (`'languages'`) | Key looked up in a PSR-11 container. |
| `initializeLanguages( array $init = [], ?ContainerInterface $container = null )` | `static` | Populates `$languages`. |

`initializeLanguages()` resolves the list in this order:

1. It reads `$init[ControllerParam::LANGUAGES]` (the `'languages'` key) from the
   `$init` array.
2. If that is `null` and a PSR-11 `$container` is provided that `has('languages')`,
   it falls back to `$container->get('languages')`.
3. The result is stored only when it is an array; any non-array value (a bare
   `string`, `null`, …) falls back to an empty `[]`.

It returns `$this`, so it chains with the other `initialize*()` calls in a
controller constructor.

```php
use Psr\Container\ContainerInterface;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\LanguagesTrait;

$controller = new class
{
    use LanguagesTrait;
};

// 1. From the init array
$controller->initializeLanguages( [ ControllerParam::LANGUAGES => [ 'fr', 'en' ] ] );
echo implode( ',', $controller->languages ); // fr,en

// 2. From a PSR-11 container (init has no 'languages' key)
$controller->initializeLanguages( [], $container ); // e.g. $container->get('languages') === ['de','it']
echo implode( ',', $controller->languages ); // de,it

// 3. A non-array value falls back to an empty list
$controller->initializeLanguages( [ ControllerParam::LANGUAGES => 'fr' ] );
var_dump( $controller->languages ); // []
```

The stored `$controller->languages` is exactly the list you then pass to
`filterLanguages()` and `getParamI18n()` below.

## i18n helpers

The three helpers live under the `oihana\controllers\helpers` namespace and are
autoloaded as free functions — import them with `use function`.

### `translate()`

Pick one translation out of a map, with an optional fallback language.

```php
function translate(
    array|object|null $fields,
    string|null       $lang    = null,
    string|null       $default = null
): mixed
```

- Returns the value for `$lang` when the key exists.
- Otherwise returns the value for `$default` when that key exists.
- Returns `null` when neither matches, when `$fields` is `null`, or when the map
  is empty.
- When `$lang` is `null`, returns the **whole** `$fields` untouched (array stays
  an array, object stays an object).

```php
use function oihana\controllers\helpers\translate;

$fields =
[
    'fr' => 'Bonjour',
    'en' => 'Hello',
    'es' => 'Hola',
];

translate( $fields, 'en' );        // 'Hello'
translate( $fields, 'de', 'fr' );  // 'Bonjour'  (fallback to fr)
translate( $fields, 'de', 'es' );  // 'Hola'     (fallback to es)
translate( $fields, 'de', null );  // null       (no match, no fallback)
translate( $fields );              // ['fr' => 'Bonjour', 'en' => 'Hello', 'es' => 'Hola']

// Objects are accepted too:
translate( (object) $fields, 'fr' ); // 'Bonjour'
```

### `filterLanguages()`

Reduce a translation map to the allowed languages, keeping only `string` or
`null` values, and optionally sanitizing each one.

```php
function filterLanguages(
    mixed     $fields,
    ?array    $languages = null,
    ?callable $sanitize  = null
): ?array
```

- Accepts an array or an object (objects are cast to arrays); any other shape
  (`string`, `int`, `bool`, …) is treated as invalid and yields `null`.
- Iterates over `$languages` and copies `$fields[$lang]` when it is a `string`
  or `null`; non-string/non-null values (numbers, nested arrays, objects) are
  skipped entirely.
- Applies the optional `$sanitize` callback — `fn(string|null $value, string $lang): string|null` —
  to each retained value.
- Returns `null` when the input is empty or when nothing was retained.

```php
use function oihana\controllers\helpers\filterLanguages;

$fields =
[
    'fr' => 'Bonjour <span style="color:red">monde</span>',
    'en' => 'Hello <span style="color:red">world</span>',
    'de' => 42,    // dropped: not string/null
    'es' => null,  // kept: null is allowed
];

// Basic filtering
filterLanguages( $fields, [ 'fr', 'en', 'de', 'es' ] );
// [
//   'fr' => 'Bonjour <span style="color:red">monde</span>',
//   'en' => 'Hello <span style="color:red">world</span>',
//   'es' => null,
// ]

// With a sanitizing callback (strip inline styles)
filterLanguages( $fields, [ 'fr', 'en' ], function( $value, $lang )
{
    return is_string( $value )
         ? preg_replace( '/(<[^>]+) style=".*?"/i', '$1', $value )
         : $value ;
} );
// [ 'fr' => 'Bonjour <span>monde</span>', 'en' => 'Hello <span>world</span>' ]

filterLanguages( $fields, [] );          // null (no language requested)
filterLanguages( 'flat string', [ 'fr' ] ); // null (invalid input shape)
```

> Note: `filterLanguages()` is permissive on input shape — invalid inputs return
> `null` rather than throwing. If you need a `422` on a malformed payload,
> validate the raw input upstream before calling it.

### `getParamI18n()`

Read an i18n parameter straight from a PSR-7 request, then pipe it through
`filterLanguages()`. It is `getParam()` + `filterLanguages()` in one call.

```php
function getParamI18n(
    ?Request  $request,
    string    $name,
    array     $default   = [],
    ?array    $languages = null,
    ?callable $sanitize  = null,
    ?string   $strategy  = HttpParamStrategy::BOTH,
    bool      $throwable = false
): ?array
```

- Reads `$name` from the request, where the value is a translation map. The
  `$strategy` selects the source — `HttpParamStrategy::QUERY`, `BODY` or `BOTH`
  (default). Dot notation is supported for nested keys (`'user.profile.bio'`).
- The retrieved value is filtered against `$languages` and optionally sanitized,
  exactly like `filterLanguages()`.
- When the parameter is missing, it falls back to `$default[$name]`; with no
  default it yields `null`, and with `$throwable = true` it throws
  `DI\NotFoundException`.

```php
use oihana\enums\http\HttpParamStrategy;

use function oihana\controllers\helpers\getParamI18n;

// Request whose query carries: description => ['fr'=>'Bonjour','en'=>'Hello','de'=>'Hallo']
$result = getParamI18n( $request, 'description', [], [ 'fr', 'en' ] );
// [ 'fr' => 'Bonjour', 'en' => 'Hello' ]   // 'de' dropped (not allowed)

// With a sanitizing callback
$result = getParamI18n( $request, 'description', [], [ 'fr', 'en' ], fn( $v, $lang ) => strip_tags( $v ) );
// '<b>Bonjour</b>' => 'Bonjour', '<b>Hello</b>' => 'Hello'

// Missing parameter falls back to $default[$name]
$default = [ 'description' => [ 'fr' => 'Def FR', 'en' => 'Def EN' ] ];
$result  = getParamI18n( $request, 'description', $default, [ 'fr', 'en' ] );
// [ 'fr' => 'Def FR', 'en' => 'Def EN' ]

// Missing parameter, no default, throw a 404-style error
getParamI18n( $request, 'description', [], [ 'fr', 'en' ], null, HttpParamStrategy::BOTH, true );
// throws DI\NotFoundException
```

A typical controller pairs the trait with the helper: `initializeLanguages()`
fills `$this->languages`, and each write endpoint passes that list to
`getParamI18n()` so a client can never persist a language the controller does
not support.

```php
$i18n = getParamI18n( $request, 'description', [], $this->languages );
```

## See also

- [Parameters](params.md) — the `getParam*()` family and the `prepare` strategies behind `getParamI18n()`.
- [Twig](twig.md) — rendering localized views.
- [Helpers](helpers.md) — the full set of autoloaded free functions.
- Back to the [Documentation index](README.md).
