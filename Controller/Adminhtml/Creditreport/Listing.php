<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Yogendra\CustomerCreditsReport\Controller\Adminhtml\Creditreport;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Listing extends \Magento\Backend\App\Action
{
     /**
     * Add reports and customer breadcrumbs
     *
     * @return $this
     */
    public function _initAction()
    {
        $act = $this->getRequest()->getActionName();
        if (!$act) {
            $act = 'default';
        }

        $this->_view->loadLayout();
        $this->_addBreadcrumb(__('Reports'), __('Reports'));
        $this->_addBreadcrumb(__('Customers'), __('Customers'));
        return $this;
    }
    
    /**
     * Customers by number of orders action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initAction()->_setActiveMenu(
            'Yogendra_CustomerCreditsReport::creditreports_list'
        )->_addBreadcrumb(
            __('Customers by Number of Orders'),
            __('Customers by Number of Orders')
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Customer Credit Report'));
        $this->_view->renderLayout();
    }
}
