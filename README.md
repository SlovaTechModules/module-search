# Slova_Search

Advanced search autocomplete with product thumbnails for Magento 2, optimized for Hyva themes and OpenSearch.

## Features

- **Autocomplete dropdown** — real-time suggestions as the customer types
- **Product thumbnails** — optional image preview in each suggestion row
- **Price display** — show product price directly in autocomplete results
- **SKU display** — optionally surface SKU alongside product name
- **OpenSearch edge n-gram** — configurable min/max n-gram lengths for fast prefix matching
- **Admin configuration** — all options managed under Stores → Configuration → Slova → Search
- **Per-store-view control** — enable/disable and tune per website or store view

## Requirements

- Magento 2.4.x
- PHP 8.1+
- OpenSearch (configured as the Magento catalog search engine)
- Hyva theme

## Installation

```bash
composer require slova/module-search
bin/magento module:enable Slova_Search
bin/magento setup:upgrade
bin/magento cache:flush
```

## Configuration

Navigate to **Stores → Configuration → Slova → Search**.

| Section | Field | Description |
|---|---|---|
| General | Enabled | Enable or disable the module |
| Autocomplete | Minimum Characters | Number of characters before suggestions appear |
| Autocomplete | Maximum Results | Maximum number of suggestions shown |
| Autocomplete | Show Thumbnail | Display product image in results |
| Autocomplete | Show Price | Display product price in results |
| Autocomplete | Show SKU | Display product SKU in results |
| OpenSearch | Edge N-Gram Minimum | Minimum token length for n-gram analyzer |
| OpenSearch | Edge N-Gram Maximum | Maximum token length for n-gram analyzer |

After changing OpenSearch n-gram settings, reindex the catalog search index:

```bash
bin/magento indexer:reindex catalogsearch_fulltext
```

## License

Proprietary. All rights reserved. Unauthorized copying, distribution, or use of this software is strictly prohibited.
