<?php

// Author: Jesse Campbell
// Date: 2014-09-14
// Website: http://www.3dpartprice.com, http://www.jbcse.com
// Tested with PHP 5.5.9 on Windows 7 & XAMPP
// Version: 0.1.4

if (stripos($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], "jbcse.com/3dpartpricelib") !== FALSE){
    echo $_SERVER['REQUEST_URI'];
    header("HTTP/1.1 301 Moved Permanently"); 
    header("Location: http://www.3dpartprice.com");
}

define("STL_UPLOAD_PATH", 'c:/xampp/htdocs/stlUploads/');
define("GCODE_OUTPUT_PATH", 'c:/xampp/htdocs/gCodeOutput/');
?>

<html>
    <head>
        <title>3DPartPrice - Price Calculator for 3D Printing</title>
        <style>body{padding: 20px; font-family: arial;}.slicer{padding: 10px; margin-bottom: 10px; border: solid 1px lightgray;}pre{white-space: pre-wrap;}img{width: 48px; height: 48px; padding-right: 10px;}td{font-size: 12px;}</style>
        <link rel="shortcut icon" href="http://www.3dpartprice.com/favicons/price.ico" type="image/x-icon">
        <link rel="icon" href="http://www.3dpartprice.com/favicons/price.ico" type="image/x-icon">
    </head>
<body>
    <h1>Price Calculator for 3D Printing</h1>

<?php
    $uploadSuccessful = true;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0){       
        $displayMaxSize = ini_get('post_max_size');
 
        switch (substr($displayMaxSize,-1)){
            case 'G':
                $displayMaxSize = $displayMaxSize * 1024;
            case 'M':
                $displayMaxSize = $displayMaxSize * 1024;
            case 'K':
                $displayMaxSize = $displayMaxSize * 1024;
        }
         
        echo '<p>Posted data is too large. '.$_SERVER['CONTENT_LENGTH'].' bytes exceeds the maximum size of '.$displayMaxSize.' bytes.</p>';
        $uploadSuccessful = false;
    }
    else if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['stlFile']['size'] == 0){
        echo '<p>Error: STL file not chosen</p>';
        $uploadSuccessful = false;
    }
    
   if ($_SERVER['REQUEST_METHOD'] != 'POST'){
 ?>
    
    <form method="POST" enctype="multipart/form-data">
    
    <table style='border: solid 1px lightgray; padding: 10px;'>
        <tr><td>STL File:</td><td><input type="file" name="stlFile" accept="stl">(<a href="http://www.jbcse.com/downloads/squished-football.stl">sample file</a>)</td></tr>
        <tr><td>Layer Height: </td><td><input name="layerHeight" value='0.50'> (min 0.1mm, max 1.2mm)</td></tr>
        <tr><td>Infill Percentage: </td><td><input name="infillPercentage" value='50'> (min 0, max 100)</td></tr>
        <tr><td>Print Speed: </td><td><input name="printSpeed" value='30'> (millimeters/sec, min 1, max 300)</td></tr>
        <tr><td>Price per hour: </td><td><input name="pricePerHour" value='2.00'> ($USD/hour)</td></tr>
        <tr><td>Price per gram: </td><td><input name="pricePerGram" value='0.25'> ($USD/gram)</td></tr>
        <tr><td>Material: </td><td><input type='radio' name="material" value='PLA' checked> PLA, <input type='radio' name="material" value='ABS'>ABS</td></tr>
        <tr><td>Slicer: </td><td>
            <input type="checkbox" name="slicer[]" value='Cura'> CuraEngine,
            <input type="checkbox" name="slicer[]" value='KISSlicer'> KISSlicer, 
            <input type="checkbox" name="slicer[]" value='slic3r'> slic3r
        </td></tr>
        <tr><td></td><td><input type='submit'></td></tr>
    </table>
</div>
</form>

<?php
   } 
   
  if ($_SERVER['REQUEST_METHOD'] == 'POST' && $uploadSuccessful == true){
      
      set_time_limit(60);
      require_once("3dPartPriceLib.php");
      
      if (file_exists(STL_UPLOAD_PATH) == FALSE)
        mkdir(STL_UPLOAD_PATH, 777);
      
      if (file_exists(GCODE_OUTPUT_PATH) == FALSE)
        mkdir(GCODE_OUTPUT_PATH, 777);
      
      $escapedFilename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $_FILES['stlFile']['name']);
      move_uploaded_file($_FILES['stlFile']['tmp_name'],STL_UPLOAD_PATH.$escapedFilename);
      
      $slicer = new Slicer();
      $slicer->stlFile = STL_UPLOAD_PATH.$escapedFilename;
      $slicer->gCodeFile = GCODE_OUTPUT_PATH.$escapedFilename.'.gcode';
      
      $slicer->layerHeight = preg_replace('/[^0-9\.]/', '',$_POST['layerHeight']);
      $slicer->infillPercentage = preg_replace('/[^0-9\.]/', '',$_POST['infillPercentage']);
      $slicer->printSpeed = preg_replace('/[^0-9\.]/', '',$_POST['printSpeed']);
      $slicer->pricePerHour = preg_replace('/[^0-9\.]/', '',$_POST['pricePerHour']);
      $slicer->pricePerGram = preg_replace('/[^0-9\.]/', '',$_POST['pricePerGram']);
      $slicer->material = preg_replace('/[^A-Za-z]/', '', $_POST['material']);
      
      if (isset($_POST['slicer']) == false || count($_POST['slicer']) == 0)
        echo "<p>Error: Choose at least one slicer</p>";
      else
         foreach($_POST['slicer'] as $slicerName){
             echo "<div class='slicer'><img src='/3dpartpricelib/$slicerName.png' style='float: left;'><h3 style='padding: 10px; border-bottom: solid 1px lightgray; margin-top: 0px;'>".preg_replace('/[^A-Za-z0-9]/', '', $slicerName)."</h3>";
             $start = microtime(true);
             $results = $slicer->slice(preg_replace('/[^A-Za-z0-9]/', '', $slicerName));
             $results['sliceTimeSeconds'] = number_format(microtime(true)-$start,2);
             if (isset($results["error"]) == false)
             echo "<table><tr><td><img src='/3dpartpricelib/dollar.png'></td><td><font style='font-size: 18px; font-weight: bold;'>\${$results["estimatedCostUSD"]} USD</font><br><font style='color: gray;'>({$results["estimatedCostUSDCalculation"]})</font></td></tr>
             <tr><td><img src='/3dpartpricelib/stopwatch.png'></td><td>{$results["formattedPrintTime"]}<br><font style='color: gray;'>({$results["printTimeSeconds"]} seconds)</font></td></tr>
             <tr><td><img src='/3dpartpricelib/scale.jpg'></td><td>{$results["filamentUsedGrams"]} grams</td></tr>
             </table></div>";
                else
             echo "<pre>".print_r($results,true)."</pre></div>";
         }
  }
  
?>

<h4>Source Code</h4>
<ul>
<li><a href="/3dpartpricelib/3dpartpricelib-0.1.4.zip">Version 0.1.4 - September 14, 2014</a> Added client API to allow your web server to use my web server for slicing</li>
<li><a href="/3dpartpricelib/3dpartpricelib-0.1.3.zip">Version 0.1.3 - June 09, 2014</a> Automated PHP script to kill stuck slicers with something like threading, PHP cURLs the script and closes the connection. The URL keeps running after disconnect and a process manager kills the stuck slicers</li>
<li><a href="/3dpartpricelib/3dpartpricelib-0.1.2.zip">Version 0.1.2 - June 06, 2014</a> More security fixes, added PHP script to kill stuck slicers, HTML interface/images added</li>
<li><a href="/3dpartpricelib/3dpartpricelib-0.1.1.zip">Version 0.1.1 - June 04, 2014</a> Command-line injection security flaw fixed</li>
<li><a href="/3dpartpricelib/3dpartpricelib-0.1.zip">Version 0.1 - June 03, 2014 </a></li>
</ul>


<h4>Programs used</h4>
<ul>
    <li>STL to Gcode converters (slicers)</li>
    <ul>
    <li><a href="http://kisslicer.com/">KISSlicer (KISSlicer.exe input.stl -o output.gcode)</a></li>
    <li><a href="https://github.com/Ultimaker/CuraEngine">CuraEngine (CuraEngine.exe -v -o output.gcode input.stl)</a></li>
    <li><a href="http://slic3r.org/">slic3r (slic3r.exe -o output.gcode input.stl)</a></li>
    </ul>
    <li>Gcode analyzers</li>
    <ul>
    <li><a href="http://www.dietzm.de/gcodesim/down.html?GCodeInfo.jar">GCodeInfoV98.jar (java -jar GCodeInfoV98.jar m output.gcode 2>&1)</a></li>
    </ul>
</ul>

<h4>Cross-Reference Tools</h4>
<ul>
    <li><a href="http://gcode.ws/">gCode Viewer</a></li>
    <li><a href="https://github.com/Ultimaker/CuraEngine/blob/master/src/settings.cpp">CuraEngine Settings</a></li>
    <li><a href="http://lazarsoft.info/objstl/#">STL Viewer</a></li>
    <li><a href="http://orion.math.iastate.edu/burkardt/g_src/ivcon/ivcon.html">ivcon.exe, 3D model format converter</a></li>
    <li><a href="http://www.cs.princeton.edu/~min/meshconv/">meshconv.exe, 3D model format converter</a></li>
</ul>

<hr>
<?php
    
function secondsTohuman($ss) {
    $s = $ss%60;
    $m = floor(($ss%3600)/60);
    $h = floor(($ss%86400)/3600);
    $d = floor(($ss%2592000)/86400);
    $M = floor($ss/2592000);

    $output = '';
    if ($M > 0)
        $output .= "$M months, ";
    if ($d > 0)
        $output .= "$d days, ";
    if ($h > 0)
        $output .= "$h hours, ";
    if ($m > 0)
        $output .= "$m minutes, ";
    if ($s > 0)
        $output .= "$s seconds";

    return $output;
}
    
?>

Last updated: <?php echo secondsTohuman(time()-filemtime(__FILE__))?> ago, Files uploaded: <?php echo count(scandir(STL_UPLOAD_PATH))-3; ?>, Author: J. Campbell, Website: <a href='http://www.jbcse.com'>http://www.jbcse.com</a>, Email: <script>document.write("jcamp"+"@"+"gmx"+"."+"com");</script>
</body>
</html>