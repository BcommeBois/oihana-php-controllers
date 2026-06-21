# Installation

## Prérequis

- **PHP 8.4 ou supérieur.**
- **[Composer](https://getcomposer.org/).**
- **`ext-imagick`** — requise au runtime par les contrôleurs d'images (`ImageTrait`)
  et par la suite de tests.

Les dépendances transitives peuvent nécessiter d'autres extensions courantes
(par ex. `ext-fileinfo` et `ext-zip` via `oihana/php-files`), présentes dans la
plupart des distributions PHP.

## Installation via Composer

```bash
composer require oihana/php-controllers
```

## Autochargement

Les classes sont autochargées en PSR-4 sous le namespace `oihana\controllers\`,
et les 20 helpers de requête/réponse via `autoload.files` de composer :

```json
{
    "autoload": {
        "psr-4": {
            "oihana\\controllers\\": "src/oihana/controllers"
        },
        "files": [
            "src/oihana/controllers/helpers/getParam.php",
            "src/oihana/controllers/helpers/getParamInt.php",
            "src/oihana/controllers/helpers/applyContentHeaders.php"
        ]
    }
}
```

> L'extrait ci-dessus est abrégé — le paquet câble **les 20** fichiers helpers.
> Voir [Helpers](../helpers.md) pour la liste complète.

Une fois installé, importez directement les classes et helpers :

```php
use oihana\controllers\Controller;

use function oihana\controllers\helpers\getParamInt;
```

## Vérifier l'installation

```php
require 'vendor/autoload.php';

use DI\Container;
use oihana\controllers\Controller;

$controller = new Controller( new Container() );
```

## Étapes suivantes

- [Dépendances](dependencies.md)
- [Contrôleur](../controller.md)
