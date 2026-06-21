# Installation

## Requirements

- **PHP 8.4 or higher.**
- **[Composer](https://getcomposer.org/).**
- **`ext-imagick`** — required at runtime by the image controllers (`ImageTrait`)
  and by the test suite.

Transitive dependencies may require other common extensions (e.g. `ext-fileinfo`
and `ext-zip` via `oihana/php-files`), which ship with most PHP distributions.

## Install via Composer

```bash
composer require oihana/php-controllers
```

## Autoloading

Classes are autoloaded via PSR-4 under the `oihana\controllers\` namespace, and
the 20 request/response helpers via composer `autoload.files`:

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

> The snippet above is abbreviated — the package wires **all 20** helper files.
> See [Helpers](../helpers.md) for the complete list.

Once installed, import the classes and helpers directly:

```php
use oihana\controllers\Controller;

use function oihana\controllers\helpers\getParamInt;
```

## Verify the installation

```php
require 'vendor/autoload.php';

use DI\Container;
use oihana\controllers\Controller;

$controller = new Controller( new Container() );
```

## Next steps

- [Dependencies](dependencies.md)
- [Controller](../controller.md)
