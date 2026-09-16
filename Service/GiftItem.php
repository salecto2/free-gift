<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Service;

class GiftItem
{
    protected const OPTION_RULE_ID = 'rule_id';
    protected const OPTION_ORIGINAL_PRODUCT_SKU = \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU;
    protected const OPTION_GIFT_ADDED_ONCE = \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::GIFT_ADDED_ONCE;

    public function isRelatedToProductAndRule(\Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem, string $productSku, int $ruleId): bool
    {
        if ($this->getRuleId($quoteItem) !== $ruleId) {
            return false;
        }

        if ($this->isAddedOnce($quoteItem)) {
            return true;
        }

        return $this->getOptionValue($quoteItem, self::OPTION_ORIGINAL_PRODUCT_SKU) == $productSku;
    }

    public function isAddedOnce(\Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem): bool
    {
        return $this->getOptionValue($quoteItem, self::OPTION_GIFT_ADDED_ONCE) == true;
    }

    public function getRuleId(\Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem): ?int
    {
        $ruleId = (int)$this->getOptionValue($quoteItem, self::OPTION_RULE_ID);
        if ($ruleId <= 0) {
            return null;
        }

        return $ruleId;
    }

    public function removeRuleIdFromAllQuoteItems(\Magento\Quote\Api\Data\CartInterface $quote, int $ruleId): void
    {
        foreach ($quote->getAllItems() as $item) {
            $appliedRuleIds = (string)$item->getAppliedRuleIds();
            if ($appliedRuleIds === '') {
                continue;
            }

            $isChanged = false;
            $filteredRuleIds = [];
            foreach (explode(',', $appliedRuleIds) as $appliedRule) {
                $appliedRuleId = (int)trim($appliedRule);
                if ($appliedRuleId <= 0) {
                    continue;
                }

                if ($appliedRuleId === $ruleId) {
                    $isChanged = true;
                    continue;
                }

                $filteredRuleIds[] = $appliedRuleId;
            }

            if ($isChanged) {
                $item->setAppliedRuleIds(!empty($filteredRuleIds) ? implode(',', array_unique($filteredRuleIds)) : null);
            }
        }
    }

    protected function getOptionValue(\Magento\Quote\Model\Quote\Item\AbstractItem $quoteItem, string $optionIdentifier): mixed
    {
        $option = $quoteItem->getOptionByCode($optionIdentifier);
        if (!$option instanceof \Magento\Quote\Model\Quote\Item\Option) {
            return null;
        }

        return $option->getValue();
    }
}
