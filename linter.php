#!/usr/bin/php
<?php
$linterRegex = array(
    // American              British
    "/\s(color)/i"        => "colour",
    "/\s(gray)/i"         => "grey",
    "/\s(center)/i"       => "centre",
    "/\s(fiber)/i"        => "fibre",
    "/\s(liter)/i"        => "litre",
    "/\s(theater)/i"      => "theatre",
    "/\s(flavor)/i"       => "flavour",
    "/\s(humor)/i"        => "humour",
    "/\s(labor)/i"        => "labour",
    "/\s(neighbor)/i"     => "neighbour",
    "/\s(apologize)/i"    => "apologise",
    "/\s(organize)/i"     => "organise",
    "/\s(recognize)/i"    => "recognise",
    "/\s(analyze)/i"      => "analyse",
    "/\s(breathalyze)/i"  => "breathalyse",
    "/\s(paralyze)/i"     => "paralyse",
    "/\s(defense)/i"      => "defence",
    "/\s(license)/i"      => "licence",
    "/\s(offense)/i"      => "offence",
    "/\s(pretence)/i"     => "pretense",
    "/\s(analog)/i"       => "analogue",
    "/\s(catalog)/i"      => "catalogue",
    "/\s(dialog)\s/i"     => "dialogue",
    "/\s(licorice)/i"     => "liquorice",
    "/\s(?!.*program)(?=.*me)^(\w+)$/" => "programme",
    "/\s(maneuver)/i"     => "manoeuvre",
    "/\s(plow)/i"         => "plough",
    "/\s(sulfur)/i"       => "sulphur",
    "/\s(specialty)/i"    => "speciality",
    "/\s(naught)/i"       => "nought",
    "/\s(skeptic)/i"      => "sceptic",
    "/\s(vial)/i"         => "phial",
    "/\s(whiskey)/i"      => "whisky",
    "/\s(artifacts)/i"    => "artefacts",
    "/\s(behavior)/i"     => "behaviour",


    // Scrum
    "/(scrum master|Scrum master|scrum Master)/"                         => "Scrum Master",
    "/(product owner|Product owner|product Owner)/"                      => "Product Owner",
    "/(sprint planning|Sprint planning|sprint Planning)/"                => "Sprint Planning",
    "/(daily scrum|Daily scrum|daily Scrum)/"                            => "Daily Scrum",
    "/(sprint backlog|Sprint backlog|sprint Backlog)/"                   => "Sprint Backlog",
    "/(product backlog|Product backlog|product Backlog)/"                => "Product Backlog",
    "/(sprint review|Sprint review|sprint Review)/"                      => "Sprint Review",
    "/(sprint retrospective|Sprint retrospective|sprint Retrospective)/" => "Sprint Retrospective",

    "/\s(on figure)/i"        => "in~\\autoref{",
    '/\s(on \\\\autoref)/i'     => "in~\\autoref{",
    "/\s(in section)/i"       => "in~\\autoref",
    "/\s(in chapter)/i"       => "in~\\autoref",
    "/\s(in subsection)/i"    => "in~\\autoref",
    "/\s(in part)/i"          => "in~\\autoref",
    "/\s(on table)/i"         => "in~\\autoref",
    "/\s(in table)/i"         => "in~\\autoref",
    '/(in\s\\\\ref)/i'         => "in~\\autoref",
    '/(in\s\\\\autoref)/i'     => "in~\\autoref",
    "/\s(can't)/i"              => "can not",
    "/\s(shouldn't)/i"          => "should not",
    "/\s(isn't)/i"              => "is not",
    "/\s(don't)/i"              => "do not",
    "/(i2c)/i"                => "I\$^2\$C",
    "/(i²c)/i"                => "I\$^2\$C",
    '/(\$i\^2c\$)/i'         => "I\$^2\$C",
    '/(KHz)/'         => "kHz",
    '/(khz)/'         => "kHz",
    '/(hz)/'         => "Hz",
    '/(HZ)/'         => "Hz",
    '/(ESP-32)/i' => "ESP32",
    '/(MPU6050)/i' => "MPU-6050",

    // Other words
    "/\s(google)/"              => "Google",
    "/\s(iot|iOT|ioT|Iot|IOt)/" => "IoT",
    '/(mosquitto)/' => "Mosquitto",

    // Extra
    "/(wifi|Wifi|wi-fi|Wi-fi|WiFi|wi-Fi|WI-FI|WIFI)/"   => "Wi-Fi",
    "/\s(github|Github|gitHub)/"                        => "GitHub",
    "/(git hub)/i"                                      => "GitHub",
    "/(apollo)/i"                                       => "DAS IS NICHT SO GUT",
);

$formattingRules = [
    // Formatting
    "/((\\\\caption){((.)*(?<!\.))}\\n)/i" =>  "Captions must end with a period (.)",
];

$ignoreFiles = [
    'preamb.tex',
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
        foreach ($files as $file) {
            $elements = explode('/', $file);
            $filename = end($elements);
            if (!in_array($filename, $ignoreFiles)) {
                CheckFile($file, $linter, $output);
            }
        }
    } else {
        CheckFile($files, $linter, $output);
    }
    return array_unique($output);
}

function CheckFile($file, $linter, &$output = array())
{
    global $ignoreString;
    global $formattingRules;
    $ignore = false;
    $fh = fopen($file, 'rb');
    $line_nr = 1;

    while ($line = fgets($fh)) {
        $line_no_tab = str_replace("\t", "", $line);
        $line_no_tab = preg_replace("/^\s+/", '', $line_no_tab);
        // Regular linter rules, this is only run on text and not latex commands.
        foreach ($linter as $regex => $replacement) {
            if ($line == $ignoreString) {
                $ignore = true;
            } else {
                if ($line_no_tab != "" && $ignore == false && $line_no_tab[0] != "%" && $line_no_tab[0] != "\\") {
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
