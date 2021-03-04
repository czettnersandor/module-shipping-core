<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Netresearch\ShippingCore\Model\ResourceModel\Provider;

use Magento\Sales\Model\ResourceModel\Provider\NotSyncedDataProviderInterface;
use Netresearch\ShippingCore\Model\ResourceModel\LabelStatus;
use Psr\Log\LoggerInterface;

/**
 * Provide order IDs to be updated.
 *
 * In asynchronous mode, grid entries are added or updated in a batch.
 * To do so, the update process needs a list of entity IDs which require an update.
 */
class UpdatedStatusIdListProvider implements NotSyncedDataProviderInterface
{
    /**
     * @var LabelStatus
     */
    private $resourceModel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LabelStatus $labelStatus, LoggerInterface $logger)
    {
        $this->resourceModel = $labelStatus;
        $this->logger = $logger;
    }

    /**
     * Obtain a list of order IDs to update.
     *
     * The ID list is generated by comparing values in the entity table and the grid table.
     *
     * @param string $mainTableName Source table name (sales_order by default).
     * @param string $gridTableName Grid table name (sales_order_grid by default).
     * @return string[]
     */
    public function getIds($mainTableName, $gridTableName): array
    {
        try {
            $connection = $this->resourceModel->getConnection();
            $mainTableName = $this->resourceModel->getMainTable();

            $mainTableName = $connection->getTableName($mainTableName);
            $gridTableName = $connection->getTableName($gridTableName);

            $select = $connection->select()
                ->from(['label_status' => $mainTableName], ['order_id'])
                ->join(['order_grid' => $gridTableName], 'label_status.order_id = order_grid.entity_id', [])
                ->where('order_grid.nrshipping_label_status != label_status.status_code')
                ->orWhere('order_grid.nrshipping_label_status IS NULL');

            return $connection->fetchCol($select);
        } catch (\Exception $exception) {
            // variety of unhandled exceptions may be raised by db libraries
            $this->logger->error('Unable to determine order IDs for grid update.', ['exception' => $exception]);
            return [];
        }
    }
}
