<?php

declare(strict_types=1);
namespace MageSuite\FreeGift\Test\Integration\Plugin\Quote\Model\CouponManagement;

class RemoveCouponRelatedGiftTest extends \PHPUnit\Framework\TestCase
{
    protected const QUOTE_RESERVED_ID = 'test01';
    protected const FREE_GIFT_SKU = 'free-gift-product';

    protected ?\Magento\Framework\App\ObjectManager $objectManager = null;
    protected ?\Magento\Quote\Model\CouponManagement $couponManagement = null;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository = null;
    protected ?\Magento\Quote\Model\QuoteRepository $quoteRepository = null;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->couponManagement = $this->objectManager->get(\Magento\Quote\Model\CouponManagement::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->quoteRepository = $this->objectManager->get(\Magento\Quote\Model\QuoteRepository::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/free_gift_once_sales_rule_with_coupon.php
     */
    public function testFreeGiftItemIsRemovedFromCartAfterRemovingCouponAndItCanBeAddedAgain():void
    {
        $quote = $this->getQuote();
        $this->couponManagement->set($quote->getId(), 'coupon_code');

        $quote = $this->quoteRepository->get($quote->getId());
        self::assertCount(2, $quote->getItems());
        self::assertEquals(self::FREE_GIFT_SKU, $quote->getItems()[1]->getSku());

        $this->couponManagement->remove($quote->getId());
        $quote = $this->quoteRepository->get($quote->getId());
        self::assertCount(1, $quote->getItems());
        self::assertEquals('simple', $quote->getItems()[0]->getSku());

        $this->couponManagement->set($quote->getId(), 'coupon_code');
        $quote = $this->quoteRepository->get($quote->getId());
        self::assertCount(2, $quote->getItems());
        self::assertEquals(self::FREE_GIFT_SKU, $quote->getItems()[1]->getSku());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/free_gift_once_sales_rule_with_coupon.php
     */
    public function testFreeGiftCouponCodeCanBeRemovedIfProductBecomeDisabled():void
    {
        $quote = $this->getQuote();
        $this->couponManagement->set($quote->getId(), 'coupon_code');
        $quote = $this->quoteRepository->get($quote->getId());

        $secondProduct = $this->productRepository->get('simple2');
        $quote->addProduct($secondProduct, 1);
        $this->quoteRepository->save($quote);

        $quote = $this->quoteRepository->get($quote->getId());
        $quoteItems = $quote->getItems();

        self::assertCount(3, $quoteItems);

        $productItem = $quoteItems[0];
        $freeGiftItem = $quoteItems[1];

        self::assertEquals(self::FREE_GIFT_SKU, $freeGiftItem->getSku());

        $firstProduct = $this->productRepository->get($productItem->getSku());
        $this->disableProduct($firstProduct);

        // refresh cached quote
        $this->quoteRepository->save($quote);

        $this->couponManagement->remove($quote->getId());

        $quote = $this->quoteRepository->get($quote->getId());
        self::assertCount(1, $quote->getItems());
    }

    protected function getQuote():\Magento\Quote\Api\Data\CartInterface
    {
        /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory */
        $searchCriteriaBuilderFactory = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilderFactory::class);
        $searchCriteriaBuilder = $searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', self::QUOTE_RESERVED_ID)->create();
        $quotes = $this->quoteRepository->getList($searchCriteria)->getItems();

        return array_pop($quotes);
    }

    protected function disableProduct(\Magento\Catalog\Api\Data\ProductInterface $product):void
    {
        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
        $this->productRepository->save($product);
    }
}
