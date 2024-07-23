<?php
mb_internal_encoding('UTF-8');

function encodeToHex($string) {
    $encoded = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $encoded .= '\\x' . dechex(ord($string[$i]));
    }
    return $encoded;
}

function decodeFromHex($encoded) {
    // Remove leading backslashes and 'x'
    $encoded = str_replace('\\x', '', $encoded);
    // Split into array of hex values
    $hexArray = str_split($encoded, 2);
    $decoded = '';
    foreach ($hexArray as $hex) {
        $decoded .= chr(hexdec(str_replace('d','0d',str_replace('a','0a',$hex))));
    }
    return $decoded;
}

function hexToText($rr)
{
    $xx = '';
    for ($c = 0; $c < strlen($rr); $c += 2) {
        $chrs = $rr[$c] . $rr[$c + 1];
        $xx .= chr(hexdec(str_replace('d','0d',str_replace('a','0a',$chrs))));
    }
    return $xx;
}

function textToHex($string)
{
    $hex = '';
    for ($i = 0; $i < strlen($string); $i++)
        $hex .= dechex(ord($string[$i]));
    return $hex;
}

// function charcode_enc($string) {
//     $encoded = '';
//     $length = mb_strlen($string, 'UTF-8');
//     for ($i = 0; $i < $length; $i++) {
//         $char = mb_substr($string, $i, 1, 'UTF-8');
//         $encoded .= mb_ord($char) . '↱';
//     }
//     return rtrim($encoded,'↱');
// }

// function charcode_dec($encodedString) {
//     $decoded = '';
//     $codes = explode('↱', $encodedString);
//     foreach ($codes as $code) {
//         $decoded .= mb_chr($code);
//     }
//     return $decoded;
// }

function mb64_enc($string) {
    $utf8String = mb_convert_encoding($string, 'UTF-8');
    $encoded = base64_encode($utf8String);
    return $encoded;
}

function mb64_dec($encodedString) {
    $decoded = base64_decode($encodedString);
    $string = mb_convert_encoding($decoded, 'UTF-8', 'UTF-8');
    return $string;
}

function gzpress64_enc($string) {
    return base64_encode(gzcompress($string, 9));
}

function gzpress64_dec($compressedString) {
    return gzuncompress(base64_decode($compressedString), 9);
}

$input = '';
$output = '';
$output2 = '';
$operation = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['input'] ?? '';
    $operation = $_POST['operation'] ?? '';

    if ($operation === 'encode') {
        $output = encodeToHex($input);
        $output2 = textToHex($input);
        $output3 = mb64_enc($input);
        $output4 = base64_encode(gzdeflate($input));
        $output5 = gzpress64_enc($input);
        $output6 = password_hash($input, PASSWORD_DEFAULT);
        $output7 = md5($input);
    } elseif ($operation === 'decode') {
        $output = decodeFromHex($input);
        $output2 = hexToText($input);
        $output3 = mb64_dec($input);
        $output4 = gzinflate(base64_decode($input));
        $output5 = gzpress64_dec($input);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encode/Decode Hexadecimal</title>
    <script>
        function hexEnc(string) {
            const encoder = new TextEncoder();
            const utf8Array = encoder.encode(string);
            let hex = ``;
            for (let byte of utf8Array) {
                hex += byte.toString(16).padStart(2, `0`);
            }
            return hex;
        }

        function hexDec(hash) {
            let bytes = [];
            for (let i = 0; i < hash.length; i += 2) {
                bytes.push(parseInt(hash.substr(i, 2), 16));
            }
            const decoder = new TextDecoder();
            return decoder.decode(new Uint8Array(bytes));
        }

        function stringEnc(string) {
            let encoded = ``;
            for (let i = 0; i < string.length; i++) {
                encoded += string.charCodeAt(i) + `↳`;
            }
            return encoded.replace(/^↳+|↳+$/g, ``);
        }

        function stringDec(encodedString) {
            let decoded = ``;
            let codes = encodedString.split(`↳`);
            for (let i = 0; i < codes.length; i++) {
                decoded += String.fromCharCode(parseInt(codes[i]));
            }
            return decoded;
        }

        function copy(ele, target) {
            var copyText = document.getElementById(target);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            ele.innerHTML = 'Copied!';
            setTimeout(function() {
                ele.innerHTML = 'Copy';
            }, 1000);
        }
    </script>
</head>
<body>
    <h1>PHP : Encode/Decode <a href="obfuscatorv1.php">Obfuscator/Deobfuscator</a></h1>

    <form method="POST" action="">
        <label for="input">Input: <button onclick="document.getElementById('input').value = ''; event.preventDefault();">Clear</button></label><br>
        <textarea name="input" id="input" rows="10" style="width: calc(100% - 8px);"><?= htmlspecialchars($input) ?></textarea><br><br>

        <input type="submit" name="operation" value="encode">
        <input type="submit" name="operation" value="decode">
    </form>

    <h2>Output: <?= $operation ?></h2>
    <p style="margin-block-end: 5px;"><button onclick="copy(this,'output')">Copy</button> hexascii: <?= htmlspecialchars(strlen($output)) ?> char</p>
    <textarea rows="3" style="width: calc(100% - 8px); color: blue;" id="output"><?= htmlspecialchars($output) ?></textarea>
    <p style="margin-block-end: 5px;"><button onclick="copy(this,'output2')">Copy</button> hex: <?= htmlspecialchars(strlen($output2)) ?> char</p>
    <textarea rows="3" style="width: calc(100% - 8px); color: blue;" id="output2"><?= htmlspecialchars($output2) ?></textarea>
    <p style="margin-block-end: 5px;"><button onclick="copy(this,'output3')">Copy</button> mb_convert + base64: <?= htmlspecialchars(strlen($output3)) ?> char</p>
    <input id="output3" type="text" style="width: calc(100% - 8px); color: blue;" value="<?= htmlspecialchars($output3) ?>"/>
    <p style="margin-block-end: 5px;"><button onclick="copy(this,'output4')">Copy</button> gzflate + base64: <?= htmlspecialchars(strlen($output4)) ?> char</p>
    <input id="output4" type="text" style="width: calc(100% - 8px); color: blue;" value="<?= htmlspecialchars($output4) ?>"/>
    <p style="margin-block-end: 5px;"><button onclick="copy(this,'output5')">Copy</button> gzpress + base64: <?= htmlspecialchars(strlen($output5)) ?> char</p>
    <input id="output5" type="text" style="width: calc(100% - 8px); color: blue;" value="<?= htmlspecialchars($output5) ?>"/>
    <p style="margin-block-end: 5px;"><button onclick="copy(this,'output6')">Copy</button> bcrypt hash: <?= htmlspecialchars(strlen($output6)) ?> char</p>
    <input id="output6" type="text" style="width: calc(100% - 8px); color: blue;" value="<?= htmlspecialchars($output6) ?>"/>
    <p style="margin-block-end: 5px;"><button onclick="copy(this,'output7')">Copy</button> md5: <?= htmlspecialchars(strlen($output7)) ?> char</p>
    <input id="output7" type="text" style="width: calc(100% - 8px); color: blue;" value="<?= htmlspecialchars($output7) ?>"/>
</body>
</html>

