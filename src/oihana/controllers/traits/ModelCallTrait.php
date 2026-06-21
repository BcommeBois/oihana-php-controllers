<?php

namespace oihana\controllers\traits ;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Provides a centralized lifecycle around model calls for controllers.
 *
 * This trait defines two extensibility hooks — {@see beforeModelCall()} and
 * {@see afterModelCall()} — that are automatically invoked around every
 * primary model operation (such as `list`, `get`, `last`, `count`,
 * `insert`, `update`, `replace`, `delete`).
 *
 * It allows controllers to:
 * - Inject request-scoped context into model calls (e.g. user, locale, filters).
 * - Normalize or enrich the `$init` payload before it reaches the model.
 * - Inspect, transform, or decorate the model result in a single place.
 * - Implement cross-cutting concerns such as logging, auditing, caching,
 *   access control, or data post-processing.
 *
 * Both hooks operate on the `$init` payload (and for the "after" hook, the
 * `$result`) **by reference**, allowing in-place mutation without duplicating
 * logic across controller actions.
 *
 * Typical lifecycle:
 *
 * ```text
 * Controller action
 *     ↓
 * beforeModelCall($request, $init)
 *     ↓
 * $result = $this->model->operation($init)
 *     ↓
 * afterModelCall($request, $init, $result)
 *     ↓
 * Response handling
 * ```
 *
 * This design helps keep controllers thin and avoids duplicating logic across
 * multiple HTTP methods by centralizing model interaction concerns.
 *
 * @internal This trait is intended to be used by base controllers such as
 *           `DocumentsController` and extended in concrete controllers.
 *
 * @see beforeModelCall()
 * @see afterModelCall()
 *
 * @package oihana\controllers\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait ModelCallTrait
{
    /**
     * Lifecycle hook invoked **after** every primary model call.
     *
     * Receives the same `$init` array passed to `$this->model->...` (after
     * any modification done in {@see self::beforeModelCall()}) and the result
     * returned by the model — both passed by reference, so an override can
     * inspect, log, or transform the response before it reaches the caller.
     *
     * @param Request|null            $request The PSR-7 request (null in CLI/test contexts).
     * @param array<array-key,mixed>  $init    The init array that was passed to the model (by reference).
     * @param mixed                   $result  The model's return value (by reference, modifiable).
     */
    protected function afterModelCall( ?Request $request , array &$init , mixed &$result ) : void
    {
        // overrides
    }

    /**
     * Lifecycle hook invoked **before** every primary model call
     * (`list/get/last/count/insert/update/replace/delete`).
     *
     * Receives the `$init` array that is about to be passed to the model,
     * by reference. Overrides can enrich it with request-scoped context.
     *
     * The default implementation is a no-op : controllers extending
     * `DocumentsController` only need to override this method when they have
     * request-scoped enrichment to inject for every CRUD operation in one
     * place (instead of overriding every HTTP verb).
     *
     * @param Request|null            $request   The PSR-7 request (null in CLI/test contexts).
     * @param array<array-key,mixed>  $init The init array about to be passed to the model (by reference).
     */
    protected function beforeModelCall( ?Request $request , array &$init ) : void
    {
        // overrides
    }
}