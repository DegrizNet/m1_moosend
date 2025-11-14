<?php
/**
 * Copyright © 2025 Degriz. All rights reserved.
 */

class Degriz_Moosend_Model_Observer
{
    /**
     * Observer for controller_front_send_response_before event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function controllerFrontSendResponseBefore(Varien_Event_Observer $observer)
    {
        if (headers_sent()) {
            return;
        }

        $helper = Mage::helper('degriz_moosend');
        if (!$helper->isEnabled()) {
            return;
        }

        // Tracker initialization handled in JavaScript
    }

    /**
     * Observer for checkout_cart_add_product_complete event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function checkoutCartAddProductComplete(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('degriz_moosend');
        $siteId = $helper->getWebsiteId();

        if (empty($siteId)) {
            return;
        }

        $product = $observer->getEvent()->getProduct();
        $request = $observer->getEvent()->getRequest();
        
        $qty = $request->getParam('qty', 1);
        $price = $product->getFinalPrice();
        $productImageUrl = Mage::helper('catalog/image')->init($product, 'image')->__toString();

        $productData = array(
            'id' => $product->getId(),
            'price' => $price,
            'url' => $product->getProductUrl(),
            'quantity' => (int)$qty,
            'total' => $price * $qty,
            'name' => $product->getName(),
            'image' => $productImageUrl,
            'category' => $helper->getProductCategoryNames($product->getCategoryIds())
        );

        // Store in session for JS to pick up
        Mage::getSingleton('core/session')->setDegrizMoosendAddToCart($productData);
    }

    /**
     * Observer for sales_quote_remove_item event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function salesQuoteRemoveItem(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('degriz_moosend');
        $siteId = $helper->getWebsiteId();

        if (empty($siteId)) {
            return;
        }

        $quoteItem = $observer->getEvent()->getQuoteItem();
        $product = $quoteItem->getProduct();
        
        $price = $product->getFinalPrice();
        $qty = $quoteItem->getQty();
        $productImageUrl = Mage::helper('catalog/image')->init($product, 'image')->__toString();

        $productData = array(
            'id' => $product->getId(),
            'price' => $price,
            'url' => $product->getProductUrl(),
            'quantity' => (int)$qty,
            'total' => $price * $qty,
            'name' => $product->getName(),
            'image' => $productImageUrl,
            'category' => $helper->getProductCategoryNames($product->getCategoryIds())
        );

        // Store in session for JS to pick up
        Mage::getSingleton('core/session')->setDegrizMoosendRemoveFromCart($productData);
    }

    /**
     * Observer for checkout_onepage_controller_success_action event
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function checkoutOnepageControllerSuccess(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('degriz_moosend');
        $siteId = $helper->getWebsiteId();

        if (empty($siteId)) {
            return;
        }

        $orderIds = $observer->getEvent()->getOrderIds();

        if (!empty($orderIds)) {
            $orderId = $orderIds[0];
            $order = Mage::getModel('sales/order')->load($orderId);
            
            if ($order->getId()) {
                $email = $order->getCustomerEmail();
                $name = $order->getCustomerName();
                $orderTotal = $order->getGrandTotal();

                $orderData = $this->prepareOrderData($order);

                $layout = Mage::app()->getLayout();
                $block = $layout->getBlock('degriz_moosend_track');
                
                if ($block) {
                    $block->setOrderInfo(array(
                        'email' => $email,
                        'name' => $name,
                        'order_data' => $orderData,
                        'order_total_price' => $orderTotal
                    ));
                }
            }
        }
    }

    /**
     * Prepare order data for tracking
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    protected function prepareOrderData($order)
    {
        $helper = Mage::helper('degriz_moosend');
        $orderProducts = array();
        $orderTotal = $order->getGrandTotal();

        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            
            if ($product) {
                $price = $product->getFinalPrice();
                $qty = (int)$item->getQtyOrdered();
                $productImageUrl = Mage::helper('catalog/image')->init($product, 'image')->__toString();

                $productOptions = $item->getProductOptions();
                $props = $helper->formatProductOptions($productOptions);
                $props['itemCategory'] = $helper->getProductCategoryNames($product->getCategoryIds());

                $orderProducts[] = array(
                    'itemCode' => $product->getId(),
                    'itemPrice' => $price,
                    'itemUrl' => $product->getProductUrl(),
                    'itemQuantity' => $qty,
                    'itemTotalPrice' => $price * $qty,
                    'itemImage' => $productImageUrl,
                    'itemName' => $product->getName(),
                    'properties' => $props
                );
            }
        }

        return array(
            'products' => $orderProducts,
            'total' => $orderTotal
        );
    }
}