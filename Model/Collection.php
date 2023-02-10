<?php

namespace Kgkrunch\ExlcudeCanceldorder\Model;

use Magento\Framework\DB\Select;

/**
 * Reports orders collection
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Reports\Model\ResourceModel\Order\Collection
{
    
    protected function _prepareSummaryLive($range, $customStart, $customEnd, $isFilter = 0)
    {
        $this->setMainTable('sales_order');
        $connection = $this->getConnection();

        /**
         * Reset all columns, because result will group only by 'created_at' field
         */
        $this->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);

        $expression = $this->_getSalesAmountExpression();
        if ($isFilter == 0) {
            $this->getSelect()->columns(
                [
                    'revenue' => new \Zend_Db_Expr(
                        sprintf(
                            'SUM((%s) * %s)',
                            $expression,
                            $connection->getIfNullSql('main_table.base_to_global_rate', 0)
                        )
                    ),
                ]
            );
        } else {
            $this->getSelect()->columns(['revenue' => new \Zend_Db_Expr(sprintf('SUM(%s)', $expression))]);
        }

        $dateRange = $this->getDateRange($range, $customStart, $customEnd);

        $tzRangeOffsetExpression = $this->_getTZRangeOffsetExpression(
            $range,
            'created_at',
            $dateRange['from'],
            $dateRange['to']
        );

        $this->getSelect()->columns(
            ['quantity' => 'COUNT(main_table.entity_id)', 'range' => $tzRangeOffsetExpression]
        )->where(
            'main_table.state NOT IN (?)',
            [\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, \Magento\Sales\Model\Order::STATE_NEW,\Magento\Sales\Model\Order::STATE_CANCELED]
        )->order(
            'range',
            \Magento\Framework\DB\Select::SQL_ASC
        )->group(
            $tzRangeOffsetExpression
        );
		
        $this->addFieldToFilter('created_at', $dateRange);

        return $this;
    }

 
}
