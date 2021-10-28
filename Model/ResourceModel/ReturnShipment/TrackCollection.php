<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Netresearch\ShippingCore\Model\ResourceModel\ReturnShipment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Netresearch\ShippingCore\Model\ReturnShipment\Track as ReturnShipmentTrack;

/**
 * @method ReturnShipmentTrack[] getItems()
 */
class TrackCollection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(ReturnShipmentTrack::class, Track::class);
    }
}
