<?php 
namespace Yogendra\CustomerCreditsReport\Block\Adminhtml;

class Grid extends \Magento\Reports\Block\Adminhtml\Grid
{
    /**
     * Should Store Switcher block be visible
     *
     * @var bool
     */
    protected $_storeSwitcherVisibility = true;

    /**
     * Should Date Filter block be visible
     *
     * @var bool
     */
    protected $_dateFilterVisibility = true;

    /**
     * Filters array
     *
     * @var array
     */
    protected $_filters = [];

    /**
     * Default filters values
     *
     * @var array
     */
    protected $_defaultFilters = ['report_from' => '', 'report_to' => '', 'report_period' => 'day'];

    /**
     * Sub-report rows count
     *
     * @var int
     */
    protected $_subReportSize = 5;

    /**
     * Errors messages aggregated array
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * Block template file name
     *
     * @var string
     */
    protected $_template = 'Yogendra_CustomerCreditsReport::grid.phtml';

    /**
     * Filter values array
     *
     * @var array
     */
    protected $_filterValues;
}