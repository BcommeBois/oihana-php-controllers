# oihana/php-controllers — boîte à outils de contrôleurs HTTP pour PHP

![Langue](https://img.shields.io/badge/langue-Français-blue)

`oihana/php-controllers` est une bibliothèque PHP 8.4+ qui fournit une classe de base `Controller` composable ainsi qu'un ensemble de traits et helpers ciblés pour construire des contrôleurs HTTP au-dessus de [Slim](https://www.slimframework.com/) et [Twig](https://twig.symfony.com/).

![Oihana PHP Controllers](https://raw.githubusercontent.com/BcommeBois/oihana-php-controllers/main/assets/images/oihana-php-controllers-logo-inline-512x160.png)

## À qui s'adresse cette documentation

Aux développeurs PHP qui souhaitent :

- construire des contrôleurs à partir d'une base **composable** et de petits traits à responsabilité unique ;
- extraire des **paramètres de requête typés** avec des stratégies de validation — `ParamsTrait`, les helpers `getParam*()` ;
- paginer, négocier les langues, rendre des vues Twig et sérialiser des réponses **JSON / CBOR** ;
- servir des **fichiers** — téléchargement, streaming, HTTP range, ETag & `304 Not Modified`, archives, upload, chiffrement et images ;
- relier les contrôleurs aux **modèles** résolus depuis un conteneur PSR-11.

## Démarrage rapide

```php
use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use oihana\controllers\Controller;

use function oihana\controllers\helpers\getParamInt;

class HelloController extends Controller
{
    public function index( ServerRequestInterface $request, ResponseInterface $response, array $args ): ResponseInterface
    {
        $page = getParamInt( $request, 'page', 1 );
        return $this->json( $response, [ 'page' => $page ] );
    }
}

$controller = new HelloController( new Container() );
```

Pour le détail complet (traits, options, énumérations), voir la table des matières ci-dessous.

## Table des matières

### Démarrage — [`getting-started/`](getting-started/)

- [Introduction](getting-started/introduction.md) — ce que fait la bibliothèque et la philosophie *oihana*.
- [Installation](getting-started/installation.md) — prérequis PHP 8.4+ / `ext-imagick` et `composer require`.
- [Dépendances](getting-started/dependencies.md) — les paquets runtime et leur rôle.

### Utilisation

- [Contrôleur](controller.md) — la base `Controller`, sa composition, le bench et le mock.
- [Paramètres](params.md) — extraction typée des paramètres de requête et stratégies `prepare`.
- [Pagination](pagination.md) — `PaginationTrait`, `LimitTrait`, tri.
- [Réponses](responses.md) — JSON, CBOR, statut et sortie API.
- [Réponses fichier](files.md) — téléchargement, streaming, HTTP range, ETag / 304, chiffrement, images.
- [Archives & upload](archives-uploads.md) — archives zip/tar et upload de fichiers.
- [Twig](twig.md) — rendu des vues Twig.
- [Langues](languages.md) — négociation de langue et helpers i18n.
- [Routage](routing.md) — routes, redirections, URLs de base, CSRF et cache HTTP.
- [Modèles](models.md) — relier les contrôleurs aux modèles de données.
- [Helpers](helpers.md) — les fonctions libres autochargées.
- [Énumérations](enums.md) — les classes d'options à constantes typées.

### Transverse

- [Tests & couverture](testing.md) — lancer la suite PHPUnit et mesurer la couverture.

## Code source

Le code de la bibliothèque se trouve sous [`src/oihana/controllers/`](../../src/oihana/controllers/) — namespace `oihana\controllers`.

## Voir aussi

- [Packagist `oihana/php-controllers`](https://packagist.org/packages/oihana/php-controllers) — la page du paquet.
- [Référence API (phpDocumentor)](https://bcommebois.github.io/oihana-php-controllers) — référence générée au niveau des classes.
