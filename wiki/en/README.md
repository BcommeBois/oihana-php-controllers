# oihana/php-controllers — HTTP controller toolkit for PHP

![Language](https://img.shields.io/badge/language-English-blue)

`oihana/php-controllers` is a PHP 8.4+ library providing a composable `Controller` base class and a set of focused traits and helpers for building HTTP controllers on top of [Slim](https://www.slimframework.com/) and [Twig](https://twig.symfony.com/).

![Oihana PHP Controllers](https://raw.githubusercontent.com/BcommeBois/oihana-php-controllers/main/assets/images/oihana-php-controllers-logo-inline-512x160.png)

## Who this documentation is for

PHP developers who want to:

- build controllers from a **composable** base and small, single-responsibility traits;
- extract **typed request parameters** with validation strategies — `ParamsTrait`, the `getParam*()` helpers;
- paginate, negotiate languages, render Twig views and serialize **JSON / CBOR** responses;
- serve **files** — download, streaming, HTTP range, ETag & `304 Not Modified`, archives, uploads, encryption and images;
- wire controllers to **models** resolved from a PSR-11 container.

## Quick start

```php
use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use oihana\controllers\Controller;

use function oihana\controllers\helpers\getParamInt;

class HelloController extends Controller
{
    public function index( ServerRequestInterface $request, ResponseInterface $response, array $args ): ResponseInterface
    {
        $page = getParamInt( $request, 'page', 1 );
        return $this->json( $response, [ 'page' => $page ] );
    }
}

$controller = new HelloController( new Container() );
```

For full details (traits, options, enums), see the table of contents below.

## Table of contents

### Getting started — [`getting-started/`](getting-started/)

- [Introduction](getting-started/introduction.md) — what the library does and the *oihana* philosophy.
- [Installation](getting-started/installation.md) — PHP 8.4+ / `ext-imagick` requirements and `composer require`.
- [Dependencies](getting-started/dependencies.md) — the runtime packages and their role.

### Usage

- [Controller](controller.md) — the base `Controller`, its composition, benching and mocking.
- [Parameters](params.md) — typed request-parameter extraction and the `prepare` strategies.
- [Pagination](pagination.md) — `PaginationTrait`, `LimitTrait`, sorting.
- [Responses](responses.md) — JSON, CBOR, status and API output.
- [File responses](files.md) — download, streaming, HTTP range, ETag / 304, encryption, images.
- [Archives & uploads](archives-uploads.md) — zip/tar archives and file uploads.
- [Twig](twig.md) — rendering Twig views.
- [Languages](languages.md) — language negotiation and i18n helpers.
- [Routing](routing.md) — routes, redirects, base URLs, CSRF and HTTP cache.
- [Models](models.md) — wiring controllers to data models.
- [Helpers](helpers.md) — the autoloaded free functions.
- [Enumerations](enums.md) — the typed-constant option classes.

### Cross-cutting

- [Tests & coverage](testing.md) — run the PHPUnit suite and measure coverage.

## Source code

The library code lives under [`src/oihana/controllers/`](../../src/oihana/controllers/) — namespace `oihana\controllers`.

## See also

- [Packagist `oihana/php-controllers`](https://packagist.org/packages/oihana/php-controllers) — the package page.
- [API reference (phpDocumentor)](https://bcommebois.github.io/oihana-php-controllers) — class-level generated reference.
