# Oihana PHP - Controllers

![Oihana PHP Controllers](https://raw.githubusercontent.com/BcommeBois/oihana-php-controllers/main/assets/images/oihana-php-controllers-logo-inline-512x160.png)

Composable HTTP controller building blocks for PHP 8.4+, built on [Slim](https://www.slimframework.com/) and [Twig](https://twig.symfony.com/).

[![Latest Version](https://img.shields.io/packagist/v/oihana/php-controllers.svg?style=flat-square)](https://packagist.org/packages/oihana/php-controllers)  
[![Total Downloads](https://img.shields.io/packagist/dt/oihana/php-controllers.svg?style=flat-square)](https://packagist.org/packages/oihana/php-controllers)  
[![License](https://img.shields.io/packagist/l/oihana/php-controllers.svg?style=flat-square)](LICENSE)

## 📚 Documentation

User guides (FR + EN), with narrative explanations and examples:

| 🇬🇧 **[English documentation](wiki/en/README.md)**                                                    | 🇫🇷 **[Documentation française](wiki/fr/README.md)**                                                  |
|-------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------|
| Getting started, controller, params, pagination, file responses, archives & upload, Twig, helpers, testing. | Démarrage, contrôleur, params, pagination, réponses fichier, archives & upload, Twig, helpers, tests. |

Auto-generated API reference (phpDocumentor):  
👉 https://bcommebois.github.io/oihana-php-controllers

## 🧠 What is it?

`oihana/php-controllers` provides a composable `Controller` base class and a set
of focused traits and helpers for building HTTP controllers on top of Slim and
Twig: typed request-parameter extraction, pagination, language negotiation,
file responses (download, streaming, HTTP range, ETag / 304), archives, uploads,
encryption, CSRF, HTTP caching and JSON/CBOR serialization.

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
```

## 🚀 Features

- 🎛️ A composable `Controller` base and focused traits — params, pagination, languages, routing, status, Twig, JSON/CBOR.
- 🧩 Typed request-parameter extraction & strategies — `ParamsTrait`, `ParamsStrategyTrait`, the `getParam*()` helpers.
- 📥 File responses — download, streaming, HTTP range, ETag & 304 Not Modified, content headers.
- 🗜️ Archives, uploads & encryption — zip/tar, file upload and OpenSSL file encryption.
- 🛡️ CSRF, HTTP cache and language negotiation on top of Slim & Twig.
- 🖼️ Image responses & resizing via `ext-imagick`.
- 🧪 Full unit-test coverage ensuring reliability and maintainability.

💡 Designed to be composable, testable, and compatible with any PHP 8.4+ project.

## 📦 Installation

> **Requires [PHP 8.4+](https://php.net/releases/)** and the **`ext-imagick`** extension.

Install via [Composer](https://getcomposer.org):
```bash
composer require oihana/php-controllers
```

## ✅ Tests & coverage

Run the full unit-test suite (PHPUnit, strict mode):
```bash
composer test
```

Run a single test case:
```bash
./vendor/bin/phpunit --filter PaginationTraitTest
```

Measure coverage (requires Xdebug or PCOV):
```bash
composer coverage        # text + Clover + HTML under build/coverage/
composer coverage:md     # readable Markdown summary (build/coverage/COVERAGE.md)
```

The suite runs in **strict mode** and targets **100% line coverage**.

## 🧾 License

This project is licensed under the [Mozilla Public License 2.0 (MPL-2.0)](https://www.mozilla.org/en-US/MPL/2.0/).

## 👤 About the author

* Author : Marc ALCARAZ (aka eKameleon)
* Mail : marc@ooop.fr
* Website : http://www.ooop.fr

## 🛠️ Generate the Documentation

We use [phpDocumentor](https://phpdoc.org/) to generate the documentation into the ./docs folder.

### Usage
Run the command : 
```bash
composer doc
```

## 🔗 Related packages

- [oihana/php-models](https://github.com/BcommeBois/oihana-php-models) – document/PDO models used by the data controllers.
- [oihana/php-files](https://github.com/BcommeBois/oihana-php-files) – file, archive and encryption helpers behind the file responses.
- [oihana/php-core](https://github.com/BcommeBois/oihana-php-core) – core helpers and utilities used by this library.
- [oihana/php-enums](https://github.com/BcommeBois/oihana-php-enums) – strongly-typed constant enumerations for PHP.
