# Archives & upload

Deux traits complémentaires couvrent le cycle de vie des paquets de fichiers d'un contrôleur HTTP : `ArchiveTrait` produit (et extrait) des **archives zip/tar**, tandis que `UploadTrait` **reçoit les fichiers téléversés**, les valide et les stocke sur le disque.

Les deux s'appuient sur le paquet [`oihana/php-files`](https://packagist.org/packages/oihana/php-files), qui effectue la création/extraction réelle des archives et se prémunit contre la traversée de chemin (Zip Slip) et les bombes de décompression. Les traits ajoutent seulement la tuyauterie PSR-7 : construction des réponses de téléchargement, assainissement des noms, association des codes d'erreur à des messages.

## `ArchiveTrait`

`oihana\controllers\traits\ArchiveTrait` regroupe des fichiers et/ou des répertoires dans une archive tar ou zip, la diffuse en tant que réponse de téléchargement PSR-7, et peut extraire les archives entrantes sur le disque. La création de l'archive est déléguée aux helpers tar/zip de `oihana\files\archive` ; l'émission des en-têtes, la diffusion et le nettoyage du fichier temporaire sont mutualisés dans une unique méthode privée `archiveDownload()`.

### Construire des réponses d'archive

| Méthode | Rôle |
|---|---|
| `zipResponse( ?Request $request, Response $response, array $files, string $archive, string $path, array $options = [] )` | Regroupe `$files` dans un ZIP et le diffuse. |
| `tarResponse( ?Request $request, Response $response, string\|array $paths, string $archive, ?string $compression = CompressionType::GZIP, array $options = [] )` | Regroupe `$paths` dans un tar (éventuellement compressé) et le diffuse. |

Points clés :

- **`zipResponse()`** préfixe `$path` à chaque entrée de `$files` (`$path` est la racine préservée), de sorte que chaque entrée archivée conserve son nom relatif à `$path`. Le `Content-Type` émis est `FileMimeType::ZIP`.
- **`tarResponse()`** ne prend en charge que trois compressions de `oihana\files\enums\CompressionType` : `GZIP` (par défaut → `application/gzip`), `BZIP2` (→ `application/x-bzip2`) et `NONE` (→ `application/x-tar`). Toute autre valeur (par ex. `CompressionType::ZIP`) déclenche une `UnsupportedCompressionException`, signalée par une réponse `500`.
- Le tableau `$options` accepte les mêmes commutateurs d'en-têtes que les helpers de réponse de fichier — les clés de `oihana\controllers\enums\FileResponseOption` telles que `USE_CONTENT_TYPE`, `USE_CONTENT_LENGTH` et `CONTENT_DISPOSITION`.
- Les deux helpers ajoutent toujours `Pragma: no-cache` et `Expires: 0`, écrivent les octets de l'archive dans le corps de la réponse, puis suppriment le fichier d'archive temporaire (le contenu est déjà diffusé).
- En cas d'échec lors de la création, le helper retourne `$this->fail( $request, $response, 500, $message )`.

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
        // les entrées sont résolues en $path . $name => /var/data/report.csv, /var/data/notes.txt
        return $this->zipResponse
        (
            $request ,
            $response ,
            [ 'report.csv' , 'notes.txt' ] , // noms relatifs à $path
            '/tmp/bundle.zip' ,              // archive construite ici, puis supprimée après diffusion
            '/var/data/'                     // racine préservée préfixée à chaque entrée
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
            CompressionType::GZIP ,                                  // par défaut ; aussi BZIP2 ou NONE
            [ FileResponseOption::CONTENT_DISPOSITION => 'inline; filename=bundle.tar.gz' ]
        ) ;
    }
}
```

### Extraire des archives

| Méthode | Rôle |
|---|---|
| `extractTar( string $archive, string $destDir, array $options = [] ) : true\|array` | Extrait une archive tar dans `$destDir`. |
| `extractZip( string $archive, string $destDir, array $options = [] ) : true\|array` | Extrait une archive ZIP dans `$destDir`. |

Les deux sont de fines enveloppes autour de `oihana\files\archive\tar\untar()` / `oihana\files\archive\zip\unzip()`, qui se prémunissent déjà contre le Zip Slip et les bombes de décompression. Elles retournent `true` en cas de succès, ou la liste des entrées lorsqu'une exécution à blanc (dry run) est demandée. Les options sont indexées par `oihana\files\enums\TarOption` / `ZipOption` — pour ZIP : `dryRun`, `overwrite`, `maxEntries`, `maxSize`, `keepPermissions`.

```php
use oihana\controllers\traits\ArchiveTrait;
use oihana\files\enums\ZipOption;
use oihana\files\exceptions\FileException;

class ImportController
{
    use ArchiveTrait ;

    public function extract() : void
    {
        // Exécution à blanc : liste les entrées sans rien écrire sur le disque.
        $entries = $this->extractZip( '/tmp/bundle.zip' , '/var/import' , [ ZipOption::DRY_RUN => true ] ) ;
        // [ 'a.txt' , 'sub/b.txt' , ... ]

        // Extraction réelle, limitée à 100 entrées et 50 Mo au total.
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
            // levée sur une entrée Zip Slip, un garde-fou anti-bombe déclenché, ou une archive invalide
        }
    }
}
```

## `UploadTrait`

`oihana\controllers\traits\UploadTrait` reçoit les fichiers téléversés en PSR-7, les valide et les déplace dans un répertoire de destination. Chaque helper délègue le travail par fichier — vérifications d'erreur/taille/MIME, assainissement du nom, garde anti-écrasement, déplacement — à une méthode privée mutualisée `storeUploadedFile()` et lève une `oihana\files\exceptions\FileException` en cas d'échec, laissant la mise en forme de la réponse au contrôleur.

| Méthode | Retourne | Rôle |
|---|---|---|
| `receiveUpload( Request $request, string $field, string $destDir, array $options = [] )` | `string` | Reçoit un seul fichier pour `$field`, retournant le chemin absolu stocké. |
| `receiveUploads( Request $request, string $field, string $destDir, array $options = [] )` | `string[]` | Reçoit un tableau de fichiers pour `$field`, retournant les chemins stockés. |

### Clés de `UploadOption`

Les options proviennent de `oihana\controllers\enums\UploadOption` :

| Constante | Valeur | Effet |
|---|---|---|
| `UploadOption::ALLOWED_MIME_TYPES` | `'allowedMimeTypes'` | Liste blanche des types MIME autorisés. Lorsqu'elle est définie, le fichier déplacé est validé avec `oihana\files\validateMimeType()` ; en cas de rejet, le fichier stocké est supprimé et l'exception est propagée. |
| `UploadOption::FILENAME` | `'filename'` | Surcharge le nom du fichier stocké (upload unique seulement). Par défaut, le nom client assaini. |
| `UploadOption::MAX_SIZE` | `'maxSize'` | Taille maximale autorisée en octets. Les uploads plus volumineux sont rejetés. Par défaut : aucune limite. |
| `UploadOption::OVERWRITE` | `'overwrite'` | Indique si une cible existante peut être écrasée. Par défaut : `false`. |

### Règles de validation

`storeUploadedFile()` applique, dans l'ordre :

1. **Erreur d'upload** — si `getError()` n'est pas `UPLOAD_ERR_OK`, une `FileException` porte un message propre au code (voir ci-dessous).
2. **Taille** — quand `MAX_SIZE` est un entier et que la taille rapportée le dépasse, l'upload est rejeté.
3. **Assainissement du nom** — le nom passe par `basename()`, ce qui supprime les composants de chemin. Un nom client tel que `../../evil.png` est stocké en `evil.png`. Un nom résultant vide est rejeté.
4. **Destination** — `$destDir` doit être un répertoire existant, sinon une `FileException` est levée.
5. **Garde anti-écrasement** — si la cible existe déjà et que `OVERWRITE` n'est pas `true`, l'upload est rejeté.
6. **Liste blanche MIME** — après le déplacement, si `ALLOWED_MIME_TYPES` est un tableau non vide, le fichier stocké est validé ; un type interdit supprime le fichier et relance l'exception.

> `receiveUploads()` ignore `UploadOption::FILENAME` : chaque fichier conserve son propre nom client assaini afin d'éviter les collisions. Un champ manquant, qui n'est pas un tableau, ou qui contient une entrée non-`UploadedFileInterface` lève une `FileException`.

### Messages par code d'erreur

Lorsque `getError()` n'est pas `UPLOAD_ERR_OK`, le message est dérivé du code PHP `UPLOAD_ERR_*` :

| Code | Le message contient |
|---|---|
| `UPLOAD_ERR_INI_SIZE` | `upload_max_filesize` |
| `UPLOAD_ERR_FORM_SIZE` | `MAX_FILE_SIZE` |
| `UPLOAD_ERR_PARTIAL` | fichier `partially` (partiellement) téléversé |
| `UPLOAD_ERR_NO_FILE` | `No file` (aucun fichier) téléversé |
| `UPLOAD_ERR_NO_TMP_DIR` | dossier temporaire (`temporary folder`) manquant |
| `UPLOAD_ERR_CANT_WRITE` | échec de l'écriture (`write`) sur le disque |
| `UPLOAD_ERR_EXTENSION` | une extension PHP (`extension`) a arrêté l'upload |
| tout autre code | `The file upload failed (error code N).` |

### Exemple

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
                'avatar' ,    // le nom du champ de fichier téléversé
                '/var/uploads' ,
                [
                    UploadOption::ALLOWED_MIME_TYPES => [ ImageMimeType::PNG ] ,
                    UploadOption::MAX_SIZE           => 2 * 1024 * 1024 , // 2 Mo
                    UploadOption::FILENAME           => 'avatar.png' ,    // nom stocké forcé
                    UploadOption::OVERWRITE          => true ,
                ]
            ) ;

            return $response->withStatus( 201 ) ; // $path => /var/uploads/avatar.png
        }
        catch ( FileException $e )
        {
            // champ manquant, erreur d'upload, trop volumineux, MIME interdit, répertoire absent, …
            return $response->withStatus( 400 ) ;
        }
    }

    public function uploadGallery( Request $request, Response $response ) : Response
    {
        // FILENAME est ignoré ici : chaque fichier conserve son propre nom client assaini.
        $paths = $this->receiveUploads( $request , 'docs' , '/var/uploads' ) ;
        // [ '/var/uploads/a.png' , '/var/uploads/b.png' ]

        return $response->withStatus( 201 ) ;
    }
}
```

## Voir aussi

- [Réponses de fichier](files.md) — téléchargement, diffusion, plage HTTP, ETag / 304, chiffrement, images.
- [Énumérations](enums.md) — les classes d'options à constantes typées.
- [Index de la documentation](README.md) — retour à la table des matières.
