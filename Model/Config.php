<?php

declare(strict_types=1);

namespace Slova\Discovery\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED          = 'slova_discovery/general/enabled';
    private const XML_PATH_MIN_CHARS        = 'slova_discovery/autocomplete/min_chars';
    private const XML_PATH_MAX_RESULTS      = 'slova_discovery/autocomplete/max_results';
    private const XML_PATH_SHOW_THUMBNAIL   = 'slova_discovery/autocomplete/show_thumbnail';
    private const XML_PATH_SHOW_PRICE       = 'slova_discovery/autocomplete/show_price';
    private const XML_PATH_SHOW_SKU         = 'slova_discovery/autocomplete/show_sku';
    private const XML_PATH_EDGE_NGRAM_MIN   = 'slova_discovery/opensearch/edge_ngram_min';
    private const XML_PATH_EDGE_NGRAM_MAX   = 'slova_discovery/opensearch/edge_ngram_max';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(mixed $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getMinChars(mixed $scopeCode = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_MIN_CHARS,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getMaxResults(mixed $scopeCode = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_MAX_RESULTS,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function showThumbnail(mixed $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_THUMBNAIL,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function showPrice(mixed $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_PRICE,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function showSku(mixed $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_SKU,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    // Edge ngram settings are configured at global scope only (showInWebsite="0" showInStore="0"
    // in system.xml), so $scopeCode is intentionally omitted here.
    public function getEdgeNgramMin(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_EDGE_NGRAM_MIN,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    public function getEdgeNgramMax(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_EDGE_NGRAM_MAX,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }
}
