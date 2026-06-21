# Routage

Les contrôleurs vivent rarement isolés : ils sont raccordés à une application
[Slim](https://www.slimframework.com/), génèrent des URL pour des routes nommées,
redirigent vers d'autres actions, construisent des liens à partir d'une URL de
base configurable, protègent les formulaires avec des jetons CSRF et émettent des
en-têtes de cache HTTP. `oihana/php-controllers` regroupe ces préoccupations dans
de petits traits à responsabilité unique que la classe `Controller` compose —
chacun étant initialisé depuis un tableau `init` ou résolu depuis un conteneur
PSR-11.

| Trait | Rôle |
|---|---|
| `AppTrait` | Détient l'instance Slim `App` et construit des URL à partir de son base path. |
| `RouterTrait` | Génère les URL des **routes nommées** et y redirige. |
| `RedirectsTrait` | Stocke une table de cibles de redirection nommées. |
| `BaseUrlTrait` | Gère l'URL de base de l'application et construit des chemins à partir d'elle. |
| `PathTrait` | Détient les chemins propres du contrôleur (`path` / `fullPath` / `ownerPath`). |
| `CsrfTrait` | Expose les jetons CSRF via `slim/csrf`. |
| `HttpCacheTrait` | Définit les en-têtes de cache (`ETag`, `Last-Modified`, …) via `slim/http-cache`. |

Chaque initialiseur suit la même priorité : d'abord le tableau `init`, puis le
conteneur DI, et enfin une valeur par défaut sûre (ou une exception lorsque la
dépendance est obligatoire). Les clés vivent dans
`oihana\controllers\enums\ControllerParam` (`ControllerParam::APP === 'app'`,
`ControllerParam::ROUTER === 'router'`, etc.), si bien que vous n'avez jamais à
coder en dur ces chaînes magiques.

## `AppTrait` — la référence vers l'App Slim

`oihana\controllers\traits\AppTrait` détient l'instance Slim `App` et l'utilise
pour construire des URL absolues. Il compose `BaseUrlTrait`, dont il hérite la
propriété `$baseUrl`.

| Méthode | Signature | Retour |
|---|---|---|
| `initializeApp` | `initializeApp( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `getBasePath` | `getBasePath(): string` | Le base path Slim (ex. `/myapp`). |
| `getUrl` | `getUrl( string $path = '', array $params = [], bool $useNow = false ): string` | URL complète. |

`initializeApp()` résout l'`App` d'abord depuis `$init[ControllerParam::APP]`,
puis depuis le conteneur (`App::class` ou un identifiant de service personnalisé),
et lève une `RuntimeException` si aucune n'est trouvée. `getUrl()` joint
`$baseUrl`, le base path Slim et `$path`, puis ajoute la chaîne de requête
formatée.

```php
use Slim\Factory\AppFactory;
use oihana\controllers\traits\AppTrait;
use oihana\controllers\enums\ControllerParam;

$app = AppFactory::create();
$app->setBasePath( '/myapp' );

$controller = new class { use AppTrait; };
$controller->initializeApp( [ ControllerParam::APP => $app ] );
$controller->baseUrl = 'https://example.com';

echo $controller->getBasePath();                          // /myapp
echo $controller->getUrl( '/users', [ 'page' => 2 ] );    // https://example.com/myapp/users?page=2
```

## `RouterTrait` — génération des URL de routes

`oihana\controllers\traits\RouterTrait` encapsule le `RouteParserInterface` de
Slim pour générer les URL des routes **nommées** et y rediriger. Il compose
`BaseUrlTrait`.

| Méthode | Signature | Retour |
|---|---|---|
| `initializeRouterParser` | `initializeRouterParser( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `redirectFor` | `redirectFor( Response $response, string $name, array $params = [], int $status = 302 ): Response` | Réponse de redirection. |
| `urlFor` (protected) | `urlFor( string $routeName ): string` | `$baseUrl` + le chemin de la route. |

`initializeRouterParser()` résout un `RouteParserInterface` depuis
`$init[ControllerParam::ROUTER]` ou depuis le conteneur, et lève une
`RuntimeException` sinon. `urlFor()` préfixe `$baseUrl` au chemin de la route ;
`redirectFor()` résout la route puis délègue au `redirectResponse()` de l'hôte
(fourni par la classe `Controller`).

```php
use Slim\Interfaces\RouteParserInterface;
use Psr\Http\Message\ResponseInterface;
use oihana\controllers\Controller;
use oihana\controllers\enums\ControllerParam;

/** @var RouteParserInterface $parser  (depuis $app->getRouteCollector()->getRouteParser()) */
class PostController extends Controller
{
    public function save( $request, ResponseInterface $response, array $args ): ResponseInterface
    {
        // ... persistance ...
        return $this->redirectFor( $response, 'post.show', [ 'id' => 42 ], 303 );
    }
}

$controller = new PostController( /* container */ );
$controller->initializeRouterParser( [ ControllerParam::ROUTER => $parser ] );
$controller->baseUrl = '/api';
// urlFor('post.show') -> '/api' . $parser->urlFor('post.show')
```

## `RedirectsTrait` — cibles de redirection nommées

`oihana\controllers\traits\RedirectsTrait` conserve une simple table associative
de cibles de redirection, généralement utilisée par un contrôleur pour savoir où
envoyer l'utilisateur après une action.

| Membre | Signature | Rôle |
|---|---|---|
| `$redirects` | `public array $redirects = []` | La table de redirection. |
| `initializeRedirects` | `initializeRedirects( array $init = [] ): void` | Lit `$init[ControllerParam::REDIRECTS]`. |

```php
use oihana\controllers\traits\RedirectsTrait;
use oihana\controllers\enums\ControllerParam;

$controller = new class { use RedirectsTrait; };
$controller->initializeRedirects([
    ControllerParam::REDIRECTS => [
        'success' => '/dashboard',
        'login'   => '/auth/login',
    ],
]);

$target = $controller->redirects[ 'success' ] ?? '/'; // '/dashboard'
```

## `BaseUrlTrait` / `PathTrait` — construction d'URL et de chemins

### `BaseUrlTrait`

`oihana\controllers\traits\BaseUrlTrait` gère le `$baseUrl` de l'application et
construit des chemins relatifs à celui-ci. L'URL de base peut provenir du tableau
`init`, du conteneur DI, ou prendre par défaut une chaîne vide.

| Méthode | Signature | Retour |
|---|---|---|
| `initializeBaseUrl` | `initializeBaseUrl( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `getCurrentPath` | `getCurrentPath( ?Request $request = null, array $params = [], bool $useNow = false ): string` | Chemin courant sous l'URL de base. |
| `getFullPath` | `getFullPath( ?array $params = null, bool $useNow = false ): string` | URL de base + chaîne de requête. |
| `getPath` | `getPath( string $path = '', ?array $params = null, bool $useNow = false ): string` | URL de base + `$path` + requête. |

`getCurrentPath()` utilise l'URI de la requête PSR-7 lorsqu'elle est fournie,
sinon il se rabat sur `$_SERVER['REQUEST_URI']`. Lorsque `$useNow` vaut `true`, un
paramètre `_` d'horodatage anti-cache est ajouté.

```php
use oihana\controllers\traits\BaseUrlTrait;
use oihana\controllers\enums\ControllerParam;

$controller = new class { use BaseUrlTrait; };
$controller->initializeBaseUrl( [ ControllerParam::BASE_URL => 'https://example.com' ] );

echo $controller->getPath( '/users', [ 'page' => 2 ] ); // https://example.com/users?page=2
echo $controller->getFullPath( [ 'q' => 'php' ] );       // https://example.com?q=php
```

### `PathTrait`

`oihana\controllers\traits\PathTrait` détient les références de chemin propres au
contrôleur — utiles pour les contrôleurs de ressources imbriqués sous un
propriétaire.

| Membre | Signature | Rôle |
|---|---|---|
| `$path` | `public string $path = ''` | Le chemin de la ressource. |
| `$fullPath` | `public string $fullPath` | Le chemin complet (par défaut `/` + `$path`). |
| `$ownerPath` | `public ?string $ownerPath = ''` | Le préfixe de chemin du propriétaire. |
| `getFullOwnerPath` | `getFullOwnerPath( string $id ): string` | `ownerPath/$id/path`. |
| `initializePath` | `initializePath( array $init = [] ): static` | Lit `path` / `fullPath` / `ownerPath`. |

```php
use oihana\controllers\traits\PathTrait;
use oihana\controllers\enums\ControllerParam;

$controller = new class { use PathTrait; };
$controller->initializePath([
    ControllerParam::PATH       => 'photos',
    ControllerParam::OWNER_PATH => 'albums',
]);

echo $controller->path;                       // photos
echo $controller->fullPath;                   // /photos
echo $controller->getFullOwnerPath( '7' );    // albums/7/photos
```

## `CsrfTrait` — protection CSRF via `slim/csrf`

`oihana\controllers\traits\CsrfTrait` expose un `Slim\Csrf\Guard` (fourni par la
DI, jamais instancié ici) pour lire et valider les jetons CSRF. Lorsque le guard
n'est pas configuré, chaque accesseur se dégrade gracieusement (`null`, `[]` ou
`false`).

| Méthode | Signature | Retour |
|---|---|---|
| `initializeCsrf` | `initializeCsrf( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `csrfTokenName` | `csrfTokenName(): ?string` | Nom du jeton courant. |
| `csrfTokenNameKey` | `csrfTokenNameKey(): ?string` | Nom de champ pour le nom du jeton. |
| `csrfTokenValue` | `csrfTokenValue(): ?string` | Valeur du jeton courant. |
| `csrfTokenValueKey` | `csrfTokenValueKey(): ?string` | Nom de champ pour la valeur du jeton. |
| `csrfTokens` | `csrfTokens(): array<string,string>` | `[ nameKey => name, valueKey => value ]`. |
| `generateCsrfToken` | `generateCsrfToken(): array<string,string>` | Génère et stocke une nouvelle paire. |
| `validateCsrf` | `validateCsrf( string $name, string $value ): bool` | Valide une paire soumise. |

`initializeCsrf()` lit d'abord `$init[CsrfTrait::CSRF]` (la clé `'csrf'`), puis se
rabat sur `$container->get(Guard::class)`. Les valeurs de jeton ne sont
disponibles qu'une fois que le middleware du guard (ou `generateCsrfToken()`) a
peuplé la requête.

```php
use Slim\Csrf\Guard;
use oihana\controllers\traits\CsrfTrait;

/** @var Guard $guard */
$controller = new class { use CsrfTrait; };
$controller->initializeCsrf( [ CsrfTrait::CSRF => $guard ] );

// Dans un contrôleur qui rend un formulaire (pas de middleware dans le pipeline) :
$tokens = $controller->generateCsrfToken();   // [ 'csrf_name' => '...', 'csrf_value' => '...' ]

// À la soumission :
$ok = $controller->validateCsrf( $name, $value ); // bool
```

## `HttpCacheTrait` — en-têtes de cache via `slim/http-cache`

`oihana\controllers\traits\HttpCacheTrait` encapsule un
`Slim\HttpCache\CacheProvider` pour ajouter des en-têtes liés au cache. **Vous
devez appeler `initializeHttpCache()`** — sinon chaque méthode retourne
silencieusement la réponse inchangée.

| Méthode | Signature | Retour |
|---|---|---|
| `initializeHttpCache` | `initializeHttpCache( array $init = [], ?ContainerInterface $container = null ): static` | `$this` |
| `allowCache` | `allowCache( ResponseInterface $response, string $type = 'private', int\|string\|null $maxAge = null, bool $mustRevalidate = false ): ResponseInterface` | `Cache-Control`. |
| `denyCache` | `denyCache( ResponseInterface $response ): ResponseInterface` | No-store / no-cache. |
| `withEtag` | `withEtag( ResponseInterface $response, string $value, string $type = 'strong' ): ResponseInterface` | `ETag`. |
| `withExpires` | `withExpires( ResponseInterface $response, string\|int $time ): ResponseInterface` | `Expires`. |
| `withLastModified` | `withLastModified( ResponseInterface $response, int\|string $time ): ResponseInterface` | `Last-Modified`. |

`initializeHttpCache()` lit d'abord `$init[ControllerParam::HTTP_CACHE]`, puis se
rabat sur `$container->get(CacheProvider::class)`.

```php
use Slim\HttpCache\CacheProvider;
use Psr\Http\Message\ResponseInterface;
use oihana\controllers\traits\HttpCacheTrait;
use oihana\controllers\enums\ControllerParam;

$controller = new class { use HttpCacheTrait; };
$controller->initializeHttpCache( [ ControllerParam::HTTP_CACHE => new CacheProvider() ] );

/** @var ResponseInterface $response */
$response = $controller->allowCache( $response, 'public', 3600, true );
$response = $controller->withEtag( $response, 'abc123' );
$response = $controller->withLastModified( $response, '-1 day' );
```

## Le helper `getController()`

`oihana\controllers\helpers\getController()` résout un `Controller` à partir d'une
définition flexible — pratique pour câbler des routes depuis une configuration.

```php
function getController(
    array|string|null|Controller $definition = null,
    ?ContainerInterface          $container  = null,
    ?Controller                  $default    = null
): ?Controller
```

Il retourne directement la définition lorsqu'elle est déjà un `Controller` ; lit
la clé `ControllerParam::CONTROLLER` (`'controller'`) quand on lui passe un
tableau ; résout un identifiant de service (chaîne) depuis le conteneur ; et
retourne `$default` sinon.

```php
use Psr\Container\ContainerInterface;
use function oihana\controllers\helpers\getController;

/** @var ContainerInterface $container */
$controller = getController( 'app.controllers.home', $container );

// Depuis un tableau de configuration de route :
$controller = getController( [ 'controller' => HomeController::class ], $container, $fallback );
```

## Voir aussi

- [Contrôleur](controller.md) — la classe de base `Controller` et sa composition.
- [Réponses fichier](files.md) — download, streaming, HTTP range, ETag / 304.
- [Index de la documentation](README.md) — la table des matières complète.
