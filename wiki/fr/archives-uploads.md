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

`oihana\controllers\traits\UploadTrait` reçoit les fichiers téléversés en PSR-7, les valide et les range dans un répertoire de destination. Chaque helper délègue le travail par fichier à une méthode privée mutualisée `storeUploadedFile()` et lève une `oihana\files\exceptions\FileException` en cas d'échec, laissant la mise en forme de la réponse au contrôleur.

| Méthode | Retourne | Rôle |
|---|---|---|
| `receiveUpload( Request $request, string $field, string $destDir, array $options = [] )` | `string` | Reçoit un seul fichier pour `$field`, retournant le chemin absolu stocké. |
| `receiveUploads( Request $request, string $field, string $destDir, array $options = [] )` | `string[]` | Reçoit un tableau de fichiers pour `$field`, retournant les chemins stockés. |

### Pourquoi le fichier n'est jamais rangé sous le nom du client

Le client choisit librement le nom de son fichier, extension comprise. Un `photo.php` qui contient une **vraie image** — avec du PHP caché dedans — passe la vérification du type (`image/png`) : rangé sous son nom dans un dossier servi par le serveur web, ouvrir `…/photo.php` exécuterait son code. Et deux téléphones envoient très souvent deux `IMG_0001.jpg` : le second entrait en collision avec le premier.

Le trait range donc par défaut sous un **nom tiré au hasard**, suivi de l'**extension du type lu dans le contenu** : `9f2c4e1a7b3d5f60a8c2e4b6d8f0a1c3.jpg`. Le nom d'origine, s'il faut l'afficher, se lit sur la requête (`getClientFilename()`) et se garde dans la ressource, pas dans le nom du fichier.

> 💡 Rangez les fichiers **hors des dossiers servis** par le serveur web, et servez-les par une route qui vérifie les droits et pose le `Content-Type` enregistré. `.svg` et `.html` peuvent contenir du JavaScript : servez-les en téléchargement (`Content-Disposition: attachment`) ou ne les admettez pas.

### Les trois façons de nommer le fichier

| Mode | Nom rangé | Si le nom est pris |
|---|---|---|
| **par défaut** | 32 hexadécimaux + extension du type détecté | ne peut pas arriver en pratique |
| `CLIENT_FILENAME => true` | le nom du client nettoyé par `slugify()` (`oihana/php-core`), avec l'extension du type : `Façade Nord (2).JPG` → `facade-nord-2.jpg`, `.user.ini` → `user.txt`, `photo.php.png` → `photo-php.png` ; rien de lisible (`日本語.png`) → nom tiré | **numéroté** : `img-0001-2.jpg`, `-3`… jusqu'à 100, puis nom tiré ; `OVERWRITE` écrase |
| `FILENAME => '…'` | le nom donné, **respecté ou refusé**, jamais modifié : une extension qui ne correspond pas au type détecté, ou un point en tête, est refusée ; sans extension, il passe | refusé ; `OVERWRITE` écrase |

`FILENAME` l'emporte sur `CLIENT_FILENAME`. `receiveUploads()` ignore `FILENAME` (un seul nom ne peut pas servir à plusieurs fichiers).

### L'extension

1. `EXTENSIONS` — la table `type => '.ext'` donnée par le contrôleur, consultée en premier ;
2. sinon le catalogue `FileMimeType::getExtension()` (`oihana/php-files`) ; quand le type a plusieurs extensions (`image/jpeg` → `.jpeg`, `.jpg`), celle du client est gardée si elle en fait partie, sinon la première ;
3. un contenu non reconnu (`application/octet-stream`), ou un type absent du catalogue, donne un **nom sans extension**.

Les types sont comparés par leur nom de référence (`oihana\files\normalizeMimeType()`) : `audio/x-wav` et `audio/wav` sont le même format.

### Clés de `UploadOption`

| Constante | Valeur | Effet |
|---|---|---|
| `UploadOption::ALLOWED_MIME_TYPES` | `'allowedMimeTypes'` | Liste blanche des types MIME autorisés, vérifiée sur le contenu **avant** que le fichier prenne sa place ; un type refusé ne laisse aucun fichier. |
| `UploadOption::CLIENT_FILENAME` | `'clientFilename'` | Range sous le nom du client, nettoyé (voir ci-dessus). Par défaut : `false`. |
| `UploadOption::EXTENSIONS` | `'extensions'` | Table `type => '.ext'` lue avant le catalogue (`[ 'audio/x-wav' => '.wav' ]`). Une extension doit être un point suivi de lettres et de chiffres, sinon `FileException`. |
| `UploadOption::FILENAME` | `'filename'` | Le nom du fichier rangé (envoi unique), respecté ou refusé. Par défaut : un nom tiré. |
| `UploadOption::MAX_SIZE` | `'maxSize'` | Taille maximale autorisée en octets. Par défaut : aucune limite. |
| `UploadOption::OVERWRITE` | `'overwrite'` | Une cible existante peut être écrasée. Par défaut : `false`. |

### L'ordre des opérations

`storeUploadedFile()` applique, dans l'ordre :

1. **Erreur d'upload** — si `getError()` n'est pas `UPLOAD_ERR_OK`, une `FileException` porte un message propre au code (voir ci-dessous).
2. **Taille** — au-delà de `MAX_SIZE`, l'upload est rejeté.
3. **Options** — un `FILENAME` vide ou commençant par un point, ou une table `EXTENSIONS` mal formée, est refusé **avant** tout déplacement.
4. **Destination** — `$destDir` doit être un répertoire existant.
5. **Dépôt provisoire** — `moveTo()` dépose le fichier sous `.upload-<hasard>.part` : un nom caché, sans extension exécutable. `move_uploaded_file()` et sa vérification restent.
6. **Type** — lu sur le contenu ; `ALLOWED_MIME_TYPES` donné → vérifié. Refusé → le fichier provisoire est effacé.
7. **Nom définitif** — selon le mode, puis **réservé d'un seul geste** (création exclusive, `fopen( … , 'x' )`) : deux envois simultanés du même nom ne s'écrasent jamais en silence.
8. **Renommage** — le fichier provisoire prend son nom définitif (même dossier : opération atomique). Au moindre échec, le fichier provisoire et la réservation sont effacés.

> Un `.upload-….part` peut rester si PHP s'arrête entre les étapes 5 et 8 (un plantage du processus) : il est inoffensif, et les anciens peuvent être effacés sans risque.

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

    public function uploadPhoto( Request $request, Response $response ) : Response
    {
        try
        {
            $path = $this->receiveUpload
            (
                $request ,
                'photo' ,              // le nom du champ de fichier téléversé
                '/var/app/uploads' ,   // hors des dossiers servis
                [
                    UploadOption::ALLOWED_MIME_TYPES => [ ImageMimeType::PNG , ImageMimeType::JPEG ] ,
                    UploadOption::MAX_SIZE           => 2 * 1024 * 1024 , // 2 Mo
                ]
            ) ;
            // $path => /var/app/uploads/9f2c4e1a7b3d5f60a8c2e4b6d8f0a1c3.jpg

            return $response->withStatus( 201 ) ;
        }
        catch ( FileException $e )
        {
            // champ manquant, erreur d'upload, trop volumineux, type refusé, répertoire absent, …
            return $response->withStatus( 400 ) ;
        }
    }

    public function uploadAvatar( Request $request, Response $response ) : Response
    {
        // Un nom voulu : respecté, ou refusé s'il ment sur le type ; remplacé s'il existe déjà.
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
        // Les noms des clients, nettoyés et numérotés : deux IMG_0001.png donnent img-0001.png et img-0001-2.png.
        $paths = $this->receiveUploads( $request , 'photos' , '/var/app/uploads' , [ UploadOption::CLIENT_FILENAME => true ] ) ;

        return $response->withStatus( 201 ) ;
    }
}
```

## Voir aussi

- [Réponses de fichier](files.md) — téléchargement, diffusion, plage HTTP, ETag / 304, chiffrement, images.
- [Énumérations](enums.md) — les classes d'options à constantes typées.
- [Index de la documentation](README.md) — retour à la table des matières.
