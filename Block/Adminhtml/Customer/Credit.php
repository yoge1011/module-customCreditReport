<?php 
namespace Yogendra\CustomerCreditsReport\Block\Adminhtml\Customer;

class Credit extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @var string
     */
    protected $_blockGroup = 'Yogendra_CustomerCreditsReport';
    /**
     * Initialize container block settings
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Yogendra_CustomerCreditsReport';
        $this->_controller = 'adminhtml_customer_credit';
        $this->_headerText = __('Customer Credit');
        parent::_construct();
        $this->buttonList->remove('add');
    }
}