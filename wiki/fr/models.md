# Modèles

Cette page décrit les traits qui relient un contrôleur à son **modèle de
données**. Dans la pile *oihana*, la persistance est déléguée à un modèle
[`oihana/php-models`](https://github.com/BcommeBois/oihana-php-models) — en
général un `DocumentsModel` — résolu depuis le conteneur PSR-11. Le contrôleur
reste léger : il extrait les paramètres, appelle le modèle, met en forme les
documents pour la réponse et valide les entrées. Chacun des traits ci-dessous
prend en charge l'une de ces responsabilités.

Tous les exemples s'appuient sur le code source réel des traits et sur leurs
tests PHPUnit. Lorsqu'un modèle est nécessaire, ils utilisent la fixture
partagée `MockDocumentsModel`
(`tests/oihana/models/mocks/MockDocumentsModel.php`), une implémentation
en mémoire de `DocumentsModel`.

## `ModelCallTrait` — hooks de cycle de vie autour des appels au modèle

`oihana\controllers\traits\ModelCallTrait` définit deux hooks d'extensibilité
**protégés** qu'un contrôleur de base invoque autour de chaque opération
principale du modèle (`list` / `get` / `last` / `count` / `insert` / `update` /
`replace` / `delete`) :

| Méthode | Signature | Rôle |
|---|---|---|
| `beforeModelCall` | `beforeModelCall( ?Request $request , array &$init ) : void` | Enrichir la charge `$init` avant qu'elle n'atteigne le modèle. |
| `afterModelCall` | `afterModelCall( ?Request $request , array &$init , mixed &$result ) : void` | Inspecter ou transformer le résultat du modèle. |

Les deux implémentations par défaut sont des **no-ops** ; `$init` (et `$result`
pour le hook *after*) sont passés **par référence**, ce qui permet à une
surcharge de les muter sur place. Le cycle de vie est :

```text
beforeModelCall($request, $init)
    ↓
$result = $this->model->operation($init)
    ↓
afterModelCall($request, $init, $result)
```

Surchargez les hooks pour centraliser les préoccupations liées à la requête —
injection de l'utilisateur courant, normalisation des filtres, journalisation ou
post-traitement — au lieu de répéter la logique dans chaque verbe HTTP :

```php
use oihana\controllers\traits\ModelCallTrait;

use Psr\Http\Message\ServerRequestInterface as Request;

class ArticlesController
{
    use ModelCallTrait ;

    // Injecte un filtre lié à la requête pour chaque opération CRUD.
    protected function beforeModelCall( ?Request $request , array &$init ) : void
    {
        $init[ 'locale' ] = $request?->getHeaderLine( 'Accept-Language' ) ?: 'en' ;
    }

    // Estampille le résultat avant qu'il ne soit rendu à l'action.
    protected function afterModelCall( ?Request $request , array &$init , mixed &$result ) : void
    {
        if ( is_array( $result ) )
        {
            $result[ 'fetchedAt' ] = time() ;
        }
    }
}
```

## `OutputDocumentsTrait` — rendu des documents dans une réponse

`oihana\controllers\traits\OutputDocumentsTrait` transforme un tableau brut de
documents en une réponse de succès standardisée. Il compose `BaseUrlTrait`
(génération d'URL) et `StatusTrait` (formatage de la réponse).

| Méthode | Signature | Rôle |
|---|---|---|
| `outputDocuments` | `outputDocuments( ?Request, ?Response, ?array $documents, array $params = [], ?array $options = null ) : array\|object\|null` | Encapsuler les documents dans une réponse **si un `$response` est fourni**, sinon retourner le tableau brut. |
| `documentsResponse` | `documentsResponse( ?Request, ?Response, ?array $documents, array $params = [], ?array $options = null ) : ?object` | Construire la charge de succès (`count`, `options`, `url`). |
| `getDocumentUrl` | `getDocumentUrl( ?Request, array $params = [] ) : string` | L'URL du document ; par défaut `BaseUrlTrait::getCurrentPath()`. Surchargeable. |

`outputDocuments()` est la méthode appelée depuis une action. Lorsqu'un
`$response` est fourni, les documents sont encapsulés via `documentsResponse()`
(qui retire au passage les entrées `null` de `$params` avant la génération de
l'URL) ; sans réponse, les documents bruts sont retournés — pratique en contexte
CLI ou de test.

```php
use oihana\controllers\traits\OutputDocumentsTrait;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use tests\oihana\models\mocks\MockDocumentsModel;

class CatalogController
{
    use OutputDocumentsTrait ;

    public function index( Request $request , Response $response ) : Response
    {
        $model = new MockDocumentsModel()->addDocuments
        ([
            [ 'id' => 1 , 'name' => 'foo' ] ,
            [ 'id' => 2 , 'name' => 'bar' ] ,
        ]);

        $documents = $model->list() ;

        // Avec une réponse : un objet de succès encapsulé (count, options, url).
        return $this->outputDocuments
        (
            $request ,
            $response ,
            $documents ,
            [ 'page' => 1 ] ,        // $params — sert à construire l'url
            [ 'extra' => true ]      // $options — renvoyées telles quelles dans la réponse
        );
    }
}
```

Sans `$response`, le même appel retourne les documents inchangés :

```php
$result = $this->outputDocuments( $request , null , $documents ) ;
// $result === $documents
```

L'URL par défaut provient du chemin de la requête courante joint à `baseUrl` :

```php
$this->baseUrl = '/api' ;
$this->getDocumentUrl( $request ) ; // '/api/documents' pour une requête vers /documents
```

## `CheckOwnerArgumentsTrait` — contrôles de propriété

`oihana\controllers\traits\CheckOwnerArgumentsTrait` vérifie que les arguments
« propriétaire » passés à une action correspondent bien à des enregistrements
existants, et lève une erreur HTTP sinon. Il compose `DocumentsTrait` et expose
une propriété publique `?array $owner` associant *nom d'argument → modèle*.

| Membre | Signature | Rôle |
|---|---|---|
| `$owner` | `public ?array $owner` | Association d'un nom d'argument à un `DocumentsModel` (ou son identifiant de conteneur). |
| `initializeOwner` | `initializeOwner( array $init = [] ) : static` | Lire la clé `owner` (`ControllerParam::OWNER`) depuis `$init`. |
| `checkOwnerArguments` | `checkOwnerArguments( array $args = [] ) : void` | Valider chaque argument propriétaire présent dans `$args`. |

Pour chaque entrée de `$owner` dont l'argument est présent dans `$args`, le
modèle est résolu avec `getDocumentsModel()` (une chaîne est donc cherchée dans
le conteneur) et sa méthode `exist()` est appelée :

- un argument absent est **ignoré** (aucune erreur) ;
- si la valeur n'existe pas, une `Error404` est levée ;
- si la référence est `null` ou n'est pas un `ExistModel`, une `Error500` est levée.

```php
use oihana\controllers\traits\CheckOwnerArgumentsTrait;

use DI\Container;

use tests\oihana\models\mocks\MockDocumentsModel;

$controller = new class( new Container() )
{
    use CheckOwnerArgumentsTrait ;

    public function __construct( public Container $container ) {}
};

$users = new MockDocumentsModel()->addDocuments
([
    [ 'id' => 1 , 'name' => 'foo' ] ,
    [ 'id' => 2 , 'name' => 'bar' ] ,
]);

$controller->owner = [ 'userId' => $users ] ;

$controller->checkOwnerArguments( [ 'userId' => 1 ] ) ; // OK, l'utilisateur n°1 existe
$controller->checkOwnerArguments( [] ) ;                // OK, argument absent → ignoré
$controller->checkOwnerArguments( [ 'userId' => 999 ] ) ; // lève Error404
```

Le modèle peut aussi être un **identifiant de conteneur** résolu au moment du
contrôle :

```php
$container = new Container() ;
$container->set( 'documents.user' , new MockDocumentsModel()->addDocument( [ 'id' => 5 ] ) ) ;

$controller->owner = [ 'userId' => 'documents.user' ] ;
$controller->checkOwnerArguments( [ 'userId' => 5 ] ) ; // résolu depuis le conteneur
```

`initializeOwner()` lit la définition depuis un tableau `$init`, la forme
utilisée lors de l'initialisation d'un contrôleur :

```php
$controller->initializeOwner( [ 'owner' => [ 'accountId' => $accountModel ] ] ) ;
```

## `ForceDocumentUrlTrait` — injection d'URL de document

`oihana\controllers\traits\ForceDocumentUrlTrait` ajoute une propriété `url` aux
documents — pratique pour exposer un lien vers soi-même dans les résultats de
`get()` et `list()`.

| Membre | Signature | Rôle |
|---|---|---|
| `$documentKey` | `public ?string $documentKey` | La clé primaire utilisée pour construire les URL par document (défaut `ControllerParam::ID` = `'id'`). |
| `$forceUrl` | `public bool $forceUrl` | Indique si le contrôleur doit forcer une URL (défaut `false`). |
| `forceDocumentUrl` | `forceDocumentUrl( null\|object\|array &$document , ?string $url , string $propertyName = ControllerParam::URL ) : object\|array\|null` | Définir `$url` sur un **seul** document. |
| `forceDocumentsUrl` | `forceDocumentsUrl( null\|array &$documents , ?string $url , ?string $key = null , string $propertyName = ControllerParam::URL ) : void` | Ajouter `"$url/$key"` sur **chaque** document d'une liste. |
| `initializeForceUrl` | `initializeForceUrl( array $init = [] ) : static` | Lire `documentKey` / `forceUrl` depuis `$init`. |

`forceDocumentUrl()` définit la propriété telle quelle sur une ressource unique
(tableau associatif ou objet ; les tableaux séquentiels et `null` sont laissés
intacts). `forceDocumentsUrl()` construit `"$url/<valeur de la clé>"` pour chaque
élément qui possède la clé.

```php
use oihana\controllers\traits\ForceDocumentUrlTrait;

$controller = new class
{
    use ForceDocumentUrlTrait ;

    public function expose( null|object|array &$document , ?string $url ) : object|array|null
    {
        return $this->forceDocumentUrl( $document , $url ) ;
    }

    public function exposeAll( null|array &$documents , ?string $url , ?string $key = null ) : void
    {
        $this->forceDocumentsUrl( $documents , $url , $key ) ;
    }
};

// Document unique : l'url est définie telle quelle.
$document = [ 'id' => 1 , 'name' => 'foo' ] ;
$controller->expose( $document , '/api/foo' ) ;
// $document['url'] === '/api/foo'

// Liste de documents : l'url devient "<base>/<valeur de la clé>".
$documents = [ [ 'id' => 1 ] , [ 'id' => 2 ] ] ;
$controller->exposeAll( $documents , '/api' , 'id' ) ;
// $documents[0]['url'] === '/api/1'
// $documents[1]['url'] === '/api/2'
```

Lorsqu'aucune `$key` n'est passée, `forceDocumentsUrl()` se rabat sur
`$documentKey`, que vous pouvez définir via `initializeForceUrl()` :

```php
$controller->initializeForceUrl([ 'documentKey' => 'id' , 'forceUrl' => true ]) ;

$documents = [ (object) [ 'id' => 7 ] ] ;
$controller->exposeAll( $documents , '/api' ) ; // utilise la clé par défaut 'id'
// $documents[0]->url === '/api/7'
```

## `ValidatorTrait` — validation des entrées

`oihana\controllers\traits\ValidatorTrait` intègre la bibliothèque
[somnambulist/validation](https://github.com/somnambulist-tech/validation). Il
compose `ContainerTrait` et `StatusTrait` et détient un `Validator $validator`
public, ainsi que `array $rules` et `array $customRules`.

| Méthode | Signature | Rôle |
|---|---|---|
| `initializeValidator` | `initializeValidator( array $init = [] ) : static` | Définir `validator`, `customRules` et `rules` depuis `$init`, puis enregistrer les règles personnalisées. |
| `addRules` | `addRules( array $rules = [] ) : void` | Enregistrer des règles supplémentaires sur le validateur (voir ci-dessous). |
| `prepareRules` | `prepareRules( ?string $method = null ) : array` | Fusionner les règles `ALL` avec celles d'une méthode HTTP donnée. |
| `initCustomValidationRules` | `initCustomValidationRules() : array` | Retourner `customRules` ; surchargeable pour étendre les valeurs par défaut. |
| `getValidatorError` | `getValidatorError( ?Request, ?Response, Validation $validation, array $errors = [], int\|string $code = 400 ) : ?Response` | Construire une réponse d'échec en fusionnant les erreurs de validation. |

### `addRules` — instances de règle ou références de conteneur

`addRules()` accepte une association *champ → règle*. Chaque valeur peut être une
instance `Somnambulist\Components\Validation\Rule`, ou une **chaîne** référençant
une règle enregistrée dans le conteneur — auquel cas elle est résolue via
`$this->container->get()`. Les valeurs qui résolvent vers autre chose qu'un
`Rule` sont silencieusement ignorées.

```php
use oihana\controllers\traits\ValidatorTrait;

use DI\Container;

use Somnambulist\Components\Validation\Factory as Validator;
use Somnambulist\Components\Validation\Rule;

$container = new Container() ;

$controller = new class( $container )
{
    use ValidatorTrait ;

    public function __construct( public Container $container )
    {
        $this->validator = new Validator() ;
    }
};

// 1. Une instance de Rule, enregistrée directement.
$controller->addRules( [ 'slug' => new MyUppercaseRule() ] ) ; // n'importe quelle sous-classe de Rule

// 2. Une référence de conteneur (chaîne), résolue en Rule à l'enregistrement.
$container->set( 'my_custom_rule' , new MyUppercaseRule() ) ;
$controller->addRules( [ 'slug' => 'my_custom_rule' ] ) ;
```

### `prepareRules` — jeux de règles par méthode

Organisez `$rules` par méthode HTTP (avec les constantes `HttpMethod`).
`prepareRules()` fusionne les règles partagées `ALL` avec celles propres à la
méthode demandée :

```php
use oihana\enums\http\HttpMethod;

$controller->rules =
[
    HttpMethod::ALL  => [ 'email' => 'required|email'  ] ,
    HttpMethod::POST => [ 'name'  => 'required|string' ] ,
];

$controller->prepareRules( HttpMethod::POST ) ;
// [ 'email' => 'required|email' , 'name' => 'required|string' ]

$controller->prepareRules( null ) ;
// [ 'email' => 'required|email' ]  (uniquement les règles ALL)
```

### `getValidatorError` — transformer les échecs en réponse

À partir d'un résultat `Validation`, `getValidatorError()` produit une réponse
d'échec. Lorsque la validation **échoue**, sa première erreur par champ
(`$validation->errors()->firstOfAll()`) est fusionnée avec les `$errors` fournis
sous la clé `Output::ERRORS` ; le `$code` vaut `400` par défaut.

```php
$validation = $this->validator->validate( $request->getParsedBody() , $rules ) ;

if ( $validation->fails() )
{
    return $this->getValidatorError
    (
        $request ,
        $response ,
        $validation ,
        [ 'context' => 'create' ] , // erreurs supplémentaires fusionnées
        422                         // code de statut HTTP
    );
}
```

Une action complète assemble les pièces : préparer les règles, valider, puis soit
retourner la réponse d'erreur, soit appeler le modèle :

```php
use oihana\enums\http\HttpMethod;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

public function create( Request $request , Response $response ) : Response
{
    $rules      = $this->prepareRules( HttpMethod::POST ) ;
    $validation = $this->validator->validate( (array) $request->getParsedBody() , $rules ) ;

    if ( $validation->fails() )
    {
        return $this->getValidatorError( $request , $response , $validation ) ;
    }

    $document  = $this->model->insert( [ 'document' => (array) $request->getParsedBody() ] ) ;
    return $this->outputDocuments( $request , $response , [ $document ] ) ;
}
```

## Voir aussi

- [Réponses](responses.md) — JSON, CBOR, statut et sortie API (`StatusTrait`).
- [Paramètres](params.md) — extraction typée des paramètres de requête.
- [Index de la documentation](README.md) — la table des matières complète.
- [oihana/php-models](https://github.com/BcommeBois/oihana-php-models) — la couche
  modèle (`DocumentsModel`, les interfaces CRUD et les traits de support).
