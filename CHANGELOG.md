# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.0] - 2026-10-18

### 🚨 BREAKING

- **`traits\prepare\PrepareFilter` and `PrepareFacets` — a filter or a facet that cannot be read is
  refused.** A `?filter=` the trait could not read — not JSON, or JSON that is neither an object nor a
  list — was logged as a `warning` and **ignored**: the route answered `200` with the whole, unfiltered
  collection, and the client read it as the filtered one. A forgotten brace was enough :

  ```
  GET /users?filter={"key":"age","val":30    →  every user, 200
  ```

  `?facets=` behaved the same, worse : `?facets=42` was ignored without even a warning, and the warning
  for a `?facets=` that was not JSON logged the facets of `$args`, not the faulty value. A parameter
  declared as a facet (`ControllerParam::PARAMS => [ 'id' => ControllerParam::FACETS ]`) that was not
  JSON was dropped without a word.

  All three now throw an `oihana\exceptions\http\Error400`, whose message reaches the client :

  | request                         | answer                                                               |
  |:--------------------------------|:---------------------------------------------------------------------|
  | `?filter={"key":"age"`          | `400` The filter parameter is not valid JSON.                        |
  | `?filter=42`                    | `400` The filter parameter must be a JSON object or list, int given. |
  | `?facets=[1,2` / `?facets=42`   | `400`, same messages for `facets`                                    |
  | `?id=abc` (declared as a facet) | `400` The id parameter is not valid JSON.                            |

  Still "no filter", as before : an absent parameter, an empty one (`?filter=`, what a blank form field
  sends) and the JSON `null`. A parameter declared as a facet still accepts a JSON scalar (`?id=12`).
  The `warning` lines are gone — the fault is told to the client, not hidden in a log.

  **What breaks.** Requests that used to answer the whole collection now answer `400`. The one most
  likely to show up is an **unquoted identifier on a facet parameter** : `GET /products?id=abc` used to
  return **every product**, and is now refused — write `?id="abc"`, or `?id=["abc","def"]` for several.
  A controller whose `catch` relays the exception code (`HttpStatusCode::fromException()`) answers `400`
  with no change; one that catches `Exception` and answers `500` should let `Error400` through.

- **`traits\UploadTrait` — a stored file no longer takes the name the client gave it.** Without
  `UploadOption::FILENAME`, it kept the client name and extension (through `basename()`): a `photo.php`
  holding a real image passed the type check (`image/png`) and was stored as `photo.php` — code the web
  server runs, if the directory is served. It is now stored under a **random name** and the **extension
  of the type read from its content** (`9f2c4e1a7b3d5f60a8c2e4b6d8f0a1c3.png`). The client name is kept
  on request only (`UploadOption::CLIENT_FILENAME`, below), cleaned.

  **`UploadOption::FILENAME` is kept or refused, never altered**: a name starting with a dot
  (`.htaccess`, `.user.ini`), or whose extension does not match the detected type (`avatar.php` for a
  PNG), throws a `FileException`; a name without extension passes.

  **What breaks.** Code that expected the client name on disk gets a random one — read
  `getClientFilename()` and keep it in the resource, or ask for `CLIENT_FILENAME`. An empty client name
  no longer throws (the name is drawn). A `FILENAME` that lied about its type is now refused.

### Fixed

- **`traits\StatusTrait::fail()` never asks for a status a response refuses.** It kept any code listed
  among the constants of `HttpStatusCode` and fell back on `HttpStatusCode::DEFAULT` otherwise — and
  two of those constants are not HTTP statuses : `DEFAULT` is `0`, `BUSY` is `600`. A PSR-7 response
  accepts 100 to 599 only, so both made the response **throw** `Invalid HTTP status code` instead of
  carrying the error, losing the message it was given.

  Worse, `includes()` let through a code that is no number at all : the SQLSTATE of a database failure,
  `'HY000'`, reads as `0` once cast to an integer, so it passed the guard and became a status of `0`.
  Handing `$e->getCode()` to `fail()` — the natural reflex — therefore broke the response on every
  database error.

  A code is now kept when `HttpStatusCode::getType()` recognises it, which is exactly the 100 to 599
  a response accepts, and anything else answers `500` with its description. A redirection is kept :
  an API retiring a version answers `301` through `fail()`, and a range limited to errors would have
  turned it into a `500`. Five cases are locked down in `StatusTraitTest`, on a response double that
  records the status it is asked for — the previous double accepted any code, which is why the defect
  went unseen.

- **`traits\UploadTrait` — two uploads of the same name no longer overwrite each other.** The guard
  checked that the target did not exist, then moved the file: two uploads of the same name at the same
  time both saw a free name, and the second replaced the first without a word (`rename()` replaces
  silently). The final name is now **reserved in one step** (exclusive creation, `fopen( … , 'x' )`)
  before the file takes it. And `receiveUploads()`, which kept the client names "to avoid collisions",
  refused the second of two `IMG_0001.jpg` — the most common case with phones: each file now gets its
  own name.

### Added

- **`traits\DefaultLangTrait` — the fallback locale, moved here from `oihana/php-arango`.**
  It carries `defaultLang`: the locale a multilingual expression answers on when the
  requested one is absent, or when no language was requested at all. It reads the value
  from an `$init` definition, then from the container entry of the same name, lowercases
  it and refuses a tag that is not one — the sibling of `LanguagesTrait`, whose shape it
  follows exactly.

  **Why it moved.** Nothing in it is about ArangoDB. It is the same sentence `languages`
  and `PrepareLang` already speak in this package: `languages` is the set a request may
  ask for, the requested language is what it asked, and `defaultLang` is what answers
  when it asked for nothing. The three belong side by side, and a consumer wiring a
  fallback locale should not have to depend on a database driver to get one.

  **⚠ Reading the key.** `DefaultLangTrait::DEFAULT_LANG` is a **fatal error** since
  PHP 8.4 — a trait constant cannot be accessed directly. Read it through the class that
  uses the trait (`MyController::DEFAULT_LANG`), or write the literal `'defaultLang'`,
  which is what a configuration file carries anyway.

- **`helpers\isLanguageCode()`** — tells whether a value is a language tag safe to use
  as an identifier: a two or three letter lowercase primary subtag, optionally followed
  by dash-separated subtags (`fr`, `pt-BR`, `zh-Hant-TW`). It travelled with the trait
  above, which validates through it.

  A language tag rarely stays a value: it names a key of a translations map, a directory,
  a template — places where it is written **verbatim** rather than passed as a parameter.
  This is the proof that such an interpolation is harmless, which is why it rejects
  `fr"`, `fr fr`, `fr.name` and `fr_FR` rather than doing its best with them. `FR` is
  refused too, deliberately: callers normalise first, so a tag never reaches storage
  under two spellings.

- **`UploadOption::CLIENT_FILENAME`** — stores the file under the client name, cleaned:
  `oihana\core\strings\slugify()` on the part before the extension (no leading dot, no dot inside,
  ASCII lower-case, at most 200 characters — `Façade Nord (2).JPG` → `facade-nord-2.jpg`,
  `.user.ini` → `user.txt`, `photo.php.png` → `photo-php.png`; nothing left gives a random name), and
  the extension of the detected type — kept when the client's matches, replaced otherwise, added when
  missing. A name already taken is numbered (`-2`, `-3`… up to 100, then a random name), unless
  `OVERWRITE` is set.
- **`UploadOption::EXTENSIONS`** — a `type => '.ext'` table read before the catalogue of
  `oihana/php-files`, for a type it does not know or to prefer one of several
  (`[ 'audio/x-wav' => '.wav' ]`). Types are compared by their reference name
  (`oihana\files\normalizeMimeType()`); an extension must be a dot followed by letters and digits.

### Security

- **`traits\UploadTrait` — a file is checked before it takes its place.** It was moved to its final
  name, then checked (`moveTo()` before `validateMimeType()`): a refused file existed, for an instant,
  under its final name in the destination. It is now moved under a provisional name
  (`.upload-<random>.part` — hidden, no executable extension), its type is read from its content and
  checked, and only then is it renamed (same directory: atomic). A refused file leaves nothing; a
  `.part` left by a crashed process is harmless.
- The extension of a stored file is always the one of its content's type — never the client's, never
  one a `FILENAME` claims against the content. Store uploads outside the served directories, and serve
  `.svg` / `.html` as downloads: both can carry JavaScript. Documented in `wiki/{en,fr}/archives-uploads.md`.


## [1.2.0] - 2026-08-25

### Fixed

- **`helpers\filterLanguages()` no longer invents the languages it was not given.**
  It used to walk the *configured* languages and fill every one absent from the body
  with a null — so a partial edit was a full replacement in disguise: a caller
  correcting a French label had the English one written to null, and read a `200`
  with nothing to warn them. Nothing in the response could tell "cleared" from
  "untouched", which is what kept the defect silent.

  The helper now walks the languages **actually received**, filtered by the allowed
  list. What a body does not name is not touched — the rule the rest of a partial
  write already follows, and the only one that survives a language being added to a
  project: a client that knows nothing of the newcomer leaves it standing instead of
  wiping it.

  - **An empty string is normalized to `null`.** A label that exists but says nothing
    states no more than a missing label, and keeping both shapes forces every reader
    to test for both. The normalization runs *after* the sanitize callback, so a value
    that sanitizing empties is cleared rather than stored as an empty label.
  - **A null `$languages` finally applies no filtering**, as the signature has always
    documented — walking a null list used to fail outright.
  - Unchanged: an input holding no usable language (an empty map, or only unknown
    languages) still returns `null`, which a payload layer reads as an explicit null.
    "Touch nothing" is expressed by omitting the property, never by an empty map.

  **Migration.** A caller that relied on every configured language coming back — to
  index the result, or to write the whole map in one pass — must now read the keys it
  actually got. Two tests of `getParamI18n()` pinned the old shape and were rewritten
  against the new contract; nothing else in the suite moved (456 tests green).

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
