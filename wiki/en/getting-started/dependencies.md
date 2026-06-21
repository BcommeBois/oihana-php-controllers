# Dependencies

`oihana/php-controllers` sits at the top of the *oihana* stack, so its runtime
footprint is the largest of the family. Here is what it requires and **why**.

## PHP extensions

| Extension | Role |
|---|---|
| `ext-imagick` | Image decoding, resizing and encoding in `ImageTrait`. |

## Oihana runtime dependencies

| Package | Role |
|---|---|
| [`oihana/php-core`](https://github.com/BcommeBois/oihana-php-core) | Core helpers — accessors, strings, arrays, `date\humanizeDuration()`, `maths\aspectFit()`. |
| [`oihana/php-enums`](https://github.com/BcommeBois/oihana-php-enums) | Typed constants — `Char`, `Output`, `http\*` (headers, status, methods). |
| [`oihana/php-exceptions`](https://github.com/BcommeBois/oihana-php-exceptions) | HTTP exception types (`http\Error404`, `http\Error500`). |
| [`oihana/php-files`](https://github.com/BcommeBois/oihana-php-files) | File, path, archive (zip/tar) and OpenSSL encryption helpers. |
| [`oihana/php-logging`](https://github.com/BcommeBois/oihana-php-logging) | PSR-3 logging (`LoggerTrait`). |
| [`oihana/php-models`](https://github.com/BcommeBois/oihana-php-models) | Document models the data controllers call (`DocumentsTrait`, `ExistModel`). |
| [`oihana/php-reflect`](https://github.com/BcommeBois/oihana-php-reflect) | `ConstantsTrait` and the JSON/CBOR serializers. |
| [`oihana/php-schema`](https://github.com/BcommeBois/oihana-php-schema) | Schema.org constants (`org\schema\constants\Prop`). |
| [`oihana/php-traits`](https://github.com/BcommeBois/oihana-php-traits) | Reusable object traits (`ContainerTrait`, `ConfigTrait`, …). |

## External runtime dependencies

| Package | Role |
|---|---|
| [`php-di/php-di`](https://packagist.org/packages/php-di/php-di) | PSR-11 DI container injected into every controller. |
| [`slim/slim`](https://packagist.org/packages/slim/slim) | The Slim app, routing and PSR-15 middleware. |
| [`slim/psr7`](https://packagist.org/packages/slim/psr7) | PSR-7 request/response/stream implementation. |
| [`slim/twig-view`](https://packagist.org/packages/slim/twig-view) | Twig integration for Slim (`TwigTrait`). |
| [`slim/csrf`](https://packagist.org/packages/slim/csrf) | CSRF protection (`CsrfTrait`). |
| [`slim/http-cache`](https://packagist.org/packages/slim/http-cache) | HTTP caching headers (`HttpCacheTrait`). |
| [`twig/twig`](https://packagist.org/packages/twig/twig) | The Twig templating engine. |
| [`somnambulist/validation`](https://github.com/somnambulist-tech/validation) | The validation engine behind `ValidatorTrait`. |
| [`psr/container`](https://packagist.org/packages/psr/container) | PSR-11 `ContainerInterface` contract. |
| [`psr/http-message`](https://packagist.org/packages/psr/http-message) | PSR-7 message interfaces. |
| [`psr/http-server-middleware`](https://packagist.org/packages/psr/http-server-middleware) | PSR-15 middleware/handler interfaces. |
| [`psr/log`](https://packagist.org/packages/psr/log) | PSR-3 `LoggerInterface` contract. |

## Development dependencies

| Package | Role |
|---|---|
| `phpunit/phpunit` | Test runner (strict mode). |
| `nunomaduro/collision` | Readable CLI error output. |
| `phpdocumentor/shim` | API documentation generation. |

## Next steps

- [Controller](../controller.md)
- [Parameters](../params.md)
