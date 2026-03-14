<?php

declare(strict_types=1);

namespace Slova\Search\Block;

use Magento\Framework\View\Element\Template;
use Slova\Search\Model\Config;

class Autocomplete extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function getMinChars(): int
    {
        return $this->config->getMinChars();
    }

    public function getSuggestUrl(): string
    {
        return $this->getUrl('slova_search/ajax/suggest');
    }
}
