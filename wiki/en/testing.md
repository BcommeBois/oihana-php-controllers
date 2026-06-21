# Tests & coverage

The library ships with a full PHPUnit suite running in **strict mode** and
covering **100% of lines** (438 tests).

## Run the suite

```bash
composer test
```

Run a single test case:

```bash
./vendor/bin/phpunit --filter PaginationTraitTest
```

> The image tests require the **`ext-imagick`** extension.

## Measure coverage

Coverage requires **Xdebug** or **PCOV**.

```bash
composer coverage        # text + Clover + HTML under build/coverage/
composer coverage:md     # readable Markdown summary (build/coverage/COVERAGE.md)
```

`build/` is **gitignored**: coverage is a snapshot that goes stale at the next
commit, so it is regenerated on demand rather than committed. `composer
coverage:md` also keeps a small local trend log (`build/coverage/history.json`)
so each run shows the delta since the previous one.

## Strict mode

`phpunit.xml` enables `failOnRisky`, `failOnWarning`, `failOnSkipped`,
`failOnIncomplete` and `failOnEmptyTestSuite`. In other words: warnings, risky
tests (no assertion) and skipped tests all **fail** the run. A test that checks
nothing protects nothing.

## Testing philosophy

- Coverage measures which lines **ran**, not which behaviours are **verified** —
  100% coverage is not zero bugs.
- When you discover a surprising behaviour, **freeze it in a test** before
  changing anything: other libraries may rely on it.
- Test everything reachable; only annotate a line `@codeCoverageIgnore` when it
  is genuinely impossible to reach (e.g. a defensive guard the public surface
  cannot trigger).

## Continuous integration

Every push and pull request runs the suite on PHP 8.4 via GitHub Actions
(`.github/workflows/ci.yml`, with the `imagick`, `zip` and `fileinfo`
extensions enabled); the API documentation is built and deployed to GitHub
Pages by `.github/workflows/docs.yml`.
