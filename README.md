# Slova Search — Lightweight M2M Discovery for Magento 2 & Hyvä

![Magento 2.4.7](https://img.shields.io/badge/Magento-2.4.7-orange)
![Hyvä Native](https://img.shields.io/badge/Hyvä-Native%20Implementation-0aa)
![OpenSearch](https://img.shields.io/badge/OpenSearch-Integrated-blue)
![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)

<p align="center">
  <img src="https://github.com/user-attachments/assets/fb09cfa1-4b67-44b5-963e-3538e157096d" width="400" title="Slova Search Autocomplete">
</p>


Advanced, blazing-fast search autocomplete for Magento 2. Optimized exclusively for **Hyvä Themes** and **OpenSearch**, built with zero bloat and pure Alpine.js & Tailwind.

## ✨ Features

* ⚡ **Blazing Fast Autocomplete** — Real-time, zero-lag suggestions as the customer types.
* 🔗 **Beyond Products (Custom Entities)** — The first search module designed to easily index and display custom entities (like **Authors**, **Collections**, or **Brands**) right alongside your products.
* 🖼️ **Rich Visual Results** — Out-of-the-box support for product thumbnails, localized formatted prices (with currency), and SKUs.
* 🧠 **Smart OpenSearch Edge N-Gram** — Configurable min/max n-gram lengths for highly accurate prefix matching.
* 🛠️ **Developer Friendly** — No Knockout.js. Just clean code as Hyvä intended.
* ⚙️ **Per-Store Control** — Fully tuneable per website or store view directly from the admin panel.

## ⚙️ Requirements

* Magento 2.4.x
* PHP 8.1+
* OpenSearch (configured as the Magento catalog search engine)
* Hyvä Theme

## 🚀 Installation

```bash
composer require slova/module-search
bin/magento module:enable Slova_Search
bin/magento setup:upgrade
bin/magento cache:flush
```

🛠 Configuration

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

Note: After changing OpenSearch n-gram settings, remember to reindex:

```bash
bin/magento indexer:reindex catalogsearch_fulltext
```

🌐 The Slova Ecosystem (Coming Soon)
This search module is just the beginning. We are currently finalizing the infrastructure for modern Magento 2 stores in Romania and beyond:

Slova M2M Engine — The architectural gold standard for Many-to-Many entity relations in Magento 2.

Slova Authors Suite — A complete M2M solution for publishers and bookstores.

Slova RO Checkout — The highest-converting One-Page Checkout optimized for the Romanian market (DPD, Sameday, SmartBill out-of-the-box).

Follow the SlovaTechModules organization for official launch updates!

📄 License

MIT License - Free to use, modify, and distribute.
