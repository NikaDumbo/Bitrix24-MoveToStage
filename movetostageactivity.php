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
                    "message" => str_replace("#FIELD_NAME#", $arFieldProperties["Name"], GetMessage("JC_GWC_FIELD_NOT_SPECIFIED")),
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
    private static function getPropertiesDialogMap($documentType = array())
    {
        $arTemplates = array();
        if (is_array($documentType) && !empty($documentType)) {
            foreach (CBPDocument::GetWorkflowTemplatesForDocumentType($documentType) as $arTemplate) {
                $arTemplates[$arTemplate["ID"]] = $arTemplate["NAME"];
            }
        }

        return [
            "Template" => [
                "Name" => GetMessage("JC_GWC_TEMPLATES_FIELD_TITLE"),
                "FieldName" => "Template",
                "Type" => FieldType::SELECT,
                "OPTIONS" => $arTemplates,
                "Required" => true
            ],
            "Template" => [
                "Name" => GetMessage("JC_GWC_TEMPLATES_FIELD_TITLE"),
                "FieldName" => "Template",
                "Type" => FieldType::SELECT,
                "OPTIONS" => $arTemplates,
                "Required" => true
            ],
            "ExcludeCurrent" => [
                "Name" => GetMessage("JC_GWC_EXCLUDE_CURRENT_FIELD_TITLE"),
                "FieldName" => "ExcludeCurrent",
                "Type" => FieldType::BOOL,
                "Required" => true,
                "Default" => "N",
            ]
        ];
    }
}
