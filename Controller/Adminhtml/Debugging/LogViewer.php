<?php

namespace RetailExpress\SkyLink\Controller\Adminhtml\Debugging;

use DateTime;
use DateTimeZone;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Monolog\Logger;
use RetailExpress\SkyLink\Api\Debugging\LogManagerInterface;

class LogViewer extends Action
{
    private $logManager;

    private $timezone;

    private $jsonResultFactory;

    public function __construct(
        Context $context,
        LogManagerInterface $logManager,
        TimezoneInterface $timezone,
        JsonResultFactory $jsonResultFactory
    ) {
        parent::__construct($context);

        $this->logManager = $logManager;
        $this->timezone = $timezone;
        $this->jsonResultFactory = $jsonResultFactory;
    }
    public function execute()
    {
        $sinceId = $this->getRequest()->getQueryValue('since_id');

        $logs = $this->logManager->getList($sinceId);
        $this->addHumanLevel($logs);
        $this->addHumanLoggedAt($logs);

        $jsonResult = $this->jsonResultFactory->create();
        $jsonResult->setData($logs);

        return $jsonResult;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('RetailExpress_SkyLink::skylink_logging');
    }

    private function addHumanLevel(array &$logs)
    {
        array_walk($logs, function (array &$log) {
            $log['human_level'] = Logger::getLevelName($log['level']);
        });
    }

    private function addHumanLoggedAt(array &$logs)
    {
        $timezone = $this->timezone->getConfigTimezone();

        array_walk($logs, function (array &$log) use ($timezone) {
            $log['human_logged_at'] = $log['logged_at']
                ->setTimezone(new DateTimeZone($timezone))
                ->format(DateTime::RFC850);
        });
    }
}
