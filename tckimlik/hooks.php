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

        if ($validation !== true)
        {
            return $validation;
        }
    }
});

add_hook('CustomFieldSave', 1, function($vars) use ($tc_field, $admin_user)
{
    //Check that the fieldid is one you wish to override
    if ($vars['fieldid'] == $tc_field) {
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
    if ($vars['fieldid'] == $tc_field) {
        $str["password2"] = $vars["value"];
        $command = "decryptpassword";
        $result = localAPI($command, $str, $admin_user);
        return array('value' => $result["password"]);
    }
    return array('value' => $vars["value"]);
});
