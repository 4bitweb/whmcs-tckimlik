<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once('helpers.php');

function tckimlik_config() {
    $db_field_names = str_putcsv(get_custom_fields());
    $configarray = array(
    "name" => "TC Kimlik No Dogrulama",
    "description" => "An addon for Turkish Identification Number validation",
    "version" => "1.0.3",
    "author" => "4-bit Developers",
        "fields" => array(
            "tc_field" => array(
                "FriendlyName" => "TC Kimlik Özel Alanı",
                "Type" => "dropdown",
                "Options" => $db_field_names,
                "Description" => "Özel alanlarınız arasından TC Kimlik için olanı seçin",
            ),
            "birthyear_field" => array(
                "FriendlyName" => "Doğum yılı alanı",
                "Type" => "dropdown",
                "Options" => $db_field_names,
                "Description" => "Özel alanlarınız arasından doğum yılı için olanı seçin",
            ),
            "only_turkish" => array(
                "FriendlyName" => "Ülke kontrolü",
                "Type" => "yesno",
                "Size" => "25",
                "Description" => "Yalnızca Türkiye adresli kullanıcılar için geçerli olsun",
            ),
            "whmcs_admin_user" => array(
                "FriendlyName" => "Admin kullanıcı adı",
                "Type" => "text",
                "Size" => 25,
                "Description" => "WHMCS Admin kullanıcı adı (şifreleme için kullanılır)",
            ),
        )
    );
    return $configarray;
}
