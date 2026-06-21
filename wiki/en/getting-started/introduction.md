# Introduction

`oihana/php-controllers` gathers the HTTP controller building blocks that used to live inside `oihana/php-system`, extracted into a focused package so a project can depend on the controller layer with a clear, declared dependency surface.

It builds on [Slim](https://www.slimframework.com/) (PSR-7/PSR-15) and [Twig](https://twig.symfony.com/): a base `Controller` wired to a DI container, composed from small single-responsibility traits, plus a set of free helper functions for request-parameter extraction and HTTP responses.

## What it provides

| Component | Type | Role |
|---|---|---|
| `Controller` | class | The composable base controller wired to a PSR-11 container. |
| `traits\ParamsTrait` / `ParamsStrategyTrait` | traits | Typed request-parameter extraction and validation strategies. |
| `traits\prepare\Prepare*` | traits | Per-parameter preparation (lang, sort, limit, filter, facets…). |
| `traits\PaginationTrait` / `LimitTrait` | traits | Pagination and limits. |
| `traits\JsonTrait` / `CborTrait` / `StatusTrait` / `ApiTrait` | traits | Response serialization and API output. |
| `traits\FileTrait` / `RangeTrait` / `ConditionalRequestTrait` | traits | File responses: download, streaming, HTTP range, ETag / 304. |
| `traits\ArchiveTrait` / `UploadTrait` / `FileEncryptionTrait` / `ImageTrait` | traits | Archives, uploads, encryption and images. |
| `traits\TwigTrait` / `LanguagesTrait` | traits | Twig rendering and language negotiation. |
| `traits\RouterTrait` / `RedirectsTrait` / `CsrfTrait` / `HttpCacheTrait` | traits | Routing, redirects, CSRF and HTTP caching. |
| `helpers\*` | free functions | Request-parameter and response helpers (autoloaded). |
| `enums\*` | classes | Strongly-typed option keys (no *magic strings*). |

## The *oihana* philosophy

- **PHP 8.4+ only** — typed constants, property hooks, no legacy shims.
- **No *magic strings*** — every option key is a typed constant in a `ConstantsTrait`-based class; the project never uses native PHP enums.
- **Composable** — each trait has a single responsibility and can be combined freely on a controller.
- **Tested** — 100% line coverage, strict PHPUnit mode (see [Tests & coverage](../testing.md)).

## Next steps

- [Installation](installation.md)
- [Dependencies](dependencies.md)
- [Controller](../controller.md)
