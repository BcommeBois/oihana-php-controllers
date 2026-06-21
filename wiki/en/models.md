# Models

This page covers the traits that wire a controller to its **data model**. In the
*oihana* stack persistence is delegated to a [`oihana/php-models`](https://github.com/BcommeBois/oihana-php-models)
model — typically a `DocumentsModel` — which is resolved from the PSR-11
container. The controller stays thin: it extracts parameters, calls the model,
shapes the documents into a response and validates the input. The traits below
each own one of those concerns.

All examples are grounded in the real trait sources and their PHPUnit tests.
Where a model is needed they use the shared `MockDocumentsModel` fixture
(`tests/oihana/models/mocks/MockDocumentsModel.php`), an in-memory
`DocumentsModel` implementation.

## `ModelCallTrait` — lifecycle hooks around model calls

`oihana\controllers\traits\ModelCallTrait` defines two **protected** extensibility
hooks that a base controller invokes around every primary model operation
(`list` / `get` / `last` / `count` / `insert` / `update` / `replace` / `delete`):

| Method | Signature | Role |
|---|---|---|
| `beforeModelCall` | `beforeModelCall( ?Request $request , array &$init ) : void` | Enrich the `$init` payload before it reaches the model. |
| `afterModelCall` | `afterModelCall( ?Request $request , array &$init , mixed &$result ) : void` | Inspect or transform the model result. |

Both default implementations are **no-ops**; `$init` (and `$result` for the
*after* hook) are passed **by reference**, so an override can mutate them in
place. The lifecycle is:

```text
beforeModelCall($request, $init)
    ↓
$result = $this->model->operation($init)
    ↓
afterModelCall($request, $init, $result)
```

Override the hooks to centralize request-scoped concerns — injecting the current
user, normalizing filters, logging or post-processing — instead of repeating the
logic in every HTTP verb:

```php
use oihana\controllers\traits\ModelCallTrait;

use Psr\Http\Message\ServerRequestInterface as Request;

class ArticlesController
{
    use ModelCallTrait ;

    // Inject a request-scoped filter for every CRUD operation.
    protected function beforeModelCall( ?Request $request , array &$init ) : void
    {
        $init[ 'locale' ] = $request?->getHeaderLine( 'Accept-Language' ) ?: 'en' ;
    }

    // Stamp the result before it is handed back to the action.
    protected function afterModelCall( ?Request $request , array &$init , mixed &$result ) : void
    {
        if ( is_array( $result ) )
        {
            $result[ 'fetchedAt' ] = time() ;
        }
    }
}
```

## `OutputDocumentsTrait` — rendering documents to a response

`oihana\controllers\traits\OutputDocumentsTrait` turns a raw array of documents
into a standardized success response. It composes `BaseUrlTrait` (URL generation)
and `StatusTrait` (response formatting).

| Method | Signature | Role |
|---|---|---|
| `outputDocuments` | `outputDocuments( ?Request, ?Response, ?array $documents, array $params = [], ?array $options = null ) : array\|object\|null` | Wrap documents in a response **if a `$response` is given**, otherwise return the raw array. |
| `documentsResponse` | `documentsResponse( ?Request, ?Response, ?array $documents, array $params = [], ?array $options = null ) : ?object` | Build the success payload (`count`, `options`, `url`). |
| `getDocumentUrl` | `getDocumentUrl( ?Request, array $params = [] ) : string` | The document URL; defaults to `BaseUrlTrait::getCurrentPath()`. Override to customize. |

`outputDocuments()` is the method you call from an action. When a `$response` is
supplied, the documents are wrapped via `documentsResponse()` (which also strips
`null` entries from `$params` before URL generation); without a response, the raw
documents are returned — handy in CLI or test contexts.

```php
use oihana\controllers\traits\OutputDocumentsTrait;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use tests\oihana\models\mocks\MockDocumentsModel;

class CatalogController
{
    use OutputDocumentsTrait ;

    public function index( Request $request , Response $response ) : Response
    {
        $model = new MockDocumentsModel()->addDocuments
        ([
            [ 'id' => 1 , 'name' => 'foo' ] ,
            [ 'id' => 2 , 'name' => 'bar' ] ,
        ]);

        $documents = $model->list() ;

        // With a response: a wrapped success object (count, options, url).
        return $this->outputDocuments
        (
            $request ,
            $response ,
            $documents ,
            [ 'page' => 1 ] ,        // $params — used to build the url
            [ 'extra' => true ]      // $options — echoed back in the response
        );
    }
}
```

Without a `$response`, the same call returns the documents unchanged:

```php
$result = $this->outputDocuments( $request , null , $documents ) ;
// $result === $documents
```

The default URL comes from the current request path joined to `baseUrl`:

```php
$this->baseUrl = '/api' ;
$this->getDocumentUrl( $request ) ; // '/api/documents' for a request to /documents
```

## `CheckOwnerArgumentsTrait` — ownership checks

`oihana\controllers\traits\CheckOwnerArgumentsTrait` validates that "owner"
arguments passed to an action actually correspond to existing records, throwing
an HTTP error otherwise. It composes `DocumentsTrait` and exposes a public
`?array $owner` map of *argument name → model*.

| Member | Signature | Role |
|---|---|---|
| `$owner` | `public ?array $owner` | Map of argument name to a `DocumentsModel` (or its container id). |
| `initializeOwner` | `initializeOwner( array $init = [] ) : static` | Read the `owner` key (`ControllerParam::OWNER`) from `$init`. |
| `checkOwnerArguments` | `checkOwnerArguments( array $args = [] ) : void` | Validate each owner argument present in `$args`. |

For each `$owner` entry whose argument is present in `$args`, the model is
resolved with `getDocumentsModel()` (so a string is looked up in the container)
and its `exist()` method is called:

- a missing argument is **ignored** (no error);
- if the value does not exist, an `Error404` is thrown;
- if the reference is `null` or not an `ExistModel`, an `Error500` is thrown.

```php
use oihana\controllers\traits\CheckOwnerArgumentsTrait;

use DI\Container;

use tests\oihana\models\mocks\MockDocumentsModel;

$controller = new class( new Container() )
{
    use CheckOwnerArgumentsTrait ;

    public function __construct( public Container $container ) {}
};

$users = new MockDocumentsModel()->addDocuments
([
    [ 'id' => 1 , 'name' => 'foo' ] ,
    [ 'id' => 2 , 'name' => 'bar' ] ,
]);

$controller->owner = [ 'userId' => $users ] ;

$controller->checkOwnerArguments( [ 'userId' => 1 ] ) ; // OK, user #1 exists
$controller->checkOwnerArguments( [] ) ;                // OK, argument absent → ignored
$controller->checkOwnerArguments( [ 'userId' => 999 ] ) ; // throws Error404
```

The model may also be a **container id** resolved at check time:

```php
$container = new Container() ;
$container->set( 'documents.user' , new MockDocumentsModel()->addDocument( [ 'id' => 5 ] ) ) ;

$controller->owner = [ 'userId' => 'documents.user' ] ;
$controller->checkOwnerArguments( [ 'userId' => 5 ] ) ; // resolved from the container
```

`initializeOwner()` reads the definition from an `$init` array, which is the form
used when booting a controller:

```php
$controller->initializeOwner( [ 'owner' => [ 'accountId' => $accountModel ] ] ) ;
```

## `ForceDocumentUrlTrait` — document URL injection

`oihana\controllers\traits\ForceDocumentUrlTrait` appends a `url` property onto
documents — convenient for exposing a self-link in `get()` and `list()` results.

| Member | Signature | Role |
|---|---|---|
| `$documentKey` | `public ?string $documentKey` | The primary key used to build per-document URLs (default `ControllerParam::ID` = `'id'`). |
| `$forceUrl` | `public bool $forceUrl` | Whether the controller should force a URL (default `false`). |
| `forceDocumentUrl` | `forceDocumentUrl( null\|object\|array &$document , ?string $url , string $propertyName = ControllerParam::URL ) : object\|array\|null` | Set `$url` on a **single** document. |
| `forceDocumentsUrl` | `forceDocumentsUrl( null\|array &$documents , ?string $url , ?string $key = null , string $propertyName = ControllerParam::URL ) : void` | Append `"$url/$key"` on **each** document of a list. |
| `initializeForceUrl` | `initializeForceUrl( array $init = [] ) : static` | Read `documentKey` / `forceUrl` from `$init`. |

`forceDocumentUrl()` sets the property verbatim on one resource (an associative
array or an object; sequential arrays and `null` are left untouched).
`forceDocumentsUrl()` builds `"$url/<key value>"` for every item that has the key.

```php
use oihana\controllers\traits\ForceDocumentUrlTrait;

$controller = new class
{
    use ForceDocumentUrlTrait ;

    public function expose( null|object|array &$document , ?string $url ) : object|array|null
    {
        return $this->forceDocumentUrl( $document , $url ) ;
    }

    public function exposeAll( null|array &$documents , ?string $url , ?string $key = null ) : void
    {
        $this->forceDocumentsUrl( $documents , $url , $key ) ;
    }
};

// Single document: the url is set verbatim.
$document = [ 'id' => 1 , 'name' => 'foo' ] ;
$controller->expose( $document , '/api/foo' ) ;
// $document['url'] === '/api/foo'

// List of documents: url becomes "<base>/<key value>".
$documents = [ [ 'id' => 1 ] , [ 'id' => 2 ] ] ;
$controller->exposeAll( $documents , '/api' , 'id' ) ;
// $documents[0]['url'] === '/api/1'
// $documents[1]['url'] === '/api/2'
```

When no `$key` is passed, `forceDocumentsUrl()` falls back to `$documentKey`,
which you can set through `initializeForceUrl()`:

```php
$controller->initializeForceUrl([ 'documentKey' => 'id' , 'forceUrl' => true ]) ;

$documents = [ (object) [ 'id' => 7 ] ] ;
$controller->exposeAll( $documents , '/api' ) ; // uses the default key 'id'
// $documents[0]->url === '/api/7'
```

## `ValidatorTrait` — validating input

`oihana\controllers\traits\ValidatorTrait` integrates the
[somnambulist/validation](https://github.com/somnambulist-tech/validation)
library. It composes `ContainerTrait` and `StatusTrait` and holds a public
`Validator $validator`, plus `array $rules` and `array $customRules`.

| Method | Signature | Role |
|---|---|---|
| `initializeValidator` | `initializeValidator( array $init = [] ) : static` | Set the `validator`, `customRules` and `rules` from `$init`, then register the custom rules. |
| `addRules` | `addRules( array $rules = [] ) : void` | Register extra rules on the validator (see below). |
| `prepareRules` | `prepareRules( ?string $method = null ) : array` | Merge the `ALL` rules with the rules of a given HTTP method. |
| `initCustomValidationRules` | `initCustomValidationRules() : array` | Return `customRules`; override to extend the defaults. |
| `getValidatorError` | `getValidatorError( ?Request, ?Response, Validation $validation, array $errors = [], int\|string $code = 400 ) : ?Response` | Build a failure response, merging the validation errors. |

### `addRules` — rule instances or container references

`addRules()` accepts a map of *field → rule*. Each value may be a
`Somnambulist\Components\Validation\Rule` instance, or a **string** referencing a
rule registered in the container — in which case it is resolved via
`$this->container->get()`. Values that resolve to anything other than a `Rule`
are silently ignored.

```php
use oihana\controllers\traits\ValidatorTrait;

use DI\Container;

use Somnambulist\Components\Validation\Factory as Validator;
use Somnambulist\Components\Validation\Rule;

$container = new Container() ;

$controller = new class( $container )
{
    use ValidatorTrait ;

    public function __construct( public Container $container )
    {
        $this->validator = new Validator() ;
    }
};

// 1. A Rule instance, registered directly.
$controller->addRules( [ 'slug' => new MyUppercaseRule() ] ) ; // any Rule subclass

// 2. A container string reference, resolved to a Rule at registration time.
$container->set( 'my_custom_rule' , new MyUppercaseRule() ) ;
$controller->addRules( [ 'slug' => 'my_custom_rule' ] ) ;
```

### `prepareRules` — per-method rule sets

Organize `$rules` by HTTP method (using `HttpMethod` constants). `prepareRules()`
merges the shared `ALL` rules with the rules specific to the requested method:

```php
use oihana\enums\http\HttpMethod;

$controller->rules =
[
    HttpMethod::ALL  => [ 'email' => 'required|email'  ] ,
    HttpMethod::POST => [ 'name'  => 'required|string' ] ,
];

$controller->prepareRules( HttpMethod::POST ) ;
// [ 'email' => 'required|email' , 'name' => 'required|string' ]

$controller->prepareRules( null ) ;
// [ 'email' => 'required|email' ]  (only the ALL rules)
```

### `getValidatorError` — turning failures into a response

Given a `Validation` result, `getValidatorError()` produces a failure response.
When the validation **fails**, its first error per field
(`$validation->errors()->firstOfAll()`) is merged into the supplied `$errors`
under the `Output::ERRORS` key; the `$code` defaults to `400`.

```php
$validation = $this->validator->validate( $request->getParsedBody() , $rules ) ;

if ( $validation->fails() )
{
    return $this->getValidatorError
    (
        $request ,
        $response ,
        $validation ,
        [ 'context' => 'create' ] , // extra errors merged in
        422                         // HTTP status code
    );
}
```

A complete action stitches the pieces together: prepare the rules, validate, and
either return the error response or call the model:

```php
use oihana\enums\http\HttpMethod;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

public function create( Request $request , Response $response ) : Response
{
    $rules      = $this->prepareRules( HttpMethod::POST ) ;
    $validation = $this->validator->validate( (array) $request->getParsedBody() , $rules ) ;

    if ( $validation->fails() )
    {
        return $this->getValidatorError( $request , $response , $validation ) ;
    }

    $document  = $this->model->insert( [ 'document' => (array) $request->getParsedBody() ] ) ;
    return $this->outputDocuments( $request , $response , [ $document ] ) ;
}
```

## See also

- [Responses](responses.md) — JSON, CBOR, status and API output (`StatusTrait`).
- [Parameters](params.md) — typed request-parameter extraction.
- [Documentation index](README.md) — the full table of contents.
- [oihana/php-models](https://github.com/BcommeBois/oihana-php-models) — the
  model layer (`DocumentsModel`, the CRUD interfaces and supporting traits).
