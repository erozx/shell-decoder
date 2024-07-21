<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $action = $_POST['action'];
    $output = '';
    $steps = [];

    function obfuscateCode($code, $iterations, $mode)
    {
        $methods_key = [
            "eval(gzinflate(base64_decode('{code}')));",
            "eval(gzinflate(str_rot13(base64_decode('{code}'))));",
            "eval(gzinflate(base64_decode(str_rot13('{code}'))));",
            "eval(gzinflate(base64_decode(base64_decode(str_rot13('{code}')))));",
            "eval(gzuncompress(base64_decode('{code}')));",
            "eval(gzuncompress(str_rot13(base64_decode('{code}'))));",
            "eval(gzuncompress(base64_decode(str_rot13('{code}'))));",
            "eval(base64_decode('{code}'));",
            "eval(str_rot13(gzinflate(base64_decode('{code}'))));",
            "eval(gzinflate(base64_decode(strrev(str_rot13('{code}')))));",
            "eval(gzinflate(base64_decode(strrev('{code}'))));",
            "eval(gzinflate(base64_decode(str_rot13(strrev('{code}')))));",
            "eval(base64_decode(gzuncompress(base64_decode('{code}'))));",
            "eval(gzinflate(base64_decode(rawurldecode('{code}'))));",
            "eval(str_rot13(gzinflate(str_rot13(base64_decode('{code}')))));",

        ];

        $methods = [
            "eval(gzinflate(base64_decode('{code}')));" => function ($codes) {
                return base64_encode(gzdeflate($codes));
            },
            "eval(gzinflate(str_rot13(base64_decode('{code}'))));" => function ($codes) {
                return base64_encode(str_rot13(gzdeflate($codes)));
            },
            "eval(gzinflate(base64_decode(str_rot13('{code}'))));" => function ($codes) {
                return str_rot13(base64_encode(gzdeflate($codes)));
            },
            "eval(gzinflate(base64_decode(base64_decode(str_rot13('{code}')))));" => function ($codes) {
                return str_rot13(base64_encode(base64_encode(gzdeflate($codes))));
            },
            "eval(gzuncompress(base64_decode('{code}')));" => function ($codes) {
                return base64_encode(gzcompress($codes));
            },
            "eval(gzuncompress(str_rot13(base64_decode('{code}'))));" => function ($codes) {
                return base64_encode(str_rot13(gzcompress($codes)));
            },
            "eval(gzuncompress(base64_decode(str_rot13('{code}'))));" => function ($codes) {
                return str_rot13(base64_encode(gzcompress($codes)));
            },
            "eval(base64_decode('{code}'));" => function ($codes) {
                return base64_encode($codes);
            },
            "eval(str_rot13(gzinflate(base64_decode('{code}'))));" => function ($codes) {
                return base64_encode(gzdeflate(str_rot13($codes)));
            },
            "eval(gzinflate(base64_decode(strrev(str_rot13('{code}')))));" => function ($codes) {
                return str_rot13(strrev(base64_encode(gzdeflate($codes))));
            },
            "eval(gzinflate(base64_decode(strrev('{code}'))));" => function ($codes) {
                return strrev(base64_encode(gzdeflate($codes)));
            },
            "eval(gzinflate(base64_decode(str_rot13(strrev('{code}')))));" => function ($codes) {
                return strrev(str_rot13(base64_encode(gzdeflate($codes))));
            },
            "eval(base64_decode(gzuncompress(base64_decode('{code}'))));" => function ($codes) {
                return base64_encode(gzcompress(base64_encode($codes)));
            },
            "eval(gzinflate(base64_decode(rawurldecode('{code}'))));" => function ($codes) {
                return rawurlencode(base64_encode(gzdeflate($codes)));
            },
            "eval(str_rot13(gzinflate(str_rot13(base64_decode('{code}')))));" => function ($codes) {
                return base64_encode(str_rot13(gzdeflate(str_rot13($codes))));
            },

        ];

        $func = $methods_key;
        $obfuscatedCode = $code;
        for ($i = 0; $i < $iterations; $i++) {
            if ($mode == 'random') {
                shuffle($func);
            }

            foreach ($func as $decode) {
                $function = $methods[$decode];
                $obfuscate = $function($obfuscatedCode);
                $deobfuscate = str_replace('{code}', $obfuscate, $decode);
                $obfuscatedCode = $deobfuscate;
            }
        }

        return $obfuscatedCode;
    }

    function deobfuscateCode($code, &$steps)
    {
        // Define patterns to identify and replace nested functions
        $patternMap = [
            "eval(gzinflate(base64_decode('" => ")))",
            "eval(gzinflate(str_rot13(base64_decode('" => "))))",
            "eval(gzinflate(base64_decode(str_rot13('" => "))))",
            "eval(gzinflate(base64_decode(base64_decode(str_rot13('" => ")))))",
            "eval(gzuncompress(base64_decode('" => ")))",
            "eval(gzuncompress(str_rot13(base64_decode('" => "))))",
            "eval(gzuncompress(base64_decode(str_rot13('" => "))))",
            "eval(base64_decode('" => "))",
            "eval(str_rot13(gzinflate(base64_decode('" => "))))",
            "eval(gzinflate(base64_decode(strrev(str_rot13('" => ")))))",
            "eval(gzinflate(base64_decode(strrev('" => "))))",
            "eval(gzinflate(base64_decode(str_rot13(strrev('" => ")))))",
            "eval(base64_decode(gzuncompress(base64_decode('" => "))))",
            "eval(gzinflate(base64_decode(rawurldecode('" => "))))",
            "eval(str_rot13(gzinflate(str_rot13(base64_decode('" => ")))))",
        ];

        $stepCount = 0;

        LOOP:
        foreach ($patternMap as $startPattern => $endPattern) {
            if (str_contains($code, $startPattern)) {
                // Log the step
                $steps[] = sprintf('%03d', ++$stepCount) . " - $startPattern...'$endPattern";

                // Replace `eval` with `echo` and update the code with the decoded content
                $decodedContent = eval(str_replace('eval', 'return ', $code));
                $code = $decodedContent;
                goto LOOP;
            }
        }

        // Return the final deobfuscated code and steps
        return $code;
    }

    if ($action === 'obfuscate') {
        $iterations = (int)$_POST['iterations'];
        $mode = $_POST['mode'];
        $output = obfuscateCode($code, $iterations, $mode);
    } elseif ($action === 'deobfuscate') {
        $output = deobfuscateCode($code, $steps);
    }
} else {
    $code = '';
    $iterations = 1;
    $mode = 'sequential';
    $output = '';
    $steps = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Obfuscator and Deobfuscator</title>
    <script>
        function toggleInputs() {
            var action = document.getElementById('action').value;
            if (action === 'obfuscate') {
                document.getElementById('obfuscateOptions').style.display = 'block';
                document.getElementById('deobfuscateOutput').style.display = 'none';
            } else {
                document.getElementById('obfuscateOptions').style.display = 'none';
                document.getElementById('deobfuscateOutput').style.display = 'block';
            }
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

<body onload="toggleInputs()">
    <h1>PHP : <a href="encoderv1.php">Encode/Decode</a> Obfuscator/Deobfuscator</h1>
    <form method="post">
        <label for="code">PHP Code: <button onclick="document.getElementById('code').value = ''; event.preventDefault();">Clear</button></label><br>
        <textarea id="code" name="code" rows="10" style="width: calc(100% - 8px);"><?php echo htmlspecialchars($code); ?></textarea><br><br>

        <label for="action">Action:</label><br>
        <select id="action" name="action" onchange="toggleInputs()">
            <option value="obfuscate" <?php echo $action === 'obfuscate' ? 'selected' : ''; ?>>Obfuscate</option>
            <option value="deobfuscate" <?php echo $action === 'deobfuscate' ? 'selected' : ''; ?>>Deobfuscate</option>
        </select><br><br>

        <div id="obfuscateOptions" style="display: none;">
            <label for="iterations">Number of Iterations:</label><br>
            <input type="number" id="iterations" name="iterations" value="<?php echo htmlspecialchars((isset($iterations) ? $iterations : '1')); ?>"><br><br>

            <label for="mode">Mode:</label><br>
            <select id="mode" name="mode">
                <option value="sequential" <?php echo $mode == 'sequential' ? 'selected' : ''; ?>>Sequential</option>
                <option value="random" <?php echo $mode == 'random' ? 'selected' : ''; ?>>Random</option>
            </select><br><br>
        </div>

        <input type="submit" value="Process">
    </form>

    <h2>Output: <button onclick="copy(this,'output')">Copy</button></h2>
    <textarea rows="10" style="width: calc(100% - 8px); color: blue;" id="output"><?php echo htmlspecialchars($output); ?></textarea>
    <div id="deobfuscateOutput" style="display: none;">
        <h2>Deobfuscation Steps:</h2>
        <pre>
Number of decoded steps applied
=======================================================================
<?php echo count($steps); ?>

The PHP code is encoded by the following nested functions sequence
=======================================================================
<?php foreach ($steps as $step) : ?>
<?php echo $step; ?>

<?php endforeach; ?>
</pre>
    </div>

</body>

</html>