# Twig

`oihana\controllers\traits\TwigTrait` integrates the [Twig](https://twig.symfony.com/)
templating engine into your controllers via
[slim/twig-view](https://www.slimframework.com/docs/v4/features/templates.html).
It resolves a `Slim\Views\Twig` renderer — either supplied directly or fetched
from a PSR-11 container — and exposes a single `render()` method that turns a
template into a PSR-7 `ResponseInterface`, defaulting the `Content-Type` to HTML
when the caller leaves it unset.

## `TwigTrait`

The trait holds one public property, the resolved `Twig` renderer, plus the
container key used to look it up.

### Property & constant

| Member | Type | Description |
|---|---|---|
| `$twig` | `Slim\Views\Twig` | The resolved Twig view renderer. Set by `initializeTwig()`. |
| `TwigTrait::TWIG` | `string` (`'twig'`) | Container / init-array key used to retrieve the Twig instance. |

### Methods

| Method | Returns | Description |
|---|---|---|
| `initializeTwig( array $init = [] , ?ContainerInterface $container = null )` | `static` | Resolves and stores the `Twig` renderer, then returns `$this` for chaining. |
| `render( ?Response $response , string $template , array $args = [] )` | `?Response` | Renders `$template` with `$args` into `$response`. Returns `null` when `$response` is `null`. |

#### `initializeTwig()` resolution order

`initializeTwig()` looks for a `Twig` instance in this order, stopping at the
first match:

1. `$init[ TwigTrait::TWIG ]` — an instance passed directly in the init array;
2. `$container->get( TwigTrait::TWIG )` — when the container `has('twig')`;
3. `$container->get( Twig::class )` — when the container `has(Twig::class)`.

If none of these yields a `Slim\Views\Twig`, an `InvalidArgumentException` is
thrown describing the type that was found instead.

#### `render()` behaviour

`render()` delegates to `Twig::render()` and then, only when the rendered
response carries **no** `Content-Type` header, adds `text/html` with its charset.
Renders that emit XML, SVG or plain text set their own type and keep it. When
`$response` is `null`, the method short-circuits and returns `null`.

### Example

Mix the trait into a controller, hydrate it from the PSR-11 container in the
constructor, then render a template:

```php
use oihana\controllers\traits\TwigTrait;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController
{
    use TwigTrait;

    public function __construct( ContainerInterface $container )
    {
        // Resolves the 'twig' service (or Twig::class) from the container.
        $this->initializeTwig( [] , $container ) ;
    }

    public function home( Request $request , Response $response ) : ?Response
    {
        return $this->render( $response , 'home.twig' , [
            'title' => 'Welcome!' ,
            'user'  => 'Marc' ,
        ] ) ;
    }
}
```

You can also inject the renderer directly — handy for tests, where a stubbed
`Twig` instance is passed through the init array:

```php
use Slim\Views\Twig;

$twig = new Twig( /* loader, settings… */ ) ;

$controller = new HomeController( $container ) ;
$controller->initializeTwig( [ HomeController::TWIG => $twig ] ) ;
```

## `TwigParam`

`oihana\controllers\enums\TwigParam` enumerates the option keys commonly passed
to Twig templates (for example, theming a rendered page). Built on
`ConstantsTrait`, it exposes the usual reflection helpers (`getAll()`,
`getConstants()`, …) over the constants below.

| Constant | Value | Description |
|---|---|---|
| `TwigParam::BACKGROUND_COLOR` | `'backgroundColor'` | Background color passed to the template. |
| `TwigParam::FULL_PATH` | `'fullPath'` | Full path / URL of the rendered resource. |
| `TwigParam::LOGO` | `'logo'` | Logo asset for the light theme. |
| `TwigParam::LOGO_DARK` | `'logoDark'` | Logo asset for the dark theme. |
| `TwigParam::PATTERN_COLOR` | `'patternColor'` | Accent / pattern color passed to the template. |
| `TwigParam::TWIG` | `'twig'` | The Twig instance / service key. |

```php
use oihana\controllers\enums\TwigParam;

return $this->render( $response , 'page.twig' , [
    TwigParam::LOGO             => '/assets/logo.svg' ,
    TwigParam::LOGO_DARK        => '/assets/logo-dark.svg' ,
    TwigParam::BACKGROUND_COLOR => '#ffffff' ,
    TwigParam::PATTERN_COLOR    => '#1d4ed8' ,
] ) ;
```

## See also

- [Responses](responses.md) — JSON, CBOR, status and API output.
- [Languages](languages.md) — language negotiation and i18n helpers.
- [Enumerations](enums.md) — the typed-constant option classes.
- [Documentation index](README.md) — back to the table of contents.
