# Réponses fichier

Cette page couvre les traits et helpers qui transforment un fichier sur disque en réponse HTTP : **téléchargements** simples, **streaming**, **plages d'octets HTTP** (médias avec positionnement), **requêtes conditionnelles** (`ETag` / `304 Not Modified`), **chiffrement OpenSSL** et transformations d'**images**.

Chaque trait valide d'abord le fichier avec `oihana\files\assertFile()` et signale un fichier manquant ou illisible par une réponse `500` (via `StatusTrait::fail()`), plutôt que de laisser fuir des warnings PHP. Chaque méthode prend le couple PSR-7 `$request` / `$response` et un tableau `$options` indexé par les énumérations décrites ci-dessous.

## FileTrait

`oihana\controllers\traits\FileTrait` expose une seule méthode, `fileResponse()`, qui diffuse un fichier de façon paresseuse (il est lu au moment de l'émission via la `StreamFactory` de Slim, donc les gros fichiers ne sont jamais chargés en mémoire).

```php
public function fileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
```

Les clés de `$options` proviennent de `oihana\controllers\enums\FileResponseOption` :

| Constante | Clé | Défaut dans `fileResponse()` | Rôle |
|---|---|---|---|
| `FileResponseOption::USE_CONTENT_TYPE` | `useContentType` | `false` | Émettre `Content-Type` (type MIME détecté). |
| `FileResponseOption::USE_CONTENT_LENGTH` | `useContentLength` | `false` | Émettre `Content-Length` (la taille du fichier). |
| `FileResponseOption::USE_CONTENT_DISPOSITION` | `useContentDisposition` | `false` | Émettre `Content-Disposition`. |
| `FileResponseOption::CONTENT_DISPOSITION` | `contentDisposition` | `attachment; filename=<basename>` | La valeur `Content-Disposition` à envoyer. |

Les en-têtes de contenu sont ici **désactivés par défaut** : l'appelant les active via `$options`.

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

## Plages HTTP

`oihana\controllers\traits\RangeTrait::rangeFileResponse()` sert un fichier en respectant l'en-tête `Range` de la requête, ce qui rend les téléchargements reprenables et les médias positionnables.

```php
public function rangeFileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
```

La réponse annonce toujours `Accept-Ranges: bytes`. L'en-tête est analysé par `oihana\controllers\helpers\parseRangeHeader()`, qui comprend les formes courantes de plage unique et retourne l'une de trois valeurs :

- `[start, end]` — une plage unique satisfaisable → `206 Partial Content` avec un en-tête `Content-Range` ;
- `false` — une plage est présente mais non satisfaisable → `416 Range Not Satisfiable` ;
- `null` — aucune plage unique exploitable (absente, malformée ou multi-plage) → le fichier complet, diffusé en `200`.

```php
use function oihana\controllers\helpers\parseRangeHeader;

parseRangeHeader( 'bytes=0-4'    , 11 ) ; // [0, 4]   -> 206
parseRangeHeader( 'bytes=6-'     , 11 ) ; // [6, 10]  -> 206 (ouvert à droite)
parseRangeHeader( 'bytes=-5'     , 11 ) ; // [6, 10]  -> 206 (5 derniers octets)
parseRangeHeader( 'bytes=0-9999' , 11 ) ; // [0, 10]  -> 206 (fin bornée)
parseRangeHeader( 'bytes=99-'    , 11 ) ; // false    -> 416 (non satisfaisable)
parseRangeHeader( 'bytes=0-2,5-' , 11 ) ; // null     -> 200 (multi-plage ignorée)
parseRangeHeader( ''             , 11 ) ; // null     -> 200 (pas de plage)
```

Le tableau `$options` (les mêmes clés `FileResponseOption`) est appliqué à la réponse `200` de contenu complet ; pour une `206`, les en-têtes `Content-Type`, `Content-Length` et `Content-Range` sont posés explicitement.

```php
use oihana\controllers\traits\RangeTrait;

class VideoController extends Controller
{
    use RangeTrait ;

    public function stream( Request $request , Response $response ) : Response
    {
        // "Range: bytes=0-1023" -> 206 Partial Content (premier Kio)
        // pas d'en-tête Range   -> 200 avec Accept-Ranges: bytes (fichier complet)
        return $this->rangeFileResponse( $request , $response , '/var/media/clip.mp4' ) ;
    }
}
```

## Requêtes conditionnelles

`oihana\controllers\traits\ConditionalRequestTrait::conditionalFileResponse()` ajoute la mise en cache basée sur les validateurs. À chaque appel, la réponse porte un en-tête `ETag` et un `Last-Modified` ; les préconditions de la requête sont ensuite évaluées, `If-None-Match` ayant priorité sur `If-Modified-Since` (RFC 7232).

```php
public function conditionalFileResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
```

Quand une précondition correspond, une réponse `304 Not Modified` sans corps est retournée (portant toujours les validateurs) ; sinon le fichier est diffusé en `200`. Au-delà de `FileResponseOption`, elle lit deux clés de `oihana\controllers\enums\ConditionalRequestOption` :

| Constante | Clé | Défaut | Rôle |
|---|---|---|---|
| `ConditionalRequestOption::WEAK` | `weak` | `false` | Émettre un validateur faible (`W/"..."`) au lieu d'un fort. |
| `ConditionalRequestOption::HASH_CONTENT` | `hashContent` | `false` | Dériver l'`ETag` du contenu (`md5_file()`) au lieu des métadonnées (`mtime`-`size`). |

Le validateur est construit par `oihana\controllers\helpers\computeETag()`, et les valeurs `If-None-Match` entrantes sont comparées avec `oihana\controllers\helpers\etagMatches()` (comparaison faible RFC 7232 : le préfixe `W/` est ignoré des deux côtés, et `*` correspond à n'importe quelle représentation). Les en-têtes de téléchargement sont appliqués via `oihana\controllers\helpers\applyContentHeaders()`, le helper partagé utilisé par toutes les réponses fichier.

```php
use function oihana\controllers\helpers\computeETag;
use function oihana\controllers\helpers\etagMatches;

$etag = computeETag( '/var/assets/app.css' ) ;        // "<mtime-hex>-<size-hex>" (fort, métadonnées)
computeETag( '/var/assets/app.css' , weak: true ) ;   // W/"<mtime-hex>-<size-hex>"
computeETag( '/var/assets/app.css' , hashContent: true ) ; // "<md5>" (fort, exact)

etagMatches( '*'            , $etag ) ; // true  (correspond à toute représentation)
etagMatches( 'W/' . $etag   , $etag ) ; // true  (comparaison faible)
etagMatches( '"nope"'       , $etag ) ; // false (aucune correspondance)
```

```php
use oihana\controllers\traits\ConditionalRequestTrait;
use oihana\controllers\enums\ConditionalRequestOption;

class AssetController extends Controller
{
    use ConditionalRequestTrait ;

    public function asset( Request $request , Response $response ) : Response
    {
        // 1re requête                              -> 200 avec ETag + Last-Modified
        // requête avec un If-None-Match concordant -> 304 Not Modified (corps vide)
        return $this->conditionalFileResponse( $request , $response , '/var/assets/app.css' ,
        [
            ConditionalRequestOption::HASH_CONTENT => true , // ETag exact, basé sur le contenu
        ]) ;
    }
}
```

## Chiffrement

`oihana\controllers\traits\FileEncryptionTrait` chiffre et déchiffre des fichiers avec OpenSSL, en déléguant la cryptographie à un `oihana\files\openssl\OpenSSLFileEncryption` configuré. La phrase secrète vit donc dans la configuration de l'injection de dépendances, jamais dans le trait.

Le helper de chiffrement est injecté via `initializeFileEncryption()` — soit depuis un tableau d'init sous la clé `FileEncryptionTrait::FILE_ENCRYPTION` (`'fileEncryption'`), soit depuis un conteneur PSR-11 qui expose `OpenSSLFileEncryption::class`. Appeler une méthode avant l'initialisation lève une `RuntimeException`.

| Méthode | Retour | Rôle |
|---|---|---|
| `encryptFile( string $input , ?string $output = null )` | `string` | Chiffrer un fichier ; retourne le chemin produit (par défaut `$input` + `.enc`). |
| `decryptFile( string $input , ?string $output = null )` | `string` | Déchiffrer un fichier ; retourne le chemin clair (par défaut `$input` sans `.enc`). |
| `encryptedFileResponse( ?Request , Response , string $file , array $options = [] )` | `Response` | Chiffrer puis diffuser le contenu chiffré en téléchargement. |
| `decryptFileResponse( ?Request , Response , string $file , array $options = [] )` | `Response` | Déchiffrer puis diffuser le contenu clair en téléchargement. |

Les deux méthodes `*Response` écrivent le fichier produit dans le corps, puis suppriment le fichier temporaire. Elles utilisent `applyContentHeaders()` avec les en-têtes **activés par défaut**, et acceptent les clés `FileResponseOption`.

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

        // déchiffre /var/vault/secret.txt.enc et diffuse le contenu clair
        return $this->decryptFileResponse( $request , $response , '/var/vault/secret.txt.enc' ) ;
    }
}
```

Lorsqu'un conteneur est disponible, le helper peut être résolu automatiquement :

```php
$this->initializeFileEncryption( [] , $container ) ; // utilise $container->get( OpenSSLFileEncryption::class )
```

## Images

`oihana\controllers\traits\ImageTrait` sert et transforme des images via `ext-imagick`. Il diffuse une image brute avec `imageResponse()`, ou applique des transformations Imagick et retourne le blob encodé avec `imagickResponse()`.

```php
public function imageResponse( ?Request $request , Response $response , string $file , array $options = [] ) : Response
public function imagickResponse( Response $response , string|Imagick $image , array $options = [] ) : Response
```

Les commutateurs de transformation proviennent de `oihana\controllers\enums\ImagickResponseOption`, combinés à `FileResponseOption` pour les en-têtes (ici `useContentType` et `useContentLength` valent `true` par défaut) :

| Constante | Clé | Défaut | Rôle |
|---|---|---|---|
| `ImagickResponseOption::COMPRESSION` | `compression` | `Imagick::COMPRESSION_JPEG` | La constante de compression Imagick. |
| `ImagickResponseOption::QUALITY` | `quality` | `70` | Qualité de compression (0–100). |
| `ImagickResponseOption::GRAY` | `gray` | `false` | Désaturer en niveaux de gris. |
| `ImagickResponseOption::STRIP` | `strip` | `false` | Supprimer profils et commentaires. |
| `FileResponseOption::FORMAT` | `format` | `jpg` | Format de sortie, utilisé pour définir le format Imagick et le type de contenu `image/<format>`. |

La méthode `resize()` redimensionne une image, avec les surcharges de `oihana\controllers\enums\ResizeOption` (`WIDTH`, `HEIGHT`, `MAX_WIDTH`, `MAX_HEIGHT` ; défauts `1920` × `1200`). Quand seule la largeur ou la hauteur est demandée, elle préserve le ratio via `oihana\core\maths\aspectFit()` afin que l'image ne soit jamais déformée ; fournir à la fois `$w` et `$h` force un ajustement exact.

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

        // redimensionnement préservant le ratio vers 320 px de large (hauteur dérivée via aspectFit)
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
        // diffuse le fichier tel quel, avec Content-Type / Content-Length
        return $this->imageResponse( $request , $response , '/var/photos/original.png' ) ;
    }
}
```

Des accesseurs complètent le trait : `getImageDimensions()`, `getImageWidth()`, `getImageHeight()` (chacun acceptant une instance `Imagick` ou un chemin), `initializeImagePath()` / `getImagePath()` pour la racine des images, et `shadow()` pour composer une ombre portée définie par `opacity,sigma,x,y`.

## Voir aussi

- [Archives et envois](archives-uploads.md) — archives zip/tar et envois de fichiers.
- [Réponses](responses.md) — JSON, CBOR, statut et sortie API.
- [Énumérations](enums.md) — les classes de constantes typées.
- Retour à l'[index de la documentation](README.md).
