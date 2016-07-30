<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// use WHMCS (Laravel) db functions
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 *
 * Module configuration
 *
 */

/**
 *
 * Get all custom field names and ids from database
 *
 * @param none
 *
 * @return array field names concat with ids - format: "id|name"
 */

function get_custom_fields()
{
    $field_names = Capsule::table('tblcustomfields')->select('fieldname', 'id')
                                                    ->get();
    $retVal = [];
    foreach ($field_names as $value) {
        array_push($retVal, $value->id . "|" . $value->fieldname);
    }
    return $retVal;
}

/**
 *
 * Return a CSV string from a PHP array
 * Taken from https://gist.github.com/johanmeiring/2894568
 *
 * @param array $csv_array An array of values
 *
 * @return string Comma seperated values of custom field names
 */

if (!function_exists('str_putcsv')) {
    function str_putcsv($input, $delimiter = ',', $enclosure = "'") {
        $fp = fopen('php://temp', 'r+b');
        fputcsv($fp, $input, $delimiter, $enclosure);
        rewind($fp);
        $data = rtrim(stream_get_contents($fp), "\n");
        fclose($fp);
        return $data;
    }
}

/**
 *
 * Get modules configuration fields for hooks
 *
 * @param none
 *
 * @return array Module configuration fields
 */

function get_module_conf()
{
    $retVal = [];
    $exclude_fields = array('version', 'access',);
    $results = Capsule::table('tbladdonmodules')->select('setting', 'value')
                                            ->where('module', 'tckimlik')
                                            ->whereNotIn('setting', $exclude_fields)
                                            ->get();
    foreach ($results as $row)
    {
        list($value, $rest) = explode("|", $row->value , 2);
        $retVal[$row->setting] = str_replace("'", "", $value);
    }
    return $retVal;
}

/**
 *
 * strtoupper function with Turkish character support. Because Turkish "i" char
 * is "İ" in upper case and mb_strtoupper doesn't know the locale and outputs "I"
 *
 * @params $str str Turkish string to convert case
 *
 * @return str
 */

function strtouppertr($str)
{
    return mb_convert_case(str_replace('i', 'İ', $str), MB_CASE_UPPER, "UTF-8");
}

/**
 * Validate Turkish Idenfication Number from tckimlik.nvi.gov.tr
 *
 * @param $tc int Turkish Identification Number to validate
 * @param $year int Birth year of person
 * @param $name str Name of person
 * @param $surname str Surname of person
 *
 * @return boolean
 */

function validate_tc($tc, $year, $name, $surname)
{
    $curl = curl_init();
    $error = [];

    // Convert name and surname to uppercase and year to an int value
    $name = strtouppertr($name);
    $surname = strtouppertr($surname);
    $year = intval($year);

    $request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
    <soap12:Body>
        <TCKimlikNoDogrula xmlns="http://tckimlik.nvi.gov.tr/WS">
            <TCKimlikNo>' . $tc . '</TCKimlikNo>
            <Ad>' . $name . '</Ad>
            <Soyad>' . $surname . '</Soyad>
            <DogumYili>' . $year . '</DogumYili>
        </TCKimlikNoDogrula>
    </soap12:Body>
</soap12:Envelope>';

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://tckimlik.nvi.gov.tr/Service/KPSPublic.asmx",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 10,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $request,
      CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/soap+xml; charset=utf-8",
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($response)
    {
        preg_match('#<TCKimlikNoDogrulaResult>(.*?)</TCKimlikNoDogrulaResult>#', $response, $result);
        if ($result[1] == "true")
        {
            return true;
        } elseif ($result[1] == "false") {
            $error[] = "TC Kimlik numaraniz geçersiz.";
        } else {
            $error[] = "TC Kimlik numaranız geçersiz.";
        }
    }

    if ($err)
    {
        $error[] = "Sunucuyla bağlantı kurulamıyor. Lütfen daha sonra tekrar deneyiniz.";
    }

    return $error;
}
