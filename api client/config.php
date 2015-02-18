<?php

class partPriceConfig{
    
    //units: mm = millimeters, USD = United States Dollar, g = grams
    
    public static $materials = array(
        "ABS"=>         array("fullName"=>"Acrylonitrile Butadiene Styrene", "price"=>array("amount"=>0.20, "unit"=>"USD/g"),  "canBeVaporPolished"=>true,  "density"=>array("amount"=>1.04, "unit"=>"g/cm^3"), "colors"=>array("#000000","#FFFFFF","#FFFAE0","#FF0F0F","#FF8324","#FFA8C8","#F7FF00","#70FF33","#140AA3","#8921FF","#9291B5","#87593E")),
        "PLA"=>         array("fullName"=>"Polylactic acid",                 "price"=>array("amount"=>0.25, "unit"=>"USD/g"),  "canBeVaporPolished"=>true,  "density"=>array("amount"=>1.25, "unit"=>"g/cm^3"), "colors"=>array("#000000","#FFFFFF","#FFFAE0","#FF0F0F","#FF8324","#FFA8C8","#F7FF00","#70FF33","#140AA3","#8921FF","#9291B5","#87593E")),
        "PC"=>          array("fullName"=>"Polycarbonate",                   "price"=>array("amount"=>0.60, "unit"=>"USD/g"),  "canBeVaporPolished"=>true,  "density"=>array("amount"=>1.20, "unit"=>"g/cm^3"), "colors"=>array("#000000","#FFFFFF","#FFFAE0","#FF0F0F","#FF8324","#FFA8C8","#F7FF00","#70FF33","#140AA3","#8921FF","#9291B5","#87593E")),
        "Nylon"=>       array("fullName"=>null,                              "price"=>array("amount"=>0.35, "unit"=>"USD/g"),  "canBeVaporPolished"=>false, "density"=>array("amount"=>1.25, "unit"=>"g/cm^3"), "colors"=>array("#000000","#FFFFFF","#FF0F0F","#70FF33","#140AA3","clear")),
        "LayWood"=>     array("fullName"=>null,                              "price"=>array("amount"=>0.80, "unit"=>"USD/g"),  "canBeVaporPolished"=>false, "density"=>array("amount"=>1.05, "unit"=>"g/cm^3"), "colors"=>array("#FFFFFF")),
        "BendLAY"=>     array("fullName"=>null,                              "price"=>array("amount"=>0.50, "unit"=>"USD/g"),  "canBeVaporPolished"=>true,  "density"=>array("amount"=>1.02, "unit"=>"g/cm^3"), "colors"=>array("#87593E")),
        "TPE"=>         array("fullName"=>"Thermoplastic elastomer",         "price"=>array("amount"=>0.60, "unit"=>"USD/g"),  "canBeVaporPolished"=>false, "density"=>array("amount"=>1.10, "unit"=>"g/cm^3"), "colors"=>array("clear")),
        "SoftPLA"=>     array("fullName"=>null,                              "price"=>array("amount"=>0.50, "unit"=>"USD/g"),  "canBeVaporPolished"=>false, "density"=>array("amount"=>1.15, "unit"=>"g/cm^3"), "colors"=>array("#000000","#FF0F0F","#140AA3","#FFFFFF")),
        "HIPS"=>        array("fullName"=>"High-impact Polystyrene",         "price"=>array("amount"=>0.20, "unit"=>"USD/g"),  "canBeVaporPolished"=>true,  "density"=>array("amount"=>1.06, "unit"=>"g/cm^3"), "colors"=>array("#FFFAE0"))
    );
    
    public static $printingCost = array("amount"=>"4.00","unit"=>"USD/hour");
    
    public static $addOns = array(
        "supportRemovalMultiplier"=>1.33,
        "vaporPolishingMultiplier"=>1.25,
        "rushPrintingMultiplier"=>1.50
    );
    
    public static $deliveryCosts = array(
        "base"=>array("amount"=>5.80,"unit"=>"USD"),
        "weightPrice"=>array("amount"=>0.01,"unit"=>"USD/g")
    );
    
    public static $slicerParams = array(
        "slicers"=>array("cura")
    );
    
    public static $layerHeights = array(
        "default"=>   array("amount"=>"0.254","unit"=>"mm"),
        "min"=>       array("amount"=>0.075,"unit"=>"mm"),
        "max"=>       array("amount"=>0.4, "unit"=>"mm")
    );
    
    public static $printSpeeds = array(
        "default"=> array("amount"=>50,"unit"=>"mm/s")
    );
}