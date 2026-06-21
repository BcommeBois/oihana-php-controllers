# Enumerations

Recurring option keys, parameter names and projection signals are exposed as
**typed constants** grouped in helper classes, instead of *magic strings*
scattered through the codebase. These classes are *not* native PHP `enum`s: each
one uses `oihana\reflect\traits\ConstantsTrait`, so the constants stay plain
`string` values you can pass anywhere, while still being introspectable.

```php
use oihana\controllers\enums\ControllerParam;

// Use a constant instead of the raw "skin" string:
$skin = $params[ ControllerParam::SKIN ] ?? ControllerParam::SKIN_DEFAULT ;
```

Because they pull in `ConstantsTrait`, every class below also offers a small
reflection API to enumerate or validate its values:

```php
ControllerParam::enums();           // string[] — all declared constant values
ControllerParam::getConstants();    // array<string,string> — name => value
ControllerParam::getConstant('x');  // resolve a value back to its constant name
ControllerParam::includes('skin');  // bool — is this a known value?
```

> The exact helper set comes from `oihana\reflect\traits\ConstantsTrait`;
> `enums()` is the most commonly used (it returns the list of constant values,
> handy for whitelists).

## `ConditionalRequestOption`

`oihana\controllers\enums\ConditionalRequestOption` — the option keys accepted
by `ConditionalRequestTrait::conditionalFileResponse()`. These keys drive how
the validating `ETag` is built. See [File responses](files.md).

| Constant | Value | Meaning |
|---|---|---|
| `ConditionalRequestOption::HASH_CONTENT` | `'hashContent'` | Derive the `ETag` from the file content (`md5_file()`) instead of its metadata (`mtime`-`size`). Reads the whole file. Default: `false`. |
| `ConditionalRequestOption::WEAK` | `'weak'` | Emit the `ETag` as a weak validator (`W/"..."`). Default: `false` (strong). |

## `ControllerParam`

`oihana\controllers\enums\ControllerParam` — the catalogue of common controller
parameters accepted by controller constructors and configuration arrays. The
constants live in `oihana\controllers\enums\traits\ControllerParamTrait` and are
exposed through the `ControllerParam` class, which also pulls in `ConstantsTrait`
(for introspection) and `xyz\oihana\schema\constants\traits\PaginationTrait`
(for the pagination keys). Keeping the keys in a trait lets them be reused by
more specialized parameter enumerations. See [Parameters](params.md).

| Constant | Value | Meaning |
|---|---|---|
| `ControllerParam::API` | `'api'` | The `api` parameter (see `ApiTrait`). |
| `ControllerParam::APP` | `'app'` | The `app` parameter (see `AppTrait`). |
| `ControllerParam::ACTIVE` | `'active'` | The `active` parameter. |
| `ControllerParam::ALL` | `'all'` | The `all` parameter. |
| `ControllerParam::ARGS` | `'args'` | The `args` parameter. |
| `ControllerParam::BASE_URL` | `'baseUrl'` | The `baseUrl` parameter. |
| `ControllerParam::BENCH` | `'bench'` | The `bench` parameter. |
| `ControllerParam::CAPABILITIES` | `'capabilities'` | Capabilities configuration block — maps param names (`SKIN`, `FILTER`, …) to per-param capability declarations. |
| `ControllerParam::CAPABILITIES_ENABLED` | `'capabilitiesEnabled'` | Kill-switch to disable the whole capability enforcement block. Defaults to `true` when the `CAPABILITIES` block is present. |
| `ControllerParam::CBOR_SERIALIZE_OPTIONS` | `'cborSerializeOptions'` | The `cborSerializeOptions` parameter (see `CborTrait`). |
| `ControllerParam::CONTROLLER` | `'controller'` | The `controller` parameter. |
| `ControllerParam::DATE_FORMAT` | `'dateFormat'` | The `dateFormat` parameter. |
| `ControllerParam::DOCUMENT_KEY` | `'documentKey'` | The `documentKey` parameter. |
| `ControllerParam::CUSTOM_RULES` | `'customRules'` | The `customRules` parameter. |
| `ControllerParam::FACETS` | `'facets'` | The `facets` parameter. |
| `ControllerParam::FIELDS` | `'fields'` | The `fields` parameter. |
| `ControllerParam::FILTER` | `'filter'` | The `filter` parameter. |
| `ControllerParam::FORCE_URL` | `'forceUrl'` | The `forceUrl` parameter. |
| `ControllerParam::FULL_PATH` | `'fullPath'` | The `fullPath` parameter. |
| `ControllerParam::GROUP_BY` | `'groupBy'` | The `groupBy` parameter. |
| `ControllerParam::HAS_TOTAL` | `'hasTotal'` | The `hasTotal` parameter. |
| `ControllerParam::HTTP_CACHE` | `'httpCache'` | The `httpCache` parameter. |
| `ControllerParam::ID` | `'id'` | The `id` parameter. |
| `ControllerParam::IDS` | `'ids'` | The `ids` parameter. |
| `ControllerParam::INTERVAL` | `'interval'` | The `interval` parameter. |
| `ControllerParam::INTERVAL_DEFAULT` | `'intervalDefault'` | The `intervalDefault` parameter. |
| `ControllerParam::JSON_SERIALIZE_OPTIONS` | `'jsonSerializeOptions'` | The `jsonSerializeOptions` parameter (see `JsonOptionsTrait`). |
| `ControllerParam::JSON_OPTIONS` | `'jsonOptions'` | The `jsonOptions` parameter (see `JsonOptionsTrait`). |
| `ControllerParam::KEY` | `'key'` | The `key` parameter. |
| `ControllerParam::LANG` | `'lang'` | The `lang` parameter. |
| `ControllerParam::LANGUAGES` | `'languages'` | The `languages` parameter. |
| `ControllerParam::LIST` | `'list'` | The `list` parameter. |
| `ControllerParam::MARGIN` | `'margin'` | The `margin` parameter. |
| `ControllerParam::MOCK` | `'mock'` | The `mock` parameter. |
| `ControllerParam::MODEL` | `'model'` | The `model` parameter. |
| `ControllerParam::ORDER` | `'order'` | The `order` parameter. |
| `ControllerParam::ORDERS` | `'orders'` | The `orders` parameter. |
| `ControllerParam::OWNER` | `'owner'` | The `owner` parameter. |
| `ControllerParam::OWNER_PATH` | `'ownerPath'` | The `ownerPath` parameter. |
| `ControllerParam::PAGINATION` | `'pagination'` | The `pagination` parameter (see `PaginationTrait`). |
| `ControllerParam::PARAMS` | `'params'` | The `params` parameter. |
| `ControllerParam::PARAMS_STRATEGY` | `'paramsStrategy'` | The `paramsStrategy` parameter. |
| `ControllerParam::PATH` | `'path'` | The `path` parameter. |
| `ControllerParam::PAYLOAD` | `'payload'` | The `payload` parameter (see `PaginationTrait`). |
| `ControllerParam::PAYLOADS` | `'payloads'` | The `payloads` parameter (see `PaginationTrait`). |
| `ControllerParam::QUANTITY` | `'quantity'` | The `quantity` parameter. |
| `ControllerParam::REDIRECTS` | `'redirects'` | The `redirects` parameter. |
| `ControllerParam::ROUTER` | `'router'` | The `router` parameter. |
| `ControllerParam::RULES` | `'rules'` | The `rules` parameter. |
| `ControllerParam::SANITIZE` | `'sanitize'` | The `sanitize` parameter. |
| `ControllerParam::SCHEMA` | `'schema'` | The `schema` parameter. |
| `ControllerParam::SEARCH` | `'search'` | The `search` parameter. |
| `ControllerParam::SKIN` | `'skin'` | The `skin` parameter. |
| `ControllerParam::SKIN_DEFAULT` | `'skinDefault'` | The `skinDefault` parameter. |
| `ControllerParam::SKIN_METHODS` | `'skinMethods'` | The `skinMethods` parameter. |
| `ControllerParam::SKINS` | `'skins'` | The `skins` parameter. |
| `ControllerParam::SORT` | `'sort'` | The `sort` parameter. |
| `ControllerParam::SORT_DEFAULT` | `'sortDefault'` | The `sortDefault` parameter. |
| `ControllerParam::STATUS` | `'status'` | The `status` parameter. |
| `ControllerParam::TIMEZONE` | `'timezone'` | The `timezone` parameter. |
| `ControllerParam::TIMEZONE_DEFAULT` | `'timezoneDefault'` | The `timezoneDefault` parameter. |
| `ControllerParam::TYPE` | `'type'` | The `type` parameter. |
| `ControllerParam::TWIG` | `'twig'` | The `twig` parameter. |
| `ControllerParam::URL` | `'url'` | The `url` parameter. |
| `ControllerParam::VALIDATOR` | `'validator'` | The `validator` parameter. |
| `ControllerParam::VALUE` | `'value'` | The `value` parameter. |

> The pagination keys (`PAGINATION`, `PAYLOAD`, `PAYLOADS`, …) are inherited
> from `xyz\oihana\schema\constants\traits\PaginationTrait`, composed into
> `ControllerParam` alongside `ControllerParamTrait`.

## `FileResponseOption`

`oihana\controllers\enums\FileResponseOption` — the option keys accepted by the
file/binary response helpers (e.g. `FileTrait::fileResponse()`). These keys
drive which content headers a download response emits. See
[File responses](files.md).

| Constant | Value | Meaning |
|---|---|---|
| `FileResponseOption::CONTENT_DISPOSITION` | `'contentDisposition'` | The `Content-Disposition` header value sent when `USE_CONTENT_DISPOSITION` is enabled. |
| `FileResponseOption::FORMAT` | `'format'` | The output format (e.g. `jpg`) used to build the `image/<format>` content type. Default: `jpg`. |
| `FileResponseOption::USE_CONTENT_DISPOSITION` | `'useContentDisposition'` | Add a `Content-Disposition` header to the response. Default: `false`. |
| `FileResponseOption::USE_CONTENT_LENGTH` | `'useContentLength'` | Add a `Content-Length` header (the file size) to the response. Default: `false`. |
| `FileResponseOption::USE_CONTENT_TYPE` | `'useContentType'` | Add a `Content-Type` header (the detected MIME type) to the response. Default: `false`. |

## `ImagickResponseOption`

`oihana\controllers\enums\ImagickResponseOption` — the Imagick transform option
keys accepted by `ImageTrait::imagickResponse()`. See [File responses](files.md).

| Constant | Value | Meaning |
|---|---|---|
| `ImagickResponseOption::COMPRESSION` | `'compression'` | The Imagick compression constant to apply (e.g. `Imagick::COMPRESSION_JPEG`). |
| `ImagickResponseOption::GRAY` | `'gray'` | Desaturate the image to grayscale. Default: `false`. |
| `ImagickResponseOption::QUALITY` | `'quality'` | The compression quality (`0`-`100`). Default: `70`. |
| `ImagickResponseOption::STRIP` | `'strip'` | Strip the image of profiles and comments. Default: `false`. |

## `ResizeOption`

`oihana\controllers\enums\ResizeOption` — the option keys accepted by
`oihana\controllers\traits\ImageTrait::resize()` (and the image geometry keys).
See [File responses](files.md).

| Constant | Value | Meaning |
|---|---|---|
| `ResizeOption::HEIGHT` | `'height'` | The current image height, as returned by `Imagick::getImageGeometry()`. |
| `ResizeOption::MAX_HEIGHT` | `'maxHeight'` | The maximum allowed height; larger images are scaled down. Default: `1200`. |
| `ResizeOption::MAX_WIDTH` | `'maxWidth'` | The maximum allowed width; larger images are scaled down. Default: `1920`. |
| `ResizeOption::WIDTH` | `'width'` | The current image width, as returned by `Imagick::getImageGeometry()`. |

## `Skin`

`oihana\controllers\enums\Skin` — the catalogue of data *skins*. A skin is a
named projection that selects which fields a document exposes through the HTTP
surface. Controllers whitelist the skins they accept via their `SKINS` list and
resolve the requested one through
`oihana\controllers\traits\prepare\PrepareSkin`. The constants live in the
companion trait `oihana\controllers\enums\traits\SkinTrait` (which itself pulls
in `ConstantsTrait`) and are exposed through the `Skin` class, so the catalogue
can be reused by more specialized skin enumerations.

| Constant | Value | Meaning |
|---|---|---|
| `Skin::AUDIOS` | `'audios'` | Projection focused on the audio resources of a document. |
| `Skin::COMPACT` | `'compact'` | Reduced projection exposing only the most essential fields. |
| `Skin::DEFAULT` | `'default'` | The default projection applied when no skin is requested. |
| `Skin::EXTEND` | `'extend'` | Extended projection enriching the default set with extra fields. |
| `Skin::FULL` | `'full'` | Full projection exposing every public field of the document. |
| `Skin::INTERNAL` | `'internal'` | Internal projection — exposes server-only fields that must **never** leak through the public HTTP surface. **Invariant: never register `Skin::INTERNAL` in a controller's `SKINS` list** (see note below). |
| `Skin::LIST` | `'list'` | Projection optimized for list/collection rendering. |
| `Skin::MAIN` | `'main'` | Projection exposing the main fields of the document. |
| `Skin::MAP` | `'map'` | Projection focused on the geographic/map data of a document. |
| `Skin::NORMAL` | `'normal'` | The standard projection of a document. |
| `Skin::PHOTOS` | `'photos'` | Projection focused on the photo resources of a document. |
| `Skin::SEARCH` | `'search'` | Projection optimized for search-result rendering. |
| `Skin::VIDEOS` | `'videos'` | Projection focused on the video resources of a document. |

> **Security invariant.** `Skin::INTERNAL` must never appear in any controller's
> `SKINS` list. `PrepareSkin::isValidSkin()` rejects any skin not in that list
> and falls back to the default, so as long as `INTERNAL` stays out, no HTTP
> caller can request it via `?skin=internal`. Server-side code may still call
> `model->get([ SKIN => INTERNAL ])` directly — those calls are trusted because
> they originate from server PHP, not the HTTP surface.

## `TwigParam`

`oihana\controllers\enums\TwigParam` — the parameter keys used when configuring
or rendering Twig views. See [Twig](twig.md).

| Constant | Value | Meaning |
|---|---|---|
| `TwigParam::BACKGROUND_COLOR` | `'backgroundColor'` | The background color. |
| `TwigParam::FULL_PATH` | `'fullPath'` | The full template path. |
| `TwigParam::LOGO` | `'logo'` | The logo asset. |
| `TwigParam::LOGO_DARK` | `'logoDark'` | The dark-mode logo asset. |
| `TwigParam::PATTERN_COLOR` | `'patternColor'` | The pattern color. |
| `TwigParam::TWIG` | `'twig'` | The Twig environment / instance. |

## `UploadOption`

`oihana\controllers\enums\UploadOption` — the option keys accepted by the upload
helpers (`oihana\controllers\traits\UploadTrait`). See
[Archives & uploads](archives-uploads.md).

| Constant | Value | Meaning |
|---|---|---|
| `UploadOption::ALLOWED_MIME_TYPES` | `'allowedMimeTypes'` | Whitelist of allowed MIME types (array of strings or arrays of strings). When set, the stored file is validated with `oihana\files\validateMimeType()`. |
| `UploadOption::FILENAME` | `'filename'` | Override the stored file name (single upload only). Defaults to the sanitized client file name. |
| `UploadOption::MAX_SIZE` | `'maxSize'` | Maximum allowed file size in bytes. Larger uploads are rejected. Default: no limit. |
| `UploadOption::OVERWRITE` | `'overwrite'` | Whether an existing target file may be overwritten. Default: `false`. |

## See also

- [Parameters](params.md) — typed request-parameter extraction and the `prepare` strategies.
- [File responses](files.md) — download, streaming, HTTP range, ETag / 304, encryption, images.
- [Archives & uploads](archives-uploads.md) — zip/tar archives and file uploads.
- [Documentation index](README.md) — back to the table of contents.
