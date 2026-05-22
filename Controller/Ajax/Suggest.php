<?php
declare(strict_types=1);

namespace Slova\Discovery\Controller\Ajax;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Slova\Discovery\Model\Config;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

class Suggest implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface           $request,
        private readonly JsonFactory               $jsonFactory,
        private readonly Config                    $config,
        private readonly CollectionFactory         $collectionFactory,
        private readonly QueryFactory              $queryFactory,
        private readonly ImageHelper               $imageHelper,
        private readonly PriceHelper               $priceHelper,
        private readonly StoreManagerInterface     $storeManager,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ResourceConnection        $resourceConnection,
    ) {
    }

    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        if (!$this->config->isEnabled()) {
            return $result->setData([]);
        }

        $query = trim((string) $this->request->getParam('q', ''));

        if (mb_strlen($query) < $this->config->getMinChars()) {
            return $result->setData([]);
        }

        return $result->setData([
            'categories' => $this->fetchCategories($query),
            'publishers'  => $this->fetchPublishers($query),
            'authors'     => $this->fetchAuthors($query),
            'products'    => $this->fetchProducts($query),
        ]);
    }

    private function fetchCategories(string $query): array
    {
        $storeId = (int) ($this->storeManager->getStore()?->getId() ?? 0);

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('level', ['gteq' => 2])
            ->addAttributeToFilter('name', ['like' => '%' . $query . '%'])
            ->addUrlRewriteToResult()
            ->addIsActiveFilter()
            ->setStoreId($storeId)
            ->setPageSize(4)
            ->setCurPage(1);

        $items = [];
        foreach ($collection as $category) {
            $items[] = [
                'name' => (string) $category->getName(),
                'url'  => (string) $category->getUrl(),
            ];
        }

        return $items;
    }

    private function fetchPublishers(string $query): array
    {
        $connection  = $this->resourceConnection->getConnection();
        $eavTable    = $this->resourceConnection->getTableName('eav_attribute');
        $optionTable = $this->resourceConnection->getTableName('eav_attribute_option');
        $valueTable  = $this->resourceConnection->getTableName('eav_attribute_option_value');
        $productInt  = $this->resourceConnection->getTableName('catalog_product_entity_int');

        $attributeId = (int) $connection->fetchOne(
            $connection->select()
                ->from($eavTable, ['attribute_id'])
                ->where('attribute_code = ?', 'brand')
                ->where('entity_type_id = ?', 4)
        );

        if (!$attributeId) {
            return [];
        }

        $select = $connection->select()
            ->from(['ov' => $valueTable], ['ov.value'])
            ->join(['o' => $optionTable], 'o.option_id = ov.option_id', [])
            ->join(
                ['pi' => $productInt],
                'pi.value = o.option_id AND pi.attribute_id = o.attribute_id',
                []
            )
            ->where('o.attribute_id = ?', $attributeId)
            ->where('ov.store_id = 0')
            ->where('LOWER(ov.value) LIKE ?', '%' . mb_strtolower($query) . '%')
            ->group('ov.value')
            ->order('COUNT(pi.entity_id) DESC')
            ->limit(5);

        return $connection->fetchCol($select);
    }

    private function fetchAuthors(string $query): array
    {
        $connection       = $this->resourceConnection->getConnection();
        $authorsTable     = $this->resourceConnection->getTableName('carusel_authors');
        $pivotTable       = $this->resourceConnection->getTableName('carusel_author_product');
        $bestsellersTable = $this->resourceConnection->getTableName('sales_bestsellers_aggregated_monthly');
        $searchQueryTable = $this->resourceConnection->getTableName('search_query');
        $storeId          = (int) ($this->storeManager->getStore()?->getId() ?? 1);
        $like             = $connection->quote('%' . mb_strtolower($query) . '%');

        // Ranking: search popularity (cât de des s-a căutat numele autorului)
        //          → vânzări bestseller (qty_ordered pe store curent)
        //          → număr titluri în catalog
        $sql = "
            SELECT
                a.author_id, a.first_name, a.last_name, a.image, a.url_key,
                COUNT(ap.product_id)                    AS book_count,
                COALESCE(SUM(sb.qty_ordered), 0)        AS total_sold,
                COALESCE((
                    SELECT SUM(sq.popularity)
                    FROM {$searchQueryTable} sq
                    WHERE sq.store_id IN (0, {$storeId})
                      AND (LOWER(sq.query_text) LIKE LOWER(CONCAT('%', a.first_name, '%'))
                           OR LOWER(sq.query_text) LIKE LOWER(CONCAT('%', a.last_name, '%')))
                ), 0) AS sq_score
            FROM {$authorsTable} a
            LEFT JOIN {$pivotTable} ap
                   ON ap.author_id = a.author_id
            LEFT JOIN {$bestsellersTable} sb
                   ON sb.product_id = ap.product_id AND sb.store_id = {$storeId}
            WHERE LOWER(CONCAT(a.first_name, ' ', a.last_name)) LIKE {$like}
            GROUP BY a.author_id
            ORDER BY sq_score DESC, total_sold DESC, book_count DESC,
                     a.is_featured DESC, a.last_name ASC
            LIMIT 10
        ";

        $rows = $connection->fetchAll($sql);

        if (empty($rows)) {
            return [];
        }

        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $baseUrl  = $this->storeManager->getStore()->getBaseUrl();

        $format = function (array $row) use ($mediaUrl, $baseUrl): array {
            return [
                'name'  => trim($row['first_name'] . ' ' . $row['last_name']),
                'url'   => $row['url_key']
                    ? $baseUrl . 'autori/' . $row['url_key']
                    : $baseUrl . 'autori/profile/view/id/' . (int) $row['author_id'],
                'image' => $row['image'] ? $mediaUrl . 'carusel/authors/' . $row['image'] : '',
            ];
        };

        $total    = count($rows);
        $primary  = $format($rows[0]);
        $secondary = array_map($format, array_slice($rows, 1, 2));

        return [
            'primary'      => $primary,
            'secondary'    => $secondary,
            'others_count' => max(0, $total - 3),
            'search_url'   => '/catalogsearch/result/?q=' . urlencode($query),
        ];
    }

    private function fetchProducts(string $query): array
    {
        $storeId = (int) ($this->storeManager->getStore()?->getId() ?? 0);
        $max     = $this->config->getMaxResults();

        $multiMatch = $this->queryFactory->create(
            QueryInterface::TYPE_MULTIMATCH,
            [
                'queryText' => $query,
                'fields'    => [
                    'name.standard_edge_ngram'             => 10,
                    'author_names.standard_edge_ngram'     => 5,
                    'collection_names.standard_edge_ngram' => 2,
                ],
                'minimumShouldMatch' => '1',
                'matchType' => 'best_fields',
            ]
        );

        $collection = $this->collectionFactory->create();
        $collection->addStoreFilter($storeId)
            ->addAttributeToSelect(['name', 'price', 'thumbnail', 'url_key', 'author_names'])
            ->addFinalPrice()
            ->addQueryFilter($multiMatch)
            ->setPageSize($max)
            ->setCurPage(1);

        $items = [];
        foreach ($collection as $product) {
            $item = [
                'id'      => (int) $product->getId(),
                'name'    => (string) ($product->getName() ?? ''),
                'url'     => (string) ($product->getProductUrl() ?? ''),
                'authors' => $product->getData('author_names') ?? '',
            ];

            if ($this->config->showSku()) {
                $item['sku'] = $product->getSku();
            }

            if ($this->config->showPrice()) {
                if ($product->getTypeId() === 'bundle') {
                    $bInst = $product->getTypeInstance();
                    $bInst->setStoreFilter($product->getStoreId(), $product);
                    $bSels = $bInst->getSelectionsCollection($bInst->getOptionsIds($product), $product);
                    $finalPrice = 0.0; $regularPrice = 0.0;
                    foreach ($bSels as $bSel) {
                        $bQty = (float)($bSel->getSelectionQty() ?: 1);
                        $finalPrice   += (float)$bSel->getSelectionPriceValue() * $bQty;
                        $regularPrice += (float)$bSel->getPrice() * $bQty;
                    }
                } else {
                    $finalPrice   = (float) $product->getFinalPrice();
                    $regularPrice = (float) $product->getPrice();
                }
                $item['price'] = $this->priceHelper->currency($finalPrice, false, false);
                if ($regularPrice > $finalPrice) {
                    $item['regular_price'] = $this->priceHelper->currency($regularPrice, false, false);
                }
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
