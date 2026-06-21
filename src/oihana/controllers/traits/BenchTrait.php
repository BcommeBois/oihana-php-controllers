<?php

namespace oihana\controllers\traits ;

use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareBench;
use oihana\enums\Output;

use function oihana\core\date\humanizeDuration;

trait BenchTrait
{
    use PrepareBench ;

    /**
     * The bench flag to test the script execution time of a function.
     * @var bool
     */
    public bool $bench = false ;

    /**
     * Initialize the `bench` property.
     * @param bool|array $init Optional initialization array or the bench boolean value.
     * @return $this
     */
    public function initializeBench( bool|array $init = [] ):static
    {
        $this->bench = is_bool( $init ) ? $init : ( $init[ ControllerParam::BENCH ] ?? false ) ;
        return $this ;
    }

    /**
     * Stop the bench.
     * @param int|float|null $timestamp
     * @param array $options
     * @return ?string The time interval of the bench.
     */
    public function endBench( null|int|float $timestamp , array &$options = [] ): ?string
    {
        if( isset( $timestamp ) && $timestamp > 0 )
        {
            $timeInterval = humanizeDuration( microtime( true ) - $timestamp ) ;
            $options[ Output::TIME ] = $timeInterval ;
            return $timeInterval ;
        }
        return null ;
    }

    /**
     * Start the bench process.
     * @param Request|null $request
     * @param array $args
     * @param array|null $params
     * @return int|float|null
     */
    public function startBench( ?Request $request , array $args = [] , ?array &$params = null ) :null|float|int
    {
        if( $this->prepareBench( $request , $args , $params ) )
        {
            return microtime(true ) ;
        }
        return 0 ;
    }
}