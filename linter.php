#!/usr/bin/php
<?php
require_once "vendor/autoload.php";


$debug = true;

$linterRegex = array(
    // American              British
    "/\s(color)/i" => "colour",
    "/\s(gray)/i" => "grey",
    "/\s(center)/i" => "centre",
    "/\s(fiber)/i" => "fibre",
    "/\s(liter)/i" => "litre",
    "/\s(theater)/i" => "theatre",
    "/\s(flavor)/i" => "flavour",
    "/\s(humor)/i" => "humour",
    "/\s(labor)/i" => "labour",
    "/\s(neighbor)/i" => "neighbour",
    "/\s(apologize)/i" => "apologise",
    "/\s(organize)/i" => "organise",
    "/\s(recognize)/i" => "recognise",
    "/\s(analyze)/i" => "analyse",
    "/\s(breathalyze)/i" => "breathalyse",
    "/\s(paralyze)/i" => "paralyse",
    "/\s(defense)/i" => "defence",
    "/\s(license)/i" => "licence",
    "/\s(offense)/i" => "offence",
    "/\s(pretence)/i" => "pretense",
    "/\s(analog)/i" => "analogue",
    "/\s(catalog)/i" => "catalogue",
    "/\s(dialog)\s/i" => "dialogue",
    "/\s(licorice)/i" => "liquorice",
    "/\s(?!.*program)(?=.*me)^(\w+)$/" => "programme",
    "/\s(maneuver)/i" => "manoeuvre",
    "/\s(plow)/i" => "plough",
    "/\s(sulfur)/i" => "sulphur",
    "/\s(specialty)/i" => "speciality",
    "/\s(naught)/i" => "nought",
    "/\s(skeptic)/i" => "sceptic",
    "/\s(vial)/i" => "phial",
    "/\s(whiskey)/i" => "whisky",

    // Scrum
    "/(scrum master|Scrum master|scrum Master)/" => "Scrum Master",
    "/(product owner|Product owner|product Owner)/" => "Product Owner",
    "/(sprint planning|Sprint planning|sprint Planning)/" => "Sprint Planning",
    "/(daily scrum|Daily scrum|daily Scrum)/" => "Daily Scrum",
    "/(sprint backlog|Sprint backlog|sprint Backlog)/" => "Sprint Backlog",
    "/(product backlog|Product backlog|product Backlog)/" => "Product Backlog",
    "/(sprint review|Sprint review|sprint Review)/" => "Sprint Review",
    "/(sprint retrospective|Sprint retrospective|sprint Retrospective)/" => "Sprint Retrospective",

    "/\s(on figure)/i" => "in~\\autoref{",
    '/\s(on \\\\autoref)/i' => "in~\\autoref{",
    "/\s(in section)/i" => "in~\\autoref",
    "/\s(in chapter)/i" => "in~\\autoref",
    "/\s(in subsection)/i" => "in~\\autoref",
    "/\s(in part)/i" => "in~\\autoref",
    "/\s(on table)/i" => "in~\\autoref",
    "/\s(in table)/i" => "in~\\autoref",
    '/(in\s\\\\ref)/i' => "in~\\autoref",
    '/(in\s\\\\autoref)/i' => "in~\\autoref",
    "/\s(can't)/i" => "can not",
    "/\s(shouldn't)/i" => "should not",
    "/\s(isn't)/i" => "is not",
    "/\s(don't)/i" => "do not",
    "/(i2c)/i" => "I\$^2\$C",
    "/(i²c)/i" => "I\$^2\$C",
    '/(\$i\^2c\$)/i' => "I\$^2\$C",
    '/(KHz)/' => "kHz",
    '/(khz)/' => "kHz",
    '/(hz)/' => "Hz",
    '/(HZ)/' => "Hz",
    '/(ESP-32)/i' => "ESP32",
    '/(MPU6050)/i' => "MPU-6050",

    // Other words
    "/\s(google)/" => "Google",
    "/\s(iot|iOT|ioT|Iot|IOt)/" => "IoT",
    '/(mosquitto)/' => "Mosquitto",

    // Extra
    "/(wifi|Wifi|wi-fi|Wi-fi|WiFi|wi-Fi|WI-FI|WIFI)/" => "Wi-Fi",
    "/\s(github|Github|gitHub)/" => "GitHub",
    "/(git hub)/i" => "GitHub",
    "/(apollo)/i" => "DAS IS NICHT SO GUT",
);

$formattingRules = [
    // Formatting
    "/((\\\\caption){((.)*(?<!\.))}\\n)/i" => "Captions must end with a period (.)",
];

$ignoreFiles = [
    'preamb.tex',
    'main.tex',
];

$ignoreString = "%IGNORE_LINE\n";


$path = getcwd() . "/";
if ($argc == "2") {
    $path = $argv[1];
}
function getDirContents($dir, &$results = array())
{
    if (is_file($dir)) {
        return $dir;
    }
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            if (stripos($path, ".tex") !== false) {
                $results[] = $path;
            }
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            //$results[] = $path;
        }
    }

    return $results;
}


function CheckAllFiles($files, $linter)
{
    global $ignoreFiles;
    $output = array();
    if (is_array($files)) {
        $file_count = count($files);
        $fileNr = 1;
        foreach ($files as $file) {
            $elements = explode('/', $file);
            $filename = end($elements);
            if (!in_array($filename, $ignoreFiles)) {
                CheckFile($file, $linter, $output, $fileNr, $file_count);
            }
            $fileNr++;

        }
    } else {
        CheckFile($files, $linter, $output, 1,1);
    }
    return array_unique($output);
}

function CheckFile($file, $linter, &$output = array(), $fileNr, $ofFile)
{
    global $ignoreString;
    global $formattingRules;
    global $count;
    global $debug;
    if ($debug) {
        echo "[$fileNr/$ofFile]Working on $file now\n";
    }
    $ignore = false;
    //$fh = fopen($file, 'rb');
    $originalContent = file_get_contents($file);
    $file_no_latex = detex($originalContent);
    $line_nr = 1;
    $file_content = "";
    foreach ($file_no_latex as $line) {
        $file_content .= $line . "\n";
        $line_no_tab = str_replace("\t", "", $line);
        $line_no_tab = preg_replace("/^\s+/", '', $line_no_tab);
        // Regular linter rules, this is only run on text and not latex commands.
        if ($line == $ignoreString) {
            $ignore = true;
        } else {
            if (!$ignore) {
                if ($line_no_tab != "" && $line_no_tab[0] != "%" && $line_no_tab[0] != "\\") {
                    $spellCheck = spellChecker($line);
                    $original = $spellCheck[0];
                    $suggestion = $spellCheck[1];
                    if ($original != $suggestion) {
                        $output[] = "$file, Line $line_nr [SPELL CHECK], '$original' should maybe be change to '$suggestion'";;
                    }
                }
                foreach ($linter as $regex => $replacement) {
                    if ($line_no_tab != "" && $line_no_tab[0] != "%" && $line_no_tab[0] != "\\") {
                        if (preg_match($regex, $line, $matches, PREG_OFFSET_CAPTURE)) {
                            foreach ($matches as $match) {
                                $val = "$file, Line $line_nr:$match[1], '$match[0]' should be '$replacement'";
                                if (!array_key_exists($val, $output)) {
                                    $output[] = $val;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Formatting rules that should be run on all lines, not just text
        foreach ($formattingRules as $regex => $replacement) {
            if ($line === $ignoreString) {
                $ignore = true;
            } else {
                if ($line_no_tab != "" && $ignore == false && $line_no_tab[0] != "%") {
                    if (preg_match($regex, $line, $matches, PREG_OFFSET_CAPTURE)) {
                        foreach ($matches as $match) {
                            $val = "$file, Line $line_nr, $replacement";
                            if (!array_key_exists($val, $output)) {
                                $output[] = $val;
                            }
                        }
                    }
                }
            }
        }

        $line_nr++;
    }
}

function detex($content)
{
    $contentNoTables = preg_replace("/(\\\\begin{table}).*(\\\\end{table})/mixs", '', $content);
    unlink("tempfile.latex.linter");
    file_put_contents("tempfile.latex.linter", $contentNoTables);
    exec("/opt/texlive/2020/bin/x86_64-linux/detex -nl tempfile.latex.linter", $file_no_latex);
    $file_no_latex = array_filter($file_no_latex);
    return $file_no_latex;
}

function spellChecker($text)
{
    $text = trim($text);
    $text_ori = explode(" ", $text);
    $text_corrected = [];
    $text = escapeshellarg($text);
    $hunSpellOutput = shell_exec("echo $text | /usr/bin/hunspell -d en_GB");
    $output_list = explode("\n", $hunSpellOutput);
    array_shift($output_list);
    $output_list = array_filter($output_list);
    $num = 0;
    $text_ori_len = count($text_ori);
    foreach ($output_list as $single) {
        if (strlen($single) > 0) {
            $correction = $single[0] === '&';
            if ($correction) {
                $corrections = explode(",", explode(":", $single)[1]);
                $text_corrected[] = $corrections[0];
            } else {
                    $text_corrected[] = array_pop($text_ori);
            }
        }
    }
    $output = [$text, implode(" ", array_flip($text_corrected))];
    return $output;
}

$count = 0;

$contents = getDirContents($path);
$output = CheckAllFiles($contents, $linterRegex);
foreach ($output as $error) {
    echo "$error\n";
}

echo count($output) . " warning(s) found!\n";
if (count($output) > 0) {
    exit(2);
} else {
    exit(0);
}
