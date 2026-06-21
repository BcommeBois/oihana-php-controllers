# Archives & uploads

Two complementary traits cover the file-bundle lifecycle of an HTTP controller: `ArchiveTrait` produces (and extracts) **zip/tar archives**, while `UploadTrait` **receives uploaded files**, validates them and stores them on disk.

Both build on the [`oihana/php-files`](https://packagist.org/packages/oihana/php-files) package, which performs the actual archive creation/extraction and guards against path traversal (Zip Slip) and decompression bombs. The traits only add the PSR-7 plumbing: building download responses, sanitizing names, mapping errors to messages.

## `ArchiveTrait`

`oihana\controllers\traits\ArchiveTrait` bundles files and/or directories into a tar or zip archive, streams it as a PSR-7 download response, and can extract incoming archives back to disk. Archive creation is delegated to the `oihana\files\archive` tar/zip helpers; header emission, streaming and temporary-file cleanup are shared through a single private `archiveDownload()` method.

### Building archive responses

| Method | Role |
|---|---|
| `zipResponse( ?Request $request, Response $response, array $files, string $archive, string $path, array $options = [] )` | Bundle `$files` into a ZIP and stream it. |
| `tarResponse( ?Request $request, Response $response, string\|array $paths, string $archive, ?string $compression = CompressionType::GZIP, array $options = [] )` | Bundle `$paths` into a tar (optionally compressed) and stream it. |

Key points:

- **`zipResponse()`** prepends `$path` to every entry in `$files` (`$path` is the preserved root), so each archived entry keeps its name relative to `$path`. The emitted `Content-Type` is `FileMimeType::ZIP`.
- **`tarResponse()`** supports only three compressions from `oihana\files\enums\CompressionType`: `GZIP` (default → `application/gzip`), `BZIP2` (→ `application/x-bzip2`) and `NONE` (→ `application/x-tar`). Any other value (e.g. `CompressionType::ZIP`) raises an `UnsupportedCompressionException`, reported as a `500` response.
- The `$options` array accepts the same header switches as the file-response helpers — `oihana\controllers\enums\FileResponseOption` keys such as `USE_CONTENT_TYPE`, `USE_CONTENT_LENGTH` and `CONTENT_DISPOSITION`.
- Both helpers always add `Pragma: no-cache` and `Expires: 0`, write the archive bytes into the response body, then delete the temporary archive file (the content is already streamed).
- On any failure during creation, the helper returns `$this->fail( $request, $response, 500, $message )`.

```php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\enums\FileResponseOption;
use oihana\controllers\traits\ArchiveTrait;
use oihana\files\enums\CompressionType;

class ExportController
{
    use ArchiveTrait ;

    public function downloadZip( Request $request, Response $response ) : Response
    {
        // entries are resolved as $path . $name => /var/data/report.csv, /var/data/notes.txt
        return $this->zipResponse
        (
            $request ,
            $response ,
            [ 'report.csv' , 'notes.txt' ] , // names relative to $path
            '/tmp/bundle.zip' ,              // archive built here, then removed after streaming
            '/var/data/'                     // preserved root prepended to each entry
        ) ;
    }

    public function downloadTarGz( Request $request, Response $response ) : Response
    {
        return $this->tarResponse
        (
            $request ,
            $response ,
            [ '/var/data/report.csv' ] ,
            '/tmp/bundle.tar.gz' ,
            CompressionType::GZIP ,                                  // default; also BZIP2 or NONE
            [ FileResponseOption::CONTENT_DISPOSITION => 'inline; filename=bundle.tar.gz' ]
        ) ;
    }
}
```

### Extracting archives

| Method | Role |
|---|---|
| `extractTar( string $archive, string $destDir, array $options = [] ) : true\|array` | Extract a tar archive into `$destDir`. |
| `extractZip( string $archive, string $destDir, array $options = [] ) : true\|array` | Extract a ZIP archive into `$destDir`. |

Both are thin wrappers over `oihana\files\archive\tar\untar()` / `oihana\files\archive\zip\unzip()`, which already guard against Zip Slip and decompression bombs. They return `true` on success, or the list of entries when a dry run is requested. Options are keyed by `oihana\files\enums\TarOption` / `ZipOption` — for ZIP: `dryRun`, `overwrite`, `maxEntries`, `maxSize`, `keepPermissions`.

```php
use oihana\controllers\traits\ArchiveTrait;
use oihana\files\enums\ZipOption;
use oihana\files\exceptions\FileException;

class ImportController
{
    use ArchiveTrait ;

    public function extract() : void
    {
        // Dry run: list the entries without writing anything to disk.
        $entries = $this->extractZip( '/tmp/bundle.zip' , '/var/import' , [ ZipOption::DRY_RUN => true ] ) ;
        // [ 'a.txt' , 'sub/b.txt' , ... ]

        // Real extraction, capped at 100 entries and 50 MB total.
        try
        {
            $this->extractZip
            (
                '/tmp/bundle.zip' ,
                '/var/import' ,
                [ ZipOption::MAX_ENTRIES => 100 , ZipOption::MAX_SIZE => 50 * 1024 * 1024 ]
            ) ;
        }
        catch ( FileException $e )
        {
            // thrown on a Zip Slip entry, a tripped bomb guard, or an invalid archive
        }
    }
}
```

## `UploadTrait`

`oihana\controllers\traits\UploadTrait` receives PSR-7 file uploads, validates them and moves them into a destination directory. Each helper delegates the per-file work — error/size/MIME checks, name sanitization, overwrite guard, move — to a shared private `storeUploadedFile()` method and throws a `oihana\files\exceptions\FileException` on any failure, leaving the response shaping to the controller.

| Method | Returns | Role |
|---|---|---|
| `receiveUpload( Request $request, string $field, string $destDir, array $options = [] )` | `string` | Receive a single file for `$field`, returning the absolute stored path. |
| `receiveUploads( Request $request, string $field, string $destDir, array $options = [] )` | `string[]` | Receive an array of files for `$field`, returning the stored paths. |

### `UploadOption` keys

Options come from `oihana\controllers\enums\UploadOption`:

| Constant | Value | Effect |
|---|---|---|
| `UploadOption::ALLOWED_MIME_TYPES` | `'allowedMimeTypes'` | Whitelist of allowed MIME types. When set, the moved file is validated with `oihana\files\validateMimeType()`; on rejection the stored file is deleted and the exception propagates. |
| `UploadOption::FILENAME` | `'filename'` | Override the stored file name (single upload only). Defaults to the sanitized client name. |
| `UploadOption::MAX_SIZE` | `'maxSize'` | Maximum allowed size in bytes. Larger uploads are rejected. Default: no limit. |
| `UploadOption::OVERWRITE` | `'overwrite'` | Whether an existing target may be overwritten. Default: `false`. |

### Validation rules

`storeUploadedFile()` enforces, in order:

1. **Upload error** — if `getError()` is not `UPLOAD_ERR_OK`, a `FileException` carries a per-code message (see below).
2. **Size** — when `MAX_SIZE` is an int and the reported size exceeds it, the upload is rejected.
3. **Filename sanitization** — the name is run through `basename()`, so path components are stripped. A client name like `../../evil.png` is stored as `evil.png`. An empty resulting name is rejected.
4. **Destination** — `$destDir` must be an existing directory, otherwise a `FileException` is thrown.
5. **Overwrite guard** — if the target already exists and `OVERWRITE` is not `true`, the upload is rejected.
6. **MIME whitelist** — after the move, if `ALLOWED_MIME_TYPES` is a non-empty array, the stored file is validated; a disallowed type deletes the file and re-throws.

> `receiveUploads()` ignores `UploadOption::FILENAME`: each file keeps its own sanitized client name to avoid collisions. A field that is missing, not an array, or contains a non-`UploadedFileInterface` entry raises a `FileException`.

### Per-error-code messages

When `getError()` is not `UPLOAD_ERR_OK`, the message is mapped from the PHP `UPLOAD_ERR_*` code:

| Code | Message contains |
|---|---|
| `UPLOAD_ERR_INI_SIZE` | `upload_max_filesize` |
| `UPLOAD_ERR_FORM_SIZE` | `MAX_FILE_SIZE` |
| `UPLOAD_ERR_PARTIAL` | `partially` uploaded |
| `UPLOAD_ERR_NO_FILE` | `No file` was uploaded |
| `UPLOAD_ERR_NO_TMP_DIR` | missing a `temporary folder` |
| `UPLOAD_ERR_CANT_WRITE` | failed to `write` to disk |
| `UPLOAD_ERR_EXTENSION` | a PHP `extension` stopped the upload |
| any other code | `The file upload failed (error code N).` |

### Example

```php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\enums\UploadOption;
use oihana\controllers\traits\UploadTrait;
use oihana\files\enums\ImageMimeType;
use oihana\files\exceptions\FileException;

class MediaController
{
    use UploadTrait ;

    public function uploadAvatar( Request $request, Response $response ) : Response
    {
        try
        {
            $path = $this->receiveUpload
            (
                $request ,
                'avatar' ,    // the uploaded-file field name
                '/var/uploads' ,
                [
                    UploadOption::ALLOWED_MIME_TYPES => [ ImageMimeType::PNG ] ,
                    UploadOption::MAX_SIZE           => 2 * 1024 * 1024 , // 2 MB
                    UploadOption::FILENAME           => 'avatar.png' ,    // forced stored name
                    UploadOption::OVERWRITE          => true ,
                ]
            ) ;

            return $response->withStatus( 201 ) ; // $path => /var/uploads/avatar.png
        }
        catch ( FileException $e )
        {
            // missing field, upload error, too large, disallowed MIME, missing dir, …
            return $response->withStatus( 400 ) ;
        }
    }

    public function uploadGallery( Request $request, Response $response ) : Response
    {
        // FILENAME is ignored here: each file keeps its own sanitized client name.
        $paths = $this->receiveUploads( $request , 'docs' , '/var/uploads' ) ;
        // [ '/var/uploads/a.png' , '/var/uploads/b.png' ]

        return $response->withStatus( 201 ) ;
    }
}
```

## See also

- [File responses](files.md) — download, streaming, HTTP range, ETag / 304, encryption, images.
- [Enumerations](enums.md) — the typed-constant option classes.
- [Documentation index](README.md) — back to the table of contents.
