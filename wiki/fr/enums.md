# Énumérations

Les clés d'options récurrentes, les noms de paramètres et les signaux de
projection sont exposés sous forme de **constantes typées** regroupées dans des
classes utilitaires, plutôt que sous forme de *chaînes magiques* éparpillées
dans le code. Ces classes ne sont *pas* des `enum` PHP natifs : chacune utilise
`oihana\reflect\traits\ConstantsTrait`, si bien que les constantes restent de
simples valeurs `string` que vous pouvez passer partout, tout en demeurant
introspectables.

```php
use oihana\controllers\enums\ControllerParam;

// Utilisez une constante plutôt que la chaîne brute "skin" :
$skin = $params[ ControllerParam::SKIN ] ?? ControllerParam::SKIN_DEFAULT ;
```

Parce qu'elles intègrent `ConstantsTrait`, toutes les classes ci-dessous
offrent aussi une petite API de réflexion pour énumérer ou valider leurs
valeurs :

```php
ControllerParam::enums();           // string[] — toutes les valeurs de constantes déclarées
ControllerParam::getConstants();    // array<string,string> — nom => valeur
ControllerParam::getConstant('x');  // retrouve le nom de constante depuis une valeur
ControllerParam::includes('skin');  // bool — cette valeur est-elle connue ?
```

> Le jeu exact de méthodes provient de
> `oihana\reflect\traits\ConstantsTrait` ; `enums()` est la plus utilisée (elle
> renvoie la liste des valeurs de constantes, pratique pour les listes blanches).

## `ConditionalRequestOption`

`oihana\controllers\enums\ConditionalRequestOption` — les clés d'options
acceptées par `ConditionalRequestTrait::conditionalFileResponse()`. Ces clés
déterminent la façon dont l'`ETag` de validation est construit. Voir
[Réponses de fichiers](files.md).

| Constante | Valeur | Signification |
|---|---|---|
| `ConditionalRequestOption::HASH_CONTENT` | `'hashContent'` | Dérive l'`ETag` du contenu du fichier (`md5_file()`) au lieu de ses métadonnées (`mtime`-`size`). Lit le fichier entier. Défaut : `false`. |
| `ConditionalRequestOption::WEAK` | `'weak'` | Émet l'`ETag` comme validateur faible (`W/"..."`). Défaut : `false` (fort). |

## `ControllerParam`

`oihana\controllers\enums\ControllerParam` — le catalogue des paramètres communs
de contrôleur acceptés par les constructeurs de contrôleurs et les tableaux de
configuration. Les constantes résident dans
`oihana\controllers\enums\traits\ControllerParamTrait` et sont exposées via la
classe `ControllerParam`, qui intègre aussi `ConstantsTrait` (pour
l'introspection) et `xyz\oihana\schema\constants\traits\PaginationTrait` (pour
les clés de pagination). Garder les clés dans un trait permet de les réutiliser
dans des énumérations de paramètres plus spécialisées. Voir
[Paramètres](params.md).

| Constante | Valeur | Signification |
|---|---|---|
| `ControllerParam::API` | `'api'` | Le paramètre `api` (voir `ApiTrait`). |
| `ControllerParam::APP` | `'app'` | Le paramètre `app` (voir `AppTrait`). |
| `ControllerParam::ACTIVE` | `'active'` | Le paramètre `active`. |
| `ControllerParam::ALL` | `'all'` | Le paramètre `all`. |
| `ControllerParam::ARGS` | `'args'` | Le paramètre `args`. |
| `ControllerParam::BASE_URL` | `'baseUrl'` | Le paramètre `baseUrl`. |
| `ControllerParam::BENCH` | `'bench'` | Le paramètre `bench`. |
| `ControllerParam::CAPABILITIES` | `'capabilities'` | Bloc de configuration des capacités — associe des noms de paramètres (`SKIN`, `FILTER`, …) à des déclarations de capacités par paramètre. |
| `ControllerParam::CAPABILITIES_ENABLED` | `'capabilitiesEnabled'` | Interrupteur permettant de désactiver tout le bloc d'application des capacités. Vaut `true` par défaut lorsque le bloc `CAPABILITIES` est présent. |
| `ControllerParam::CBOR_SERIALIZE_OPTIONS` | `'cborSerializeOptions'` | Le paramètre `cborSerializeOptions` (voir `CborTrait`). |
| `ControllerParam::CONTROLLER` | `'controller'` | Le paramètre `controller`. |
| `ControllerParam::DATE_FORMAT` | `'dateFormat'` | Le paramètre `dateFormat`. |
| `ControllerParam::DOCUMENT_KEY` | `'documentKey'` | Le paramètre `documentKey`. |
| `ControllerParam::CUSTOM_RULES` | `'customRules'` | Le paramètre `customRules`. |
| `ControllerParam::FACETS` | `'facets'` | Le paramètre `facets`. |
| `ControllerParam::FIELDS` | `'fields'` | Le paramètre `fields`. |
| `ControllerParam::FILTER` | `'filter'` | Le paramètre `filter`. |
| `ControllerParam::FORCE_URL` | `'forceUrl'` | Le paramètre `forceUrl`. |
| `ControllerParam::FULL_PATH` | `'fullPath'` | Le paramètre `fullPath`. |
| `ControllerParam::GROUP_BY` | `'groupBy'` | Le paramètre `groupBy`. |
| `ControllerParam::HAS_TOTAL` | `'hasTotal'` | Le paramètre `hasTotal`. |
| `ControllerParam::HTTP_CACHE` | `'httpCache'` | Le paramètre `httpCache`. |
| `ControllerParam::ID` | `'id'` | Le paramètre `id`. |
| `ControllerParam::IDS` | `'ids'` | Le paramètre `ids`. |
| `ControllerParam::INTERVAL` | `'interval'` | Le paramètre `interval`. |
| `ControllerParam::INTERVAL_DEFAULT` | `'intervalDefault'` | Le paramètre `intervalDefault`. |
| `ControllerParam::JSON_SERIALIZE_OPTIONS` | `'jsonSerializeOptions'` | Le paramètre `jsonSerializeOptions` (voir `JsonOptionsTrait`). |
| `ControllerParam::JSON_OPTIONS` | `'jsonOptions'` | Le paramètre `jsonOptions` (voir `JsonOptionsTrait`). |
| `ControllerParam::KEY` | `'key'` | Le paramètre `key`. |
| `ControllerParam::LANG` | `'lang'` | Le paramètre `lang`. |
| `ControllerParam::LANGUAGES` | `'languages'` | Le paramètre `languages`. |
| `ControllerParam::LIST` | `'list'` | Le paramètre `list`. |
| `ControllerParam::MARGIN` | `'margin'` | Le paramètre `margin`. |
| `ControllerParam::MOCK` | `'mock'` | Le paramètre `mock`. |
| `ControllerParam::MODEL` | `'model'` | Le paramètre `model`. |
| `ControllerParam::ORDER` | `'order'` | Le paramètre `order`. |
| `ControllerParam::ORDERS` | `'orders'` | Le paramètre `orders`. |
| `ControllerParam::OWNER` | `'owner'` | Le paramètre `owner`. |
| `ControllerParam::OWNER_PATH` | `'ownerPath'` | Le paramètre `ownerPath`. |
| `ControllerParam::PAGINATION` | `'pagination'` | Le paramètre `pagination` (voir `PaginationTrait`). |
| `ControllerParam::PARAMS` | `'params'` | Le paramètre `params`. |
| `ControllerParam::PARAMS_STRATEGY` | `'paramsStrategy'` | Le paramètre `paramsStrategy`. |
| `ControllerParam::PATH` | `'path'` | Le paramètre `path`. |
| `ControllerParam::PAYLOAD` | `'payload'` | Le paramètre `payload` (voir `PaginationTrait`). |
| `ControllerParam::PAYLOADS` | `'payloads'` | Le paramètre `payloads` (voir `PaginationTrait`). |
| `ControllerParam::QUANTITY` | `'quantity'` | Le paramètre `quantity`. |
| `ControllerParam::REDIRECTS` | `'redirects'` | Le paramètre `redirects`. |
| `ControllerParam::ROUTER` | `'router'` | Le paramètre `router`. |
| `ControllerParam::RULES` | `'rules'` | Le paramètre `rules`. |
| `ControllerParam::SANITIZE` | `'sanitize'` | Le paramètre `sanitize`. |
| `ControllerParam::SCHEMA` | `'schema'` | Le paramètre `schema`. |
| `ControllerParam::SEARCH` | `'search'` | Le paramètre `search`. |
| `ControllerParam::SKIN` | `'skin'` | Le paramètre `skin`. |
| `ControllerParam::SKIN_DEFAULT` | `'skinDefault'` | Le paramètre `skinDefault`. |
| `ControllerParam::SKIN_METHODS` | `'skinMethods'` | Le paramètre `skinMethods`. |
| `ControllerParam::SKINS` | `'skins'` | Le paramètre `skins`. |
| `ControllerParam::SORT` | `'sort'` | Le paramètre `sort`. |
| `ControllerParam::SORT_DEFAULT` | `'sortDefault'` | Le paramètre `sortDefault`. |
| `ControllerParam::STATUS` | `'status'` | Le paramètre `status`. |
| `ControllerParam::TIMEZONE` | `'timezone'` | Le paramètre `timezone`. |
| `ControllerParam::TIMEZONE_DEFAULT` | `'timezoneDefault'` | Le paramètre `timezoneDefault`. |
| `ControllerParam::TYPE` | `'type'` | Le paramètre `type`. |
| `ControllerParam::TWIG` | `'twig'` | Le paramètre `twig`. |
| `ControllerParam::URL` | `'url'` | Le paramètre `url`. |
| `ControllerParam::VALIDATOR` | `'validator'` | Le paramètre `validator`. |
| `ControllerParam::VALUE` | `'value'` | Le paramètre `value`. |

> Les clés de pagination (`PAGINATION`, `PAYLOAD`, `PAYLOADS`, …) sont héritées
> de `xyz\oihana\schema\constants\traits\PaginationTrait`, composé dans
> `ControllerParam` aux côtés de `ControllerParamTrait`.

## `FileResponseOption`

`oihana\controllers\enums\FileResponseOption` — les clés d'options acceptées par
les utilitaires de réponse fichier/binaire (par ex. `FileTrait::fileResponse()`).
Ces clés déterminent quels en-têtes de contenu une réponse de téléchargement
émet. Voir [Réponses de fichiers](files.md).

| Constante | Valeur | Signification |
|---|---|---|
| `FileResponseOption::CONTENT_DISPOSITION` | `'contentDisposition'` | La valeur de l'en-tête `Content-Disposition` envoyée lorsque `USE_CONTENT_DISPOSITION` est activé. |
| `FileResponseOption::FORMAT` | `'format'` | Le format de sortie (par ex. `jpg`) utilisé pour construire le type de contenu `image/<format>`. Défaut : `jpg`. |
| `FileResponseOption::USE_CONTENT_DISPOSITION` | `'useContentDisposition'` | Ajoute un en-tête `Content-Disposition` à la réponse. Défaut : `false`. |
| `FileResponseOption::USE_CONTENT_LENGTH` | `'useContentLength'` | Ajoute un en-tête `Content-Length` (la taille du fichier) à la réponse. Défaut : `false`. |
| `FileResponseOption::USE_CONTENT_TYPE` | `'useContentType'` | Ajoute un en-tête `Content-Type` (le type MIME détecté) à la réponse. Défaut : `false`. |

## `ImagickResponseOption`

`oihana\controllers\enums\ImagickResponseOption` — les clés d'options de
transformation Imagick acceptées par `ImageTrait::imagickResponse()`. Voir
[Réponses de fichiers](files.md).

| Constante | Valeur | Signification |
|---|---|---|
| `ImagickResponseOption::COMPRESSION` | `'compression'` | La constante de compression Imagick à appliquer (par ex. `Imagick::COMPRESSION_JPEG`). |
| `ImagickResponseOption::GRAY` | `'gray'` | Désature l'image en niveaux de gris. Défaut : `false`. |
| `ImagickResponseOption::QUALITY` | `'quality'` | La qualité de compression (`0`-`100`). Défaut : `70`. |
| `ImagickResponseOption::STRIP` | `'strip'` | Supprime les profils et commentaires de l'image. Défaut : `false`. |

## `ResizeOption`

`oihana\controllers\enums\ResizeOption` — les clés d'options acceptées par
`oihana\controllers\traits\ImageTrait::resize()` (ainsi que les clés de
géométrie d'image). Voir [Réponses de fichiers](files.md).

| Constante | Valeur | Signification |
|---|---|---|
| `ResizeOption::HEIGHT` | `'height'` | La hauteur courante de l'image, telle que renvoyée par `Imagick::getImageGeometry()`. |
| `ResizeOption::MAX_HEIGHT` | `'maxHeight'` | La hauteur maximale autorisée ; les images plus grandes sont réduites. Défaut : `1200`. |
| `ResizeOption::MAX_WIDTH` | `'maxWidth'` | La largeur maximale autorisée ; les images plus grandes sont réduites. Défaut : `1920`. |
| `ResizeOption::WIDTH` | `'width'` | La largeur courante de l'image, telle que renvoyée par `Imagick::getImageGeometry()`. |

## `Skin`

`oihana\controllers\enums\Skin` — le catalogue des *skins* de données. Un skin
est une projection nommée qui sélectionne les champs qu'un document expose à
travers la surface HTTP. Les contrôleurs déclarent en liste blanche les skins
qu'ils acceptent via leur liste `SKINS` et résolvent celui demandé via
`oihana\controllers\traits\prepare\PrepareSkin`. Les constantes résident dans le
trait compagnon `oihana\controllers\enums\traits\SkinTrait` (qui intègre
lui-même `ConstantsTrait`) et sont exposées via la classe `Skin`, afin que le
catalogue puisse être réutilisé par des énumérations de skins plus spécialisées.

| Constante | Valeur | Signification |
|---|---|---|
| `Skin::AUDIOS` | `'audios'` | Projection centrée sur les ressources audio d'un document. |
| `Skin::COMPACT` | `'compact'` | Projection réduite n'exposant que les champs les plus essentiels. |
| `Skin::DEFAULT` | `'default'` | La projection par défaut appliquée lorsqu'aucun skin n'est demandé. |
| `Skin::EXTEND` | `'extend'` | Projection étendue enrichissant l'ensemble par défaut avec des champs supplémentaires. |
| `Skin::FULL` | `'full'` | Projection complète exposant tous les champs publics du document. |
| `Skin::INTERNAL` | `'internal'` | Projection interne — expose des champs réservés au serveur qui ne doivent **jamais** fuiter via la surface HTTP publique. **Invariant : ne jamais enregistrer `Skin::INTERNAL` dans la liste `SKINS` d'un contrôleur** (voir la note ci-dessous). |
| `Skin::LIST` | `'list'` | Projection optimisée pour le rendu de listes/collections. |
| `Skin::MAIN` | `'main'` | Projection exposant les champs principaux du document. |
| `Skin::MAP` | `'map'` | Projection centrée sur les données géographiques/cartographiques d'un document. |
| `Skin::NORMAL` | `'normal'` | La projection standard d'un document. |
| `Skin::PHOTOS` | `'photos'` | Projection centrée sur les ressources photo d'un document. |
| `Skin::SEARCH` | `'search'` | Projection optimisée pour le rendu de résultats de recherche. |
| `Skin::VIDEOS` | `'videos'` | Projection centrée sur les ressources vidéo d'un document. |

> **Invariant de sécurité.** `Skin::INTERNAL` ne doit jamais figurer dans la
> liste `SKINS` d'un contrôleur. `PrepareSkin::isValidSkin()` rejette tout skin
> absent de cette liste et retombe sur le skin par défaut ; tant qu'`INTERNAL`
> reste en dehors, aucun appelant HTTP ne peut le demander via
> `?skin=internal`. Le code côté serveur peut toujours appeler
> `model->get([ SKIN => INTERNAL ])` directement — ces appels sont de confiance
> car ils proviennent du code PHP serveur, et non de la surface HTTP.

## `TwigParam`

`oihana\controllers\enums\TwigParam` — les clés de paramètres utilisées lors de
la configuration ou du rendu des vues Twig. Voir [Twig](twig.md).

| Constante | Valeur | Signification |
|---|---|---|
| `TwigParam::BACKGROUND_COLOR` | `'backgroundColor'` | La couleur de fond. |
| `TwigParam::FULL_PATH` | `'fullPath'` | Le chemin complet du template. |
| `TwigParam::LOGO` | `'logo'` | L'actif logo. |
| `TwigParam::LOGO_DARK` | `'logoDark'` | L'actif logo pour le mode sombre. |
| `TwigParam::PATTERN_COLOR` | `'patternColor'` | La couleur de motif. |
| `TwigParam::TWIG` | `'twig'` | L'environnement / instance Twig. |

## `UploadOption`

`oihana\controllers\enums\UploadOption` — les clés d'options acceptées par les
utilitaires d'upload (`oihana\controllers\traits\UploadTrait`). Voir
[Archives et uploads](archives-uploads.md).

| Constante | Valeur | Signification |
|---|---|---|
| `UploadOption::ALLOWED_MIME_TYPES` | `'allowedMimeTypes'` | Liste blanche des types MIME autorisés (tableau de chaînes ou de tableaux de chaînes). Lorsqu'elle est définie, le fichier stocké est validé avec `oihana\files\validateMimeType()`. |
| `UploadOption::FILENAME` | `'filename'` | Remplace le nom du fichier stocké (upload simple uniquement). Par défaut, le nom de fichier client assaini. |
| `UploadOption::MAX_SIZE` | `'maxSize'` | Taille maximale autorisée du fichier en octets. Les uploads plus volumineux sont rejetés. Défaut : aucune limite. |
| `UploadOption::OVERWRITE` | `'overwrite'` | Si un fichier cible existant peut être écrasé. Défaut : `false`. |

## Voir aussi

- [Paramètres](params.md) — extraction typée des paramètres de requête et les stratégies `prepare`.
- [Réponses de fichiers](files.md) — téléchargement, streaming, plages HTTP, ETag / 304, chiffrement, images.
- [Archives et uploads](archives-uploads.md) — archives zip/tar et uploads de fichiers.
- [Index de la documentation](README.md) — retour à la table des matières.
