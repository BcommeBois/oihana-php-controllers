<?php

namespace tests\oihana\controllers\traits;

use DI\Container;

use PHPUnit\Framework\TestCase;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\PaginationTrait;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use ReflectionException;

use xyz\oihana\schema\Pagination;

class MockPaginationController
{
    use PaginationTrait ;
}

final class PaginationTraitTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function testInitializePaginationWithInstance(): void
    {
        $controller = new MockPaginationController();
        $pagination = new Pagination();
        $controller->initializePagination( [ControllerParam::PAGINATION => $pagination ] );
        $this->assertSame( $pagination , $controller->pagination ) ;
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testInitializePaginationWithArray(): void
    {
        $data = ['limit' => 10, 'page' => 2];

        $controller = new MockPaginationController() ;

        $controller->initializePagination([ControllerParam::PAGINATION => $data]);

        $this->assertInstanceOf(Pagination::class, $controller->pagination);
        $this->assertSame(10 , $controller->pagination->limit ) ;
        $this->assertSame(  2, $controller->pagination->page  ) ;
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testInitializePaginationWithContainer(): void
    {
        $container  = new Container() ;
        $controller = new MockPaginationController() ;

        $container->set( 'pagination', ['limit' => 50, 'offset' => 10] );

        $controller->initializePagination( container: $container ) ;

        $this->assertInstanceOf(Pagination::class, $controller->pagination);
        $this->assertSame( 50 , $controller->pagination->limit  ) ;
        $this->assertSame( 10 , $controller->pagination->offset ) ;
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testInitializePaginationWithContainerAndPaginationDefinition(): void
    {
        $container  = new Container() ;
        $controller = new MockPaginationController() ;
        $pagination = new Pagination();

        $container->set( 'pagination', $pagination );

        $controller->initializePagination( container: $container ) ;

        $this->assertSame($pagination, $controller->pagination);
    }
}