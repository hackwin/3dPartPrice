<?php require_once("config.php"); ?>

<?php if ($_SERVER['REQUEST_METHOD'] != "POST"){ ?>
<html>
<head>
<title></title>

<script>
function addAnotherFileUploadElement(){
    //document.getElementById('fileUploadElements').innerHTML += '<input type=file name=stlFiles[]>';
    var theDiv = document.getElementById("fileUploadElements");
    var newNode = document.createElement('span');      
    newNode.innerHTML = "<input type=file name=stlFiles[] multiple>";
    theDiv.appendChild(newNode);
}

window.onload = function(){
    var rad = document.customQuote.color;
    for(var i = 0; i < rad.length; i++) {
        rad[i].onclick = function() {
            document.getElementsByName("material")[0].value = this.className;
        };
    }
}

</script>

</head>
<body>
<form method="POST" enctype="multipart/form-data" name="customQuote">
STLs or zipped STL Files: <span id="fileUploadElements"><input type="file" name="stlFiles[]" multiple></span><a href="javascript:addAnotherFileUploadElement()">Add another</a><br>
<!-- Material: -->
<select name="material" style="display: none;">
<?php
    
    foreach(partPriceConfig::$materials as $key=>$val){
        echo "<option value=$key>$key";
        if ($val['fullName'] != null)
            echo " ({$val['fullName']})";
        echo "</option>";
    }
    
?>
</select><!--br-->
<style>
table#availableColors{
    background-image: url("dimension.png");
    border-spacing: 1px;
}

table#availableColors tr:nth-child(odd){ 
  border-top: gray 2px solid;  
}

table#availableColors td:first-child{
    background: white;
    text-align: right;
}
table#availableColors td{
    border: solid 1px darkgray;
    text-align: center;
    padding: 5px;
}

</style>
Available Materials/Colors:
<table id="availableColors">
<?php

    $colors = array();
    foreach(partPriceConfig::$materials as $material){
        foreach($material["colors"] as $color)
            if (in_array($color, $colors) == false)
                array_push($colors, $color);
    }

    $checkedFirst = false;
    
    foreach(partPriceConfig::$materials as $key => $material){
            
        echo "<tr><td>$key";
        if (partPriceConfig::$materials[$key]["fullName"] != null)
            echo " (".partPriceConfig::$materials[$key]["fullName"].")";
        echo ": </td>";
        
        
        foreach($colors as $color){
            if (in_array($color, $material["colors"])){
                echo "<td style='background: {$color};'><input type='radio' name='color' value='{$color}' class='".$key."' ";
                
                if ($checkedFirst == false){
                    echo "checked";
                    $checkedFirst = true;
                }
                    
                echo "></td>";
            }
            else
                echo "<td></td>";
        }
        echo "</tr>";
    }
?>

</table>

Layer Height: <input type="text" name="layerHeight" value="<?php echo partPriceConfig::$layerHeights["default"]["amount"] ?>">
(<?php echo partPriceConfig::$layerHeights["default"]["unit"];?>, 
min: <?php echo partPriceConfig::$layerHeights["min"]["amount"].partPriceConfig::$layerHeights["min"]["unit"] ?>, 
max: <?php echo partPriceConfig::$layerHeights["max"]["amount"].partPriceConfig::$layerHeights["max"]["unit"] ?>)
<br>
Infill Percentage: <input type="text" name="infillPercentage" value="30"> (0 to 100%)<br>
Support Removal: <input type="checkbox" name="supportRemoval"> (weight * <?php echo partPriceConfig::$addOns["supportRemovalMultiplier"] ?>)<br>
Vapor Polishing: <input type="checkbox" name="vaporPolishing"> (weight * <?php echo partPriceConfig::$addOns["vaporPolishingMultiplier"] ?>, eligible materials: <?php

$materialsVaporPolished = array();
foreach(partPriceConfig::$materials as $key => $value)
    if ($value["canBeVaporPolished"] == true)
        $materialsVaporPolished[] = $key;

echo implode("/",$materialsVaporPolished);
   
?>)<br>
Shipping: <input type="radio" name="shipping" value="pickup"> pickup (free), <input type="radio" name="shipping" value="delivery" checked> delivery (<?php echo '$'.number_format(partPriceConfig::$deliveryCosts["base"]["amount"],2).' '.partPriceConfig::$deliveryCosts["base"]["unit"].' + $'. number_format(partPriceConfig::$deliveryCosts["weightPrice"]["amount"],2).' '.partPriceConfig::$deliveryCosts["weightPrice"]["unit"] ?>)<br>
Rush Printing: <input type="checkbox" name="rushPrinting"><br>

<input type="submit">
</form>

</body>
</html>
<?php } ?>

<?php

function buildMultipartPost($fields, $files, $boundary=""){
    
    //echo "<pre>".print_r($fields,true)."</pre>";
    //echo "<pre>".print_r($files)."</pre>";
    
    $output = "";
    $disallowedChars = array("\0", "\"", "\r", "\n");
    foreach($fields as $key => $value){
        $key = str_replace($disallowedChars, "_", $key);
        $value = str_replace($disallowedChars, "_", $value);
        $output .= $boundary . "\n" . "Content-Disposition: form-data; name=\"".$key."\"\n\n".$value."\n";
    }
    
    foreach($files as $key => $value){
        $key = str_replace($disallowedChars, "_", $key);
        
        if (is_array($value["name"]) == false){
            $value['name'] = str_replace($disallowedChars, "_", $value['name']);
            $value['type'] = str_replace($disallowedChars, "_", $value['type']);
            $output .= $boundary . "\n" . "Content-Disposition: form-data; name=\"".$key."\"; filename=\"".$value['name']."\"\n" . "Content-Type: ".$value['type']."\n\n".file_get_contents($value['tmp_name'])."\n";
        }
        else{
            for($i=0; $i<count($value["name"]); $i++){
                if ($value["error"][$i] != 0)
                    continue;
                $value['name'][$i] = str_replace($disallowedChars, "_", $value['name'][$i]);
                $value['type'][$i] = str_replace($disallowedChars, "_", $value['type'][$i]);
                $output .= $boundary . "\n" . "Content-Disposition: form-data; name=\"".$key."[]\"; filename=\"".$value['name'][$i]."\"\n" . "Content-Type: ".$value['type'][$i]."\n\n".file_get_contents($value['tmp_name'][$i])."\n";
            }
        }
    }
    
    return $output.$boundary."--";
}

if ($_SERVER['REQUEST_METHOD'] == "POST"){
    
    //print_r($_FILES);
    
    $boundary = "------WebKitFormBoundary".substr(md5(microtime(true)),0,16);
    
    $ch = curl_init("http://api.3dpartprice.com");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: multipart/form-data; boundary=".substr($boundary,2)));
    curl_setopt($ch, CURLOPT_POST, true);
    $_POST["configFile"] = urlencode(base64_encode(serialize(get_class_vars("partPriceConfig"))));
    $_POST["density"] = partPriceConfig::$materials[$_POST["material"]]["density"]["amount"];
    curl_setopt($ch, CURLOPT_POSTFIELDS, buildMultipartPost($_POST, $_FILES, $boundary));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode != 200){
        echo "http code: ".$httpCode.", error occured!";
        echo "<pre>".print_r(unserialize($response),true)."</pre>";
    }
    else{
        $responseArray = unserialize($response);
        $totalWeight = 0;
        $totalTime = 0;
        
        foreach($responseArray as $model){
            $totalWeight += $model["filamentUsed"]["amount"];
            $totalTime += $model["printDuration"]["amount"];
        }
        
        $summary = array();
        
        $summary["metadata"]["material"] = $_POST["material"];
        $summary["metadata"]["color"] = $_POST["color"];
        $summary["metadata"]["infillPercentage"] = $_POST["infillPercentage"];
        $summary["metadata"]["layerHeight"] = $_POST["layerHeight"];
        $summary["metadata"]["supportRemoval"] = isset($_POST["supportRemoval"])?"true":"false";
        $summary["metadata"]["vaporPolishing"] = isset($_POST["vaporPolishing"])?"true":"false";
        $summary["metadata"]["shipping"] = $_POST["shipping"];
        $summary["metadata"]["rushPrinting"] = isset($_POST["shipping"])?"true":"false";
        
        $summary["totalWeight"]["amount"] = $totalWeight;
        $summary["totalWeight"]["unit"] = "grams";
        
        $summary["totalTime"]["amount"] = $totalTime;
        $summary["totalTime"]["unit"] = "seconds";
        
        //$summary["material"] = partPriceConfig::$materials[$_POST["material"]];
        
        $summary["costs"]["printTime"]["amount"] = number_format($totalTime*1/(60*60)*partPriceConfig::$printingCost["amount"],2);
        $summary["costs"]["printTime"]["calculation"] = $totalTime." seconds * (1 hour)/(60*60 seconds) * \$".partPriceConfig::$printingCost["amount"].' '.partPriceConfig::$printingCost["unit"];
        $summary["costs"]["printTime"]["unit"] = "USD";
        
        $summary["costs"]["material"]["amount"] = number_format($totalWeight * partPriceConfig::$materials[$_POST["material"]]["price"]["amount"],2);
        $summary["costs"]["material"]["calculation"] = number_format($totalWeight,2)." grams * ".number_format(partPriceConfig::$materials[$_POST["material"]]["price"]["amount"],2). ' '.partPriceConfig::$materials[$_POST["material"]]["price"]["unit"];
        $summary["costs"]["material"]["unit"] = "USD";
        
        if (isset($_POST["supportRemoval"])){
            $summary["costs"]["supportRemoval"]["amount"] = number_format($summary["costs"]["material"]["amount"] * (partPriceConfig::$addOns["supportRemovalMultiplier"]-1),2);
            $summary["costs"]["supportRemoval"]["calculation"] = number_format($summary["costs"]["material"]["amount"],2)." USD * (".partPriceConfig::$addOns["supportRemovalMultiplier"]."-1) supportRemovalMultiplier";
            $summary["costs"]["supportRemoval"]["unit"] = "USD";
        }
        
        if (isset($_POST["vaporPolishing"])){
            $summary["costs"]["vaporPolishing"]["amount"] = number_format($summary["costs"]["material"]["amount"] * (partPriceConfig::$addOns["vaporPolishingMultiplier"]-1),2);
            $summary["costs"]["vaporPolishing"]["calculation"] = number_format($summary["costs"]["material"]["amount"],2)." USD * (".partPriceConfig::$addOns["vaporPolishingMultiplier"]."-1) vaporPolishingMultiplier";
            $summary["costs"]["vaporPolishing"]["unit"] = "USD";
        }
        
        if ($_POST["shipping"] == "delivery"){
            $summary["costs"]["delivery"]["amount"] = number_format(partPriceConfig::$deliveryCosts["base"]["amount"] + $totalWeight*partPriceConfig::$deliveryCosts["weightPrice"]["amount"],2);
            $summary["costs"]["delivery"]["calculation"] = number_format(partPriceConfig::$deliveryCosts["base"]["amount"],2) . " ".partPriceConfig::$deliveryCosts["base"]["unit"].' base + '.number_format($totalWeight,2).' grams * '.number_format(partPriceConfig::$deliveryCosts["weightPrice"]["amount"],2).' '.partPriceConfig::$deliveryCosts["weightPrice"]["unit"];
            $summary["costs"]["delivery"]["unit"] = "USD";
        }
        
        $summary["subtotal"]["amount"] = 0;
        $summary["subtotal"]["calculation"] = "";
        foreach($summary["costs"] as $costName => $costArray){
            $summary["subtotal"]["amount"] += $costArray["amount"];
            $summary["subtotal"]["calculation"] .= $costArray["amount"]." ". $costArray["unit"]." ".$costName." + ";
        }
        $summary["subtotal"]["calculation"] = substr($summary["subtotal"]["calculation"],0,-3);
        $summary["subtotal"]["unit"] = "USD";
        
        if (isset($_POST["rushPrinting"])){
            $summary["total"]["amount"] = number_format($summary["subtotal"]["amount"]*partPriceConfig::$addOns["rushPrintingMultiplier"],2);
            $summary["total"]["calculation"] = $summary["subtotal"]["amount"]." ".$summary["subtotal"]["unit"]." subtotal * ".partPriceConfig::$addOns["rushPrintingMultiplier"].' rushDeliveryMultiplier';
        }
        else{
            $summary["total"]["amount"] = number_format($summary["subtotal"]["amount"],2);
            $summary["total"]["calculation"] = $summary["subtotal"]["amount"].' subtotal';
        }
        $summary["total"]["unit"] = "USD";
        
        echo "<pre><h2>Total Costs</h2>".print_r($summary,true)."</pre>";
        
        echo "<pre><h2>API Response</h2>".print_r($responseArray,true)."</pre>";
        
    }
    
    //echo "<pre>".print_r($response,true)."</pre>";
}   