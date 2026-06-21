# Réponses

![Langue](https://img.shields.io/badge/langue-Français-blue)

Les contrôleurs construits sur `oihana/php-controllers` produisent leur sortie HTTP via une petite pile de traits ciblés. Ensemble, ils permettent de sérialiser une charge utile en **JSON** ou en **CBOR**, de définir le bon `Content-Type` et le **code de statut** HTTP, et — lorsqu'un contrat cohérent pour les clients est nécessaire — d'envelopper la charge utile dans une **enveloppe API** standardisée (`status`, `code`, `message`, métadonnées de pagination et `result`).

Les traits se composent proprement :

- [`JsonTrait`](#jsontrait) — sérialise des données dans une réponse PSR-7 JSON.
- [`CborTrait`](#cbortrait) — sérialise des données dans une réponse binaire CBOR.
- [`StatusTrait`](#statustrait) — les helpers de haut niveau (`response()`, `status()`, `fail()`, `success()`) qui négocient le format à partir de l'en-tête `Accept` du client et construisent l'enveloppe. Il agrège `JsonTrait`, `CborTrait`, `BaseUrlTrait` et `LoggerTrait`.
- [`ApiTrait`](#apitrait) — conserve le tableau de réglages `api` du contrôleur, résolu depuis le conteneur DI.

Tous les helpers de réponse opèrent sur un `ResponseInterface` PSR-7 et retournent une **nouvelle** instance de réponse (les messages PSR-7 sont immuables) : utilisez donc toujours la valeur retournée.

## JsonTrait

`JsonTrait` gère les drapeaux d'encodage JSON et produit une réponse PSR-7 JSON. La sérialisation passe par `oihana\reflect\utils\JsonSerializer`, qui respecte à la fois les drapeaux d'encodage entiers et les options structurelles `jsonSerializeOptions` (par exemple `ArrayOption::REDUCE`).

### Propriétés

| Propriété | Type | Défaut | Rôle |
|-----------|------|--------|------|
| `$jsonOptions` | `int` | `JsonParam::JSON_NONE` | Drapeaux binaires de `json_encode` (ex. `JSON_PRETTY_PRINT`). |
| `$jsonSerializeOptions` | `array` | `[ ArrayOption::REDUCE => true ]` | Options structurelles passées à `JsonSerializer`. |

### `initializeJsonOptions( array $init = [], ?ContainerInterface $container = null ): static`

Résout les drapeaux d'encodage et les options du sérialiseur. Ordre de résolution :

1. `$init[ ControllerParam::JSON_OPTIONS ]` pour les drapeaux, et `$init[ ControllerParam::JSON_SERIALIZE_OPTIONS ]` pour les options du sérialiseur ;
2. sinon la clé correspondante dans le conteneur PSR-11 `$container`, si elle est présente.

Les drapeaux d'encodage invalides (ceux non acceptés par `isValidJsonEncodeFlags()`) retombent sur `JsonParam::JSON_NONE`. Retourne `$this` pour le chaînage.

### `jsonResponse( Response $response, mixed $data = null, int $status = HttpStatusCode::OK ): Response`

Encode `$data`, l'écrit dans le corps de la réponse, définit le statut et l'en-tête `Content-Type: application/json`.

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\JsonTrait;
use oihana\enums\http\HttpStatusCode;

class JsonController
{
    use JsonTrait;

    public function show( ServerRequestInterface $request, ResponseInterface $response ): ResponseInterface
    {
        // Indenté, slashes non échappés
        $this->initializeJsonOptions
        ([
            ControllerParam::JSON_OPTIONS => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ]);

        return $this->jsonResponse( $response, [ 'foo' => 'bar' ], HttpStatusCode::OK );
    }
}
```

Le corps contient alors `{"foo":"bar"}` (indenté) et la réponse porte `Content-Type: application/json` avec le code HTTP `200`.

## CborTrait

`CborTrait` reflète `JsonTrait` mais émet du [CBOR](https://cbor.io/) binaire via `oihana\reflect\utils\CborSerializer`. CBOR est un format binaire compact, bien adapté aux API à fort débit ou sensibles à la bande passante.

### Propriété

| Propriété | Type | Défaut | Rôle |
|-----------|------|--------|------|
| `$cborSerializeOptions` | `array` | `[ ArrayOption::REDUCE => true ]` | Options structurelles passées à `CborSerializer`. |

### `initializeCborOptions( array $init = [], ?ContainerInterface $container = null ): static`

Résout `$cborSerializeOptions` depuis `$init[ ControllerParam::CBOR_SERIALIZE_OPTIONS ]` en priorité, puis depuis la clé du conteneur lorsque la valeur fournie est vide. Une valeur non tableau conserve le défaut courant. Retourne `$this`.

### `cborResponse( Response $response, mixed $data = null, int $status = HttpStatusCode::OK ): Response`

Encode `$data` en CBOR, remplace le corps de la réponse par un flux neuf contenant les octets, et définit `Content-Type: application/cbor` ainsi que le `Content-Length` exact. Tout tampon de sortie en attente est vidé au préalable via `ob_clean()`, afin que la charge utile binaire ne soit jamais polluée par une sortie parasite.

```php
use Psr\Http\Message\ResponseInterface;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\CborTrait;
use oihana\core\options\ArrayOption;
use oihana\enums\http\HttpStatusCode;

class CborController
{
    use CborTrait;

    public function export( ResponseInterface $response ): ResponseInterface
    {
        $this->initializeCborOptions
        ([
            ControllerParam::CBOR_SERIALIZE_OPTIONS => [ ArrayOption::REDUCE => false ]
        ]);

        return $this->cborResponse( $response, [ 'foo' => 'bar' ], HttpStatusCode::CREATED );
    }
}
```

La réponse porte `Content-Type: application/cbor`, un `Content-Length` correct et le code HTTP `201`.

## StatusTrait

`StatusTrait` est le point d'entrée de haut niveau. Il compose `JsonTrait`, `CborTrait`, `BaseUrlTrait` et `LoggerTrait`, et expose les helpers qu'un contrôleur appelle réellement. Chaque helper négocie le format de sortie : il inspecte l'en-tête `Accept` (ou un argument `$accept` explicite) et délègue à `cborResponse()` pour `application/cbor` / `application/cbor-seq`, en retombant sur `jsonResponse()` sinon.

### `response( Response $response, mixed $data = null, int $status = 200, ?string $accept = null ): Response`

La primitive de négociation de format. Utilisez-la lorsque vous voulez simplement retourner les données brutes `$data` dans le format demandé par le client.

```php
return $this->response( $response, $data, 200, $request->getHeaderLine( 'Accept' ) );
```

### `status( ?Request $request, ?Response $response, mixed $message = '', int|string|null $code = 200, ?array $options = null, ?string $accept = null ): ?Response`

Produit une enveloppe de statut générique. Le corps est construit à partir de :

- `Output::STATUS` — le *type* de statut dérivé du code (`HttpStatusCode::getType()`) ;
- `Output::CODE` — le code entier ;
- `Output::MESSAGE` — le message ;
- toute clé supplémentaire de `$options`, fusionnée.

Retourne `null` lorsque `$response` vaut `null`.

```php
return $this->status( $request, $response, 'bad request', 400 );
```

Produit (en JSON) :

```json
{ "status": "error", "code": 400, "message": "bad request" }
```

### `fail( ?Request $request, ?Response $response, string|int|null $code = 400, ?string $details = null, array $options = [], ?string $accept = null ): ?Response`

Une spécialisation de `status()` pour les erreurs. Elle :

- valide le code par rapport à `HttpStatusCode` (les codes inconnus retombent sur `HttpStatusCode::DEFAULT`) ;
- dérive le `$message` lisible depuis `HttpStatusCode::getDescription( $code )` ;
- lorsque `$details` est une chaîne non vide, l'ajoute sous `Output::DETAILS` ;
- journalise l'erreur (classe, code, message et détails optionnels) lorsque `$this->loggable` vaut `true`.

```php
return $this->fail
(
    $request,
    $response,
    406,
    'fields validation failed',
    [
        'firstName' => 'firstName is required',
        'lastName'  => 'lastName must be a string'
    ]
);
```

Produit (en JSON) :

```json
{
    "status": "error",
    "code": 406,
    "message": "Not Acceptable",
    "firstName": "firstName is required",
    "lastName": "lastName must be a string",
    "details": "fields validation failed"
}
```

### `success( ?Request $request, ?Response $response, mixed $data = null, ?array $init = null, ?string $accept = null ): mixed`

Enveloppe une charge utile réussie dans l'enveloppe API. L'enveloppe commence toujours par `status: "success"` et se termine par la charge utile sous `Output::RESULT`. Le tableau optionnel `$init` l'enrichit de métadonnées — seules les valeurs du bon type et de la bonne plage sont ajoutées :

| Clé `$init` | Type | Clé d'enveloppe |
|-------------|------|-----------------|
| `Output::COUNT` | `int >= 0` | `count` |
| `Output::LIMIT` | `int` | `limit` |
| `Output::OFFSET` | `int` | `offset` |
| `Output::POSITION` | `int >= 0` | `position` |
| `Output::TOTAL` | `int >= 0` | `total` |
| `Output::URL` | `string` | `url` (par défaut `getCurrentPath()`) |
| `Output::OWNER` | `array`/`object` | `owner` |
| `Output::PARAMS` | `array` | alimente `getCurrentPath()` |
| `Output::OPTIONS` | `array` | fusionné dans l'enveloppe |
| `Output::STATUS` | `int` | code de statut HTTP (défaut `200`) |

Lorsque `$response` vaut `null`, `success()` retourne `$data` inchangé — pratique lorsqu'un sous-contrôleur veut la valeur brute plutôt qu'une réponse HTTP.

```php
use oihana\enums\Output;

return $this->success
(
    $request,
    $response,
    $data,
    [
        Output::COUNT  => count( $data ),
        Output::PARAMS => $request->getQueryParams()
    ]
);
```

Produit (en JSON) :

```json
{
    "status": "success",
    "url": "/current/path",
    "count": 12,
    "result": [ /* ...$data... */ ]
}
```

### `successWithNewBody( ... )` et `withFreshBody( ?Response $response ): ?Response`

`withFreshBody()` retourne la même réponse avec un flux de corps **vide**, en écartant tout ce qu'un acteur en amont (un sous-contrôleur ou un middleware) aurait déjà écrit. `successWithNewBody()` est `success()` appliqué sur un corps neuf : utilisez-le lorsqu'un appel précédent a déjà écrit dans le corps PSR-7 partagé et qu'un `success()` simple concaténerait deux enveloppes — JSON invalide pour les parseurs stricts.

```php
// Écarte une écriture en amont, puis émet une seule enveloppe d'erreur propre
return $this->fail( $request, $this->withFreshBody( $response ), 502, 'zitadel_sync_failed' );
```

## ApiTrait

`ApiTrait` conserve les réglages au niveau API d'un contrôleur dans une unique propriété `protected array $api` — typiquement des valeurs comme le nom de l'API, sa version ou la configuration publique de base que vous voulez cohérentes entre contrôleurs.

### `initializeApi( array $init = [], ?ContainerInterface $container = null ): static`

Résout les réglages `api`. Le conteneur PSR-11 a priorité : si `$container` possède `ControllerParam::API`, sa valeur l'emporte sur `$init[ ControllerParam::API ]`. Un résultat non tableau retombe sur un tableau vide. Retourne `$this`.

```php
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ApiTrait;

class ApiController
{
    use ApiTrait;

    public function boot(): void
    {
        $this->initializeApi
        ([
            ControllerParam::API => [ 'name' => 'my-api', 'version' => 2 ]
        ]);
    }
}
```

Lorsque à la fois une valeur `$init` et une définition de conteneur sont présentes, la valeur du conteneur est utilisée — ce qui permet à la configuration de déploiement de surcharger les valeurs par défaut du contrôleur.

## Voir aussi

- [Modèles](models.md) — câbler les contrôleurs aux modèles de données.
- [Réponses de fichiers](files.md) — téléchargement, streaming, plages HTTP, ETag / 304, chiffrement, images.
- [Index de la documentation](README.md) — retour à la table des matières.
