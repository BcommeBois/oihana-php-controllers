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

`oihana\controllers\traits\UploadTrait` receives PSR-7 file uploads, validates them and stores them in a destination directory. Each helper delegates the per-file work to a shared private `storeUploadedFile()` method and throws an `oihana\files\exceptions\FileException` on failure, leaving the response shaping to the controller.

| Method | Returns | Purpose |
|---|---|---|
| `receiveUpload( Request $request, string $field, string $destDir, array $options = [] )` | `string` | Receives a single file for `$field`, returning the stored absolute path. |
| `receiveUploads( Request $request, string $field, string $destDir, array $options = [] )` | `string[]` | Receives an array of files for `$field`, returning the stored paths. |

### Why a file is never stored under the client's name

The client chooses the name of its file freely, extension included. A `photo.php` holding a **real image** — with PHP hidden inside — passes the type check (`image/png`): stored under its name in a directory the web server serves, opening `…/photo.php` would run its code. And two phones very often send two `IMG_0001.jpg`: the second collided with the first.

So the trait stores by default under a **random name**, followed by the **extension of the type read from the content**: `9f2c4e1a7b3d5f60a8c2e4b6d8f0a1c3.jpg`. The original name, when it has to be shown, is read from the request (`getClientFilename()`) and kept in the resource, not in the file name.

> 💡 Store files **outside the directories the web server serves**, and serve them through a route that checks the rights and sets the recorded `Content-Type`. `.svg` and `.html` can hold JavaScript: serve them as downloads (`Content-Disposition: attachment`) or do not allow them.

### The three ways to name the file

| Mode | Stored name | When the name is taken |
|---|---|---|
| **default** | 32 hexadecimal characters + the extension of the detected type | cannot happen in practice |
| `CLIENT_FILENAME => true` | the client name cleaned by `slugify()` (`oihana/php-core`), with the extension of the type: `Façade Nord (2).JPG` → `facade-nord-2.jpg`, `.user.ini` → `user.txt`, `photo.php.png` → `photo-php.png`; nothing readable (`日本語.png`) → a random name | **numbered**: `img-0001-2.jpg`, `-3`… up to 100, then a random name; `OVERWRITE` replaces |
| `FILENAME => '…'` | the given name, **kept or refused**, never altered: an extension that does not match the detected type, or a leading dot, is refused; without extension, it passes | refused; `OVERWRITE` replaces |

`FILENAME` wins over `CLIENT_FILENAME`. `receiveUploads()` ignores `FILENAME` (one name cannot serve several files).

### The extension

1. `EXTENSIONS` — the `type => '.ext'` table given by the controller, read first;
2. otherwise the `FileMimeType::getExtension()` catalogue (`oihana/php-files`); when the type has several extensions (`image/jpeg` → `.jpeg`, `.jpg`), the client's is kept if it is one of them, the first one otherwise;
3. an unrecognized content (`application/octet-stream`), or a type the catalogue does not know, gives a **name without extension**.

Types are compared by their reference name (`oihana\files\normalizeMimeType()`): `audio/x-wav` and `audio/wav` are the same format.

### `UploadOption` keys

| Constant | Value | Effect |
|---|---|---|
| `UploadOption::ALLOWED_MIME_TYPES` | `'allowedMimeTypes'` | Whitelist of allowed MIME types, checked on the content **before** the file takes its place; a refused type leaves no file. |
| `UploadOption::CLIENT_FILENAME` | `'clientFilename'` | Stores under the client name, cleaned (see above). Default: `false`. |
| `UploadOption::EXTENSIONS` | `'extensions'` | `type => '.ext'` table read before the catalogue (`[ 'audio/x-wav' => '.wav' ]`). An extension must be a dot followed by letters and digits, otherwise `FileException`. |
| `UploadOption::FILENAME` | `'filename'` | The name of the stored file (single upload), kept or refused. Default: a random name. |
| `UploadOption::MAX_SIZE` | `'maxSize'` | Maximum allowed size in bytes. Default: no limit. |
| `UploadOption::OVERWRITE` | `'overwrite'` | An existing target may be replaced. Default: `false`. |

### The order of operations

`storeUploadedFile()` applies, in order:

1. **Upload error** — when `getError()` is not `UPLOAD_ERR_OK`, a `FileException` carries a code-specific message (see below).
2. **Size** — above `MAX_SIZE`, the upload is rejected.
3. **Options** — an empty `FILENAME` or one starting with a dot, or a malformed `EXTENSIONS` table, is refused **before** anything is moved.
4. **Destination** — `$destDir` must be an existing directory.
5. **Provisional file** — `moveTo()` puts the file under `.upload-<random>.part`: a hidden name, with no executable extension. `move_uploaded_file()` and its check remain.
6. **Type** — read from the content; `ALLOWED_MIME_TYPES` given → checked. Refused → the provisional file is removed.
7. **Final name** — chosen by the mode, then **reserved in one step** (exclusive creation, `fopen( … , 'x' )`): two simultaneous uploads of the same name never overwrite each other silently.
8. **Rename** — the provisional file takes its final name (same directory: an atomic operation). On any failure, the provisional file and the reservation are removed.

> A `.upload-….part` may remain if PHP stops between steps 5 and 8 (a crash of the process): it is harmless, and old ones can be deleted safely.

### Messages by error code

When `getError()` is not `UPLOAD_ERR_OK`, the message is derived from the PHP `UPLOAD_ERR_*` code:

| Code | The message contains |
|---|---|
| `UPLOAD_ERR_INI_SIZE` | `upload_max_filesize` |
| `UPLOAD_ERR_FORM_SIZE` | `MAX_FILE_SIZE` |
| `UPLOAD_ERR_PARTIAL` | file `partially` uploaded |
| `UPLOAD_ERR_NO_FILE` | `No file` uploaded |
| `UPLOAD_ERR_NO_TMP_DIR` | missing `temporary folder` |
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

    public function uploadPhoto( Request $request, Response $response ) : Response
    {
        try
        {
            $path = $this->receiveUpload
            (
                $request ,
                'photo' ,              // the uploaded-file field name
                '/var/app/uploads' ,   // outside the served directories
                [
                    UploadOption::ALLOWED_MIME_TYPES => [ ImageMimeType::PNG , ImageMimeType::JPEG ] ,
                    UploadOption::MAX_SIZE           => 2 * 1024 * 1024 , // 2 MB
                ]
            ) ;
            // $path => /var/app/uploads/9f2c4e1a7b3d5f60a8c2e4b6d8f0a1c3.jpg

            return $response->withStatus( 201 ) ;
        }
        catch ( FileException $e )
        {
            // missing field, upload error, too large, refused type, missing directory, …
            return $response->withStatus( 400 ) ;
        }
    }

    public function uploadAvatar( Request $request, Response $response ) : Response
    {
        // A chosen name: kept, or refused when it lies about the type; replaced when it exists.
        $path = $this->receiveUpload( $request , 'avatar' , '/var/app/avatars/42' ,
        [
            UploadOption::ALLOWED_MIME_TYPES => [ ImageMimeType::PNG ] ,
            UploadOption::FILENAME           => 'avatar.png' ,
            UploadOption::OVERWRITE          => true ,
        ]) ;

        return $response->withStatus( 201 ) ;
    }

    public function uploadGallery( Request $request, Response $response ) : Response
    {
        // The client names, cleaned and numbered: two IMG_0001.png give img-0001.png and img-0001-2.png.
        $paths = $this->receiveUploads( $request , 'photos' , '/var/app/uploads' , [ UploadOption::CLIENT_FILENAME => true ] ) ;

        return $response->withStatus( 201 ) ;
    }
}
```

## See also

- [File responses](files.md) — download, streaming, HTTP range, ETag / 304, encryption, images.
- [Enumerations](enums.md) — the typed-constant option classes.
- [Documentation index](README.md) — back to the table of contents.
