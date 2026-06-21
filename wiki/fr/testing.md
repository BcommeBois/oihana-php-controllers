# Tests & couverture

La bibliothèque est livrée avec une suite PHPUnit complète exécutée en **mode
strict** et couvrant **100 % des lignes** (438 tests).

## Lancer la suite

```bash
composer test
```

Lancer un seul cas de test :

```bash
./vendor/bin/phpunit --filter PaginationTraitTest
```

> Les tests d'images requièrent l'extension **`ext-imagick`**.

## Mesurer la couverture

La couverture nécessite **Xdebug** ou **PCOV**.

```bash
composer coverage        # texte + Clover + HTML sous build/coverage/
composer coverage:md     # résumé Markdown lisible (build/coverage/COVERAGE.md)
```

`build/` est **gitignoré** : la couverture est un instantané qui devient obsolète
au prochain commit ; elle est donc régénérée à la demande plutôt que commitée.
`composer coverage:md` tient aussi un petit journal de tendance local
(`build/coverage/history.json`) afin que chaque exécution montre le delta depuis
la précédente.

## Mode strict

`phpunit.xml` active `failOnRisky`, `failOnWarning`, `failOnSkipped`,
`failOnIncomplete` et `failOnEmptyTestSuite`. Autrement dit : les avertissements,
les tests risqués (sans assertion) et les tests ignorés font tous **échouer**
l'exécution. Un test qui ne vérifie rien ne protège rien.

## Philosophie de test

- La couverture mesure quelles lignes ont **été exécutées**, pas quels
  comportements sont **vérifiés** — 100 % de couverture ≠ zéro bug.
- Quand vous découvrez un comportement surprenant, **figez-le dans un test**
  avant de toucher à quoi que ce soit : d'autres bibliothèques peuvent en dépendre.
- Testez tout ce qui est atteignable ; n'annotez une ligne `@codeCoverageIgnore`
  que lorsqu'elle est réellement impossible à atteindre (par exemple une garde
  défensive que la surface publique ne peut pas déclencher).

## Intégration continue

Chaque push et chaque pull request exécute la suite sur PHP 8.4 via GitHub
Actions (`.github/workflows/ci.yml`, avec les extensions `imagick`, `zip` et
`fileinfo` activées) ; la documentation API est construite et déployée sur
GitHub Pages par `.github/workflows/docs.yml`.
