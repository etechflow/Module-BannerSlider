<?php
declare(strict_types=1);

namespace ETechFlow\BannerSlider\Model\Storefront;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves a product-type banner's product into render-ready data.
 *
 * Kept out of the slider block so the catalog dependencies (repository, image
 * helper, pricing) stay isolated and the result is a plain array the template
 * can render without touching the product model directly.
 */
class ProductBannerResolver
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ImageHelper $imageHelper,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly PostHelper $postHelper,
        private readonly UrlInterface $urlBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Build the data needed to render a product banner, or null when the
     * product is missing/disabled so the slide can be skipped gracefully.
     *
     * @param int $productId
     * @param int $storeId
     * @return array{id:int,name:string,url:string,image_url:string,final_price:string,regular_price:?string,in_stock:bool,add_to_cart:string}|null
     */
    public function resolve(int $productId, int $storeId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        try {
            $product = $this->productRepository->getById($productId, false, $storeId);
        } catch (NoSuchEntityException $e) {
            return null;
        } catch (\Throwable $e) {
            $this->logger->warning(
                'ETechFlow BannerSlider: failed to load product banner #' . $productId . ': ' . $e->getMessage()
            );
            return null;
        }

        if ((int)$product->getStatus() !== ProductStatus::STATUS_ENABLED) {
            return null;
        }

        $finalAmount = (float)$product->getPriceInfo()
            ->getPrice(FinalPrice::PRICE_CODE)->getAmount()->getValue();
        $regularAmount = (float)$product->getPriceInfo()
            ->getPrice(RegularPrice::PRICE_CODE)->getAmount()->getValue();

        return [
            'id' => (int)$product->getId(),
            'name' => (string)$product->getName(),
            'url' => $product->getProductUrl(),
            'image_url' => $this->imageHelper->init($product, 'product_base_image')->getUrl(),
            'final_price' => $this->priceCurrency->convertAndFormat($finalAmount, false),
            'regular_price' => $regularAmount > $finalAmount
                ? $this->priceCurrency->convertAndFormat($regularAmount, false)
                : null,
            'in_stock' => $product->isSalable(),
            'add_to_cart' => $this->buildAddToCartPostData($product),
        ];
    }

    /**
     * FPC-safe add-to-cart payload. The form key is injected client-side by
     * Magento's mage/dataPost handler, so the cached markup carries no session
     * data.
     *
     * @param ProductInterface $product
     * @return string
     */
    private function buildAddToCartPostData(ProductInterface $product): string
    {
        $url = $this->urlBuilder->getUrl('checkout/cart/add', ['product' => $product->getId()]);
        return $this->postHelper->getPostData($url, [
            'product' => (int)$product->getId(),
            ActionInterface::PARAM_NAME_URL_ENCODED => '',
        ]);
    }
}
