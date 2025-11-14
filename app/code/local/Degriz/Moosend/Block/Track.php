<?php
class Degriz_Moosend_Block_Track extends Mage_Core_Block_Template
{
    protected $_orderInfo = null;

    protected function getCurrentProductId()
    {
        $product = Mage::registry('current_product');
        return $product ? $product->getId() : null;
    }

    public function isProductPage()
    {
        return Mage::app()->getRequest()->getModuleName() === 'catalog' 
            && Mage::app()->getRequest()->getControllerName() === 'product'
            && Mage::app()->getRequest()->getActionName() === 'view';
    }

    public function getWebsiteId()
    {
        return Mage::helper('degriz_moosend')->getWebsiteId();
    }

    public function getCurrentProduct()
    {
        $productId = $this->getCurrentProductId();
        if (!$productId) return false;

        $product = Mage::getModel('catalog/product')->load($productId);
        if (!$product->getId()) return false;

        $productImageUrl = Mage::helper('catalog/image')->init($product, 'image')->__toString();
        $finalPrice = $product->getFinalPrice();

        return [
            'product' => [
                'itemCode' => $product->getId(),
                'itemPrice' => $finalPrice,
                'itemUrl' => $product->getProductUrl(),
                'itemQuantity' => 1,
                'itemTotalPrice' => $finalPrice,
                'itemImage' => $productImageUrl,
                'itemName' => $product->getName(),
                'itemCategory' => Mage::helper('degriz_moosend')->getProductCategoryNames($product->getCategoryIds()),
            ]
        ];
    }

    public function getUserData()
    {
        $customerSession = Mage::getSingleton('customer/session');
        if ($customerSession->isLoggedIn()) {
            $customer = $customerSession->getCustomer();
            return [
                'name' => $customer->getName(),
                'email' => $customer->getEmail()
            ];
        }
        return null;
    }

    public function setOrderInfo($orderInfo)
    {
        $this->_orderInfo = $orderInfo;
        return $this;
    }

    public function getOrderInfo()
    {
        return $this->_orderInfo;
    }

    public function getTrackingData()
    {
        return [
            "current_website_id" => $this->getWebsiteId(),
            "is_product_page" => $this->isProductPage(),
            "current_product" => $this->isProductPage() ? $this->getCurrentProduct() : false,
            "order_info" => $this->getOrderInfo(),
            "user_data" => $this->getUserData(),
        ];
    }
}
