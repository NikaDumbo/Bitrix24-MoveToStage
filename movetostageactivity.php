<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Bizproc\FieldType;
use Local\Facades\Deal;

class CBPMoveToStageActivity extends CBPActivity
{
    /**
     * @see parent::_construct()
     * @param void
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'Title' => '',
            'Deal' => null,
            'Stage' => null,
            'EnableAutomation' => true,
        ];

        $this->SetPropertiesTypes([
            'Deal' => [
                'Type' => FieldType::INT
            ],
            'Stage' => [
                'Type' => FieldType::SELECT
            ],
            'EnableAutomation' => [
                'Type' => FieldType::BOOL
            ]
        ]);
    }
    /**
     * Start the execution of activity
     * @return CBPActivityExecutionStatus
     */
    public function Execute()
    {
        $rootActivity = $this->GetRootActivity();

        try {
            Deal::moveToStage($this->Deal, $this->Stage, $this->EnableAutomation);
        } catch (Exception $e) {
            $this->WriteToTrackingService($e->getMessage(), 0, CBPTrackingType::Report);
        }

        return CBPActivityExecutionStatus::Closed;
    }

    
}
