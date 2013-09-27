<?php

/*
 * @copyright  Copyright (c) 2013 by  ESS-UA.
 */

class Ess_M2ePro_Model_Buy_Template_NewProduct_Source
{
    const ADDITIONAL_IMAGES_COUNT_MAX = 4;

    /* @var $listingProduct Ess_M2ePro_Model_Buy_Listing_Product */
    private $listingProduct = null;

    /* @var $category Ess_M2ePro_Model_Buy_Template_NewProduct */
    private $category = null;

    /* @var $coreTemplate Ess_M2ePro_Model_Buy_Template_NewProduct_Core */
    private $coreTemplate = null;

    /* @var $attributeTemplates Ess_M2ePro_Model_Buy_Template_NewProduct_Attribute[] */
    private $attributeTemplates = array();

    // ########################################

    public function __construct($args)
    {
        list($this->listingProduct,$this->category) = $args;

        $this->coreTemplate = $this->category->getCoreTemplate();
        $this->attributeTemplates = $this->category->getAttributesTemplate();
    }

    // ########################################

    public function getCoreData()
    {
        $msrp = $this->getPriceMsrp();

        return array(
            'seller_sku' => $this->getSellerSku(),
            'gtin' => $this->getGtin(),
            'isbn' => $this->getIsbn(),
            'asin' => $this->getAsin(),
            'mfg_name' => $this->getMfgName(),
            'mfg_part_number' => $this->getMfgPartNumber(),
            'product_set_id' => $this->getProductSetId(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'main_image' => $this->getMainImage(),
            'additional_images' => $this->getAdditionalImages(),
            'keywords' => $this->getKeywords(),
            'features' => $this->getFeatures(),
            'weight' => $this->getWeight(),
            'listing_price' => $msrp,
            'msrp' => $msrp,
            'category_id' => $this->getCategoryId(),
        );
    }

    public function getAttributesData()
    {
        $attributes = array();

        foreach ($this->attributeTemplates as $attribute) {

            $src = $attribute->getAttributeSource();
            $value = '';

            switch ($src['mode']) {
                case Ess_M2ePro_Model_Buy_Template_NewProduct_Attribute::ATTRIBUTE_MODE_CUSTOM_VALUE:
                    //$value = str_replace(',','^',$src['custom_value']);
                    $value = $src['custom_value'];
                    break;

                case Ess_M2ePro_Model_Buy_Template_NewProduct_Attribute::ATTRIBUTE_MODE_CUSTOM_ATTRIBUTE:
                    $value = $this->listingProduct
                            ->getMagentoProduct()
                            ->getAttributeValue($src['custom_attribute']);

                    $value = str_replace(',','^',$value);
                    break;

                case Ess_M2ePro_Model_Buy_Template_NewProduct_Attribute::ATTRIBUTE_MODE_RECOMMENDED_VALUE:
                    $value = $src['recommended_value'];
                    is_array($value) && $value = implode('^',$value);
                    break;

                default:
                    $value = '';
                    break;
            }

            $attributes = array_merge($attributes,array($src['name'] => $value));
        }

        return $attributes;
    }

    // ########################################

    public function getCategoryId()
    {
        return $this->category->getCategoryId();
    }

    public function getPriceMsrp()
    {
        //var_dump($this->listingProduct->getSellingFormatTemplate()->getChildObject()->getPriceSource());
        //var_dump($this->listingProduct->getPrice());
        return $this->listingProduct->getPrice();
    }

    public function getSellerSku()
    {
        $src = $this->coreTemplate->getSellerSkuSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_NewProduct_Core::SELLER_SKU_MODE_CUSTOM_VALUE) {
            $seller_sku = $src['custom_value'];
        } else {
            $seller_sku = $this->listingProduct
                    ->getMagentoProduct()
                    ->getAttributeValue($src['custom_attribute']);
        }

        return $seller_sku;
    }

    public function getGtin()
    {
        $gtin = NULL;
        $src = $this->coreTemplate->getGtinSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_NewProduct_Core::GTIN_MODE_CUSTOM_VALUE) {
            $gtin = $src['custom_value'];
        } else {
            $gtin = $this->listingProduct
                    ->getMagentoProduct()
                    ->getAttributeValue($src['custom_attribute']);
        }

        return $gtin;
    }

    public function getIsbn()
    {
        $src = $this->coreTemplate->getIsbnSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_NewProduct_Core::ISBN_MODE_CUSTOM_VALUE) {
            $isbn = $src['custom_value'];
        } elseif ($src['mode'] == Ess_M2ePro_Model_Buy_Template_NewProduct_Core::ISBN_MODE_CUSTOM_ATTRIBUTE) {
            $isbn = $this->listingProduct
                    ->getMagentoProduct()
                    ->getAttributeValue($src['custom_attribute']);
        } else {
            $isbn = NULL;
        }

        return $isbn;
    }

    public function getAsin()
    {
        $src = $this->coreTemplate->getAsinSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_NewProduct_Core::ASIN_MODE_CUSTOM_VALUE) {
            $asin = $src['custom_value'];
        } elseif ($src['mode'] == Ess_M2ePro_Model_Buy_Template_NewProduct_Core::ASIN_MODE_CUSTOM_ATTRIBUTE) {
            $asin = $this->listingProduct
                    ->getMagentoProduct()
                    ->getAttributeValue($src['custom_attribute']);
        } else {
            $asin = NULL;
        }

        return $asin;
    }

    public function getMfgName()
    {
        $src = $this->coreTemplate->getMfgSource();

        $mfg_name =NULL;
        if ($src['template'] != '') {
            $mfg_name = Mage::getSingleton('M2ePro/Template_Description_Parser')
                ->parseTemplate(
                $src['template'],
                $this->listingProduct->getMagentoProduct()->getProduct()
            );
        }

        return $mfg_name;
    }

    public function getMfgPartNumber()
    {
        $src = $this->coreTemplate->getMfgPartNumberSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_NewProduct_Core::MFG_PART_NUMBER_MODE_CUSTOM_VALUE) {
            $mfg_part_number = $src['custom_value'];
        } else {
            $mfg_part_number = $this->listingProduct
                    ->getMagentoProduct()
                    ->getAttributeValue($src['custom_attribute']);
        }

        return $mfg_part_number;
    }

    public function getProductSetId()
    {
        $src = $this->coreTemplate->getProductSetIdSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_NewProduct_Core::PRODUCT_SET_ID_MODE_CUSTOM_VALUE) {
            $product_set_id = $src['custom_value'];
        } else {
            $product_set_id = $this->listingProduct
                    ->getMagentoProduct()
                    ->getAttributeValue($src['custom_attribute']);
        }

        return $product_set_id;
    }

    public function getTitle()
    {
        $src = $this->coreTemplate->getTitleSource();

        switch ($src['mode']) {
            case Ess_M2ePro_Model_Buy_Template_NewProduct_Core::TITLE_MODE_PRODUCT_NAME:
                $title = $this->listingProduct->getMagentoProduct()->getName();
                break;

            case Ess_M2ePro_Model_Buy_Template_NewProduct_Core::TITLE_MODE_CUSTOM_TEMPLATE:
                $title = Mage::getSingleton('M2ePro/Template_Description_Parser')->parseTemplate(
                    $src['template'],
                    $this->listingProduct->getMagentoProduct()->getProduct()
                );
                break;

            default:
                $title = $this->listingProduct->getMagentoProduct()->getName();
                break;
        }

        return $title;
    }

    public function getDescription()
    {
        $src = $this->coreTemplate->getDescriptionSource();
        /* @var $templateProcessor Mage_Core_Model_Email_Template_Filter */
        $templateProcessor = Mage::getModel('Core/Email_Template_Filter');

        switch ($src['mode']) {
            case Ess_M2ePro_Model_Buy_Template_NewProduct_Core::DESCRIPTION_MODE_PRODUCT_FULL:
                $description = $this->listingProduct->getMagentoProduct()->getProduct()->getDescription();
                $description = $templateProcessor->filter($description);
                break;

            case Ess_M2ePro_Model_Buy_Template_NewProduct_Core::DESCRIPTION_MODE_PRODUCT_SHORT:
                $description = $this->listingProduct->getMagentoProduct()->getProduct()->getShortDescription();
                $description = $templateProcessor->filter($description);
                break;

            case Ess_M2ePro_Model_Buy_Template_NewProduct_Core::DESCRIPTION_MODE_CUSTOM_TEMPLATE:
                $description = Mage::getSingleton('M2ePro/Template_Description_Parser')->parseTemplate(
                    $src['template'],
                    $this->listingProduct->getMagentoProduct()->getProduct()
                );
                break;

            default:
                return;
                break;
        }

        $description = preg_replace('/(\t|<[a-z]+>|[\r\n])/i','',$description);
        return str_replace(array('<![CDATA[', ']]>'), '', $description);
    }

    public function getMainImage()
    {
        $imageLink = NULL;

        if ($this->coreTemplate->isMainImageBroductBase()) {
            $imageLink = $this->listingProduct->getMagentoProduct()->getImageLink('image');
        }

        if ($this->coreTemplate->isMainImageAttribute()) {
            $src = $this->coreTemplate->getMainImageSource();
            $imageLink = $this->listingProduct->getMagentoProduct()->getImageLink($src['attribute']);
        }

        return $imageLink;
    }

    public function getAdditionalImages()
    {
        $src = $this->coreTemplate->getAdditionalImageSource();
        $limitImages = self::ADDITIONAL_IMAGES_COUNT_MAX;
        $galleryImages = array();

        if ($this->coreTemplate->isAdditionalImageNone()) {
            return;
        }

        if ($this->coreTemplate->isAdditionalImageProduct()) {
            $limitImages = (int)$src['limit'];
            $galleryImages = $this->listingProduct
                    ->getMagentoProduct()
                    ->getGalleryImagesLinks((int)$src['limit']+1);
        }

        if ($this->coreTemplate->isAdditionalImageCustomAttribute()) {
            $limitImages = self::ADDITIONAL_IMAGES_COUNT_MAX;
            $galleryImagesTemp = $this->listingProduct
                    ->getMagentoProduct()
                    ->getAttributeValue($src['attribute']);
            $galleryImagesTemp = (array)explode(',', $galleryImagesTemp);

            foreach ($galleryImagesTemp as $tempImageLink) {
                $tempImageLink = trim($tempImageLink);
                if (!empty($tempImageLink)) {
                    $galleryImages[] = $tempImageLink;
                }
            }
        }

        $galleryImages = array_unique($galleryImages);
        if (count($galleryImages) <= 0) {
            return;
        }

        $galleryImages = array_slice($galleryImages,0,$limitImages);

        return implode('|',$galleryImages);
    }

    public function getFeatures()
    {
        $src = $this->coreTemplate->getFeaturesSource();

        if ($this->coreTemplate->isFeaturesNone()) {
            return;
        } else {
            foreach ($src['template'] as $feature) {
                $features[] = Mage::getSingleton('M2ePro/Template_Description_Parser')
                        ->parseTemplate(
                                        $feature,
                                        $this->listingProduct->getMagentoProduct()->getProduct()
                );
            }
        }

        $features = implode('|',$features);
        return $features;
    }

    public function getKeywords()
    {
        $src = $this->coreTemplate->getKeywordsSource();

        if ($this->coreTemplate->isKeywordsNone()) {
            return;
        } elseif ($this->coreTemplate->isKeywordsCustomValue()) {
            $keywords = $src['custom_value'];
        } elseif ($this->coreTemplate->isKeywordsCustomAttribute()) {
                $keywords = $this->listingProduct
                    ->getMagentoProduct()
                    ->getAttributeValue($src['custom_attribute']);
        }

        $keywords = preg_replace('/(?<=,)\s/','',$keywords);
        $keywords = str_replace(',','|',$keywords);
        return $keywords;
    }

    public function getWeight()
    {
        $weight = NULL;
        $src = $this->coreTemplate->getWeightSource();

        if ($src['mode'] == Ess_M2ePro_Model_Buy_Template_NewProduct_Core::WEIGHT_MODE_CUSTOM_VALUE) {
            $weight = $src['custom_value'];
        } else {
            $weight = $this->listingProduct
                    ->getMagentoProduct()
                    ->getAttributeValue($src['custom_attribute']);
        }

        return $weight;
    }

    // ########################################
}