<?php
namespace Yogendra\CustomerCreditsReport\Ui\Component;

class Topup extends \Magento\Backend\Block\Widget\Grid\Column
{
    /**
     * Retrieve row column field value for display
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $_helperCore;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_backendSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Framework\Code\NameBuilder
     */
    protected $nameBuilder;

    protected $_httpRespone;

    protected $_encryptor;

    protected $authSession;

    protected $timezone;

    /**
    * @var \Magestore\Customercredit\Model\TransactionFactory
    */
    protected $_transaction;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context, 
        \Magento\Framework\App\RequestInterface $httpRespone,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magestore\Customercredit\Model\ResourceModel\Transaction\Collection $transaction,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Pricing\Helper\Data $helperCore,
        array $data = []
    )
    {
        $this->authSession = $authSession;
        $this->_encryptor = $encryptor;
        $this->_backendHelper = $backendHelper;
        $this->_helperCore = $helperCore;
        $this->_transaction = $transaction;
        $this->_httpRespone = $httpRespone;
        $this->timezone = $timezone;
        $this->_localeDate = $context->getLocaleDate();
        $this->_authorization = $context->getAuthorization();
        $this->mathRandom = $context->getMathRandom();
        $this->_backendSession = $context->getBackendSession();
        $this->formKey = $context->getFormKey();
        $this->nameBuilder = $context->getNameBuilder();
        parent::__construct($context, $data);
    }

    public function getRowField(\Magento\Framework\DataObject $row)
    {
        $this->_transaction->clear()->getSelect()->reset(\Zend_Db_Select::WHERE);
        $renderedValue = $this->getRenderer()->render($row);
        $topupRefund = $row->getData('topup_refund');
        $filterDate = $this->_httpRespone->getParam('filter');
        $data = $this->_backendHelper->prepareFilterString($filterDate);

        return $this->_helperCore->currency(sprintf('%0.2f', $topupRefund));
    
    }
}