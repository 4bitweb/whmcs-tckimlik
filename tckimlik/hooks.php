<?php

require_once('helpers.php');

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

add_hook('ClientDetailsValidation', 1, function ($vars)
{
    $error = [];
    // Get the module config
    $conf = get_module_conf();
    $tc_field = $conf["tc_field"];
    $birthyear_field = $conf["birthyear_field"];
    $country_check = $conf["only_turkish"];

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
