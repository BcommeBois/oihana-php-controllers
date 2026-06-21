<?php

namespace oihana\controllers\enums\traits;

use oihana\controllers\traits\ApiTrait;
use oihana\controllers\traits\AppTrait;
use oihana\controllers\traits\CborTrait;
use oihana\controllers\traits\PaginationTrait;
use oihana\traits\JsonOptionsTrait;

/**
 * The enumeration of all the common controller's parameters.
 */
trait ControllerParamTrait
{
    /**
     * The 'api' parameter.
     * @see ApiTrait
     */
    public const string API = 'api' ;

    /**
     * The 'app' parameter.
     * @see AppTrait
     */
    public const string APP = 'app' ;

    /**
     * The 'active' parameter.
     */
    public const string ACTIVE = 'active' ;

    /**
     * The 'all' parameter.
     */
    public const string ALL = 'all' ;

    /**
     * The 'args' parameter.
     */
    public const string ARGS = 'args' ;

    /**
     * The 'baseUrl' parameter.
     */
    public const string BASE_URL = 'baseUrl' ;

    /**
     * The 'bench' parameter.
     */
    public const string BENCH = 'bench' ;

    /**
     * Capabilities configuration block — maps param names (SKIN, FILTER, ...) to per-param capability declarations.
     */
    public const string CAPABILITIES = 'capabilities' ;

    /**
     * Optional kill-switch to disable the whole capability enforcement block without removing its declaration.
     * Defaults to true when the CAPABILITIES block is present.
     */
    public const string CAPABILITIES_ENABLED = 'capabilitiesEnabled' ;

    /**
     * The 'cborSerializeOptions' parameter.
     * @see CborTrait
     */
    public const string CBOR_SERIALIZE_OPTIONS = 'cborSerializeOptions' ;

    /**
     * The 'controller' parameter.
     */
    public const string CONTROLLER = 'controller' ;

    /**
     * The 'dateFormat' parameter.
     */
    public const string DATE_FORMAT = 'dateFormat' ;

    /**
     * The 'documentKey' parameter.
     */
    public const string DOCUMENT_KEY = 'documentKey' ;

    /**
     * The 'customRules' parameter.
     */
    public const string CUSTOM_RULES = 'customRules' ;

    /**
     * The 'facets' parameter.
     */
    public const string FACETS = 'facets' ;

    /**
     * The 'fields' parameter.
     */
    public const string FIELDS = 'fields' ;

    /**
     * The 'filter' parameter.
     */
    public const string FILTER = 'filter' ;

    /**
     * The 'forceUrl' parameter.
     */
    public const string FORCE_URL = 'forceUrl' ;

    /**
     * The 'fullPath' parameter.
     */
    public const string FULL_PATH = 'fullPath' ;

    /**
     * The 'groupBy' parameter.
     */
    public const string GROUP_BY = 'groupBy' ;

    /**
     * The 'hasTotal' parameter.
     */
    public const string HAS_TOTAL = 'hasTotal' ;

    /**
     * The 'httpCache' parameter.
     */
    public const string HTTP_CACHE = 'httpCache' ;

    /**
     * The 'id' parameter.
     */
    public const string ID = 'id' ;

    /**
     * The 'ids' parameter.
     */
    public const string IDS = 'ids' ;

    /**
     * The 'interval' parameter.
     */
    public const string INTERVAL = 'interval' ;

    /**
     * The 'intervalDefault' parameter.
     */
    public const string INTERVAL_DEFAULT = 'intervalDefault' ;

    /**
     * The 'jsonSerializeOptions' parameter.
     * @see JsonOptionsTrait
     */
    public const string JSON_SERIALIZE_OPTIONS = 'jsonSerializeOptions' ;

    /**
     * The 'jsonOptions' parameter.
     * @see JsonOptionsTrait
     */
    public const string JSON_OPTIONS = 'jsonOptions' ;

    /**
     * The 'key' parameter.
     */
    public const string KEY = 'key' ;

    /**
     * The 'lang' parameter.
     */
    public const string LANG = 'lang' ;

    /**
     * The 'languages' parameter.
     */
    public const string LANGUAGES = 'languages' ;

    /**
     * The 'list' parameter.
     */
    public const string LIST = 'list' ;

    /**
     * The 'margin' parameter.
     */
    public const string MARGIN = 'margin' ;

    /**
     * The 'mock' parameter.
     */
    public const string MOCK = 'mock' ;

    /**
     * The 'model' parameter.
     */
    public const string MODEL = 'model' ;

    /**
     * The 'order' parameter.
     */
    public const string ORDER = 'order' ;

    /**
     * The 'orders' parameter.
     */
    public const string ORDERS = 'orders' ;

    /**
     * The 'owner' parameter.
     */
    public const string OWNER = 'owner' ;

    /**
     * The 'ownerPath' parameter.
     */
    public const string OWNER_PATH = 'ownerPath' ;

    /**
     * The 'pagination' parameter.
     * @see PaginationTrait
     */
    public const string PAGINATION = 'pagination' ;

    /**
     * The 'params' parameter.
     */
    public const string PARAMS = 'params' ;

    /**
     * The 'paramsStrategy' parameter.
     */
    public const string PARAMS_STRATEGY = 'paramsStrategy' ;

    /**
     * The 'path' parameter.
     */
    public const string PATH = 'path' ;

    /**
     * The 'payload' parameter.
     * @see PaginationTrait
     */
    public const string PAYLOAD = 'payload' ;

    /**
     * The 'payloads' parameter.
     * @see PaginationTrait
     */
    public const string PAYLOADS = 'payloads' ;

    /**
     * The 'quantity' parameter.
     */
    public const string QUANTITY = 'quantity' ;

    /**
     * The 'redirects' parameter.
     */
    public const string REDIRECTS = 'redirects' ;

    /**
     * The 'router' parameter.
     */
    public const string ROUTER = 'router' ;

    /**
     * The 'rules' parameter.
     */
    public const string RULES = 'rules' ;

    /**
     * The 'sanitize' parameter.
     */
    public const string SANITIZE = 'sanitize' ;

    /**
     * The 'schema' parameter.
     */
    public const string SCHEMA = 'schema' ;

    /**
     * The 'search' parameter.
     */
    public const string SEARCH = 'search' ;

    /**
     * The 'skin' parameter.
     */
    public const string SKIN = 'skin' ;

    /**
     * The 'skinDefault' parameter.
     */
    public const string SKIN_DEFAULT = 'skinDefault' ;

    /**
     * The 'skinMethods' parameter.
     */
    public const string SKIN_METHODS = 'skinMethods' ;

    /**
     * The 'skins' parameter.
     */
    public const string SKINS = 'skins' ;

    /**
     * The 'sort' parameter.
     */
    public const string SORT = 'sort' ;

    /**
     * The 'sortDefault' parameter.
     */
    public const string SORT_DEFAULT = 'sortDefault' ;

    /**
     * The 'status' parameter.
     */
    public const string STATUS = 'status' ;

    /**
     * The 'timezone' parameter.
     */
    public const string TIMEZONE = 'timezone' ;

    /**
     * The 'timezoneDefault' parameter.
     */
    public const string TIMEZONE_DEFAULT = 'timezoneDefault' ;

    /**
     * The 'type' parameter.
     */
    public const string TYPE = 'type' ;

    /**
     * The 'twig' parameter.
     */
    public const string TWIG = 'twig' ;

    /**
     * The 'url' parameter.
     */
    public const string URL = 'url' ;

    /**
     * The 'validator' parameter.
     */
    public const string VALIDATOR = 'validator' ;

    /**
     * The 'value' parameter.
     */
    public const string VALUE = 'value' ;
}