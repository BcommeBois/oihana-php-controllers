<?php

namespace oihana\controllers\enums\traits;

use oihana\reflect\traits\ConstantsTrait;

/**
 * The enumeration of all skins in the API.
 *
 * A *skin* is a named projection that selects which fields a document
 * exposes through the HTTP surface. Controllers whitelist the skins they
 * accept via their `SKINS` list and resolve the requested one through
 * {@see \oihana\controllers\traits\prepare\PrepareSkin}.
 */
trait SkinTrait
{
    use ConstantsTrait ;

    /**
     * Projection focused on the audio resources of a document.
     * @var string
     */
    public const string AUDIOS = 'audios' ;

    /**
     * Reduced projection exposing only the most essential fields.
     * @var string
     */
    public const string COMPACT = 'compact' ;

    /**
     * The default projection applied when no skin is requested.
     * @var string
     */
    public const string DEFAULT = 'default' ;

    /**
     * Extended projection enriching the default set with extra fields.
     * @var string
     */
    public const string EXTEND = 'extend' ;

    /**
     * Full projection exposing every public field of the document.
     * @var string
     */
    public const string FULL = 'full' ;

    /**
     * Internal projection — exposes server-only fields that must NEVER
     * leak through the public HTTP surface (e.g. the SHA-256 of the
     * pending-email verification code on `User`).
     *
     * **Invariant — do NOT register `Skin::INTERNAL` in any controller's
     * `Arango::SKINS` list.** Doing so would expose the underlying fields
     * via `?skin=internal` on a public route. The controller's
     * {@see \oihana\controllers\traits\prepare\PrepareSkin::isValidSkin()}
     * filter rejects any skin not in that list and falls back to the
     * default — so as long as `INTERNAL` stays out of the list, no HTTP
     * caller can request it. This is the security guarantee.
     *
     * No matching Casbin permission exists, by design. Granting one
     * (e.g. `users:skin.internal`) would let a superadmin attribute it
     * to a user via `POST /users/{id}/permissions/{permKey}` and break
     * the invariant. If a future use-case really needs HTTP access to
     * an `internal`-projected document (admin debug tool, audit page),
     * introduce a dedicated permission AND a `Capability::PARAMS` gate
     * AND a hardcoded whitelist preventing the permission from being
     * attributed in the first place — all three layers, not just one.
     *
     * Server-side traits call `model->get([SKIN => INTERNAL])` directly.
     * The capability framework lives on the HTTP controller layer, not
     * on the model — direct model calls are therefore not gated, by
     * design. They remain trusted because they originate from server
     * PHP code.
     */
    public const string INTERNAL = 'internal' ;

    /**
     * Projection optimized for list/collection rendering.
     * @var string
     */
    public const string LIST = 'list' ;

    /**
     * Projection exposing the main fields of the document.
     * @var string
     */
    public const string MAIN = 'main' ;

    /**
     * Projection focused on the geographic/map data of a document.
     * @var string
     */
    public const string MAP = 'map' ;

    /**
     * The standard projection of a document.
     * @var string
     */
    public const string NORMAL = 'normal' ;

    /**
     * Projection focused on the photo resources of a document.
     * @var string
     */
    public const string PHOTOS = 'photos' ;

    /**
     * Projection optimized for search-result rendering.
     * @var string
     */
    public const string SEARCH = 'search' ;

    /**
     * Projection focused on the video resources of a document.
     * @var string
     */
    public const string VIDEOS = 'videos' ;
}
