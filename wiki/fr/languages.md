# Langues

Les contrôleurs servent souvent du contenu multilingue : une même ressource
porte plusieurs traductions (`['fr' => 'Bonjour', 'en' => 'Hello']`), et c'est
le client qui choisit laquelle (ou lesquelles) il veut. `oihana/php-controllers`
garde cette préoccupation réduite et explicite :

- un **trait**, `LanguagesTrait`, qui enregistre l'ensemble des codes de langue
  qu'un contrôleur prend en charge ;
- trois **helpers i18n** — `translate()`, `filterLanguages()` et
  `getParamI18n()` — qui sélectionnent, filtrent et lisent des traductions,
  depuis un simple tableau/objet ou directement depuis une requête PSR-7.

Aucun de ces helpers n'analyse l'en-tête `Accept-Language` à votre place : la
négociation se résume ici à *« ne conserver que les langues autorisées par ce
contrôleur »*, pilotée par une liste explicite de codes plutôt que par la
négociation de contenu HTTP.

## LanguagesTrait

`oihana\controllers\traits\LanguagesTrait` dote un contrôleur d'une seule
propriété publique et d'un initialiseur :

| Membre | Type | Rôle |
|---|---|---|
| `$languages` | `array<string>` | Les codes de langue valides pris en charge par le contrôleur. Vaut `[]` par défaut. |
| `LanguagesTrait::LANGUAGES` | `string` (`'languages'`) | Clé recherchée dans un conteneur PSR-11. |
| `initializeLanguages( array $init = [], ?ContainerInterface $container = null )` | `static` | Renseigne `$languages`. |

`initializeLanguages()` résout la liste dans cet ordre :

1. Elle lit `$init[ControllerParam::LANGUAGES]` (la clé `'languages'`) dans le
   tableau `$init`.
2. Si la valeur est `null` et qu'un conteneur PSR-11 `$container` est fourni et
   qu'il `has('languages')`, elle se rabat sur `$container->get('languages')`.
3. Le résultat n'est stocké que s'il s'agit d'un tableau ; toute valeur non
   tableau (une simple `string`, `null`, …) se rabat sur un tableau vide `[]`.

Elle retourne `$this`, ce qui permet de l'enchaîner avec les autres appels
`initialize*()` dans le constructeur d'un contrôleur.

```php
use Psr\Container\ContainerInterface;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\LanguagesTrait;

$controller = new class
{
    use LanguagesTrait;
};

// 1. Depuis le tableau d'initialisation
$controller->initializeLanguages( [ ControllerParam::LANGUAGES => [ 'fr', 'en' ] ] );
echo implode( ',', $controller->languages ); // fr,en

// 2. Depuis un conteneur PSR-11 (init ne contient pas la clé 'languages')
$controller->initializeLanguages( [], $container ); // ex. $container->get('languages') === ['de','it']
echo implode( ',', $controller->languages ); // de,it

// 3. Une valeur non tableau se rabat sur une liste vide
$controller->initializeLanguages( [ ControllerParam::LANGUAGES => 'fr' ] );
var_dump( $controller->languages ); // []
```

La liste stockée dans `$controller->languages` est exactement celle que vous
passerez ensuite à `filterLanguages()` et `getParamI18n()` ci-dessous.

## Helpers i18n

Les trois helpers vivent dans l'espace de noms `oihana\controllers\helpers` et
sont autochargés comme des fonctions libres — importez-les avec `use function`.

### `translate()`

Sélectionne une traduction dans une table, avec une langue de repli optionnelle.

```php
function translate(
    array|object|null $fields,
    string|null       $lang    = null,
    string|null       $default = null
): mixed
```

- Retourne la valeur de `$lang` lorsque la clé existe.
- Sinon retourne la valeur de `$default` lorsque cette clé existe.
- Retourne `null` si aucune ne correspond, si `$fields` vaut `null`, ou si la
  table est vide.
- Lorsque `$lang` vaut `null`, retourne l'**intégralité** de `$fields` telle
  quelle (un tableau reste un tableau, un objet reste un objet).

```php
use function oihana\controllers\helpers\translate;

$fields =
[
    'fr' => 'Bonjour',
    'en' => 'Hello',
    'es' => 'Hola',
];

translate( $fields, 'en' );        // 'Hello'
translate( $fields, 'de', 'fr' );  // 'Bonjour'  (repli sur fr)
translate( $fields, 'de', 'es' );  // 'Hola'     (repli sur es)
translate( $fields, 'de', null );  // null       (aucune correspondance, aucun repli)
translate( $fields );              // ['fr' => 'Bonjour', 'en' => 'Hello', 'es' => 'Hola']

// Les objets sont acceptés également :
translate( (object) $fields, 'fr' ); // 'Bonjour'
```

### `filterLanguages()`

Réduit une table de traductions aux langues autorisées, en ne gardant que les
valeurs `string` ou `null`, et en assainissant éventuellement chacune d'elles.

```php
function filterLanguages(
    mixed     $fields,
    ?array    $languages = null,
    ?callable $sanitize  = null
): ?array
```

- Accepte un tableau ou un objet (les objets sont convertis en tableaux) ; toute
  autre forme (`string`, `int`, `bool`, …) est considérée comme invalide et
  produit `null`.
- Parcourt `$languages` et copie `$fields[$lang]` lorsque c'est une `string` ou
  `null` ; les valeurs non string/non null (nombres, tableaux imbriqués, objets)
  sont entièrement ignorées.
- Applique le callback optionnel `$sanitize` — `fn(string|null $value, string $lang): string|null` —
  à chaque valeur conservée.
- Retourne `null` lorsque l'entrée est vide ou que rien n'a été conservé.

```php
use function oihana\controllers\helpers\filterLanguages;

$fields =
[
    'fr' => 'Bonjour <span style="color:red">monde</span>',
    'en' => 'Hello <span style="color:red">world</span>',
    'de' => 42,    // ignoré : ni string ni null
    'es' => null,  // conservé : null est autorisé
];

// Filtrage de base
filterLanguages( $fields, [ 'fr', 'en', 'de', 'es' ] );
// [
//   'fr' => 'Bonjour <span style="color:red">monde</span>',
//   'en' => 'Hello <span style="color:red">world</span>',
//   'es' => null,
// ]

// Avec un callback d'assainissement (suppression des styles en ligne)
filterLanguages( $fields, [ 'fr', 'en' ], function( $value, $lang )
{
    return is_string( $value )
         ? preg_replace( '/(<[^>]+) style=".*?"/i', '$1', $value )
         : $value ;
} );
// [ 'fr' => 'Bonjour <span>monde</span>', 'en' => 'Hello <span>world</span>' ]

filterLanguages( $fields, [] );          // null (aucune langue demandée)
filterLanguages( 'flat string', [ 'fr' ] ); // null (forme d'entrée invalide)
```

> Note : `filterLanguages()` est permissif sur la forme de l'entrée — les
> entrées invalides retournent `null` plutôt que de lever une exception. Si vous
> avez besoin d'un `422` sur une charge utile malformée, validez l'entrée brute
> en amont avant de l'appeler.

### `getParamI18n()`

Lit un paramètre i18n directement depuis une requête PSR-7, puis le fait passer
par `filterLanguages()`. C'est `getParam()` + `filterLanguages()` en un seul
appel.

```php
function getParamI18n(
    ?Request  $request,
    string    $name,
    array     $default   = [],
    ?array    $languages = null,
    ?callable $sanitize  = null,
    ?string   $strategy  = HttpParamStrategy::BOTH,
    bool      $throwable = false
): ?array
```

- Lit `$name` dans la requête, où la valeur est une table de traductions. La
  `$strategy` sélectionne la source — `HttpParamStrategy::QUERY`, `BODY` ou
  `BOTH` (par défaut). La notation pointée est prise en charge pour les clés
  imbriquées (`'user.profile.bio'`).
- La valeur récupérée est filtrée selon `$languages` et éventuellement
  assainie, exactement comme `filterLanguages()`.
- Lorsque le paramètre est absent, elle se rabat sur `$default[$name]` ; sans
  valeur par défaut elle produit `null`, et avec `$throwable = true` elle lève
  `DI\NotFoundException`.

```php
use oihana\enums\http\HttpParamStrategy;

use function oihana\controllers\helpers\getParamI18n;

// Requête dont la query porte : description => ['fr'=>'Bonjour','en'=>'Hello','de'=>'Hallo']
$result = getParamI18n( $request, 'description', [], [ 'fr', 'en' ] );
// [ 'fr' => 'Bonjour', 'en' => 'Hello' ]   // 'de' ignoré (non autorisé)

// Avec un callback d'assainissement
$result = getParamI18n( $request, 'description', [], [ 'fr', 'en' ], fn( $v, $lang ) => strip_tags( $v ) );
// '<b>Bonjour</b>' => 'Bonjour', '<b>Hello</b>' => 'Hello'

// Paramètre absent : repli sur $default[$name]
$default = [ 'description' => [ 'fr' => 'Def FR', 'en' => 'Def EN' ] ];
$result  = getParamI18n( $request, 'description', $default, [ 'fr', 'en' ] );
// [ 'fr' => 'Def FR', 'en' => 'Def EN' ]

// Paramètre absent, aucun défaut, lève une erreur de type 404
getParamI18n( $request, 'description', [], [ 'fr', 'en' ], null, HttpParamStrategy::BOTH, true );
// lève DI\NotFoundException
```

Un contrôleur typique associe le trait au helper : `initializeLanguages()`
renseigne `$this->languages`, et chaque point d'entrée d'écriture passe cette
liste à `getParamI18n()`, de sorte qu'un client ne peut jamais persister une
langue que le contrôleur ne prend pas en charge.

```php
$i18n = getParamI18n( $request, 'description', [], $this->languages );
```

## Voir aussi

- [Paramètres](params.md) — la famille `getParam*()` et les stratégies `prepare` qui sous-tendent `getParamI18n()`.
- [Twig](twig.md) — le rendu de vues localisées.
- [Helpers](helpers.md) — l'ensemble complet des fonctions libres autochargées.
- Retour à l'[index de la documentation](README.md).
