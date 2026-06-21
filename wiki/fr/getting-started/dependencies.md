# Dépendances

`oihana/php-controllers` se trouve au sommet de la pile *oihana* ; son empreinte
runtime est donc la plus large de la famille. Voici ce qu'elle requiert et
**pourquoi**.

## Extensions PHP

| Extension | Rôle |
|---|---|
| `ext-imagick` | Décodage, redimensionnement et encodage d'images dans `ImageTrait`. |

## Dépendances runtime oihana

| Paquet | Rôle |
|---|---|
| [`oihana/php-core`](https://github.com/BcommeBois/oihana-php-core) | Helpers de base — accesseurs, chaînes, tableaux, `date\humanizeDuration()`, `maths\aspectFit()`. |
| [`oihana/php-enums`](https://github.com/BcommeBois/oihana-php-enums) | Constantes typées — `Char`, `Output`, `http\*` (en-têtes, statut, méthodes). |
| [`oihana/php-exceptions`](https://github.com/BcommeBois/oihana-php-exceptions) | Types d'exceptions HTTP (`http\Error404`, `http\Error500`). |
| [`oihana/php-files`](https://github.com/BcommeBois/oihana-php-files) | Helpers fichiers, chemins, archives (zip/tar) et chiffrement OpenSSL. |
| [`oihana/php-logging`](https://github.com/BcommeBois/oihana-php-logging) | Journalisation PSR-3 (`LoggerTrait`). |
| [`oihana/php-models`](https://github.com/BcommeBois/oihana-php-models) | Modèles de documents appelés par les contrôleurs de données (`DocumentsTrait`, `ExistModel`). |
| [`oihana/php-reflect`](https://github.com/BcommeBois/oihana-php-reflect) | `ConstantsTrait` et les sérialiseurs JSON/CBOR. |
| [`oihana/php-schema`](https://github.com/BcommeBois/oihana-php-schema) | Constantes Schema.org (`org\schema\constants\Prop`). |
| [`oihana/php-traits`](https://github.com/BcommeBois/oihana-php-traits) | Traits d'objets réutilisables (`ContainerTrait`, `ConfigTrait`, …). |

## Dépendances runtime externes

| Paquet | Rôle |
|---|---|
| [`php-di/php-di`](https://packagist.org/packages/php-di/php-di) | Conteneur DI PSR-11 injecté dans chaque contrôleur. |
| [`slim/slim`](https://packagist.org/packages/slim/slim) | L'application Slim, le routage et les middlewares PSR-15. |
| [`slim/psr7`](https://packagist.org/packages/slim/psr7) | Implémentation PSR-7 requête/réponse/flux. |
| [`slim/twig-view`](https://packagist.org/packages/slim/twig-view) | Intégration Twig pour Slim (`TwigTrait`). |
| [`slim/csrf`](https://packagist.org/packages/slim/csrf) | Protection CSRF (`CsrfTrait`). |
| [`slim/http-cache`](https://packagist.org/packages/slim/http-cache) | En-têtes de cache HTTP (`HttpCacheTrait`). |
| [`twig/twig`](https://packagist.org/packages/twig/twig) | Le moteur de templates Twig. |
| [`somnambulist/validation`](https://github.com/somnambulist-tech/validation) | Le moteur de validation derrière `ValidatorTrait`. |
| [`psr/container`](https://packagist.org/packages/psr/container) | Contrat PSR-11 `ContainerInterface`. |
| [`psr/http-message`](https://packagist.org/packages/psr/http-message) | Interfaces de messages PSR-7. |
| [`psr/http-server-middleware`](https://packagist.org/packages/psr/http-server-middleware) | Interfaces middleware/handler PSR-15. |
| [`psr/log`](https://packagist.org/packages/psr/log) | Contrat PSR-3 `LoggerInterface`. |

## Dépendances de développement

| Paquet | Rôle |
|---|---|
| `phpunit/phpunit` | Lanceur de tests (mode strict). |
| `nunomaduro/collision` | Sortie d'erreurs CLI lisible. |
| `phpdocumentor/shim` | Génération de la documentation API. |

## Étapes suivantes

- [Contrôleur](../controller.md)
- [Paramètres](../params.md)
