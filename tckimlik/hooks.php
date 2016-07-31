<?php

require_once('helpers.php');

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Get the module config
$conf = get_module_conf();
$tc_field = $conf["tc_field"];
$birthyear_field = $conf["birthyear_field"];
$country_check = $conf["only_turkish"];
$admin_user = $conf["whmcs_admin_user"];

add_hook('ClientDetailsValidation', 1, function ($vars) use ($tc_field, $birthyear_field, $country_check)
{
    $error = [];

    // Only on registration
    if ($vars["SCRIPT_NAME"] != "/cart.php" || $vars["SCRIPT_NAME"] != "/register.php")
    {
        return;
    }

    // Get the custom fields from vars
    $form_tckimlik = $vars["customfield"][$tc_field];
    $form_birthyear = $vars["customfield"][$birthyear_field];

    if (($country_check == "on" && $vars["country"] == "TR") || $country_check == "")
    {
        if (is_null($form_tckimlik) || is_null($form_birthyear))
        {
            $error[] = "TC Kimlik Numaranız veya doğum tarihi alanını doldurmadınız.";
            return $error;
        }

        $validation = validate_tc($form_tckimlik, $form_birthyear, $vars["firstname"], $vars["lastname"]);
        logModuleCall('tckimlik','validation',array($form_tckimlik, $form_birthyear, $vars["firstname"], $vars["lastname"]), $validation, $validationn);

        if ($validation !== true)
        {
            return $validation;
        }
    }
});

add_hook('CustomFieldSave', 1, function($vars) use ($tc_field, $admin_user)
{
    //Check that the fieldid is one you wish to override
    if ($vars['fieldid'] == $tc_field)
    {
        $str["password2"] = $vars["value"];
        $command = "encryptpassword";
        $result = localAPI($command, $str, $admin_user);
        return array('value' => $result["password"]);
    }
    return array('value' => $vars["value"]);
});

add_hook('CustomFieldLoad', 1, function($vars) use ($tc_field, $admin_user)
{
    //Check that the fieldid is one you wish to override
    if ($vars['fieldid'] == $tc_field)
    {
        $str["password2"] = $vars["value"];
        $command = "decryptpassword";
        $result = localAPI($command, $str, $admin_user);
        return array('value' => $result["password"]);
    }
    return array('value' => $vars["value"]);
});

add_hook('ClientAreaPage', 99, function($vars) use ($tc_field, $admin_user)
{
    if ($vars["SCRIPT_NAME"] != "/viewinvoice.php")
    {
        return true;
    }
    $return_value = array();
    //Check if the fieldid is one you wish to override
    foreach ($vars["customfields"] as $key => $customfield) {
        if ($customfield["id"] == $tc_field)
        {
            $str["password2"] = $customfield["value"];
            $command = "decryptpassword";
            $result = localAPI($command, $str, $admin_user);
            if ($result["password"] != "")
            {
                $return_value = array("customfields" => (array($key => array("id" => $tc_field,
                                        "fieldname" => $customfield["fieldname"],
                                        "value" => $result["password"]))));
            } else {
                array_push($return_value, array("customfields" => array($customfield)));
            }
        } else {
            array_push($return_value, array("customfields" => array($customfield)));
        }
    }
    return $return_value;
});

// Ugly hack to disable custom inputs
add_hook('ClientAreaPage', 1, function($vars) use ($tc_field, $birthyear_field, $admin_user)
{
    // Not on registration
    if ($vars["SCRIPT_NAME"] == "/cart.php" || $vars["SCRIPT_NAME"] == "/register.php")
    {
        return true;
    }

    $return_value = array();
    foreach ($vars["customfields"] as $key => $customfield)
    {
        if ($customfield["id"] == $tc_field && $customfield["input"] != NULL)
        {
            $regex = '/\/>$/';
            $replace = 'disabled="disabled" />';
            $disabled_field = preg_replace($regex, $replace, $customfield["input"]);
            $return_value["customfields"][] = array(
                "id" => $birthyear_field,
                "textid" => $customfield["textid"],
                "name" => $customfield["name"],
                "description" => $customfield["description"],
                "type" => $customfield["type"],
                "input" => $disabled_field,
                "value" => $customfield["value"],
                "rawvalue" => $customfield["rawvalue"],
                "required" => $customfield["required"],
                "adminonly" => $customfield["adminonly"],
            );
        } elseif ($customfield["id"] == $birthyear_field && $customfield["input"] != NULL) {
            $regex = '/^<select/';
            $replace = '$0 disabled="disabled"';
            $disabled_field = preg_replace($regex, $replace, $customfield["input"]);
            $return_value["customfields"][] = array(
                "id" => $birthyear_field,
                "textid" => $customfield["textid"],
                "name" => $customfield["name"],
                "description" => $customfield["description"],
                "type" => $customfield["type"],
                "input" => $disabled_field,
                "value" => $customfield["value"],
                "rawvalue" => $customfield["rawvalue"],
                "required" => $customfield["required"],
                "adminonly" => $customfield["adminonly"],
            );
        } else {
            $return_value["customfields"][] = $customfield;
        }
    }
    return $return_value;
});
