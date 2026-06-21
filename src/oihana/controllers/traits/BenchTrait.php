<?php

namespace oihana\controllers\traits ;

use Psr\Http\Message\ServerRequestInterface as Request;

use oihana\controllers\enums\ControllerParam;
use oihana\controllers\traits\prepare\PrepareBench;
use oihana\enums\Output;

use function oihana\core\date\humanizeDuration;

/**
 * Provides lightweight benchmarking helpers to measure the execution time of a controller action.
 *
 * When benchmarking is enabled (via the `bench` flag), a controller can mark the start of an
 * operation, then later compute and store a human-readable elapsed time in its output options.
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
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
     *
     * The flag is read directly when `$init` is a boolean, otherwise from the
     * `ControllerParam::BENCH` key of the initialization array (defaulting to `false`).
     *
     * @param bool|array $init Optional initialization array or the bench boolean value.
     *
     * @return static Returns the current instance for method chaining.
     */
    public function initializeBench( bool|array $init = [] ):static
    {
        $this->bench = is_bool( $init ) ? $init : ( $init[ ControllerParam::BENCH ] ?? false ) ;
        return $this ;
    }

    /**
     * Stop the bench and compute the elapsed time since the given start timestamp.
     *
     * When a valid positive `$timestamp` is supplied, the elapsed duration is humanized
     * (e.g. `"1.2 s"`) and stored in `$options` under the `Output::TIME` key.
     *
     * @param int|float|null $timestamp The start timestamp returned by {@see self::startBench()}, or `null`.
     * @param array          $options   Reference to the output options array updated with the elapsed time.
     *
     * @return ?string The human-readable time interval of the bench, or `null` when no valid timestamp was given.
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
     *
     * Benchmarking only starts when {@see self::prepareBench()} confirms it is enabled for the
     * current request; otherwise the method returns `0`.
     *
     * @param Request|null $request The current PSR-7 request, used to decide whether benchmarking applies.
     * @param array        $args    Optional route arguments forwarded to the preparation step.
     * @param array|null   $params  Reference to the request parameters, possibly populated by the preparation step.
     *
     * @return int|float|null The start timestamp (from `microtime(true)`) when benchmarking is enabled, otherwise `0`.
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