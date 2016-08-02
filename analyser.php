<?php
/**
 * Created by PhpStorm.
 * User: Lionel
 * Date: 29-07-15
 * Time: 08:15
 */

/**
 * Reporter
 * */

echo "********************************************************************************\n";
echo "*                                                                              *\n";
echo "*                               CBCP Analyser                                  *\n";
echo "*                                                                              *\n";
echo "********************************************************************************\n";

/** Apache runing ? + version*/

exec("sudo apachectl -v | head -n1| awk '{print $3}' | sed 's|Apache/||'", $output, $return);
preg_match("/^\d\.\d/", $output[0], $o);
$apacheVersion = $o[0];

exec("sudo service apache2 status",$output,$return);
if ($return != 0) {
    echo colorize("Apache not running : ", "FAILURE");
    echo $output . "\n";
    exit(1);
}
else{
    exec("sudo apachectl -v | head -n1| awk '{print $3}' | sed 's|Apache/||'", $output, $return);
    preg_match("/^\d\.\d/", $output[0], $o);
    $apacheVersion = $o[0];
    if ($apacheVersion != "2.4" && $apacheVersion != "2.2"){
        echo colorize("Unknow apache version : $apacheVersion", "FAILURE");
    }

    echo colorize("Apache $apacheVersion is running : \n", "SUCCESS");
}

/** Virtual Document Root détection */

exec("cat /etc/apache2/sites-available/default | grep VirtualDocumentRoot",$output,$return);
if ($return != 1)
    echo colorize("* Virtual Vhost Mode\n", "WARNING");
else
    echo colorize("* Vhost Mode\n", "SUCCESS");

unset($output);

exec("sudo apachectl -S |grep namevhost | wc -l",$output,$return);

echo colorize("* $output[0] vhosts actif/\n", "SUCCESS");




function colorize($text, $status)
{
    $out = "";
    switch ($status) {
        case "SUCCESS":
            $out = "[32m"; //Green background
            break;
        case "FAILURE":
            $out = "[31m"; //Red background
            break;
        case "WARNING":
            $out = "[33m"; //Yellow background
            break;
        case "NOTE":
            $out = "[34m"; //Blue background
            break;
        default:
            throw new Exception("Invalid status: " . $status);
    }
    return chr(27) . "$out" . "$text" . chr(27) . "[0m";
}

function csvExport(array $vhosts, $fileName)
{
    foreach ($vhosts as $key => $vhost) {
        $lines[$key] = $vhost['server_name'] . ';';
        if (isset($vhost['aliases'])) {
            $aliases = implode('|', $vhost['aliases']);
            $lines[$key] .= $aliases . ';';
        } else
            $lines[$key] .= ";";
        if (isset($vhost['framework'])) {
            $lines[$key] .= $vhost['framework'] . ';';
        } else
            $lines[$key] .= ";";
    }

    $fileData = implode("\n", $lines);
    if (file_exists($fileName))
        unlink($fileName);
    file_put_contents($fileName, $fileData);
}

function csvImport($file)
{
    $lines = file($file);
    foreach ($lines as $key => $line) {
        $line = explode(";", $line);
        $line = preg_replace("/\n/", '', $line);
        $vhosts[$key]['server_name'] = $line[0];
        if ($line[1])
            $vhosts[$key]['aliases'] = explode('|', $line[1]);
        if ($line[2])
            $vhosts[$key]['framework'] = $line[2];
    }
    return $vhosts;
}

function get_framwork($vhosts)
{
    // vérifier et detecter le vhost avec le DocumentRootccccccclvfrlgfbvrvjbcltjrvglhicujfhbgelbvilf

    foreach ($vhosts as $key => $vhost) {
        if (is_wordpress($vhost['server_name']))
            $vhosts[$key]['framework'] = is_wordpress($vhost['server_name']);
        if (is_drupal($vhost['server_name']))
            $vhosts[$key]['framework'] = is_drupal($vhost['server_name']);
        if (is_joomla($vhost['server_name']))
            $vhosts[$key]['framework'] = is_joomla($vhost['server_name']);
    }
    return $vhosts;
}

function is_wordpress($serverName)
{

    if (file_exists('/var/www/' . $serverName . '/wp-config.php'))
        return 'wordpress';
    else
        return false;
}

function is_drupal($serverName)
{
    if (file_exists('/var/www/' . $serverName . '/settings.php'))                //TODO
        return 'drupal';
    else
        return false;
}

function is_joomla($serverName)
{
    if (file_exists('/var/www/' . $serverName . '/configuration.php'))               //TODO
        return 'joomla';
    else
        return false;
}

