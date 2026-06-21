# Introduction

`oihana/php-controllers` rassemble les briques de contrôleurs HTTP qui vivaient auparavant dans `oihana/php-system`, extraites dans un paquet dédié afin qu'un projet puisse dépendre de la couche contrôleur avec une surface de dépendances claire et déclarée.

Elle s'appuie sur [Slim](https://www.slimframework.com/) (PSR-7/PSR-15) et [Twig](https://twig.symfony.com/) : une base `Controller` reliée à un conteneur DI, composée de petits traits à responsabilité unique, plus un ensemble de fonctions libres pour l'extraction des paramètres de requête et les réponses HTTP.

## Ce qu'elle fournit

| Composant | Type | Rôle |
|---|---|---|
| `Controller` | classe | Le contrôleur de base composable, relié à un conteneur PSR-11. |
| `traits\ParamsTrait` / `ParamsStrategyTrait` | traits | Extraction typée des paramètres de requête et stratégies de validation. |
| `traits\prepare\Prepare*` | traits | Préparation par paramètre (lang, tri, limite, filtre, facettes…). |
| `traits\PaginationTrait` / `LimitTrait` | traits | Pagination et limites. |
| `traits\JsonTrait` / `CborTrait` / `StatusTrait` / `ApiTrait` | traits | Sérialisation des réponses et sortie API. |
| `traits\FileTrait` / `RangeTrait` / `ConditionalRequestTrait` | traits | Réponses fichier : téléchargement, streaming, HTTP range, ETag / 304. |
| `traits\ArchiveTrait` / `UploadTrait` / `FileEncryptionTrait` / `ImageTrait` | traits | Archives, upload, chiffrement et images. |
| `traits\TwigTrait` / `LanguagesTrait` | traits | Rendu Twig et négociation de langue. |
| `traits\RouterTrait` / `RedirectsTrait` / `CsrfTrait` / `HttpCacheTrait` | traits | Routage, redirections, CSRF et cache HTTP. |
| `helpers\*` | fonctions libres | Helpers de paramètres et de réponses (autochargés). |
| `enums\*` | classes | Clés d'options fortement typées (pas de *magic strings*). |

## La philosophie *oihana*

- **PHP 8.4+ uniquement** — constantes typées, property hooks, aucun palliatif legacy.
- **Pas de *magic strings*** — chaque clé d'option est une constante typée dans une classe basée sur `ConstantsTrait` ; le projet n'utilise jamais d'enum natif PHP.
- **Composable** — chaque trait a une responsabilité unique et se combine librement sur un contrôleur.
- **Testée** — 100 % de couverture de lignes, mode strict PHPUnit (voir [Tests & couverture](../testing.md)).

## Étapes suivantes

- [Installation](installation.md)
- [Dépendances](dependencies.md)
- [Contrôleur](../controller.md)
