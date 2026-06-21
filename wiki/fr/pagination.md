# Pagination

![Langue](https://img.shields.io/badge/langue-Français-blue)

La pagination d'une collection dans `oihana/php-controllers` est répartie entre trois petits traits à responsabilité unique :

- **`PaginationTrait`** détient une définition `Pagination` typée — les métadonnées de pagination à l'échelle de l'application/de l'API (`limit`, `offset`, `page`, `numberOfPages`, …), résolues depuis un tableau d'initialisation ou un conteneur PSR-11.
- **`LimitTrait`** détient la fenêtre `limit` / `offset` propre au contrôleur ainsi que ses bornes `minLimit` / `maxLimit`.
- **`SortAfterTrait`** réordonne un jeu de résultats déjà récupéré selon une propriété imbriquée déclarée sur le modèle.

Les métadonnées de pagination elles-mêmes correspondent à l'objet schéma [`xyz\oihana\schema\Pagination`](https://packagist.org/packages/oihana/php-schema) — un objet-valeur compatible JSON-LD dont les noms de propriétés (`limit`, `maxLimit`, `minLimit`, `offset`, `page`, `numberOfPages`) sont aussi exposés via les constantes `Pagination::LIMIT`, `Pagination::OFFSET`, …

## `PaginationTrait`

`oihana\controllers\traits\PaginationTrait` donne au contrôleur une seule propriété publique et un initialiseur :

| Membre | Type | Rôle |
|---|---|---|
| `$pagination` | `?Pagination` | La définition de pagination résolue (ou `null`). |
| `initializePagination( array $init = [], ?ContainerInterface $container = null ): static` | — | Renseigne `$pagination`, retourne `$this` pour le chaînage. |

`initializePagination()` résout la définition dans cet ordre :

1. Depuis `$init[ ControllerParam::PAGINATION ]` (la clé `'pagination'`) lorsqu'elle est présente.
2. Sinon, si un conteneur est fourni et que `$container->has('pagination')`, depuis `$container->get('pagination')`.
3. Si la valeur résolue est un **tableau**, elle est encapsulée dans un `new Pagination( $array )`.
4. Si c'est déjà une instance de `Pagination`, elle est utilisée telle quelle ; toute autre valeur laisse `$pagination` à `null`.

### Fournir une instance prête à l'emploi

```php
use xyz\oihana\schema\Pagination;
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\PaginationTrait;

$controller = new class { use PaginationTrait; };

$pagination = new Pagination();
$controller->initializePagination( [ ControllerParam::PAGINATION => $pagination ] );

$controller->pagination === $pagination; // true
```

### Fournir un tableau de configuration

Lorsque la valeur est un tableau, elle est hydratée en un objet `Pagination`, de sorte que ses clés deviennent des propriétés typées :

```php
use oihana\controllers\enums\ControllerParam;

$controller->initializePagination(
[
    ControllerParam::PAGINATION => [ 'limit' => 10, 'page' => 2 ],
] );

$controller->pagination->limit; // 10
$controller->pagination->page;  // 2
```

### Résoudre depuis un conteneur PSR-11

Si aucune clé `pagination` n'est présente dans `$init`, la définition est récupérée depuis le conteneur sous l'identifiant de service `'pagination'`. Un tableau stocké dans le conteneur est hydraté de la même manière :

```php
use DI\Container;

$container = new Container();
$container->set( 'pagination', [ 'limit' => 50, 'offset' => 10 ] );

$controller->initializePagination( container: $container );

$controller->pagination->limit;  // 50
$controller->pagination->offset; // 10
```

Une instance de `Pagination` stockée dans le conteneur est retournée intacte (sans ré-encapsulation).

## `LimitTrait`

`oihana\controllers\traits\LimitTrait` porte la fenêtre de pagination propre au contrôleur — le `limit`/`offset` réellement appliqué à une requête — ainsi que les bornes utilisées pour borner une limite fournie par le client.

| Propriété | Type | Rôle |
|---|---|---|
| `$limit` | `?int` | Nombre d'éléments par page par défaut. |
| `$maxLimit` | `?int` | Borne supérieure pour `limit`. |
| `$minLimit` | `?int` | Borne inférieure pour `limit`. |
| `$offset` | `?int` | Nombre d'éléments à ignorer. |

`initializeLimit( array $init = [] ): static` lit chaque valeur depuis `$init` à l'aide des constantes de clé `Pagination`, en retombant sur la valeur **courante** de la propriété lorsqu'une clé est absente (de sorte qu'il n'écrase jamais ce qui est déjà défini), puis retourne `$this`.

```php
use xyz\oihana\schema\Pagination;
use oihana\controllers\traits\LimitTrait;

$controller = new class { use LimitTrait; };

$controller->initializeLimit(
[
    Pagination::LIMIT     => 25,
    Pagination::MAX_LIMIT => 200,
    Pagination::MIN_LIMIT => 5,
    Pagination::OFFSET    => 50,
] );

$controller->limit;    // 25
$controller->maxLimit; // 200
$controller->minLimit; // 5
$controller->offset;   // 50
```

Comme les clés absentes retombent sur la valeur existante, une initialisation partielle ne remplace que ce que vous transmettez :

```php
$controller->limit    = 10;
$controller->maxLimit = 100;

$controller->initializeLimit( [ Pagination::LIMIT => 20 ] ); // seul limit change

$controller->limit;    // 20
$controller->maxLimit; // 100 (inchangé)
```

Appeler `initializeLimit()` sans argument laisse chaque propriété telle quelle, et transmettre explicitement `null` pour une clé met cette propriété à `null`.

### Comment limit, offset et métadonnées s'articulent

`LimitTrait` fournit la *fenêtre* (`limit`/`offset`) et les *bornes* (`minLimit`/`maxLimit`) qu'un contrôleur utilise pour construire sa requête ; `PaginationTrait` fournit l'*objet de métadonnées* (`Pagination`) que vous retournez avec la page afin qu'un client sache où il se situe. Un contrôleur compose typiquement les deux — borner le `limit` du client entre `minLimit` et `maxLimit`, dériver `offset` depuis la page demandée, exécuter la requête, puis exposer un `Pagination` décrivant le résultat :

```php
use xyz\oihana\schema\Pagination;
use oihana\controllers\traits\LimitTrait;
use oihana\controllers\traits\PaginationTrait;

class ProductsController
{
    use LimitTrait, PaginationTrait;
}

$controller = new ProductsController();
$controller->initializeLimit( [ Pagination::MIN_LIMIT => 1, Pagination::MAX_LIMIT => 100 ] );

// borner une limite fournie par le client dans les bornes configurées
$requested = 250;
$limit     = max( $controller->minLimit, min( $requested, $controller->maxLimit ) ); // 100

$page   = 3;
$offset = ( $page - 1 ) * $limit; // 200

$meta              = new Pagination();
$meta->limit       = $limit;
$meta->offset      = $offset;
$meta->page        = $page;
```

## `SortAfterTrait`

`oihana\controllers\traits\SortAfterTrait` réordonne un jeu de résultats **en mémoire**, une fois récupéré, selon une règle de tri déclarée sur le `model` du contrôleur.

`sortAfter( $items )` examine `$this->model->sortable['after']`. Lorsque cette entrée existe et qu'il s'agit d'un chemin pointé `"objet.propriété"` d'exactement deux segments, les éléments sont triés avec `usort()` en comparant la propriété imbriquée `$item->{$segment0}->{$segment1}` via `strcmp()`. Dans tout autre cas — pas de modèle, pas de clé `after`, ou un chemin qui ne comporte pas exactement deux segments — les éléments sont retournés inchangés.

```php
use oihana\controllers\traits\SortAfterTrait;

$controller = new class
{
    use SortAfterTrait;
    public ?object $model = null;
};

// le modèle déclare une règle de tri "after" à deux segments
$controller->model = (object) [ 'sortable' => [ 'after' => 'group.label' ] ];

$items =
[
    (object) [ 'group' => (object) [ 'label' => 'charlie' ] ],
    (object) [ 'group' => (object) [ 'label' => 'alpha'   ] ],
    (object) [ 'group' => (object) [ 'label' => 'bravo'   ] ],
];

$sorted = $controller->sortAfter( $items );

array_map( fn( $i ) => $i->group->label, $sorted );
// [ 'alpha', 'bravo', 'charlie' ]
```

Si le modèle est `null`, si le tableau `sortable` ne contient pas de clé `after`, ou si `after` n'est pas un chemin à deux segments, `sortAfter()` retourne le tableau d'origine intact :

```php
$controller->model = null;
$controller->sortAfter( $items ) === $items; // true
```

Cela fait de `SortAfterTrait` l'étape finale d'une lecture paginée : récupérer une page avec le `limit`/`offset` de `LimitTrait`, puis appliquer `sortAfter()` afin que la page soit ordonnée selon la clé imbriquée déclarée par le modèle avant d'être sérialisée.

## Voir aussi

- [Paramètres](params.md) — extraction typée des paramètres de requête et stratégies `prepare`.
- [Réponses](responses.md) — JSON, CBOR, statut et sortie API.
- [Index de la documentation](README.md) — retour à la table des matières.
