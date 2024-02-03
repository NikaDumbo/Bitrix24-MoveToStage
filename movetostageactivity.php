<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Bizproc\FieldType;
use Local\Facades\Deal;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\Loader;

Loader::includeModule("crm");

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
            Deal::moveToStage($this->Deal, $this->Stage, CBPHelper::getBool($this->EnableAutomation));
        } catch (Exception $e) {
            $this->WriteToTrackingService($e->getMessage(), 0, CBPTrackingType::FaultActivity);
        }

        return CBPActivityExecutionStatus::Closed;
    }
    /**
     * Generate setting form
     * 
     * @param array $documentType
     * @param string $activityName
     * @param array $arWorkflowTemplate
     * @param array $arWorkflowParameters
     * @param array $arWorkflowVariables
     * @param array $arCurrentValues
     * @param string $formName
     * @return string
     */
    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
    {
        $dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
            "documentType" => $documentType,
            "activityName" => $activityName,
            "workflowTemplate" => $arWorkflowTemplate,
            "workflowParameters" => $arWorkflowParameters,
            "workflowVariables" => $arWorkflowVariables,
            "currentValues" => $arCurrentValues,
            "formName" => $formName,
            "siteId" => $siteId
        ));
        $dialog->setMap(static::getPropertiesDialogMap($documentType));

        return $dialog;
    }

    /**
     * Process form submition
     * 
     * @param array $documentType
     * @param string $activityName
     * @param array &$arWorkflowTemplate
     * @param array &$arWorkflowParameters
     * @param array &$arWorkflowVariables
     * @param array &$arCurrentValues
     * @param array &$arErrors
     * @return bool
     */
    public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
    {
        $documentService = CBPRuntime::GetRuntime(true)->getDocumentService();
        $dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
            "documentType" => $documentType,
            "activityName" => $activityName,
            "workflowTemplate" => $arWorkflowTemplate,
            "workflowParameters" => $arWorkflowParameters,
            "workflowVariables" => $arWorkflowVariables,
            "currentValues" => $arCurrentValues,
        ));

        $arProperties = [];
        foreach (static::getPropertiesDialogMap($documentType) as $fieldID => $arFieldProperties) {
            $field = $documentService->getFieldTypeObject($dialog->getDocumentType(), $arFieldProperties);
            if (!$field) {
                continue;
            }

            $arProperties[$fieldID] = $field->extractValue(
                ["Field" => $arFieldProperties["FieldName"]],
                $arCurrentValues,
                $arErrors
            );
        }

        $arErrors = static::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));

        if (count($arErrors) > 0) {
            return false;
        }

        $currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $currentActivity["Properties"] = $arProperties;

        return true;
    }

    /**
     * Validate user provided properties
     * 
     * @param array $arTestProperties
     * @param CBPWorkflowTemplateUser $user
     * @return array
     */
    public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $arErrors = array();
        foreach (static::getPropertiesDialogMap($documentType) as $fieldID => $arFieldProperties) {
            if (isset($arFieldProperties["Required"]) && $arFieldProperties["Required"] && empty($arTestProperties[$fieldID])) {
                $arErrors[] = array(
                    "code" => "emptyText",
                    "parameter" => $fieldID,
                    "message" => str_replace("#FIELD_NAME#", $arFieldProperties["Name"], GetMessage("MOVETOSTAGE_FIELD_NOT_SPECIFIED")),
                );
            }
        }

        return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
    }

    /**
     * User provided properties
     * 
     * @return array
     */
    private static function getPropertiesDialogMap()
    {

        $statuses = StatusTable::getList([
            "select" => ["STATUS_NAME" => "NAME"],
            "filter" => [
                "%=ENTITY_ID" => "DEAL_STAGE%"
            ],
            "group" => ["NAME"]
        ])->fetchAll();

        $arStages = array_column($statuses, "STATUS_NAME");



        return [
            "Deal" => [
                "Name" => GetMessage("MOVETOSTAGE_DEAL_FIELD_TITLE"),
                "FieldName" => "Deal",
                "Type" => FieldType::INT,
                "Required" => true
            ],
            "Stage" => [
                "Name" => GetMessage("MOVETOSTAGE_STAGE_FIELD_TITLE"),
                "FieldName" => "Stage",
                "Type" => FieldType::SELECT,
                "OPTIONS" => array_combine($arStages, $arStages),
                "Required" => true
            ],
            "EnableAutomation" => [
                "Name" => GetMessage("MOVETOSTAGE_AUTOMATION_FIELD_TITLE"),
                "FieldName" => "EnableAutomation",
                "Type" => FieldType::BOOL,
                "Required" => true,
                "Default" => "Y",
            ]
        ];
    }
}
