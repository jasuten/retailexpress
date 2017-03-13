<?php

namespace RetailExpress\SkyLink\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\Intl\NumberFormatterFactory;
use Magento\Framework\Locale\ResolverInterface;

class CapturedLogsTokeep implements ArrayInterface
{
    use NumberFormatter;

    public function __construct(
        NumberFormatterFactory $numberFormatterFactory,
        ResolverInterface $localeResolver
    ) {
        $this->numberFormatterFactory = $numberFormatterFactory;
        $this->localeResolver = $localeResolver;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        $options = [];

        for ($count = 10000; $count <= 100000; $count += 10000) {
            $options[] = [
                'value' => $count,
                'label' => $this->getNumberFormatter()->format($count),
            ];
        }

        return $options;
    }
}
