<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Yogendra\CustomerCreditsReport\Controller\Adminhtml\Creditreport;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\ConvertToCsv;
use Magento\Framework\App\Response\Http\FileFactory;
use Magestore\Customercredit\Model\ResourceModel\Transaction\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;

class ExportCustomerCreditCsv extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Massactions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var MetadataProvider
     */
    protected $metadataProvider;
    /**
     * @var WriteInterface
     */
    protected $directory;
    /**
     * @var ConvertToCsv
     */
    protected $converter;
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Magento\Framework\Registry
    */

    protected $_registry;

    protected $_dateTime;

    protected $_messageManager;

    protected $_filesystem;


    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        Filter $filter,
        Filesystem $filesystem,
        ConvertToCsv $converter,
        FileFactory $fileFactory,
        \Magento\Ui\Model\Export\MetadataProvider $metadataProvider,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Magestore\Customercredit\Model\TransactionFactory $transactionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $registry,
        \Magestore\Customercredit\Model\ResourceModel\Transaction\Collection $transaction,
        \Magestore\Customercredit\Model\TransactionTypeFactory $transactionTypeFactory,
        \Magestore\Customercredit\Model\ResourceModel\Transaction $resource,
        CollectionFactory $collectionFactory
        ) {
            $this->resources = $resource;
            $this->_registry = $registry;
            $this->_transaction = $transaction;
            $this->_messageManager = $messageManager;
            $this->_transactionFactory = $transactionFactory;
            $this->_transactionTypeFactory = $transactionTypeFactory;
            $this->_backendHelper = $backendHelper;
            $this->filter = $filter;
            $this->_connection = $this->resources->getConnection();
            $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $this->metadataProvider = $metadataProvider;
            $this->converter = $converter;
            $this->_dateTime =  $dateTime;
            $this->fileFactory = $fileFactory;
            parent::__construct($context);
            $this->resultForwardFactory = $resultForwardFactory;
            $this->collectionFactory = $collectionFactory;
            $this->_filesystem = $filesystem;
    }

    protected function getCollectionData($from, $to)
    {
        $connection = $this->_connection;
        
        $sql = "SELECT SUM(`credit_transaction`.`begin_balance`) AS `begin_balance`, SUM(CASE WHEN `credit_transaction`.`type_transaction_id` = 6 THEN `credit_transaction`.`spent_credit` ELSE 0 END) AS `spent_credit`, SUM(CASE WHEN `credit_transaction`.`type_transaction_id` IN (5,8,10) THEN `credit_transaction`.`received_credit` ELSE 0 END) AS `topup_refund`, SUM(CASE WHEN `credit_transaction`.`type_transaction_id` IN (1,2,3,4,7) THEN `credit_transaction`.`received_credit` ELSE 0 END) AS `adjustments`, SUM(`credit_transaction`.`end_balance`) AS `end_balance`, `customer_detail`.`firstname` AS `customer_firstname`, `customer_detail`.`lastname` AS `customer_lastname`,`customer_detail`.`email` AS `customer_email`, `customer_detail`.`entity_id` AS `customer_key` FROM `mgnf_credit_transaction` AS `credit_transaction` INNER JOIN `mgnf_customer_entity` AS `customer_detail` ON `customer_detail`.`entity_id` = `credit_transaction`.`customer_id` AND (`credit_transaction`.`transaction_time` BETWEEN '". $from ."' AND '". $to ."') WHERE (`customer_detail`.`entity_id` = `credit_transaction`.`customer_id`) GROUP BY `credit_transaction`.`customer_id` HAVING (SUM(`credit_transaction`.`spent_credit`) > 0) ORDER BY `credit_transaction`.`customer_id` ASC";

        $result = $connection->fetchAll($sql);
        
        return $result;
    }

    /**
     * export.
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        $collection = $this->collectionFactory->create();
        $filterDate = $this->getRequest()->getParam('filter');
        $data = $this->_backendHelper->prepareFilterString($filterDate);

        $reportFrom = date('Y-m-d 00:00:00', strtotime($data['report_from']));
        $reportTo = date('Y-m-d 00:00:00', strtotime($data['report_to']));

        $reportData = $this->getCollectionData($reportFrom,$reportTo);

        if(!empty($reportData)){
            $i=0;
            foreach($reportData as $data){
                $this->_transaction->clear()->getSelect()->reset(\Zend_Db_Select::WHERE);
                    $reportData[$i]['begin_balance'] = $this->_transaction
                                                        ->addFieldToSelect('*')
                                                        ->addFieldToFilter('customer_id', $data['customer_key'])
                                                        ->addFieldToFilter('transaction_time', ['lteq' => $reportTo ])
                                                        ->addFieldToFilter('transaction_time', ['gteq' => $reportFrom ])
                                                        ->getFirstItem()->getBeginBalance();

                $this->_transaction->clear()->getSelect()->reset(\Zend_Db_Select::WHERE);
                    $reportData[$i]['end_balance'] = $this->_transaction
                                                        ->addFieldToSelect('*')
                                                        ->addFieldToFilter('customer_id', $data['customer_key'])
                                                        ->addFieldToFilter('transaction_time', ['lteq' => $reportTo ])
                                                        ->addFieldToFilter('transaction_time', ['gteq' => $reportFrom ])
                                                        ->getLastItem()->getEndBalance();
                
                $i++;
            }
            $filename = 'customer-credit-report-'.-time().'.csv';
            $mediapath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            $this->generateCsv($reportData,$mediapath.$filename);

            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename='.$filename);
            header('Pragma: no-cache');
            readfile($mediapath.$filename);
            return;
        }  
    }

    public function generateCsv($creditData, $filePath)
    {
        $dataToWrite = array(); //Data to be written in file
        $headers = array();
        foreach (array_keys($creditData[0]) as $header) {
            $header = explode('_',$header);
            $header = implode(' ', $header);
            $header = ucwords($header);
            $headers[] = $header;
        }
        $dataToWrite[] = implode(',', $headers);
        foreach ($creditData as $data) {
            $dataToWrite[] = implode(',', $data);
        }
        if(!file_exists($filePath)){
            $file = fopen($filePath, 'w') or die("Can't create file");
        } else {
            $file = fopen($filePath,"w");
        }
        foreach ($dataToWrite as $data)
        {
            fputcsv($file,explode(',',$data));
        }
        fclose($file);
        
        return true;
    }

    function get_months($date1, $date2) {
        $time1  = strtotime($date1);
        $time2  = strtotime($date2);
        $my     = date('mY', $time2);
     
        $months = array(date('01-m-Y', $time1));
     
        while($time1 < $time2) {
           $time1 = strtotime(date('Y-m-d', $time1).' +1 month');
           if(date('mY', $time1) != $my && ($time1 < $time2))
              $months[] = date('01-m-Y', $time1);
        }
     
        $months[] = date('01-m-Y', $time2);
        return $months;
    }

    protected function prepareBetweenSql($fieldName, $from, $to)
    {
        return sprintf(
            "(%s BETWEEN '".$from."' AND '".$to."')",
            $fieldName
        );
    }
}

