<?php

namespace oihana\controllers\traits;

use oihana\controllers\traits\prepare\PrepareActive;
use oihana\controllers\traits\prepare\PrepareBench;
use oihana\controllers\traits\prepare\PrepareDate;
use oihana\controllers\traits\prepare\PrepareFacets;
use oihana\controllers\traits\prepare\PrepareFilter;
use oihana\controllers\traits\prepare\PrepareGroupBy;
use oihana\controllers\traits\prepare\PrepareHasTotal;
use oihana\controllers\traits\prepare\PrepareIDs;
use oihana\controllers\traits\prepare\PrepareInt;
use oihana\controllers\traits\prepare\PrepareInterval;
use oihana\controllers\traits\prepare\PrepareLang;
use oihana\controllers\traits\prepare\PrepareLimit;
use oihana\controllers\traits\prepare\PrepareMargin;
use oihana\controllers\traits\prepare\PrepareMock;
use oihana\controllers\traits\prepare\PrepareOrder;
use oihana\controllers\traits\prepare\PrepareQuantity;
use oihana\controllers\traits\prepare\PrepareSearch;
use oihana\controllers\traits\prepare\PrepareSkin;
use oihana\controllers\traits\prepare\PrepareSort;
use oihana\controllers\traits\prepare\PrepareTimezone;

trait PrepareParamTrait
{
    use PrepareActive ,
        PrepareBench ,
        PrepareDate ,
        PrepareFilter ,
        PrepareFacets ,
        PrepareGroupBy ,
        PrepareHasTotal ,
        PrepareIDs ,
        PrepareInterval ,
        PrepareInt ,
        PrepareLang ,
        PrepareLimit ,
        PrepareMargin ,
        PrepareMock ,
        PrepareOrder ,
        PrepareQuantity ,
        PrepareSearch ,
        PrepareSkin ,
        PrepareSort ,
        PrepareTimezone ;
}