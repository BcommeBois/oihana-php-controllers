# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-07-23

### Added
- `helpers\injectRouteValues()` — returns the request whose parsed body carries
  the given route placeholders, forced over whatever the caller supplied.
  A sub-resource route such as `/owners/{ownerId}/items` already names part of
  the payload: the body should neither have to repeat it nor be able to
  contradict it. Until now a controller could read a value from the query string
  or from the body (`getParam()` and its typed variants), but never from the
  route, so imposing a URL-borne value meant hand-rolling it in every controller.
  - Takes a binding map (`placeholder => body field`), so a route imposing
    several fields costs one entry each, not one call each.
  - The route always wins: a value supplied by the caller on a bound field is
    overwritten, never merged.
  - Body fields support dot notation (`'owner.id'`), like the rest of the helper
    family: the value is written at the designated depth, the missing levels
    being created and the sibling keys left alone.
  - Only a non-empty string or an int is injected. Anything else — a missing,
    empty or non-scalar placeholder, or an empty target field — is skipped, the
    route saying nothing about that field, so the caller's value stands. When no
    binding applies, the request is returned untouched, without cloning.
  - Bodies decoded as `stdClass` are accepted (normalized through
    `toAssociativeArray()`, like `getBodyParam()`); the rewritten body is always
    an associative array. Route values are cast to string, a placeholder always
    being text in the URL.
  - Wired via composer `autoload.files`, bringing the free-function helpers to 21.
- Unit tests for the new helper (null request, injection into an empty / filled
  body, route winning over the supplied value, several bindings at once, dotted
  fields, object body, and the untouched-request cases).

## [1.0.0] - 2026-06-21

First release. The `oihana\controllers` namespace is extracted from
`oihana/php-system` into its own focused HTTP-controller package for PHP 8.4+,
built on [Slim](https://www.slimframework.com/) and [Twig](https://twig.symfony.com/).

### Added
- Project scaffolding: `composer.json`, `phpunit.xml`, `phpdoc.xml`,
  CI and Docs GitHub workflows (with the `imagick`, `zip` and `fileinfo`
  extensions enabled), coverage tooling, phpDocumentor template, README,
  CONTRIBUTING and license.
- Brand assets (logos) under `assets/images/`.
- The `oihana\controllers` library, imported from `oihana/php-system`
  (identical FQNs):
  - `Controller` — the composable base controller wired to a PSR-11 container.
  - Request parameters — `traits\ParamsTrait`, `traits\ParamsStrategyTrait`,
    `traits\PrepareParamTrait` and the `traits\prepare\Prepare*` strategy family.
  - Pagination — `traits\PaginationTrait`, `traits\LimitTrait`,
    `traits\SortAfterTrait`.
  - Responses — `traits\JsonTrait`, `traits\CborTrait`, `traits\StatusTrait`,
    `traits\ApiTrait`.
  - File responses — `traits\FileTrait`, `traits\RangeTrait`,
    `traits\ConditionalRequestTrait` (ETag / 304), `traits\FileEncryptionTrait`,
    `traits\ImageTrait` (`ext-imagick`).
  - Archives & uploads — `traits\ArchiveTrait`, `traits\UploadTrait`.
  - Rendering & i18n — `traits\TwigTrait`, `traits\LanguagesTrait`.
  - Routing — `traits\RouterTrait`, `traits\RedirectsTrait`,
    `traits\BaseUrlTrait`, `traits\PathTrait`, `traits\AppTrait`,
    `traits\CsrfTrait`, `traits\HttpCacheTrait`.
  - Model integration — `traits\ModelCallTrait`, `traits\OutputDocumentsTrait`,
    `traits\CheckOwnerArgumentsTrait`, `traits\ForceDocumentUrlTrait`,
    `traits\ValidatorTrait`, `traits\MockTrait`, `traits\BenchTrait`.
  - `enums\*` — `ConstantsTrait`-based typed-constant option classes
    (`ControllerParam`, `FileResponseOption`, `ConditionalRequestOption`,
    `ResizeOption`, `ImagickResponseOption`, `UploadOption`, `TwigParam`,
    `Skin`), no native enums.
  - 20 request/response free-function helpers under `helpers\*`, wired via
    composer `autoload.files`.
- Unit-test suite imported from `oihana/php-system` (PHPUnit, strict mode),
  plus the `tests\oihana\models\mocks\MockDocumentsModel` fixture used by the
  data-controller tests. **100% line coverage** (943/943 lines, 125/125
  methods, 55/55 classes), 438 tests.
- Bilingual user guide under `wiki/` (English + French): getting started
  (introduction, installation, dependencies), controller, parameters,
  pagination, responses, file responses, archives & uploads, Twig, languages,
  routing, models, helpers, enumerations and a testing guide.

### Changed
- `traits\BenchTrait` and `traits\ImageTrait` no longer depend on the
  `oihana\date` / `oihana\graphics` namespaces (which stay in `oihana/php-system`).
  They now call the `oihana/php-core` 1.2.0 free functions
  `oihana\core\date\humanizeDuration()` and `oihana\core\maths\aspectFit()`
  instead, keeping the controller layer's dependency surface limited to
  `oihana/php-core`. Behaviour is unchanged.
