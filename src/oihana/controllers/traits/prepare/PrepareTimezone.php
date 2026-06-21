<?php

namespace oihana\controllers\traits\prepare;

use DateTimeZone;
use Exception;

use oihana\controllers\enums\ControllerParam;
use Psr\Http\Message\ServerRequestInterface as Request;
use function oihana\controllers\helpers\getParam;

/**
 * Prepares the `timezone` parameter of a controller request.
 *
 * This trait builds a {@see DateTimeZone} from the {@see ControllerParam::TIMEZONE} request
 * value, falling back to the {@see ControllerParam::TIMEZONE_DEFAULT} entry of
 * `$timeOptions` and then to `$defaultValue`. The resulting object is assigned to the
 * referenced `$timezone` variable and mirrored into the parameter bag.
 *
 * @package oihana\controllers\traits\prepare
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
trait PrepareTimezone
{
    /**
     * Prepare the timezone component.
     *
     * @param Request|null $request      The incoming PSR-7 server request, or null when no request context is available.
     * @param array|null   $params       A reference to the parameter bag updated in place with the resolved {@see DateTimeZone}.
     * @param string|null  $timezone     A reference to the timezone holder assigned in place with the built {@see DateTimeZone}.
     * @param array|null   $timeOptions  The time configuration providing the default timezone entry.
     * @param string       $defaultValue The fallback timezone identifier used when none is supplied (defaults to `Europe/Paris`).
     * @return void
     *
     * @throws Exception If the resolved identifier is not a valid timezone accepted by {@see DateTimeZone}.
     */
    protected function prepareTimezone
    (
        ?Request $request ,
        ?array   &$params ,
        ?string  &$timezone ,
        ?array   $timeOptions ,
        string   $defaultValue = 'Europe/Paris'
    )
    :void
    {
        if( isset( $request ) )
        {
            $params[ ControllerParam::TIMEZONE ]
            = $timezone
            = new DateTimeZone( getParam( $request , ControllerParam::TIMEZONE ) ?? $timeOptions[ ControllerParam::TIMEZONE_DEFAULT ] ?? $defaultValue ) ;
        }
    }
}