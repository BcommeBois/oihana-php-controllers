# Paramètres

Les contrôleurs HTTP passent l'essentiel de leur temps à transformer des entrées brutes — chaîne de requête, corps analysé, arguments de route — en valeurs propres et typées auxquelles ils peuvent se fier. `oihana/php-controllers` propose pour cela deux couches complémentaires :

- une famille d'**aides `getParam*()`** sans état qui lisent une valeur unique dans une requête PSR-7 et la convertissent dans le type demandé (`int`, `bool`, `string`, `array`, `float`, intervalles, cartes i18n) ;
- un ensemble de **stratégies `Prepare*`** composables (`prepare\Prepare*`) qui encapsulent les conventions des paramètres récurrents d'une API REST — `lang`, `sort`, `limit`, `offset`, `filter`, `facets`, … — y compris la validation, les valeurs par défaut et la comptabilité nécessaire pour transmettre la valeur au modèle sous-jacent.

Le vocabulaire des noms de paramètres est centralisé dans l'énumération `ControllerParam`, et la source à lire (query, body ou les deux) est décrite par l'énumération `HttpParamStrategy`.

## Les aides getParam

Toutes les aides résident dans l'espace de noms `oihana\controllers\helpers` et acceptent un `ServerRequestInterface` PSR-7 (nullable). Les accesseurs typés prennent en charge la **notation pointée** pour les clés imbriquées (`'user.profile.email'`) et partagent la même fin de signature : `( ..., array $args = [], $defaultValue = null, ?string $strategy = HttpParamStrategy::BOTH, bool $throwable = false )`.

| Aide | Retourne | Ce qu'elle extrait |
|---|---|---|
| `getParam( $request, $name, $default = [], $strategy = BOTH, $throwable = false )` | `mixed` | Valeur brute issue de la query et/ou du body selon `$strategy`. Repli sur `$default[$name]`, ou lève `NotFoundException` si `$throwable`. |
| `getParamInt( $request, $name, $args = [], $defaultValue = null, ... )` | `?int` | Valeur convertie en `int` si numérique, sinon `$defaultValue`. |
| `getParamFloat( $request, $name, $args = [], $defaultValue = null, ... )` | `?float` | Valeur convertie en `float` si numérique, sinon `$defaultValue`. |
| `getParamBool( $request, $name, $args = [], $defaultValue = null, ... )` | `?bool` | Valeur normalisée via `FILTER_VALIDATE_BOOLEAN` (`true/false`, `1/0`, `yes/no`, `on/off`). |
| `getParamString( $request, $name, $args = [], $defaultValue = null, ... )` | `?string` | Valeur convertie en `string` lorsqu'elle est définie. |
| `getParamArray( $request, $name, $args = [], $defaultValue = null, ... )` | `?array` | Valeur lorsqu'elle est un tableau, sinon `$defaultValue`. |
| `getParamI18n( $request, $name, $default = [], $languages = null, $sanitize = null, ... )` | `?array` | Une carte de traductions (`['fr' => …, 'en' => …]`), filtrée selon `$languages` et éventuellement assainie valeur par valeur. |
| `getParamNumberRange( $request, $name, $min, $max, $defaultValue = null, ... )` | `int\|float\|null` | Valeur numérique bornée à `[$min, $max]`. |
| `getParamIntRange( $request, $name, $min, $max, $defaultValue = null, ... )` | `?int` | `getParamNumberRange()` converti en `int`. |
| `getParamFloatRange( $request, $name, $min, $max, $defaultValue = null, ... )` | `?float` | `getParamNumberRange()` converti en `float`. |
| `getQueryParam( $request, $name )` | `mixed` | Une valeur unique issue de la **chaîne de requête uniquement** (`$request->getQueryParams()`). |
| `getBodyParam( $request, $name )` | `mixed` | Une valeur unique issue du **corps analysé uniquement** (`$request->getParsedBody()`). |
| `getBodyParams( $request, $names = [] )` | `array` | Plusieurs valeurs du corps à la fois, réassemblées en tableau associatif imbriqué. |

> La page dédiée [Aides](helpers.md) liste toutes les fonctions de la bibliothèque ; cette section se concentre sur celles dédiées aux paramètres.

### Lire des valeurs typées

```php
use oihana\enums\http\HttpParamStrategy;

use function oihana\controllers\helpers\getParamInt;
use function oihana\controllers\helpers\getParamBool;
use function oihana\controllers\helpers\getParamString;

// Chaîne de requête : ?page=2&active=true&name=Alice
$page   = getParamInt   ( $request , 'page'   , [] , 1 ) ;          // 2
$active = getParamBool  ( $request , 'active' , [] , false ) ;      // true
$name   = getParamString( $request , 'name'   , [] , 'Guest' ) ;    // "Alice"

// Restreindre la source au corps de la requête uniquement
$comment = getParamString( $request , 'comment' , [] , null , HttpParamStrategy::BODY ) ;
```

### Bornage et clés imbriquées

```php
use function oihana\controllers\helpers\getParamIntRange;
use function oihana\controllers\helpers\getBodyParam;

// Borner dans une plage sûre — les valeurs hors limites sont ramenées à min/max
$quantity = getParamIntRange( $request , 'quantity' , 1 , 10 , 5 ) ; // 10 si ?quantity=999

// La notation pointée parcourt les structures imbriquées du corps
// Corps POST : ['geo' => ['latitude' => 42.5]]
$latitude = getBodyParam( $request , 'geo.latitude' ) ; // 42.5
```

## ParamsTrait & ParamsStrategyTrait

Un contrôleur déclare *quels* paramètres il comprend et *d'où* ils peuvent provenir grâce à deux petits traits.

`ParamsTrait` expose une définition `?array $params` et une méthode `initializeParams()` qui la lit depuis la clé `ControllerParam::PARAMS` des options de construction. Cette carte `$params` pilote des comportements conventionnels tels que `prepareFacets()` (voir plus bas).

`ParamsStrategyTrait` expose une propriété `string $paramsStrategy` (par défaut `HttpParamStrategy::BOTH`) et `initializeParamsStrategy()`. La stratégie décide si les paramètres sont recherchés dans la chaîne de requête, dans le corps analysé, ou dans les deux. Elle accepte soit une simple chaîne de stratégie, soit un tableau indexé par `ParamsStrategyTrait::PARAMS_STRATEGY`, et ignore silencieusement toute valeur non reconnue par `HttpParamStrategy::includes()`.

```php
use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\ParamsTrait;
use oihana\controllers\traits\ParamsStrategyTrait;
use oihana\enums\http\HttpParamStrategy;

use function oihana\controllers\helpers\getParam;

class ProductController
{
    use ParamsTrait , ParamsStrategyTrait ;

    public function __construct( array $init = [] )
    {
        $this->initializeParams( $init ) ;            // lit ControllerParam::PARAMS
        $this->initializeParamsStrategy( $init ) ;    // lit ParamsStrategyTrait::PARAMS_STRATEGY
    }

    public function read( $request )
    {
        // Résoudre une valeur en utilisant la stratégie du contrôleur
        $owner = getParam( $request , ControllerParam::OWNER , [] , $this->paramsStrategy ) ;
        // ...
    }
}

$controller = new ProductController(
[
    ControllerParam::PARAMS          => [ ControllerParam::ID => ControllerParam::FACETS ] ,
    ParamsStrategyTrait::PARAMS_STRATEGY => HttpParamStrategy::QUERY ,
] ) ;
```

## Stratégies Prepare

Les traits `oihana\controllers\traits\prepare\Prepare*` possèdent chacun un seul paramètre récurrent. Ils suivent un schéma cohérent : une méthode `protected function prepare<Name>( ?Request $request, array $args = [], ?array &$params = null, ... )` qui

1. amorce une valeur depuis les `$args` de route (un défaut raisonnable lorsque la requête est absente — pratique pour les tests) ;
2. la remplace par la valeur de la requête lorsqu'elle est présente et valide (la plupart lisent dans la **chaîne de requête** via `getQueryParam()`) ;
3. enregistre la valeur effective dans la référence `&$params`, afin que le contrôleur transmette exactement ce qu'il a résolu à la couche modèle ;
4. retourne la valeur résolue et typée.

`PrepareParamTrait` regroupe les plus courants dans un trait unique que vous pouvez `use` dans un contrôleur, vous donnant `prepareLang()`, `prepareSort()`, `prepareLimit()`, `prepareOffset()`, `prepareFilter()`, `prepareFacets()` et consorts en une seule fois.

| Trait | Méthode | Rôle |
|---|---|---|
| `PrepareActive` | `prepareActive()` | Résoudre l'indicateur booléen `active`. |
| `PrepareBench` | `prepareBench()` | Résoudre l'indicateur de benchmark `bench`. |
| `PrepareBoolean` | `prepareBoolean()` | Aide générique pour résoudre n'importe quel paramètre booléen nommé. |
| `PrepareDate` | `prepareDate()` | Résoudre et normaliser un paramètre `date`. |
| `PrepareFacets` | `prepareFacets()` | Construire les définitions de `facets` à partir des query params et de la carte `$params`. |
| `PrepareFilter` | `prepareFilter()` | Décoder le paramètre de requête JSON `filter` en tableau. |
| `PrepareGroupBy` | `prepareGroupBy()` | Résoudre l'expression `groupBy`. |
| `PrepareHasTotal` | `prepareHasTotal()` | Résoudre l'indicateur `hasTotal` (demander un total). |
| `PrepareIDs` | `preparedIDs()` | Résoudre une liste d'identifiants (`ids`). |
| `PrepareInt` | `prepareInt()` | Aide générique pour résoudre n'importe quel paramètre entier nommé. |
| `PrepareInterval` | `prepareInterval()` | Résoudre l'`interval` temporel selon les options autorisées. |
| `PrepareLang` | `prepareLang()` | Résoudre et valider le paramètre `lang` parmi les langues autorisées. |
| `PrepareLimit` | `prepareLimit()` / `prepareOffset()` | Résoudre et borner la pagination `limit` / `offset`. |
| `PrepareMargin` | `prepareMargin()` | Résoudre l'indicateur `margin`. |
| `PrepareMock` | `prepareMock()` | Résoudre l'indicateur `mock` (retourner des données fictives). |
| `PrepareOrder` | `prepareOrder()` | Résoudre la direction de tri `order`. |
| `PrepareQuantity` | `prepareQuantity()` | Résoudre le paramètre entier `quantity`. |
| `PrepareSearch` | `prepareSearch()` | Résoudre le paramètre de recherche libre `search`. |
| `PrepareSkin` | `prepareSkin()` | Résoudre et valider le paramètre `skin` (variante de vue). |
| `PrepareSort` | `prepareSort()` | Résoudre le paramètre `sort`, avec repli sur une valeur par défaut. |
| `PrepareTimezone` | `prepareTimezone()` | Résoudre le paramètre `timezone`. |
| `PrepareOrRedirectArgumentTrait` | `prepareOrRedirectArgument()` | Préparer un argument et rediriger vers lui lorsque c'est possible. |

### `PrepareLang`

`prepareLang()` lit le paramètre de **requête** `lang` (jamais le corps), le met en minuscules et ne l'accepte que s'il appartient à `$this->languages`. La valeur spéciale `all` se résout en `null` (aucun filtre de langue). Lorsqu'une langue est retenue, elle est aussi réécrite dans `&$params` sous `ControllerParam::LANG`.

```php
use oihana\controllers\traits\prepare\PrepareLang;

class CmsController
{
    use PrepareLang ;

    public array $languages = [ 'fr' , 'en' ] ;

    public function page( $request , array $args )
    {
        $params = [] ;
        // ?lang=FR  -> "fr" ; ?lang=all -> null ; ?lang=de -> repli sur $args/défaut
        $lang = $this->prepareLang( $request , $args , $params ) ;
        // $params contient désormais [ 'lang' => 'fr' ] lorsqu'une langue valide a été fournie
    }
}
```

### `PrepareSort`

`prepareSort()` lit le paramètre de requête `sort` ; lorsqu'il est présent, il est stocké dans `&$params` et retourné tel quel. Lorsqu'il est absent, la méthode retourne, dans l'ordre, l'argument `$default`, puis `$this->sortDefault` (issu de `SortDefaultTrait`). Le nom du paramètre est configurable, de sorte que le même trait peut piloter plusieurs axes de tri.

```php
use oihana\controllers\traits\prepare\PrepareSort;

class ListController
{
    use PrepareSort ;

    public string $sortDefault = 'name' ;

    public function index( $request , array $args )
    {
        $params = [] ;
        $sort = $this->prepareSort( $request , $args , $params ) ; // "price" pour ?sort=price, sinon "name"
    }
}
```

### `PrepareLimit`

`prepareLimit()` (et son pendant `prepareOffset()`, qui l'appelle simplement avec la propriété `offset`) lit la valeur dans la chaîne de requête et la valide comme un entier **borné** à `[$this->minLimit, $this->maxLimit]` (avec repli sur l'objet de pagination, puis `0..100`). Lorsque la requête ne porte pas la valeur, la limite résolue provient de la propriété du contrôleur / pagination / `$defaultValue`. Seules les valeurs fournies par la requête sont réécrites dans `&$params`.

```php
use xyz\oihana\schema\Pagination;
use oihana\controllers\traits\prepare\PrepareLimit;

class ListController
{
    use PrepareLimit ;

    public int $limit    = 20 ;
    public int $maxLimit = 100 ;

    public function index( $request , array $args )
    {
        $params = [] ;
        $limit  = $this->prepareLimit ( $request , $args , $params ) ; // ?limit=50 -> 50 ; ?limit=999 -> 100
        $offset = $this->prepareOffset( $request , $args , $params ) ;
    }
}
```

### `PrepareFilter` & `PrepareFacets`

`prepareFilter()` attend que le paramètre de requête `filter` soit une chaîne JSON. Il valide le JSON, le décode en tableau (en journalisant un avertissement et en se repliant sur `$args` sinon), stocke le JSON d'origine dans `&$params` et retourne le tableau décodé.

`prepareFacets()` combine deux sources : le paramètre de requête JSON `facets`, et les définitions de facette par paramètre déclarées dans la carte `$params` du contrôleur (`prepareParamsFacets()`). Cela permet à une route telle que `/products?id=[12,255,300]` de traduire un simple paramètre de requête en définition de facette lorsque `ControllerParam::PARAMS => [ Prop::ID => ControllerParam::FACETS ]` est configuré.

```php
use oihana\controllers\traits\prepare\PrepareFilter;

class SearchController
{
    use PrepareFilter ;

    public function index( $request , array $args )
    {
        $params = [] ;
        // ?filter={"status":"active"}  ->  [ 'status' => 'active' ]
        $filter = $this->prepareFilter( $request , $args , $params ) ;
    }
}
```

## Voir aussi

- [Pagination](pagination.md) — `limit` / `offset` et le modèle de pagination.
- [Aides](helpers.md) — le catalogue complet des fonctions utilitaires.
- [Énumérations](enums.md) — `ControllerParam`, `HttpParamStrategy` et énumérations associées.
- Retour à l'[Index de la documentation](README.md).
