<?php

// Author: Jesse Campbell
// Date: 2014-09-14
// Website: http://www.3dpartprice.com, http://www.jbcse.com
// Tested with PHP 5.5.9 on Windows 7 & XAMPP
// Version: 0.1.4

class Slicer{
    
    const DEBUG = false;
    const CURAENGINE_PATH = "c:/xampp/htdocs/Cura/CuraEngine.exe";
    const KISSLICER_PATH = "c:/xampp/htdocs/KISSlicer/KISSlicer64.exe";
    const KISSLICER_CONFIG_PATH = "c:/xampp/htdocs/KISSlicer/_styles.ini"; //KISSlicer requires a config file in addition to command-line arguments
    const SLIC3R_PATH = "c:/xampp/htdocs/Slic3r/slic3r.exe";
    const JAVA_PATH = "c:/Program Files/Java/jdk1.7.0_45/bin/java.exe";
    const GCODEINFO_PATH = "c:/xampp/htdocs/GCodeInfoV99.jar";
    const KILL_FROZEN_SLICERS_URL = "http://www.3dpartprice.com/3dpartpricelib/killFrozenSlicers.php?timeout=60";
        
    const ABS_DENSITY = 0.00105;
    const PLA_DENSITY = 0.00125;
    
    public $enabledSlicers = array("cura","kisslicer","slic3r");
    public $enabledMaterials = array("PLA","ABS");
    
    public $layerHeight = 0.5;
    public $infillPercentage = 100;
    public $printSpeed = 30;
    public $pricePerHour = 2;
    public $pricePerGram = 0.2;
    public $material = "PLA";
    
    public $gCodeFile = "";
    public $stlFile = "";
    public $args = array();
    public $output = array();
    
    function checkArgs(){
        if (file_exists($this->stlFile) == false)
            trigger_error("STL file not found at ".$this->stlFile, E_USER_ERROR);
        if (is_file($this->stlFile) == false)
            trigger_error("STL path is not a file, ".$this->stlFile, E_USER_ERROR);
        if ($this->infillPercentage < 0 || $this->infillPercentage > 100)
            trigger_error("Infill percentage out of bounds: ".$this->infillPercentage.", (0 to 100) ".$this->stlFile, E_USER_ERROR);
    }
    
    function slice($slicer){
        $slicer = strtolower($slicer);
        if (in_array($slicer, $this->enabledSlicers) == FALSE)
            trigger_error("Chosen slicer \"$slicer\" is not in \$this->enabledSlicers, ".implode(",",$this->enabledSlicers), E_USER_ERROR);
        
        $printTime = $filamentUsed = 0;
        $this->checkArgs();
        $this->args = array();
        $this->output = array();
        
        $ch = curl_init(self::KILL_FROZEN_SLICERS_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
        curl_exec($ch);
                
        if ($slicer == "cura"){
            $this->args[] = "-v";
            $this->args[] = "-s layerThickness=".($this->layerHeight*1000);
            
            $this->args[] = "-s sparseInfillLineDistance=".(100*($this->layerHeight*1000)/$this->infillPercentage);
            $this->args[] = "-s printSpeed=".$this->printSpeed;
            $this->args[] = "-s infillSpeed=".$this->printSpeed;
            $this->args[] = "-s filamentDiameter=".(1.75*1000);
            $this->args[] = "-o ".$this->gCodeFile." ".$this->stlFile;
            
            $command = self::CURAENGINE_PATH." ".implode(" ", $this->args);
            exec($command, $output, $return);
            //echo "<pre>".print_r($output,true)."</pre>";
            if (self::DEBUG) $this->output["slicerCommand"] = array("slicer"=>$slicer,"command"=>$command,"returnCode"=>$return,"commandOutput"=>$output);
            
            foreach($output as $line)
                if (substr($line, 0, 4) == "Fail")
                    $this->output['error'] = $line;
                else if (substr($line, 0, 12) == "Print time: ")
                    $printTime = substr($line, 12);
                else if (substr($line, 0, 10) == "Filament: ")
                    $filamentUsed = substr($line, 10);
        }
        else if ($slicer == "kisslicer"){
            $this->args[] = $this->stlFile;
            $this->args[] = "-o ".$this->gCodeFile;
            
            $fp = fopen(self::KISSLICER_CONFIG_PATH, 'r');
            $modifiedLines = array();
            $i=0;
            
            while(feof($fp) == false){
                $line = str_replace(array("\r\n","\n"),"", fgets($fp));
                if (stripos($line, 'infill_density_denominator=') !== FALSE){
                    $modifiedLines[$i] = 'infill_density_denominator=';
                    $allowedInfills = array(50, 33.3, 25, 20, 16.7, 12.5, 10, 5, 2.5, 0);
                    
                    foreach($allowedInfills as $key => $value){
                        if ($this->infillPercentage >= $value){
                            $modifiedLines[$i] .= $key;
                            break;
                        }
                    }
                }
                else if (stripos($line, 'layer_thickness_mm=') !== FALSE)
                    $modifiedLines[$i] = 'layer_thickness_mm='.$this->layerHeight;
                else if (stripos($line, 'skin_thickness_mm=') !== FALSE)
                    $modifiedLines[$i] = 'skin_thickness_mm='.$this->layerHeight;
                else
                    $modifiedLines[$i] = $line;
                
                $i++;
            }
        
            fclose($fp);
            file_put_contents(self::KISSLICER_CONFIG_PATH, implode(PHP_EOL, $modifiedLines));
            
            $command = self::KISSLICER_PATH." ".implode(" ", $this->args);
            exec($command, $output, $return);
            if (self::DEBUG) $this->output["slicerCommand"] = array("slicer"=>$slicer,"command"=>$command,"returnCode"=>$return,"commandOutput"=>$output,"configFile"=>file_get_contents($this->configFile));
                    
            $command2 = "\"".self::JAVA_PATH."\" -jar \"".self::GCODEINFO_PATH."\" m \"".$this->gCodeFile."\" 2>&1";
            
            exec($command2,$output2,$return2);
            if (self::DEBUG) $this->output["gCodeInfoCommand"] = array("slicer"=>$slicer,"command"=>$command2,"returnCode"=>$return2,"commandOutput"=>$output2);
            
            if ($return2 == 0){
                foreach($output2 as $line){
                    if (strpos($line,'Weight: ') !== false){
                        $filamentUsed = str_replace('Weight: ','',substr($line,0,-1));
                    }
                    else if (strpos($line,'Overall Time (w/ Acceleration):') !== false){
                        $printTime = str_replace("Overall Time (w/ Acceleration):    ","",$line);
                        $printTime = substr($printTime, strpos($printTime,'(')+1, -4);
                    }
                }
            }
            else
                trigger_error("GCodeInfo exited with an error: ".$return2, E_USER_ERROR);
        }
        else if ($slicer == "slic3r"){
            $this->args[] = "-o ".$this->gCodeFile;
            $this->args[] = $this->stlFile;
            
            $command = self::SLIC3R_PATH." ".implode(" ", $this->args);
            
            exec($command, $output, $return);
            if (self::DEBUG) $this->output["slicerCommand"] = array("slicer"=>$slicer,"command"=>$command,"returnCode"=>$return,"commandOutput"=>$output);

            $command2 = "\"".self::JAVA_PATH."\" -jar \"".self::GCODEINFO_PATH."\" m \"".$this->gCodeFile."\" 2>&1";
            exec($command2,$output2,$return2);
            if (self::DEBUG) $this->output["gCodeInfoCommand"] = array("slicer"=>__CLASS__,"command"=>$command2,"returnCode"=>$return2,"commandOutput"=>$output2);

            if ($return2 == 0){
                foreach($output2 as $line){
                    if (strpos($line,'Weight: ') !== false){
                        $filamentUsed = str_replace('Weight: ','',substr($line,0,-1));
                    }
                    else if (strpos($line,'Overall Time (w/ Acceleration):') !== false){
                        $printTime = str_replace("Overall Time (w/ Acceleration):    ","",$line);
                        $printTime = substr($printTime, strpos($printTime,'(')+1, -4);
                    }
                }
            }
            else
                trigger_error("GCodeInfo exited with an error: ".$return2, E_USER_ERROR);
        }
        
        if ($printTime != 0 && $filamentUsed != 0)
            return $this->formatOutput($printTime,$filamentUsed,$slicer);
        else{
            $this->output["error"] = "Impossible results detected, print time: ".$printTime.", filament used: ".$filamentUsed;
            return $this->output;
        }
    }
    
    function filamentMillimetersToGrams($length, $material){
        if (in_array($material, $this->enabledMaterials) == FALSE)
            trigger_error("Chosen material \"$material\" is not in \$this->enabledMaterials, ".implode(",",$this->enabledSlicers), E_USER_ERROR);
        
        $diameter = 1.75; //mm
        $radius = $diameter / 2;
        $area = $radius * $radius * pi();
        
        switch($material){
            case "ABS": 
                $density = self::ABS_DENSITY;
                break;
            case "PLA":
                $density = self::PLA_DENSITY;
                break;
            default: 
                $density = 0;
        }
        
        $weight = $area * $length * $density;
        return $weight;
    }
    
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
    
    function formatOutput($printTime,$filamentUsed,$slicer){
        $this->output["formattedPrintTime"] = $this->secondsTohuman($printTime);
        
        if ($slicer == "cura")
            $this->output["filamentUsedGrams"] = number_format($this->filamentMillimetersToGrams($filamentUsed,$this->material),2);
        else
            $this->output["filamentUsedGrams"] = $filamentUsed;
            
        $this->output["estimatedCostUSD"] = number_format($printTime/3600*$this->pricePerHour + $this->output["filamentUsedGrams"]*$this->pricePerGram, 2);
        $this->output["estimatedCostUSDCalculation"] = number_format(($printTime/3600),2)." hours * \$".number_format($this->pricePerHour,2)."/hour + " .number_format($this->output["filamentUsedGrams"],2)." grams * $".number_format($this->pricePerGram,2)."/gram";
        $this->output["printTimeSeconds"] = number_format($printTime,0,".","");
        
        
        return $this->output;
    }
}