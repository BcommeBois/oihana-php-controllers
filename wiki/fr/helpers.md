# Helpers

Vingt et une fonctions libres enregistrées via la directive composer
`autoload.files`, toutes dans l'espace de noms `oihana\controllers\helpers`. Ce
sont des fonctions globales et non des méthodes de classe : importez chacune avec
une instruction `use function`, par exemple
`use function oihana\controllers\helpers\getParamInt;`. Elles couvrent la
plomberie quotidienne d'un contrôleur HTTP — extraction de paramètres de requête
typés (query, body ou les deux, avec prise en charge de la notation pointée),
imposition dans le body des valeurs portées par la route, négociation de valeurs
multilingues, et production de réponses de fichiers correctes (en-têtes de
contenu, validateurs ETag, analyse des en-têtes `If-None-Match` et `Range`).

```php
use function oihana\controllers\helpers\getParamInt;
use function oihana\controllers\helpers\getParamString;
```

Comme ce sont de simples fonctions, vous pouvez les appeler n'importe où — dans
un `Controller`, un middleware ou un service autonome — sans étendre de classe de
base.

## Paramètres de requête

Ces helpers lisent des valeurs depuis une requête PSR-7
`ServerRequestInterface`. Ils prennent tous en charge la **notation pointée**
pour les clés imbriquées (par exemple `'user.profile.email'`) et une stratégie
`HttpParamStrategy` qui sélectionne la source : `QUERY`, `BODY` ou `BOTH` (la
valeur par défaut, query string d'abord puis body). Lorsqu'un paramètre est
absent, ils renvoient la valeur par défaut correspondante, sauf si `$throwable`
vaut `true`, auquel cas une exception `DI\NotFoundException` est levée.

| Signature | Retour | Rôle |
|-----------|--------|------|
| `getParam(?Request $request, string $name, array $default = [], ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `mixed` | Accesseur de base : renvoie la valeur brute trouvée, `$default[$name]` ou `null`. |
| `getParamInt(?Request $request, string $name, array $args = [], ?int $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?int` | Convertit la valeur en `int` si numérique, sinon `$defaultValue`. |
| `getParamFloat(?Request $request, string $name, array $args = [], ?float $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?float` | Convertit la valeur en `float` si numérique, sinon `$defaultValue`. |
| `getParamBool(?Request $request, string $name, array $args = [], ?bool $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?bool` | Interprète `true/false/1/0/yes/no/on/off` via `FILTER_VALIDATE_BOOLEAN`. |
| `getParamString(?Request $request, string $name, array $args = [], ?string $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?string` | Convertit la valeur en `string` si définie, sinon `$defaultValue`. |
| `getParamArray(?Request $request, string $name, array $args = [], ?array $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?array` | Renvoie la valeur uniquement si c'est un `array`, sinon `$defaultValue`. |
| `getParamI18n(?Request $request, string $name, array $default = [], ?array $languages = null, ?callable $sanitize = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?array` | Lit une table de traductions et la filtre via `filterLanguages()`. |
| `getParamNumberRange(?Request $request, string $name, int\|float $min, int\|float $max, null\|int\|float $defaultValue = null, array $args = [], ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `int\|float\|null` | Borne une valeur numérique dans `[$min, $max]`, sinon `$defaultValue`. |
| `getParamIntRange(?Request $request, string $name, int $min, int $max, ?int $defaultValue = null, array $args = [], ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?int` | Surcouche de `getParamNumberRange()` forçant un retour `int`. |
| `getParamFloatRange(?Request $request, string $name, float $min, float $max, ?float $defaultValue = null, array $args = [], ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false)` | `?float` | Surcouche de `getParamNumberRange()` forçant un retour `float`. |
| `getQueryParam(?Request $request, string $name)` | `mixed` | Lit une seule valeur depuis la query string uniquement ; `null` si absente. |
| `getBodyParam(?Request $request, string $name)` | `mixed` | Lit une seule valeur depuis le body analysé uniquement ; `null` si absente. |
| `getBodyParams(?Request $request, array $names = [])` | `array` | Extrait plusieurs clés du body et les reconstruit en préservant l'imbrication de la notation pointée. |

## Injection depuis la route

Contrairement aux accesseurs ci-dessus, ce helper **écrit** dans la requête. Une
route de sous-ressource nomme déjà une partie du payload : `/owners/{ownerId}/items`
dit qui est le propriétaire, le body ne devrait donc ni avoir à le répéter ni
pouvoir le contredire.

| Signature | Retour | Rôle |
|-----------|--------|------|
| `injectRouteValues(?Request $request, array $args, array $bindings)` | `?Request` | Renvoie la requête dont le body analysé porte les paramètres de route, imposés par-dessus ce que l'appelant a fourni. |

`$bindings` est une table `paramètre de route => champ du body` : une route
imposant plusieurs champs coûte une entrée chacun. La route gagne toujours — un
champ lié fourni par l'appelant est écrasé, jamais fusionné. Les champs du body
acceptent la **notation pointée**, les niveaux manquants étant créés et les clés
sœurs laissées intactes.

Seuls une chaîne non vide ou un `int` sont injectés, un paramètre de route étant
toujours du texte dans l'URL — tout le reste (paramètre absent, vide ou non
scalaire, ou champ cible vide) est ignoré, la valeur de l'appelant restant alors
en place. Quand aucune liaison ne s'applique, la requête est renvoyée telle
quelle, sans clonage. Le body est normalisé via `toAssociativeArray()`, comme
`getBodyParam()` : les payloads `stdClass` sont acceptés, mais le body réécrit
est toujours un tableau associatif.

## Réponses HTTP / fichiers

Les briques de base pour servir des fichiers efficacement : décorer une réponse
avec les en-têtes de téléchargement, calculer et comparer des validateurs
`ETag`, et analyser un en-tête `Range` en un unique intervalle d'octets.

| Signature | Retour | Rôle |
|-----------|--------|------|
| `applyContentHeaders(Response $response, string $file, ?string $contentType = null, array $options = [], bool $defaultOn = true)` | `Response` | Ajoute `Content-Type` / `Content-Length` / `Content-Disposition`, activés par les options `FileResponseOption`. |
| `computeETag(string $file, bool $weak = false, bool $hashContent = false)` | `string` | Construit un `ETag` entre guillemets à partir des métadonnées (`mtime`-`size`) ou, en option, de `md5_file()`. |
| `etagMatches(string $header, string $etag)` | `bool` | Test `If-None-Match` RFC 7232 (`*`, liste séparée par virgules, comparaison faible) — `true` ⇒ répondre `304`. |
| `parseRangeHeader(string $rangeHeader, int $fileSize)` | `array{0:int,1:int}\|false\|null` | `[start, end]` ⇒ `206`, `false` ⇒ `416`, `null` ⇒ `200` complet. Plages simples uniquement. |

## Langues

Lire et filtrer des tables multilingues (par exemple
`['fr' => 'Bonjour', 'en' => 'Hello']`).

| Signature | Retour | Rôle |
|-----------|--------|------|
| `translate(array\|object\|null $fields, string\|null $lang = null, string\|null $default = null)` | `mixed` | Renvoie la valeur pour `$lang`, le repli sur la langue `$default`, tous les champs si `$lang` vaut `null`, ou `null`. |
| `filterLanguages(mixed $fields, ?array $languages = null, ?callable $sanitize = null)` | `?array` | Ne garde que les valeurs `string`/`null` des langues **reçues** que la liste autorise — une langue absente du corps n'est pas reconstruite. Chaîne vide ramenée à `null`, callback de nettoyage optionnel par valeur ; `null` si l'entrée est invalide, vide ou sans langue connue. |

## Contrôleurs

| Signature | Retour | Rôle |
|-----------|--------|------|
| `getController(array\|string\|null\|Controller $definition = null, ?ContainerInterface $container = null, ?Controller $default = null)` | `?Controller` | Résout un `Controller` depuis une instance, un tableau portant `ControllerParam::CONTROLLER`, ou un identifiant de conteneur PSR-11 ; repli sur `$default`. |

## Exemples

Lecture de paramètres typés dans une action de contrôleur :

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use oihana\enums\http\HttpParamStrategy;

use function oihana\controllers\helpers\getParamInt;
use function oihana\controllers\helpers\getParamBool;
use function oihana\controllers\helpers\getParamIntRange;

function index( ServerRequestInterface $request, ResponseInterface $response ): ResponseInterface
{
    // ?page=3&active=yes&limit=500
    $page   = getParamInt( $request, 'page', [], 1 );                 // 3
    $active = getParamBool( $request, 'active', [], false );          // true
    $limit  = getParamIntRange( $request, 'limit', 1, 100, 20 );      // 100 (borné)

    // Valeur imbriquée du body uniquement : { "filter": { "status": "open" } }
    $status = getParamInt( $request, 'filter.status', [], 0, HttpParamStrategy::BODY );

    return $response;
}
```

Imposer dans le payload soumis le propriétaire porté par l'URL :

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function oihana\controllers\helpers\injectRouteValues;

// POST /owners/7/items
function create( ServerRequestInterface $request, ResponseInterface $response, array $args ): ResponseInterface
{
    // Body : { "name": "Chair", "owner": "99" } — le client contredit l'URL.
    $request = injectRouteValues( $request, $args, [ 'ownerId' => 'owner' ] );
    // Body : [ 'name' => 'Chair', 'owner' => '7' ] — la route gagne.

    return $response;
}
```

La notation pointée atteint un champ imbriqué sans toucher à ses clés sœurs :

```php
// Body : { "owner": { "id": "99", "name": "Alice" } }
$request = injectRouteValues( $request, $args, [ 'ownerId' => 'owner.id' ] );
// Body : [ 'owner' => [ 'id' => '7', 'name' => 'Alice' ] ]
```

Servir un fichier avec gestion conditionnelle de l'`ETag` et prise en charge des
plages :

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use oihana\enums\http\HttpHeader;

use function oihana\controllers\helpers\applyContentHeaders;
use function oihana\controllers\helpers\computeETag;
use function oihana\controllers\helpers\etagMatches;
use function oihana\controllers\helpers\parseRangeHeader;

function download( ServerRequestInterface $request, ResponseInterface $response, string $file ): ResponseInterface
{
    $etag = computeETag( $file ) ;

    // Court-circuit avec un 304 quand le cache du client est encore frais.
    if ( etagMatches( $request->getHeaderLine( HttpHeader::IF_NONE_MATCH ), $etag ) )
    {
        return $response->withStatus( 304 )->withHeader( HttpHeader::ETAG, $etag ) ;
    }

    $response = applyContentHeaders( $response, $file )->withHeader( HttpHeader::ETAG, $etag ) ;

    // Honorer une plage d'octets unique, le cas échéant.
    $range = parseRangeHeader( $request->getHeaderLine( HttpHeader::RANGE ), filesize( $file ) ) ;
    if ( $range === false )
    {
        return $response->withStatus( 416 ) ; // Range Not Satisfiable
    }
    if ( is_array( $range ) )
    {
        [ $start, $end ] = $range ;
        $response = $response->withStatus( 206 ) ; // Partial Content
        // ... diffuser les octets $start..$end ...
    }

    return $response ;
}
```

Négocier une valeur multilingue soumise par le client :

```php
use function oihana\controllers\helpers\getParamI18n;
use function oihana\controllers\helpers\translate;

// Body : { "title": { "fr": "Bonjour", "en": "Hello", "de": 42 } }
$title = getParamI18n( $request, 'title', [], [ 'fr', 'en' ] );
// [ 'fr' => 'Bonjour', 'en' => 'Hello' ]  (la valeur non-string 'de' est écartée)

echo translate( $title, 'en' );          // 'Hello'
echo translate( $title, 'de', 'fr' );    // 'Bonjour' (langue de repli)
```

## Voir aussi

- [Paramètres](params.md) — extraction de paramètres de requête typés et stratégies `prepare`.
- [Réponses de fichiers](files.md) — téléchargement, streaming, plages HTTP, ETag / 304.
- [Index de la documentation](README.md) — retour à la table des matières.
