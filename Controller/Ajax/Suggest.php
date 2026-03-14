<?php

declare(strict_types=1);

namespace Slova\Search\Controller\Ajax;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\StoreManagerInterface;
use Slova\Search\Model\Config;

class Suggest implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface      $request,
        private readonly JsonFactory           $jsonFactory,
        private readonly Config                $config,
        private readonly CollectionFactory     $collectionFactory,
        private readonly Visibility            $visibility,
        private readonly ImageHelper           $imageHelper,
        private readonly PriceHelper           $priceHelper,
        private readonly StoreManagerInterface $storeManager,
    ) {}

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->config->isEnabled()) {
            return $result->setData([]);
        }

        $query = trim((string) $this->request->getParam('q', ''));

        if (mb_strlen($query) < $this->config->getMinChars()) {
            return $result->setData([]);
        }

        return $result->setData($this->fetchProducts($query));
    }

    private function fetchProducts(string $query): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        $collection = $this->collectionFactory->create();
        $collection->addStoreFilter($storeId)
            ->addAttributeToSelect(['name', 'price', 'thumbnail', 'sku', 'url_key', 'author_names'])
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', ['in' => $this->visibility->getVisibleInSearchIds()])
            ->addAttributeToFilter([
                ['attribute' => 'name', 'like' => '%' . addslashes($query) . '%'],
                ['attribute' => 'author_names', 'like' => '%' . addslashes($query) . '%'],
            ])
            ->addFinalPrice()
            ->setPageSize($this->config->getMaxResults())
            ->setCurPage(1);

        $items = [];
        foreach ($collection as $product) {
            $item = [
                'id'   => (int) $product->getId(),
                'name' => $product->getName(),
                'url'  => $product->getProductUrl(),
            ];

            if ($this->config->showSku()) {
                $item['sku'] = $product->getSku();
            }

            if ($this->config->showPrice()) {
                $item['price'] = $this->priceHelper->currency(
                    (float) $product->getFinalPrice(),
                    false,
                    false
                );
            }

            if ($this->config->showThumbnail()) {
                $item['thumbnail'] = $this->imageHelper
                    ->init($product, 'product_thumbnail_image')
                    ->getUrl();
            }

            $items[] = $item;
        }

        return $items;
    }
}
