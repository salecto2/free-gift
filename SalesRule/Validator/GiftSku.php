<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\SalesRule\Validator;

class GiftSku
{
    protected \Magento\Framework\App\ResourceConnection $resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function isValid(\Magento\Framework\DataObject $rule): bool
    {
        $skus = $rule->getGiftSkus();

        if (empty($skus)) {
            return true;
        }

        $skus = array_unique(explode(',', $skus));

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity'), 'COUNT(*)')
            ->where('sku IN (?)', $skus);
        $count = $connection->fetchOne($select);

        return $count == count($skus);
    }
}
