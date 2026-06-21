<?php

namespace tests\oihana\controllers\traits;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use oihana\controllers\traits\CheckOwnerArgumentsTrait;
use oihana\exceptions\http\Error404;
use oihana\exceptions\http\Error500;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use tests\oihana\models\mocks\MockDocumentsModel;

final class CheckOwnerArgumentsTraitTest extends TestCase
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws Error500
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws Error404
     * @throws DependencyException
     */
    public function test_passes_when_all_owners_exist(): void
    {
        $model = new MockDocumentsModel()->addDocuments([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
        ]);

        $controller = new MockCheckOwnerArgumentsController() ;
        $controller->owner = ['userId' => $model];

        $this->expectNotToPerformAssertions();
        $controller->checkOwnerArguments(['userId' => 1]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Error500
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     */
    public function test_throws_404_when_owner_does_not_exist(): void
    {
        $model = new MockDocumentsModel()->addDocument(['id' => 1, 'name' => 'foo']);

        $controller = new MockCheckOwnerArgumentsController() ;
        $controller->owner = ['userId' => $model];

        $this->expectException(Error404::class);
        $this->expectExceptionMessage('The userId argument is not found.');

        $controller->checkOwnerArguments(['userId' => 999]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws Error404
     * @throws DependencyException
     */
    public function test_throws_500_when_model_is_invalid(): void
    {
        $controller = new MockCheckOwnerArgumentsController() ;
        $controller->owner = ['userId' => null];

        $this->expectException( Error500::class ) ;
        $this->expectExceptionMessage
        (
            "The userId argument can't be checked with a null or bad Documents model reference."
        );

        $controller->checkOwnerArguments(['userId' => 1]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Error500
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws Error404
     * @throws DependencyException
     */
    public function test_resolves_model_from_container(): void
    {
        $model = new MockDocumentsModel()->addDocument(['id' => 5]);

        $container = new Container();
        $container->set('documents.user' , $model ) ;

        $controller = new MockCheckOwnerArgumentsController( $container ) ;
        $controller->owner = ['userId' => 'documents.user'];

        $this->expectNotToPerformAssertions();
        $controller->checkOwnerArguments(['userId' => 5]);
    }

    public function test_initialize_owner_definition(): void
    {
        $controller = new MockCheckOwnerArgumentsController() ;
        $model = new MockDocumentsModel();

        $controller->initializeOwner(['owner' => ['accountId' => $model]]);

        $this->assertIsArray($controller->owner);
        $this->assertArrayHasKey('accountId', $controller->owner);
        $this->assertSame($model, $controller->owner['accountId']);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Error500
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws Error404
     * @throws DependencyException
     */
    public function test_ignores_missing_args(): void
    {
        $model = new MockDocumentsModel()->addDocument(['id' => 1]);
        $controller = new MockCheckOwnerArgumentsController() ;
        $controller->owner = ['userId' => $model];

        $this->expectNotToPerformAssertions();
        $controller->checkOwnerArguments([]); // no 'userId' key
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Error500
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws Error404
     * @throws DependencyException
     */
    public function test_no_error_if_owner_not_defined(): void
    {
        $controller = new MockCheckOwnerArgumentsController() ;
        $controller->owner = null;

        $this->expectNotToPerformAssertions();
        $controller->checkOwnerArguments(['id' => 123]);
    }
}

class MockCheckOwnerArgumentsController
{
    public function __construct( ?Container $container = null )
    {
        $this->container = $container ?? new Container() ;
    }

    use CheckOwnerArgumentsTrait;

    public Container $container ;
}
