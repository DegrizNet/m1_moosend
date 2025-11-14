<?php
/**
 * Copyright © 2025 Degriz. All rights reserved.
 */

class Degriz_Moosend_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Formats product options
     *
     * @param array $productConfigurations
     * @return array
     */
    public function formatProductOptions($productConfigurations)
    {
        if (count($productConfigurations) === 0) {
            return array();
        }

        $productOptions = $this->_getProductOptions($productConfigurations);
        $productAttributes = $this->_getProductAttributes($productConfigurations);

        return array_merge($productAttributes, $productOptions);
    }

    /**
     * @param $productConfigurations
     * @return array
     */
    private function _getProductOptions($productConfigurations)
    {
        if (!isset($productConfigurations['options']) || count($productConfigurations['options']) === 0) {
            return array();
        }

        $productOptions = $productConfigurations['options'];

        return $this->mapProductConfigurations($productOptions);
    }

    /**
     * @param $productConfigurations
     * @return array
     */
    private function _getProductAttributes($productConfigurations)
    {
        if (!isset($productConfigurations['attributes_info']) || count($productConfigurations['attributes_info']) === 0) {
            return array();
        }

        $productAttributes = $productConfigurations['attributes_info'];

        return $this->mapProductConfigurations($productAttributes);
    }

    /**
     * @param $productConfigurations
     * @return array
     */
    private function mapProductConfigurations($productConfigurations)
    {
        $formattedConfiguration = array();

        foreach ($productConfigurations as $productConfiguration) {
            $configurationLabel = $productConfiguration['label'];
            $configurationValue = $productConfiguration['value'];

            $formattedConfiguration[$configurationLabel] = $configurationValue;
        }

        return $formattedConfiguration;
    }

    /**
     * @param array $category_ids
     * @return mixed
     */
    public function getProductCategoryNames(array $category_ids)
    {
        $product_cats_names = array();
        
        foreach ($category_ids as $categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            if ($category->getId()) {
                $product_cats_names[] = $category->getName();
            }
        }
        
        return !empty($product_cats_names) ? implode(', ', $product_cats_names) : null;
    }
    
    /**
     * Get website ID from configuration
     *
     * @return string
     */
    public function getWebsiteId()
    {
        return Mage::getStoreConfig('degriz_moosend/settings/website_id');
    }
    
    /**
     * Check if module is enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        $websiteId = $this->getWebsiteId();
        return !empty($websiteId);
    }
}