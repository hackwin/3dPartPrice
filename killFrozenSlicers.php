<?php

// Author: Jesse Campbell
// Date: 2014-09-14
// Website: http://www.3dpartprice.com, http://www.jbcse.com
// Tested with PHP 5.5.9 on Windows 7 & XAMPP
// Version: 0.1.4

  set_time_limit(0);
  ignore_user_abort(true);
  $pslist = "c:/xampp/htdocs/pstools/pslist.exe -accepteula";
  $pskill = "c:/xampp/htdocs/pstools/pskill.exe -accepteula";
  if (isset($_GET['timeout']) == false)
    $_GET['timeout'] = 30;
  
  time_sleep_until(time()+$_GET['timeout']+5);
  
  $killedCount = 0;
    
  exec($pslist, $output, $return);
  
  //echo '<pre>'.print_r($output,true).'</pre>';
  
  foreach($output as $index => $line){
      if ($index < 5)
        continue;
      else{
          $program = preg_split('/[\s]+/', $line);
          if ($program[0] == "KISSlicer64" || $program[0] == "CuraEngine" || $program[0] == "slic3r"){
              $timeSpentRunning = preg_split('/[\:]/', $program[7]);
              $secondsRunning = $timeSpentRunning[0]*60*60 + $timeSpentRunning[1]*60 + $timeSpentRunning[2];
    //          echo '<pre>'.print_r($timeSpentRunning,true).'</pre>';
              if ($secondsRunning > $_GET['timeout']){
                  exec($pskill." ".$program[1], $output2, $return2);
                  $killedCount++;
              }
          }
      }
      
  }
  echo $killedCount;