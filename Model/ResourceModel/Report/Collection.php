<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Yogendra\CustomerCreditsReport\Model\ResourceModel\Report;

/**
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Reports\Model\ResourceModel\Report\Collection
{

    protected function _getIntervals()
    {
        if (!$this->_intervals) {
            $this->_intervals = [];
            if (!$this->_from && !$this->_to) {
                return $this->_intervals;
            }
            $dateStart = $this->_from;
            $dateEnd = $this->_to;
            $interval = $this->_getDefaultDayInterval($dateStart, $dateEnd );
            $this->_intervals[$interval['period']] = new \Magento\Framework\DataObject($interval);
        }
        return $this->_intervals;
    }

     /**
     * Get interval for a day
     *
     * @param \DateTime $dateStart
     * @return array
     */
    protected function _getDefaultDayInterval(\DateTime $dateStart, \DateTime $dateEnd)
    {
        $interval = [
            'period' => $this->_localeDate->formatDateTime(
                $dateStart,
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::NONE
            ),
            'start' => $this->_localeDate->convertConfigTimeToUtc($dateStart->format('Y-m-d 00:00:00')),
            'end' => $this->_localeDate->convertConfigTimeToUtc($dateEnd->format('Y-m-d 23:59:59')),
        ];
        return $interval;
    }

   
}
