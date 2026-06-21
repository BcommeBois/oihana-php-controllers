# Contrôleur

`oihana\controllers\Controller` est la classe de base composable que tout point
d'entrée d'`oihana/php-controllers` étend. C'est une classe **abstraite** câblée
à un conteneur [PSR-11](https://www.php-fig.org/psr/psr-11/) (le `DI\Container`
de PHP-DI) : son constructeur reçoit le conteneur, le stocke dans la propriété
publique `$container`, puis exécute une chaîne d'appels `initializeXxx()` qui
amorcent chacun des aspects dont le contrôleur a besoin.

Plutôt qu'un bloc monolithique, `Controller` est *assemblé* à partir d'un
ensemble de petits traits à responsabilité unique. Chaque trait porte une seule
fonctionnalité et expose sa propre méthode `initializeXxx()`, ce qui vous permet
de réutiliser ces mêmes briques dans vos propres classes. Les groupes de traits,
et les pages qui les documentent, sont :

- [Paramètres](params.md) — extraction typée des paramètres de requête et les stratégies `prepare`.
- [Pagination](pagination.md) — `PaginationTrait`, limites et tri.
- [Réponses](responses.md) — JSON, CBOR, statut et sortie API.
- [Réponses de fichiers](files.md) — téléchargement, streaming, HTTP range, ETag / 304, chiffrement, images.
- [Twig](twig.md) — rendu des vues Twig.
- [Langues](languages.md) — négociation de langue et helpers i18n.
- [Routage](routing.md) — routes, redirections, URL de base, CSRF et cache HTTP.
- [Modèles](models.md) — branchement des contrôleurs aux modèles de données.

En plus des traits, `Controller` ajoute lui-même quelques helpers de routage :

- `getAllowedMethods( ?Request $request ) : array` — les méthodes HTTP autorisées pour la route courante (tableau vide si aucune requête n'est fournie).
- `getRoute( ?Request $request ) : ?RouteInterface` — la route Slim associée à la requête (ou `null`).
- `redirectResponse( Response $response , string $url , int $status = HttpStatusCode::FOUND ) : Response` — une réponse de redirection HTTP portant un en-tête `Location` (statut par défaut `302`).

Il expose également les propriétés de chemin `$path`, `$fullPath`, `$ownerPath`
et le tableau `$conditions`.

## Construire un contrôleur

Étendez `Controller`, injectez un `DI\Container` et ajoutez vos méthodes de
traitement. Chaque méthode suit la signature standard PSR-7 / Slim.

```php
use DI\Container;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\Controller;

class UserController extends Controller
{
    public function list( Request $request, Response $response, array $args ): Response
    {
        $users = [ [ 'id' => 1, 'name' => 'Ada' ] , [ 'id' => 2, 'name' => 'Alan' ] ] ;
        return $this->jsonResponse( $response, $users ) ;
    }

    public function redirectHome( Request $request, Response $response ): Response
    {
        return $this->redirectResponse( $response, '/' ) ;
    }
}

$container  = new Container() ;
$controller = new UserController( $container ) ;
```

Le constructeur accepte aussi un tableau d'options `$init` facultatif dont les
clés proviennent de l'énumération `oihana\controllers\enums\ControllerParam` —
par exemple `ControllerParam::APP` (`'app'`), `ControllerParam::ROUTER`
(`'router'`), `ControllerParam::PATH` (`'path'`), `ControllerParam::BENCH`
(`'bench'`) et `ControllerParam::MOCK` (`'mock'`) :

```php
use oihana\controllers\enums\ControllerParam;

$controller = new UserController( $container , [
    ControllerParam::PATH  => 'users' ,
    ControllerParam::BENCH => true ,
] ) ;
```

## Benchmarking

`oihana\controllers\traits\BenchTrait` mesure le temps d'exécution d'une méthode
de traitement. C'est l'un des traits déjà composés dans `Controller`, et il
expose :

- `public bool $bench` — le drapeau de benchmarking (par défaut `false`).
- `initializeBench( bool|array $init = [] ) : static` — définit `$bench` à partir d'un booléen ou de la clé `ControllerParam::BENCH` d'un tableau `$init`.
- `startBench( ?Request $request, array $args = [], ?array &$params = null ) : null|float|int` — renvoie le `microtime( true )` courant lorsque le benchmarking est activé (une fois `prepareBench()` validé), sinon `0`.
- `endBench( null|int|float $timestamp, array &$options = [] ) : ?string` — arrête le bench et renvoie une **chaîne de durée lisible** construite avec [`oihana\core\date\humanizeDuration`](https://bcommebois.github.io/oihana-php-core). Elle écrit aussi cette chaîne dans `$options[ Output::TIME ]`. Renvoie `null` lorsqu'aucun timestamp valide n'est fourni.

```php
use oihana\enums\Output;

$controller->bench = true ;

$options = [] ;
$start   = $controller->startBench( null ) ; // microtime flottant, ou 0 si bench désactivé

usleep( 100000 ) ; // exécution du travail (0,1 s ici)

$duration = $controller->endBench( $start , $options ) ;

// $duration est une chaîne lisible, également stockée dans $options
echo $duration ;                // p. ex. "100 ms"
echo $options[ Output::TIME ] ; // même valeur
```

Lorsque le timestamp est absent ou non positif, le bench est sans effet :

```php
$options = [] ;
$result  = $controller->endBench( null , $options ) ;

var_dump( $result ) ;  // NULL
var_dump( $options ) ; // [] — inchangé
```

## Mocking

`oihana\controllers\traits\MockTrait` porte un unique drapeau servant à basculer
un contrôleur (et les modèles qu'il pilote) en mode mock — pratique pour les
tests et le développement local, lorsqu'on souhaite des données simulées plutôt
qu'un vrai backend.

Il fournit :

- `public ?bool $mock` — le drapeau de mock (par défaut `null`, c.-à-d. non défini).
- `initializeMock( bool|array $init = [] ) : static` — définit `$mock` à partir d'un booléen, ou de la clé `ControllerParam::MOCK` d'un tableau `$init` ; vaut `null` par défaut lorsqu'aucun des deux n'est présent.

```php
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\MockTrait;

$service = new class { use MockTrait ; } ;

$service->initializeMock( true ) ;
var_dump( $service->mock ); // bool(true)

$service->initializeMock( [ ControllerParam::MOCK => true ] ) ;
var_dump( $service->mock ); // bool(true)

$service->initializeMock() ;
var_dump( $service->mock ); // NULL — pas de clé mock, reste non défini
```

## Voir aussi

- [Paramètres](params.md) — extraction typée des paramètres de requête.
- [Réponses](responses.md) — JSON, CBOR et sortie API.
- [Index de la documentation](README.md) — table des matières complète.
