<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Netresearch\ShippingCore\Model\ShippingSettings\Processor\Packaging;

use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CommentInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\Processor\Packaging\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\Config\ParcelProcessingConfig;
use Netresearch\ShippingCore\Model\ItemAttribute\ShipmentItemAttributeReader;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

class PackageInputDataProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ShippingConfigInterface
     */
    private $shippingConfig;

    /**
     * @var ParcelProcessingConfig
     */
    private $parcelConfig;

    /**
     * @var ShipmentItemAttributeReader
     */
    private $itemAttributeReader;

    /**
     * @var CommentInterfaceFactory
     */
    private $commentFactory;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    public function __construct(
        ShippingConfigInterface $shippingConfig,
        ParcelProcessingConfig $parcelConfig,
        ShipmentItemAttributeReader $itemAttributeReader,
        CommentInterfaceFactory $commentFactory,
        OptionInterfaceFactory $optionFactory
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->parcelConfig = $parcelConfig;
        $this->itemAttributeReader = $itemAttributeReader;
        $this->commentFactory = $commentFactory;
        $this->optionFactory = $optionFactory;
    }

    /**
     * Set options and values to inputs on package level.
     *
     * @param ShippingOptionInterface $shippingOption
     * @param ShipmentInterface $shipment
     */
    private function processInputs(ShippingOptionInterface $shippingOption, ShipmentInterface $shipment)
    {
        $defaultPackage = $this->parcelConfig->getDefaultPackage($shipment->getStoreId());

        foreach ($shippingOption->getInputs() as $input) {
            switch ($input->getCode()) {
                // shipping product
                case Codes::PACKAGING_INPUT_PRODUCT_CODE:
                    $option = $this->optionFactory->create();
                    $value = substr(strrchr((string) $shipment->getOrder()->getShippingMethod(), '_'), 1);
                    $option->setValue($value);
                    $option->setLabel(
                        $shipment->getOrder()->getShippingDescription()
                    );
                    $input->setOptions([$option]);
                    $input->setDefaultValue($value);
                    break;

                case Codes::PACKAGING_INPUT_PACKAGING_WEIGHT:
                    $comment = $this->commentFactory->create();
                    $comment->setContent($this->shippingConfig->getWeightUnit($shipment->getStoreId()));
                    $input->setComment($comment);
                    $input->setDefaultValue($defaultPackage ? (string) $defaultPackage->getWeight() : '');
                    break;

                // weight
                case Codes::PACKAGING_INPUT_WEIGHT:
                    $itemTotalWeight = $this->itemAttributeReader->getTotalWeight($shipment);
                    $packagingWeight = $defaultPackage ? $defaultPackage->getWeight() : 0;
                    $totalWeight = $itemTotalWeight + $packagingWeight;
                    $comment = $this->commentFactory->create();
                    $comment->setContent($this->shippingConfig->getWeightUnit($shipment->getStoreId()));
                    $input->setComment($comment);
                    $input->setDefaultValue((string) $totalWeight);
                    break;

                case Codes::PACKAGING_INPUT_WEIGHT_UNIT:
                    $weightUnit = $this->shippingConfig->getWeightUnit($shipment->getStoreId()) === 'kg'
                        ? \Zend_Measure_Weight::KILOGRAM
                        : \Zend_Measure_Weight::POUND;
                    $input->setDefaultValue($weightUnit);
                    break;

                // dimensions
                case Codes::PACKAGING_INPUT_WIDTH:
                    $comment = $this->commentFactory->create();
                    $comment->setContent($this->shippingConfig->getDimensionUnit($shipment->getStoreId()));
                    $input->setComment($comment);
                    $input->setDefaultValue($defaultPackage ? (string) $defaultPackage->getWidth() : '');
                    break;

                case Codes::PACKAGING_INPUT_HEIGHT:
                    $comment = $this->commentFactory->create();
                    $comment->setContent($this->shippingConfig->getDimensionUnit($shipment->getStoreId()));
                    $input->setComment($comment);
                    $input->setDefaultValue($defaultPackage ? (string) $defaultPackage->getHeight() : '');
                    break;

                case Codes::PACKAGING_INPUT_LENGTH:
                    $comment = $this->commentFactory->create();
                    $comment->setContent($this->shippingConfig->getDimensionUnit($shipment->getStoreId()));
                    $input->setComment($comment);
                    $input->setDefaultValue($defaultPackage ? (string) $defaultPackage->getLength() : '');
                    break;

                case Codes::PACKAGING_INPUT_SIZE_UNIT:
                    $dimensionsUnit = $this->shippingConfig->getDimensionUnit($shipment->getStoreId()) === 'cm'
                        ? \Zend_Measure_Length::CENTIMETER
                        : \Zend_Measure_Length::INCH;
                    $input->setDefaultValue($dimensionsUnit);
                    break;

                // customs
                case Codes::PACKAGING_INPUT_CUSTOMS_VALUE:
                    $price = $this->itemAttributeReader->getTotalPrice($shipment);
                    $currency = $shipment->getStore()->getBaseCurrency();
                    $currencySymbol = $currency->getCurrencySymbol() ?: $currency->getCode();
                    $comment = $this->commentFactory->create();
                    $comment->setContent($currencySymbol);
                    $input->setComment($comment);
                    $input->setDefaultValue((string) $price);
                    break;

                case Codes::PACKAGING_INPUT_EXPORT_DESCRIPTION:
                    $exportDescriptions = $this->itemAttributeReader->getPackageExportDescriptions($shipment);
                    $exportDescription = implode(', ', $exportDescriptions);
                    $input->setDefaultValue(substr($exportDescription, 0, 80));
                    break;

                case Codes::PACKAGING_INPUT_DG_CATEGORY:
                    $dgCategories = $this->itemAttributeReader->getPackageDgCategories($shipment);
                    $input->setDefaultValue(implode(', ', $dgCategories));
                    break;

                case Codes::PACKAGING_INPUT_TERMS_OF_TRADE:
                    // fixme(nr): move to carrier
//                    $input->setOptions(
//                        array_map(
//                            function ($optionArray) {
//                                $option = $this->optionFactory->create();
//                                $option->setValue($optionArray['value']);
//                                $option->setLabel((string)$optionArray['label']);
//                                return $option;
//                            },
//                            $this->termsOfTradeSource->toOptionArray()
//                        )
//                    );
                    break;

                case Codes::PACKAGING_INPUT_CONTENT_TYPE:
                    // fixme(nr): move to carrier
//                    $input->setOptions(
//                        array_map(
//                            function ($optionArray) {
//                                $option = $this->optionFactory->create();
//                                $option->setValue($optionArray['value']);
//                                $option->setLabel((string)$optionArray['label']);
//                                return $option;
//                            },
//                            $this->contentTypeSource->toOptionArray()
//                        )
//                    );
                    break;
            }
        }
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param ShipmentInterface $shipment
     *
     * @return ShippingOptionInterface[]
     */
    public function process(array $optionsData, ShipmentInterface $shipment): array
    {
        foreach ($optionsData as $optionGroup) {
            $this->processInputs($optionGroup, $shipment);
        }

        return $optionsData;
    }
}
