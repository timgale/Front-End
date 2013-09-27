<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Amazon_Template_NewProduct_Source
{
    const GALLERY_IMAGES_COUNT_MAX = 8;

    /* @var $listingProduct Ess_M2ePro_Model_Amazon_Listing_Product */
    private $listingProduct = null;

    /* @var $templateNewProduct Ess_M2ePro_Model_Amazon_Template_NewProduct */
    private $templateNewProduct = null;

    /* @var $templateNewProductDescription Ess_M2ePro_Model_Amazon_Template_NewProduct_Description */
    private $templateNewProductDescription = null;

    // ########################################

    public function __construct($args)
    {
        list($this->listingProduct,$this->templateNewProduct) = $args;

        $this->templateNewProductDescription = $this->templateNewProduct->getDescription();
    }

    // ########################################

    public function getRegisteredParameter()
    {
        return (string)$this->templateNewProduct->getRegisteredParameter();
    }

    // ########################################

    public function getWorldwideId()
    {
        $result = '';
        $src = $this->templateNewProduct->getWorldwideIdSource();

        if ($src['mode'] == Ess_M2ePro_Model_Amazon_Template_NewProduct::WORLDWIDE_ID_MODE_NONE) {
            $result = NULL;
        }

        if ($src['mode'] == Ess_M2ePro_Model_Amazon_Template_NewProduct::WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->listingProduct->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        is_string($result) && $result = trim($result);

        return $result;
    }

    // ########################################

    public function getProductData()
    {
        $arrayXml = array();
        foreach ($this->templateNewProduct->getSpecifics() as $specific) {

            $xpath = trim($specific['xpath'],'/');
            $xpathParts = explode('/',$xpath);

            $path = '';
            $isFirst = true;

            foreach ($xpathParts as $part) {
                list($tag,$index) = explode('-',$part);

                if (!$tag) {
                    continue;
                }

                $isFirst || $path .= '{"childNodes": ';
                $path .= "{\"$tag\": {\"$index\": ";
                $isFirst = false;
            }

            if ($specific['mode'] == 'none') {

                $path .= '[]';
                $path .= str_repeat('}',substr_count($path,'{'));

                $arrayXml = Mage::helper('M2ePro')->arrayReplaceRecursive(
                    $arrayXml,
                    json_decode($path,true)
                );

                continue;
            }

            $value = $specific[$specific['mode']];

            if ($specific['mode'] == 'custom_attribute') {
                $value = $this->listingProduct->getMagentoProduct()->getAttributeValue($specific['custom_attribute']);
            }

            $specific['type'] == 'int' && $value = (int)$value;
            $specific['type'] == 'float' && $value = (float)str_replace(',','.',$value);
            $specific['type'] == 'date_time' && $value = str_replace(' ','T',$value);

            $value = str_replace('"','\"',$value);

            $attributes = array();
            foreach (json_decode($specific['attributes'],1) as $i=>$attribute) {

                list($attributeName) = array_keys($attribute);

                $attributeData = $attribute[$attributeName];

                $attributeValue = $attributeData['mode'] == 'custom_value'
                    ? $attributeData['custom_value']
                    : $this->listingProduct->getMagentoProduct()->getAttributeValue($attributeData['custom_attribute']);

                $attributeValue = str_replace('"','\"',$attributeValue);

                $attributes[$i] = array(
                    'name' => str_replace(' ','',$attributeName),
                    'value' => $attributeValue,
                );
            }

            $attributes = json_encode($attributes);

            $path .= '%data%';
            $path .= str_repeat('}',substr_count($path,'{'));

            $path = str_replace(
                '%data%',
                "{\"value\": \"$value\",\"attributes\": $attributes}",
                $path
            );

            $arrayXml = Mage::helper('M2ePro')->arrayReplaceRecursive(
                $arrayXml,
                json_decode($path,true)
            );
        }

        return $arrayXml;
    }

    // ---------------------------------------

    public function getDescriptionData()
    {
        $descriptionData = array(
            'title' => $this->getTitle(),
            'brand' => $this->getBrand(),
            'description' => $this->getDescription(),
            'bullets' => $this->getBulletPoints(),
            'search_terms' => $this->getSearchTerms(),
            'manufacturer' => $this->getManufacturer(),
            'manufacturer_part_number' => $this->getManufacturerPartNumber(),
            'target_audience' => $this->getTargetAudience(),
        );

        $categoryIdentifiers = $this->templateNewProduct->getCategoryIdentifiers();

        $descriptionData['item_types'] = $categoryIdentifiers['item_types'];
        $descriptionData['browsenode_id'] = $categoryIdentifiers['browsenode_id'];

        return $descriptionData;
    }

    // ---------------------------------------

    public function getImagesData()
    {
        if ($this->templateNewProductDescription->isImageMainModeNone()) {
            return array();
        }

        $mainImage = $this->getMainImageLink();

        if ($mainImage == '') {
            return array();
        }

        $mainImage = array($mainImage);

        if ($this->templateNewProductDescription->isGalleryImagesModeNone()) {
            return $mainImage;
        }

        $galleryImages = array();
        $gallerySource = $this->templateNewProductDescription->getGalleryImagesSource();
        $limitGalleryImages = self::GALLERY_IMAGES_COUNT_MAX;

        if ($this->templateNewProductDescription->isGalleryImagesModeProduct()) {
            $limitGalleryImages = (int)$gallerySource['limit'];
            $galleryImages = $this->listingProduct
                ->getMagentoProduct()
                ->getGalleryImagesLinks((int)$gallerySource['limit']+1);
        }

        if ($this->templateNewProductDescription->isGalleryImagesModeAttribute()) {
            $limitGalleryImages = self::GALLERY_IMAGES_COUNT_MAX;
            $galleryImagesTemp = $this->listingProduct
                ->getMagentoProduct()
                ->getAttributeValue($gallerySource['attribute']);
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
            return $mainImage;
        }

        $mainImagePosition = array_search($mainImage[0], $galleryImages);
        if ($mainImagePosition !== false) {
            unset($galleryImages[$mainImagePosition]);
        }

        $galleryImages = array_slice($galleryImages,0,$limitGalleryImages);
        return array_merge($mainImage, $galleryImages);
    }

    // ########################################

    public function getTitle()
    {
        $src = $this->templateNewProductDescription->getTitleSource();

        switch ($src['mode']) {
            case Ess_M2ePro_Model_Amazon_Template_NewProduct_Description::TITLE_MODE_PRODUCT:
                $title = $this->listingProduct->getMagentoProduct()->getName();
                break;

            case Ess_M2ePro_Model_Amazon_Template_NewProduct_Description::TITLE_MODE_CUSTOM:
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

    public function getBrand()
    {
        $brand = '';
        $src = $this->templateNewProductDescription->getBrandSource();

        if ($src['mode'] == Ess_M2ePro_Model_Amazon_Template_NewProduct_Description::BRAND_MODE_CUSTOM) {
            $brand = Mage::getSingleton('M2ePro/Template_Description_Parser')->parseTemplate(
                $src['template'],
                $this->listingProduct->getMagentoProduct()->getProduct()
            );
        }

        return $brand;
    }

    public function getDescription()
    {
        $src = $this->templateNewProductDescription->getDescriptionSource();
        /* @var $templateProcessor Mage_Core_Model_Email_Template_Filter */
        $templateProcessor = Mage::getModel('Core/Email_Template_Filter');

        switch ($src['mode']) {
            case Ess_M2ePro_Model_Amazon_Template_NewProduct_Description::DESCRIPTION_MODE_PRODUCT:
                $description = $this->listingProduct->getMagentoProduct()->getProduct()->getDescription();
                $description = $templateProcessor->filter($description);
                break;

            case Ess_M2ePro_Model_Amazon_Template_NewProduct_Description::DESCRIPTION_MODE_SHORT:
                $description = $this->listingProduct->getMagentoProduct()->getProduct()->getShortDescription();
                $description = $templateProcessor->filter($description);
                break;

            case Ess_M2ePro_Model_Amazon_Template_NewProduct_Description::DESCRIPTION_MODE_CUSTOM:
                $description = Mage::getSingleton('M2ePro/Template_Description_Parser')->parseTemplate(
                    $src['template'],
                    $this->listingProduct->getMagentoProduct()->getProduct()
                );
                break;

            default:
                $description = '';
                break;
        }

        $description = str_replace(array('<![CDATA[', ']]>'), '', $description);
        $description = strip_tags($description);

        return $description;
    }

    public function getBulletPoints()
    {
        $bullets = array();

        $src = $this->templateNewProductDescription->getBulletPointsSource();

        if ($src['mode'] == Ess_M2ePro_Model_Amazon_Template_NewProduct_Description::BULLET_POINTS_MODE_CUSTOM) {

            foreach ($src['template'] as $bullet) {
                $bullets[] = Mage::getSingleton('M2ePro/Template_Description_Parser')->parseTemplate(
                    $bullet,
                    $this->listingProduct->getMagentoProduct()->getProduct()
                );
            }
        }

        return $bullets;
    }

    public function getSearchTerms()
    {
        $searchTerms = array();

        $src = $this->templateNewProductDescription->getSearchTermsSource();

        if ($src['mode'] == Ess_M2ePro_Model_Amazon_Template_NewProduct_Description::SEARCH_TERMS_MODE_CUSTOM) {

            foreach ($src['template'] as $searchTerm) {
                $searchTerms[] = Mage::getSingleton('M2ePro/Template_Description_Parser')->parseTemplate(
                    $searchTerm,
                    $this->listingProduct->getMagentoProduct()->getProduct()
                );
            }
        }

        return $searchTerms;
    }

    public function getManufacturer()
    {
        $manufacturer = '';
        $src = $this->templateNewProductDescription->getManufacturerSource();

        if ($src['mode'] == Ess_M2ePro_Model_Amazon_Template_NewProduct_Description::MANUFACTURER_MODE_CUSTOM) {
            $manufacturer = Mage::getSingleton('M2ePro/Template_Description_Parser')->parseTemplate(
                $src['template'],
                $this->listingProduct->getMagentoProduct()->getProduct());
        }

        return $manufacturer;
    }

    public function getManufacturerPartNumber()
    {
        $src = $this->templateNewProductDescription->getManufacturerPartNumberSource();

        if ($this->templateNewProductDescription->isManufacturerPartNumberModeCustomValue()) {
            return $src['custom_value'];
        }

        return $this->listingProduct->getMagentoProduct()->getAttributeValue($src['custom_attribute']);
    }

    public function getTargetAudience()
    {
        $src = $this->templateNewProductDescription->getTargetAudienceSource();

        if ($this->templateNewProductDescription->isTargetAudienceModeCustomValue()) {
            return $src['custom_value'];
        }

        return $this->listingProduct->getMagentoProduct()->getAttributeValue($src['custom_attribute']);
    }

    // ########################################

    public function getMainImageLink()
    {
        $imageLink = '';

        if ($this->templateNewProductDescription->isImageMainModeProduct()) {
            $imageLink = $this->listingProduct->getMagentoProduct()->getImageLink('image');
        }

        if ($this->templateNewProductDescription->isImageMainModeAttribute()) {
            $src = $this->templateNewProductDescription->getImageMainSource();
            $imageLink = $this->listingProduct->getMagentoProduct()->getImageLink($src['attribute']);
        }

        return $imageLink;
    }

    // ########################################

}