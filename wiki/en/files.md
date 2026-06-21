# File responses

This page covers the traits and helpers that turn a file on disk into an HTTP response: plain **downloads**, **streaming**, **HTTP byte ranges** (seekable media), **conditional requests** (`ETag` / `304 Not Modified`), **OpenSSL encryption**, and **image** transforms.

Every trait validates the file first with `oihana\files\assertFile()` and reports a missing or unreadable file as a `500` response (via `StatusTrait::fail()`) instead of leaking PHP warnings. Each method takes the PSR-7 `$request` / `$response` pair and an `$options` array keyed by the enumerations described below.

## FileTrait

`oihana\controllers\traits\FileTrait` exposes a single method, `fileResponse()`, which streams a file lazily (it is read at emit time through Slim's `StreamFactory`, so large files are never loaded into memory).

```php
public function fileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
```

The `$options` keys come from `oihana\controllers\enums\FileResponseOption`:

| Constant | Key | Default in `fileResponse()` | Role |
|---|---|---|---|
| `FileResponseOption::USE_CONTENT_TYPE` | `useContentType` | `false` | Emit `Content-Type` (detected MIME type). |
| `FileResponseOption::USE_CONTENT_LENGTH` | `useContentLength` | `false` | Emit `Content-Length` (the file size). |
| `FileResponseOption::USE_CONTENT_DISPOSITION` | `useContentDisposition` | `false` | Emit `Content-Disposition`. |
| `FileResponseOption::CONTENT_DISPOSITION` | `contentDisposition` | `attachment; filename=<basename>` | The `Content-Disposition` value to send. |

Content headers are **off by default** here: the caller opts in through `$options`.

```php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\Controller;
use oihana\controllers\traits\FileTrait;
use oihana\controllers\enums\FileResponseOption;

class DownloadController extends Controller
{
    use FileTrait ;

    public function invoice( Request $request , Response $response ) : Response
    {
        return $this->fileResponse( $request , $response , '/var/invoices/2026-04.pdf' ,
        [
            FileResponseOption::USE_CONTENT_TYPE        => true ,
            FileResponseOption::USE_CONTENT_LENGTH      => true ,
            FileResponseOption::USE_CONTENT_DISPOSITION => true ,
            FileResponseOption::CONTENT_DISPOSITION     => 'attachment; filename=invoice.pdf' ,
        ]) ;
    }
}
```

## HTTP range

`oihana\controllers\traits\RangeTrait::rangeFileResponse()` serves a file while honoring the request `Range` header, which is what makes downloads resumable and media seekable.

```php
public function rangeFileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
```

The response always advertises `Accept-Ranges: bytes`. The header is parsed by `oihana\controllers\helpers\parseRangeHeader()`, which understands the common single-range forms and returns one of three things:

- `[start, end]` — a satisfiable single range → `206 Partial Content` with a `Content-Range` header;
- `false` — a range is present but unsatisfiable → `416 Range Not Satisfiable`;
- `null` — no usable single range (absent, malformed, or multi-range) → the full file, streamed as `200`.

```php
use function oihana\controllers\helpers\parseRangeHeader;

parseRangeHeader( 'bytes=0-4'    , 11 ) ; // [0, 4]   -> 206
parseRangeHeader( 'bytes=6-'     , 11 ) ; // [6, 10]  -> 206 (open-ended)
parseRangeHeader( 'bytes=-5'     , 11 ) ; // [6, 10]  -> 206 (last 5 bytes)
parseRangeHeader( 'bytes=0-9999' , 11 ) ; // [0, 10]  -> 206 (end clamped)
parseRangeHeader( 'bytes=99-'    , 11 ) ; // false    -> 416 (unsatisfiable)
parseRangeHeader( 'bytes=0-2,5-' , 11 ) ; // null     -> 200 (multi-range ignored)
parseRangeHeader( ''             , 11 ) ; // null     -> 200 (no range)
```

The `$options` array (the same `FileResponseOption` keys) is applied to the full-content `200` response; for a `206` the `Content-Type`, `Content-Length` and `Content-Range` headers are set explicitly.

```php
use oihana\controllers\traits\RangeTrait;

class VideoController extends Controller
{
    use RangeTrait ;

    public function stream( Request $request , Response $response ) : Response
    {
        // "Range: bytes=0-1023" -> 206 Partial Content (first 1 KiB)
        // no Range header       -> 200 with Accept-Ranges: bytes (full file)
        return $this->rangeFileResponse( $request , $response , '/var/media/clip.mp4' ) ;
    }
}
```

## Conditional requests

`oihana\controllers\traits\ConditionalRequestTrait::conditionalFileResponse()` adds validator-based caching. On every call the response carries an `ETag` and a `Last-Modified` header; the request preconditions are then evaluated, with `If-None-Match` taking precedence over `If-Modified-Since` (RFC 7232).

```php
public function conditionalFileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
```

When a precondition matches, a bodyless `304 Not Modified` is returned (still carrying the validators); otherwise the file is streamed as `200`. Beyond `FileResponseOption`, it reads two keys from `oihana\controllers\enums\ConditionalRequestOption`:

| Constant | Key | Default | Role |
|---|---|---|---|
| `ConditionalRequestOption::WEAK` | `weak` | `false` | Emit a weak validator (`W/"..."`) instead of a strong one. |
| `ConditionalRequestOption::HASH_CONTENT` | `hashContent` | `false` | Derive the `ETag` from the content (`md5_file()`) instead of the metadata (`mtime`-`size`). |

The validator is built by `oihana\controllers\helpers\computeETag()`, and incoming `If-None-Match` values are matched with `oihana\controllers\helpers\etagMatches()` (RFC 7232 weak comparison: the `W/` prefix is ignored on both sides, and `*` matches any representation). The download headers are applied through `oihana\controllers\helpers\applyContentHeaders()`, the shared helper used by every file response.

```php
use function oihana\controllers\helpers\computeETag;
use function oihana\controllers\helpers\etagMatches;

$etag = computeETag( '/var/assets/app.css' ) ;        // "<mtime-hex>-<size-hex>" (strong, metadata)
computeETag( '/var/assets/app.css' , weak: true ) ;   // W/"<mtime-hex>-<size-hex>"
computeETag( '/var/assets/app.css' , hashContent: true ) ; // "<md5>" (strong, exact)

etagMatches( '*'            , $etag ) ; // true  (matches any representation)
etagMatches( 'W/' . $etag   , $etag ) ; // true  (weak comparison)
etagMatches( '"nope"'       , $etag ) ; // false (no match)
```

```php
use oihana\controllers\traits\ConditionalRequestTrait;
use oihana\controllers\enums\ConditionalRequestOption;

class AssetController extends Controller
{
    use ConditionalRequestTrait ;

    public function asset( Request $request , Response $response ) : Response
    {
        // 1st request                          -> 200 with ETag + Last-Modified
        // request with a matching If-None-Match -> 304 Not Modified (empty body)
        return $this->conditionalFileResponse( $request , $response , '/var/assets/app.css' ,
        [
            ConditionalRequestOption::HASH_CONTENT => true , // exact, content-based ETag
        ]) ;
    }
}
```

## Encryption

`oihana\controllers\traits\FileEncryptionTrait` encrypts and decrypts files with OpenSSL, delegating the cryptography to a configured `oihana\files\openssl\OpenSSLFileEncryption`. The passphrase therefore lives in the DI configuration, never in the trait.

The encryption helper is injected through `initializeFileEncryption()` — either from an init array under the `FileEncryptionTrait::FILE_ENCRYPTION` key (`'fileEncryption'`), or from a PSR-11 container that exposes `OpenSSLFileEncryption::class`. Calling a method before initialization throws a `RuntimeException`.

| Method | Returns | Role |
|---|---|---|
| `encryptFile( string $input , ?string $output = null )` | `string` | Encrypt a file; returns the produced path (defaults to `$input` + `.enc`). |
| `decryptFile( string $input , ?string $output = null )` | `string` | Decrypt a file; returns the clear path (defaults to `$input` without `.enc`). |
| `encryptedFileResponse( ?Request , Response , string $file , array $options = [] )` | `Response` | Encrypt then stream the encrypted content as a download. |
| `decryptFileResponse( ?Request , Response , string $file , array $options = [] )` | `Response` | Decrypt then stream the clear content as a download. |

The two `*Response` methods write the produced file into the body, then remove the temporary file. They use `applyContentHeaders()` with headers **on by default**, and accept the `FileResponseOption` keys.

```php
use oihana\controllers\traits\FileEncryptionTrait;
use oihana\files\openssl\OpenSSLFileEncryption;

class VaultController extends Controller
{
    use FileEncryptionTrait ;

    public function download( Request $request , Response $response ) : Response
    {
        $this->initializeFileEncryption(
        [
            FileEncryptionTrait::FILE_ENCRYPTION => new OpenSSLFileEncryption( 'my-passphrase' ) ,
        ]) ;

        // decrypts /var/vault/secret.txt.enc and streams the clear content
        return $this->decryptFileResponse( $request , $response , '/var/vault/secret.txt.enc' ) ;
    }
}
```

When a container is available, the helper can be resolved automatically:

```php
$this->initializeFileEncryption( [] , $container ) ; // uses $container->get( OpenSSLFileEncryption::class )
```

## Images

`oihana\controllers\traits\ImageTrait` serves and transforms images through `ext-imagick`. It streams a raw image with `imageResponse()`, or applies Imagick transforms and returns the encoded blob with `imagickResponse()`.

```php
public function imageResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
public function imagickResponse( Response $response , string|Imagick $image , array $options = [] ) : Response
```

Transform switches come from `oihana\controllers\enums\ImagickResponseOption`, combined with `FileResponseOption` for the header switches (here `useContentType` and `useContentLength` default to `true`):

| Constant | Key | Default | Role |
|---|---|---|---|
| `ImagickResponseOption::COMPRESSION` | `compression` | `Imagick::COMPRESSION_JPEG` | The Imagick compression constant. |
| `ImagickResponseOption::QUALITY` | `quality` | `70` | Compression quality (0–100). |
| `ImagickResponseOption::GRAY` | `gray` | `false` | Desaturate to grayscale. |
| `ImagickResponseOption::STRIP` | `strip` | `false` | Strip profiles and comments. |
| `FileResponseOption::FORMAT` | `format` | `jpg` | Output format, used to set the Imagick format and the `image/<format>` content type. |

The `resize()` method scales an image, using overrides from `oihana\controllers\enums\ResizeOption` (`WIDTH`, `HEIGHT`, `MAX_WIDTH`, `MAX_HEIGHT`; defaults `1920` × `1200`). When only one of width/height is requested, it preserves the aspect ratio via `oihana\core\maths\aspectFit()` so the image is never distorted; passing both `$w` and `$h` forces an exact fit.

```php
use Imagick;

use oihana\controllers\traits\ImageTrait;
use oihana\controllers\enums\ImagickResponseOption;
use oihana\controllers\enums\FileResponseOption;
use oihana\controllers\enums\ResizeOption;

class ThumbController extends Controller
{
    use ImageTrait ;

    public function thumbnail( Request $request , Response $response ) : Response
    {
        $image = new Imagick( '/var/photos/original.png' ) ;

        // ratio-preserving resize to a 320 px width (height derived via aspectFit)
        $image = $this->resize( $image , 320 , null ,
        [
            ResizeOption::MAX_WIDTH => 2000 ,
        ]) ;

        return $this->imagickResponse( $response , $image ,
        [
            FileResponseOption::FORMAT       => 'webp' ,
            ImagickResponseOption::QUALITY   => 80 ,
            ImagickResponseOption::STRIP     => true ,
        ]) ;
    }

    public function raw( Request $request , Response $response ) : Response
    {
        // stream the file untouched, with Content-Type / Content-Length
        return $this->imageResponse( $request , $response , '/var/photos/original.png' ) ;
    }
}
```

Helper accessors round out the trait: `getImageDimensions()`, `getImageWidth()`, `getImageHeight()` (each accepting an `Imagick` instance or a path), `initializeImagePath()` / `getImagePath()` for the images root, and `shadow()` to composite a drop shadow defined as `opacity,sigma,x,y`.

## See also

- [Archives & uploads](archives-uploads.md) — zip/tar archives and file uploads.
- [Responses](responses.md) — JSON, CBOR, status and API output.
- [Enumerations](enums.md) — the typed-constant option classes.
- Back to the [Documentation index](README.md).
