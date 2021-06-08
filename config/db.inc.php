<?php
/**************************************************************************
 * Copyright(c) 2006, JS-X.com, All rights reserved.                      *
 *                                                                        *
 * Author: JS-X.com                                                       *
 *                                                                        *
 * A more detailed version of the legal information is in the file:       *
 * COPYRIGHT.html                                                         *
 *                                                                        *
 * Permission to use, copy, modify and distribute this software and its   *
 * documentation strictly for non-commercial purposes is hereby granted   *
 * without fee, provided that the above copyright notice appears in all   *
 * copies and that both the copyright notice and this permission notice   *
 * appear in the supporting documentation. The authors make no claims     *
 * about the suitability of this software for any purpose. It is          *
 * provided "as is" without express or implied warranty.                  *
 **************************************************************************/


if (!isset($db))
{ /* only include this once - only make 1 database connection */
   $DB_Q_TIME=0;
   $DB_Q_COUNT=0;
   function dbstat()
   {
     global $DB_Q_TIME,$DB_Q_COUNT;
     $_temp=$DB_Q_TIME*1000;
     settype($_temp,"integer");
     $DB_Q_TIME=$_temp/1000;
     return "Total of $DB_Q_COUNT MySQL querries in $DB_Q_TIME seconds.";
   }

   function dbquery($strSQL)
   {
       global $db;
       global $DEBUG;
       global $DB_Q_TIME,$DB_Q_COUNT;
       global $adminEmail;
       global $_SESSION;
       global $_FORM;
       global $CURRENT_USER;
       global $MAIL_HEADER;
       global $PHP_SELF;
       global $_SERVER;
       $this_page=$_SERVER['PHP_SELF'];
       $DB_Q_COUNT++;
       if($DEBUG)
       {
         print "<span class=warn>DB QUERY:<DIR>$strSQL</DIR><BR></span>\n";
       }
       $_start=microtime();
       $queryValue = @mysql_query($strSQL, $db);
       $_end=microtime();
       $delta=0;
       if($_end>$_start)$delta=$_end-$_start;
       $DB_Q_TIME+=$delta;
       if (!$queryValue)
       {
          $msgBody ="User :".$CURRENT_USER['LAST_NAME'].", ".$CURRENT_USER['FIRST_NAME']." [USER ID=".$CURRENT_USER['ID']."]<BR><BR><BR>\n";
          $msgBody.="Query:<DIR>".$strSQL."</DIR><BR><BR>\n";
          $msgBody.="PAGE: $this_page<BR><BR><BR>\n";
          $msgBody.="Mysql Error()<DIR>".mysql_error()."</DIR><BR><BR>\n";
	  $x="";reset($_SESSION);while(list($n,$v)=each($_SESSION))$x.="$n = $v<BR>";
          $msgBody.="SESSION DATA:<DIR>$x</DIR><BR><BR>\n";
	  $x="";reset($_FORM);while(list($n,$v)=each($_FORM))$x.="$n = $v<BR>";
          $msgBody.="FORM DATA:<DIR>$x</DIR><BR><BR>\n";
          mail($adminEmail,"DB MySQL Error: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);
	  //exit;
          die("An Error with the database has occurred.");
          //die("<br><span class=warn>MySQL Error: ".mysql_error()."<BR>QUERY: $strSQL<BR>\n");
       }
       else
       {
           return $queryValue;
       }
   }

   function dbqueryWithAlert($strSQL, $adminEmail, $errorMessage)
   {
       global $db, $strError, $criticalTransactionError;
       if (!$queryValue = @mysql_query($strSQL, $db)) {
           mail($adminEmail, "DB MySQL Error: ".date("m-d-Y"), $errorMessage);
           $strError = $errorMessage;
           $criticalTransactionError = TRUE;
       }
       else
       {
           return $queryValue;
       }
   }

   $db = mysql_connect($DB_HOST,$DB_USER,$DB_PASSWORD);
   mysql_select_db($DB_TABLE,$db);
}
?>
