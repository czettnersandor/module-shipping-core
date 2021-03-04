<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Netresearch\ShippingCore\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Netresearch\ShippingCore\Setup\Patch\Data\Migration\RecipientStreet;

class MigrateRecipientStreetPatch implements DataPatchInterface
{
    /**
     * @var RecipientStreet
     */
    private $recipientStreet;

    public function __construct(RecipientStreet $recipientStreet)
    {
        $this->recipientStreet = $recipientStreet;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * Migrate split address values from the dhl/shipping-m2 extension.
     *
     * @return void
     * @throws \Exception
     */
    public function apply()
    {
        $this->recipientStreet->migrate();
    }
}
