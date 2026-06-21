# Twig

`oihana\controllers\traits\TwigTrait` intègre le moteur de templates
[Twig](https://twig.symfony.com/) à vos contrôleurs via
[slim/twig-view](https://www.slimframework.com/docs/v4/features/templates.html).
Il résout un moteur de rendu `Slim\Views\Twig` — fourni directement ou récupéré
depuis un conteneur PSR-11 — et expose une unique méthode `render()` qui
transforme un template en `ResponseInterface` PSR-7, en appliquant par défaut le
`Content-Type` HTML lorsque l'appelant ne le définit pas.

## `TwigTrait`

Le trait détient une propriété publique, le moteur de rendu `Twig` résolu, ainsi
que la clé de conteneur utilisée pour le retrouver.

### Propriété & constante

| Membre | Type | Description |
|---|---|---|
| `$twig` | `Slim\Views\Twig` | Le moteur de rendu Twig résolu. Défini par `initializeTwig()`. |
| `TwigTrait::TWIG` | `string` (`'twig'`) | Clé de conteneur / tableau d'init utilisée pour récupérer l'instance Twig. |

### Méthodes

| Méthode | Retour | Description |
|---|---|---|
| `initializeTwig( array $init = [] , ?ContainerInterface $container = null )` | `static` | Résout et stocke le moteur `Twig`, puis retourne `$this` pour le chaînage. |
| `render( ?Response $response , string $template , array $args = [] )` | `?Response` | Rend `$template` avec `$args` dans `$response`. Retourne `null` lorsque `$response` est `null`. |

#### Ordre de résolution de `initializeTwig()`

`initializeTwig()` recherche une instance de `Twig` dans cet ordre, en
s'arrêtant à la première correspondance :

1. `$init[ TwigTrait::TWIG ]` — une instance passée directement dans le tableau d'init ;
2. `$container->get( TwigTrait::TWIG )` — lorsque le conteneur a `has('twig')` ;
3. `$container->get( Twig::class )` — lorsque le conteneur a `has(Twig::class)`.

Si aucune de ces sources ne fournit un `Slim\Views\Twig`, une
`InvalidArgumentException` est levée, décrivant le type trouvé à la place.

#### Comportement de `render()`

`render()` délègue à `Twig::render()` puis, uniquement lorsque la réponse rendue
ne porte **aucun** en-tête `Content-Type`, ajoute `text/html` avec son charset.
Les rendus qui produisent du XML, du SVG ou du texte brut définissent leur propre
type et le conservent. Lorsque `$response` est `null`, la méthode court-circuite
et retourne `null`.

### Exemple

Intégrez le trait dans un contrôleur, hydratez-le depuis le conteneur PSR-11
dans le constructeur, puis rendez un template :

```php
use oihana\controllers\traits\TwigTrait;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController
{
    use TwigTrait;

    public function __construct( ContainerInterface $container )
    {
        // Résout le service 'twig' (ou Twig::class) depuis le conteneur.
        $this->initializeTwig( [] , $container ) ;
    }

    public function home( Request $request , Response $response ) : ?Response
    {
        return $this->render( $response , 'home.twig' , [
            'title' => 'Welcome!' ,
            'user'  => 'Marc' ,
        ] ) ;
    }
}
```

Vous pouvez aussi injecter le moteur directement — pratique pour les tests, où
une instance `Twig` simulée (stub) est passée via le tableau d'init :

```php
use Slim\Views\Twig;

$twig = new Twig( /* loader, settings… */ ) ;

$controller = new HomeController( $container ) ;
$controller->initializeTwig( [ HomeController::TWIG => $twig ] ) ;
```

## `TwigParam`

`oihana\controllers\enums\TwigParam` énumère les clés d'options couramment
passées aux templates Twig (par exemple, pour la mise en thème d'une page
rendue). Construit sur `ConstantsTrait`, il expose les helpers de réflexion
habituels (`getAll()`, `getConstants()`, …) sur les constantes ci-dessous.

| Constante | Valeur | Description |
|---|---|---|
| `TwigParam::BACKGROUND_COLOR` | `'backgroundColor'` | Couleur d'arrière-plan passée au template. |
| `TwigParam::FULL_PATH` | `'fullPath'` | Chemin / URL complet de la ressource rendue. |
| `TwigParam::LOGO` | `'logo'` | Ressource du logo pour le thème clair. |
| `TwigParam::LOGO_DARK` | `'logoDark'` | Ressource du logo pour le thème sombre. |
| `TwigParam::PATTERN_COLOR` | `'patternColor'` | Couleur d'accent / de motif passée au template. |
| `TwigParam::TWIG` | `'twig'` | L'instance Twig / la clé de service. |

```php
use oihana\controllers\enums\TwigParam;

return $this->render( $response , 'page.twig' , [
    TwigParam::LOGO             => '/assets/logo.svg' ,
    TwigParam::LOGO_DARK        => '/assets/logo-dark.svg' ,
    TwigParam::BACKGROUND_COLOR => '#ffffff' ,
    TwigParam::PATTERN_COLOR    => '#1d4ed8' ,
] ) ;
```

## Voir aussi

- [Responses](responses.md) — sorties JSON, CBOR, statut et API.
- [Languages](languages.md) — négociation de langue et helpers i18n.
- [Enumerations](enums.md) — les classes de constantes typées.
- [Index de la documentation](README.md) — retour à la table des matières.
