<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Report Sold Products collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Yogendra\CustomerCreditsReport\Model\ResourceModel\Customer\Credit;
/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Collection extends \Magento\Reports\Model\ResourceModel\Customer\Collection
{
    /**
     * Set Date range to collection
     *
     * @param int $from
     * @param int $to
     * @return $this
     */
    public function setDateRange($from, $to)
    {
        $this->_reset()->addAttributeToSelect(
            '*'
        )->addOrderedQty(
            $from,
            $to
        )->setOrder('customer_detail.entity_id',self::SORT_ORDER_DESC);
        
        return $this;
    }
    /**
     * Add ordered qty's
     *
     * @param string $from
     * @param string $to
     * @return $this
     */
    public function addOrderedQty($from = '', $to = '')
    {
        $connection = $this->getConnection();
        $orderTableAliasName = $connection->quoteIdentifier('mgnf');
        $orderJoinCondition = [
             'customer_detail.entity_id = credit_transaction.customer_id',
        ];
        if ($from != '' && $to != '') {
            $fieldName = 'credit_transaction.transaction_time';
            $orderJoinCondition[] = $this->prepareBetweenSql($fieldName, $from, $to);
        }
        $this->getSelect()->reset()->from(
            ['credit_transaction' => $this->getTable('mgnf_credit_transaction')],
            [   
                // 'begin_balance' => new \Zend_Db_Expr('FIRST_VALUE(credit_transaction.begin_balance) OVER (ORDER BY credit_transaction.transaction_time DESC)'),
                'begin_balance' => 'credit_transaction.begin_balance',
                'spent_credit' => 'SUM(CASE WHEN credit_transaction.type_transaction_id = 6 THEN credit_transaction.spent_credit ELSE 0 END)', 
                'topup_refund' => 'SUM(CASE WHEN credit_transaction.type_transaction_id IN (5,8,10) THEN credit_transaction.received_credit ELSE 0 END)',
                'adjustments' => 'SUM(CASE WHEN credit_transaction.type_transaction_id IN (1,2,3,4,7) THEN credit_transaction.received_credit ELSE 0 END)',
                // 'end_balance' => new \Zend_Db_Expr('LAST_VALUE(credit_transaction.end_balance) OVER (ORDER BY credit_transaction.transaction_time DESC)')
                'end_balance' => 'credit_transaction.end_balance'
            ]
        )->joinInner(
            ['customer_detail' => $this->getTable('mgnf_customer_entity')],
            implode(' AND ', $orderJoinCondition),
            [
                'customer_email'=>'email',
                'customer_key' => 'entity_id'
            ]
        )->where(
            'customer_detail.entity_id = credit_transaction.customer_id'
        )->group(
            'credit_transaction.customer_id'
        )->having(
            'SUM(credit_transaction.spent_credit) > ?',
            0
        )->order('credit_transaction.customer_id');

        return $this;
    }
    /**
     * Set store filter to collection
     *
     * @param array $storeIds
     * @return $this
     */
    public function setStoreIds($storeIds)
    {
        if ($storeIds) {
            $this->getSelect()->where('customer_detail.store_id IN (?)', (array)$storeIds);
        }
        return $this;
    }
    /**
     * Set order
     *
     * @param string $attribute
     * @param string $dir
     * @return $this
     */
    public function setOrder($attribute, $dir = self::SORT_ORDER_DESC)
    {
        if (in_array($attribute, ['orders', 'ordered_qty'])) {
            $this->getSelect()->order($attribute . ' ' . $dir);
        } else {
            parent::setOrder($attribute, $dir);
        }
        return $this;
    }
    /**
     * Prepare between sql
     *
     * @param string $fieldName Field name with table suffix ('created_at' or 'main_table.created_at')
     * @param string $from
     * @param string $to
     * @return string Formatted sql string
     */
    protected function prepareBetweenSql($fieldName, $from, $to)
    {
        return sprintf(
            '(%s BETWEEN %s AND %s)',
            $fieldName,
            $this->getConnection()->quote($from),
            $this->getConnection()->quote($to)
        );
    }
}