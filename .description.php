<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Main\Localization\Loc;

$arActivityDescription = [
    "NAME" => Loc::getMessage("MOVETOSTAGE_NAME"),
    "DESCRIPTION" => Loc::getMessage("MOVETOSTAGE_DESCRIPTION"),
    "TYPE" => "activity",
    "CLASS" => "movetostageactivity",
    "JSCLASS" => "BizProcActivity",
    "CATEGORY" => [
        "OWN_ID" => "customActivity",
        "OWN_NAME" => "Custom Activity",
    ]
];
