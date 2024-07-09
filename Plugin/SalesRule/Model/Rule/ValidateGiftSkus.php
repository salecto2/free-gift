<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Plugin\SalesRule\Model\Rule;

class ValidateGiftSkus
{
    protected \MageSuite\FreeGift\SalesRule\Validator\GiftSku $giftSkuValidator;

    public function __construct(
        \MageSuite\FreeGift\SalesRule\Validator\GiftSku $giftSkuValidator
    ) {
        $this->giftSkuValidator = $giftSkuValidator;
    }

    public function aroundValidateData(
        \Magento\SalesRule\Model\Rule $subject,
        callable $proceed,
        \Magento\Framework\DataObject $dataObject
    ) {
        $result = $proceed($dataObject);
        $isSkuValid = $this->giftSkuValidator->isValid($dataObject);

        if ($isSkuValid) {
            return $result;
        }

        if ($result === true) {
            $result = [];
        }

        $result[] = __('Provided gift SKU does not exist in the catalog.');
        return $result;
    }
}
