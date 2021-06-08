<?
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

function toDo($str)
{
  global $TODO;
  $TODO[count($TODO)]=$str;
}
function showToDo()
{
  global $TODO;
  print "<ul>";
  for($i=0;$i<count($TODO);$i++)
    print "<li>".$TODO[$i]."</li>\n";
  print "</ul>";
}
function debug($str)
{
  global $DEBUG;
  if($DEBUG)
    print "DEBUG: $str<BR>\n";
}
Function recordTransaction($_ticket,$_type,$_str)
{
  global $TABLE_TRANSACTIONS;
  global $CURRENT_USER;
  global $PAGE_VIEW_USER;
  // might want to put [username] in case first&last name are not specified
  $_actor="<a href=\"$PAGE_VIEW_USER?txtUserID=".$CURRENT_USER['ID']."\">".$CURRENT_USER['FIRST_NAME'].", ".$CURRENT_USER['LAST_NAME']."</a>";
  //$_type=addslashes($_type);
  //$_str=addslashes($_str);
  //$_actor=addslashes($_actor);
  $_date=date("U",time());
  debug("-- RECORD --[ticket $_ticket] $_type | $_str<BR>\n");
  $strSQL1 = "INSERT INTO $TABLE_TRANSACTIONS SET";
  $strSQL1.= " eticket_id='$_ticket'";
  $strSQL1.= ", ticket_id='$_ticket'";
  $strSQL1.= ", actor='$_actor'";
  $strSQL1.= ", type='$_type'";
  $strSQL1.= ", trans_data='$_str'";
  $strSQL1.= ", trans_date='$_date'";
  $result1 = dbquery($strSQL1);
  //echo "INSERT-TRANSACTION:SAVED($result1):<DIR>$strSQL1</DIR>\n";
}
function showTransactions($_ticket)
{
  global $TABLE_TRANSACTIONS;
  global $DEBUG;
  // debug("Looking for transactions from TICKET # $_ticket");
  $num=0;
  $strSQL1 = "SELECT *";
  $strSQL1.= " FROM $TABLE_TRANSACTIONS";
  $strSQL1.= " WHERE eticket_id='$_ticket'";
  $strSQL1.= " ORDER BY trans_date ASC";
  if($result1 = dbquery($strSQL1))
  {
    // debug("RESULT of QUERY:$result1");
    $_ret ="<table width=600 cellspacing=0 cellpadding=3 class=wrap2>";
    $_ret.="<tr><th colspan=2 align=center>TRANSACTIONS</th></tr>";
    while($row1= mysql_fetch_array($result1))
    {
      $num++;
      $row=$num%3;
      // debug("NUM=$num");
      $strET  = $row1['eticket_id'];
      $strT   = $row1['ticket_id'];
      $strWho = $row1['actor'];
      $strType= $row1['type'];
      $strData= $row1['trans_data'];
      $strDate= $row1['trans_date'];
      // debug("READ::::<b>$strType = \"$strData\"</b>");
      $DATE=date("F dS, Y",$strDate);
      $strData=ereg_replace("\n","<BR>",$strData);
      $extra="";
      if($strET!=$strT)$extra="<BR>&nbsp;&nbsp;[Ticket ID was $strT]";
      if(ereg("^mergedata$",$strType,$m))
      {
        $row--;if($row<0)$row=2;
        $_ret.="<tr class=row$row><td colspan=2><table cellspacing=0 cellpadding=2><tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>$strData</td></tr></table></td></tr>";
      }
      else if(ereg("^merge$",$strType,$m))
      {
        $_ret.="<tr class=row$row><td>$strWho merged $strData into this ticket.</td><td>$DATE $extra</td></tr>";
      }
      else if(ereg("^spawn<(.+)>$",$strType,$m))
      {
	if(strcmp($m[1],"to"))
	{
          $_ret.="<tr class=row$row><td>$strWho spawned into a new ticket. New ticket is $strData</td><td>$DATE $extra</td></tr>";
	}
	else
	{
          $_ret.="<tr class=row$row><td>$strWho spawned this new ticket. Original ticket is $strData</td><td>$DATE $extra</td></tr>";
	}
      }
      else if(ereg("^duplicate<(.+)>$",$strType,$m))
      {
	if(strcmp($m[1],"to"))
	{
          $_ret.="<tr class=row$row><td>$strWho duplicated ticket. New ticket is $strData</td><td>$DATE $extra</td></tr>";
	}
	else
	{
          $_ret.="<tr class=row$row><td>$strWho duplicated ticket. Original ticket is $strData</td><td>$DATE $extra</td></tr>";
	}
      }
      else if(ereg("^(.+)<BigText>",$strType,$m))
      {
        $_ret.="<tr class=row$row><td>$strWho recorded:</td><td>$DATE $extra</td></tr>";
        $_ret.="<tr class=row$row><td colspan=2><DIR class=bigtext_history>".$strData."</DIR></td></tr>";
      }
      else if(ereg("^(.+)<Comment>",$strType,$m))
      {
        $_ret.="<tr class=row$row><td>$strWho commented:</td><td>$DATE $extra</td></tr>";
        $_ret.="<tr class=row$row><td colspan=2><DIR class=comment_history>".$strData."</DIR></td></tr>";
      }
      else
      {
        $_ret.="<tr class=row$row><td>$strWho set $strType to \"$strData\"</td><td>$DATE $extra</td></tr>";
      }
    }
    $_ret.="</table>\n";
  }
  if(!$num)
  {
    $_ret="<table class=warn><tr><td>ERROR: No transactions stored for this ticket?</td></tr></table>\n";
  }
  return $_ret;
}

  Function getPostedData()
  {
    global $_POST,$_GET;
    global $_FORM;
    global $DEBUG;
    global $_SESSION;
    global $sequence1;
    if(!isset($sequence1) || $sequence1!="seq1")die("Error: License Key Error\n");

    $test="";
    if(isset($_SESSION['TEST']))$test=$_SESSION['TEST'];
    $str="";
    $str.="TESTING=$test<BR>";
    $str.="SESSION-FORM<BR>\n";
    /* get the saved FORM data from a session tie out if it exists */
    if(isset($_SESSION['FORM']))
    {
      $str.= "XXXX1XXXX<BR>\n";
      $x=unserialize($_SESSION['FORM']);
      $str.="x:<DIR>$x</DIR>\n";
      if(is_array($x))
      {
        $str.= "XXXX2XXXX<BR>\n";
        reset($x);
        {
          while(list($name,$value)=each($x))
          {
            $str.= "XXXX3XXXX $name , $value<BR>\n";
            if(!is_array($value))
            {
              $_FORM[$name]=$value;
              $str.= "[SESSION-FORM] $name=\"$value\"<BR>\n";
            }
            else
            {
              while(list($name2,$value2)=each($x[$name]))
              {
                $str.= "XXXX4XXXX $name , $value<BR>\n";
                if(!is_array($_FORM[$name]))
                {
                  $_FORM[$name]=array($name2=>$value2);
                  $str.= "[SESSION-FORM]".$name."[".$name2."]=\"$value2\"<BR>\n";
                }
                else
                {
                  $_FORM[$name][$name2]=$value2;
                  $str.= "[SESSION-FORM]".$name."[".$name2."]=\"$value2\"<BR>\n";
                }
              }
            }
          }
        }
      }
    } /* end of _SESSION['FORM'] */
    //unset($_SESSION['FORM']);

    $str.="GET-FORM<BR>\n";
    reset($_GET);
    while(list($name,$value)=each($_GET))
    {
      if(!is_array($value))
      {
        $_FORM[$name]=$value;
        $str.= "$name=\"$value\"<BR>\n";
      }
      else
      {
        while(list($name2,$value2)=each($_GET[$name]))
        {
          if(!is_array($_FORM[$name]))
          {
            $_FORM[$name]=array($name2=>$value2);
            $str.= $name."[".$name2."]=\"$value2\"<BR>\n";
          }
          else
          {
            $_FORM[$name][$name2]=$value2;
            $str.= $name."[".$name2."]=\"$value2\"<BR>\n";
          }
        }
      }
    }
    $str.="POST-FORM<BR>\n";
    reset($_POST);
    while(list($name,$value)=each($_POST))
    {
      if(!is_array($value))
      {
        $_FORM[$name]=$value;
        $str.= "$name=\"$value\"<BR>\n";
      }
      else
      {
        while(list($name2,$value2)=each($_POST[$name]))
        {
          if(!is_array($_FORM[$name]))
          {
            $_FORM[$name]=array($name2=>$value2);
            $str.= $name."[".$name2."]=\"$value2\"<BR>\n";
          }
          else
          {
            $_FORM[$name][$name2]=$value2;
            $str.= $name."[".$name2."]=\"$value2\"<BR>\n";
          }
        }
      }
    }
    return $str;
  }

// keep this old method around just in case we need it later...
  Function getPostedData2()
  {
    global $_POST,$_GET;
    global $_FORM;
    global $DEBUG;
    for(reset($_POST);$key=key($_POST);next($_POST))
    {
      $_FORM[$key] =  $_POST[$key];
      if($DEBUG)print "$key=&quot;".$_POST[$key]."&quot;<BR>\n";
    }
                                                                                                                                                                                                        
    for(reset($_GET);$key=key($_GET);next($_GET))
    {
      $_FORM[$key] =  $_GET[$key];
      if($DEBUG)print "$key=&quot;".$_GET[$key]."&quot;<BR>\n";
    }
  }

  Function cleanFormInput($value) {
     $value = Trim(strip_tags($value));
     return $value;
  }

  Function encryptData($m) {
     global $encryptKey;

     $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC),MCRYPT_RAND);
     $c = mcrypt_encrypt(MCRYPT_BLOWFISH, $encryptKey, $m, MCRYPT_MODE_CBC, $iv);
     // encode and tack on the iv
     $c1 = base64_encode($c . "\$IV\$" . $iv);
     return $c1;
  }

  Function unEncryptData($c) {
     global $encryptKey;

     // decode and get the iv off
     list($c1,$iv)=explode("\$IV\$",base64_decode($c));
     $m = mcrypt_decrypt(MCRYPT_BLOWFISH,$encryptKey,$c1,MCRYPT_MODE_CBC,$iv);
     return rtrim($m);
  }

  Function writeNA($value) {
      If ($value) {
          return $value;
      } Else {
          return "N/A";
      }
  }

  Function writeStatus($statLetter) {
      If ($statLetter == "w") {
          Return "<font color='green'>Working</font>";
      } ElseIf ($statLetter == "i") {
          Return "<font color='cc6600'>In Service</font>";
      } ElseIf ($statLetter == "n") {
          Return "<font color='red'>Needs Service</font>";
      }
  }

  /* To use paging, first call determinePageNumber, passing it the sql select
     statement you intend to ultimately build off of. After you have done that, 
     execute the query as normal.

     At the end of where you display the results of the query, call createPaging, 
     which will build the paging nav. Also note: assuming your result set is 
     built in part from user input via a form, make sure the form is GET, not POST.

     Before you call determinePageNumber, you may optionally choose to (globally) 
     set $rowLimit equal to some number other than 30, which is the default 
     record limit per page.
  */

  Function determinePageNumber($strSQL) {
      global $rowLimit, $rowOffset, $pageNumber;

      If (!$rowLimit) {
          $rowLimit = 30;
      }
      if (!$rowOffset) {
          $rowOffset = 0; 
      }
      $result = mysql_query($strSQL);
      $numrec = mysql_num_rows($result);
      $pageNumber = intval($numrec/$rowLimit);
      if ($numrec%$rowLimit) $pageNumber++; // add one page if remainder

      $strSQL .= " LIMIT $rowOffset, $rowLimit";
      Return $strSQL;

      # would be nice to return recordset in future...
      # result=mysql_query("select * from tablename $query_where limit $rowOffset,$rowLimit");
  }

  Function createPaging($qsParamToRemove="") {
    global $rowLimit, $rowOffset, $pageNumber, $QUERY_STRING;
    If (strpos($QUERY_STRING, "owOffset")) {
        $posQSMinusOffset = strpos($QUERY_STRING, "&")+1;
        $qstring = substr($QUERY_STRING, $posQSMinusOffset);
    } Else {
        $qstring = $QUERY_STRING;
    }

    If ($qsParamToRemove) { # need to make this capable of taking arrays someday.
        # $stringToFind = substr($qsParamToRemove, 1);
        # If (strpos($qstring, $stringToFind)) {
            $pattern = "/".$qsParamToRemove."[\045|\w|\075]*[\046]?/";
            $qstring = preg_replace($pattern, "", $qstring);
        # }
    }
      
    if ($pageNumber>1) {
      echo "<TABLE CELLPADDING=0 BORDER=0 CELLSPACING=5 WIDTH=100%><TR><TD>";
          if ($rowOffset>=$rowLimit) {
              $newoff=$rowOffset-$rowLimit;
              
              echo "<A HREF=\"$PHP_SELF?rowOffset=$newoff&$qstring\">&lt;-- PREV</A> ";
          } else {
              echo "&lt;-- PREV ";
          }
  
          echo " &nbsp; ";
  
          for ($i=1;$i<=$pageNumber;$i++) {
              if ((($i-1)*$rowLimit)==$rowOffset) {
                  echo "$i ";
              } else {
                  #if (($i < 8) OR ($i > ($pageNumber - 8))) {
                       $newoff=($i-1)*$rowLimit;
                       echo " <A HREF=\"$PHP_SELF?rowOffset=$newoff&$qstring\">$i</A> ";
                  #} elseif (!$wroteDots) {
                  #     echo " ... ";
                  #     $wroteDots = TRUE;
                  #}
              }
          }
          echo "&nbsp; ";
          if ($rowOffset!=$rowLimit*($pageNumber-1)) {
              $newoff=$rowOffset+$rowLimit;
              echo "<A HREF=\"$PHP_SELF?rowOffset=$newoff&$qstring\">NEXT--&gt;</A> ";
          }else{
              echo "NEXT--&gt; ";
          }
          echo "</TD></TR></TABLE>";
    }
  }

  Function resetRowColor()
  {
     global $rowStyle;
     $rowStyle=0;
  }
  # use this to set the class tag of alternating rows in tables (in conjunction with stylesheet)
  Function alternateRowColor() {
      global $rowStyle;
      $rowStyle ++;
      If ($rowStyle%2 == 1) {
           Return "row1";
      } Else {
           Return "row2";
      }
  }

  Function formatForBrowser($strIE, $strElse) {
      global $HTTP_USER_AGENT;
      If (strpos($HTTP_USER_AGENT, "MSIE")) {
           echo $strIE;
      } Else {
           echo $strElse;
      }
  }

  Function getPageName() {
      global $PHP_SELF;
      $returnString = strrchr($PHP_SELF, "/");
      $returnString = substr($returnString, 1);
      Return $returnString;
  }

  Function makeHomeURL($stringToRemove = "") {
      global $_SERVER;
      global $DEBUG;
      $temp=""; if ( isset($_SERVER['HTTPS'])){ $temp="s"; }
      //$strURL = "http".$temp."://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
      $strURL = "http".$temp."://".$_SERVER['SERVER_NAME']."/php_rt";
      If ($stringToRemove != "") {
         $intPos = strpos($strURL, $stringToRemove);
         if($intPos) /*if not defined - not found */
           $strURL = substr($strURL, 0, ($intPos-1));
      }
      if($DEBUG){echo "makeHomeURL: $strURL<BR>($intPos, $strURL, $stringToRemove)\n";}
      Return $strURL;
  }

  Function buildName($strFirstName, $strMiddleName, $strLastName, $intShowType="") {
      If ($strMiddleName) {
           If ($intShowType == 1) {
                $strFullName = $strFirstName." ".$strMiddleName." ".$strLastName;
           } Else {
                $strFullName = $strLastName.", ".$strFirstName." ".$strMiddleName;
           }
      } Else {
           If ($intShowType == 1) {
                $strFullName = $strFirstName." ".$strLastName;
           } Else {
                $strFullName = $strLastName.", ".$strFirstName;
           }
      }
      Return $strFullName;
  }

  Function urlSafe($strQueryString) {
      $strQueryString = urlencode($strQueryString);
      $strQueryString = str_replace("%26", "&", $strQueryString);
      $strQueryString = str_replace("%3D", "=", $strQueryString);
      Return $strQueryString;
  }

  Function redirect($strURL, $strQueryString = "") {
    global $DEBUG;
    if($DEBUG){echo "Redirect: $strURL?$strQueryString<BR>\n";}
      $strQueryString = urlSafe($strQueryString);
      header ("Location: $strURL?$strQueryString");
      header ("QUERY_STRING: $strQueryString");
      exit;
  }

  Function writeSelected($SelectValue,$OurValue) {
      If ($SelectValue==$OurValue) {
          Return "selected";
      }
  }

  Function writeChecked($SelectValue,$OurValue) {
      If ($SelectValue==$OurValue) {
          Return "checked";
      }
  }

  Function makeNull($val, $includeSingleQuotes = "") {
      If ($val == "" OR $val == "00/00/0000") {
           return "NULL";
      } Else {
           If ($includeSingleQuotes) {
               return "'".$val."'";
           } Else {
               return $val;
           }
      }
  }

  Function antiSlash($strValue) {
      If ($strValue != "") {
          $strValue = stripslashes($strValue);
          $strValue = str_replace("\"", "&quot;", $strValue); # fixes broken html input field problem
      }
      Return $strValue;
  }

  Function fillError($strValue) {
      global $strError;
      If ($strError == "") {
           $strError = $strValue;
      }
      else { $strError.="<BR>".$strValue; }
  }

  Function validateText($strFieldName, $strValidate, $intMin, $intMax, $bolRequired, $bolHTML) {
      global $strError;
      // no matter what take out HTML comments!
      If ($bolHTML == FALSE) {
          $strValidate = Trim(strip_tags($strValidate));
      } Else {
          $strValidate = Trim($strValidate);
      }
      $strValidate=htmlentities($strValidate);
      $strValidate=ereg_replace("<!--","--",$strValidate);
      $strValidate=ereg_replace("<script-","&lt; Script",$strValidate);
      If ($bolRequired == TRUE OR $strValidate != "") {
          If ($strValidate=="") {
              fillError("$strFieldName is required.");
              Return $strValidate;
          } Else {
              $intField = strlen($strValidate);
              If (($intField >= $intMin) AND ($intField <= $intMax)) {
                  Return $strValidate;
              } Else {
                  If ($intMin==$intMax) {
                       fillError("$strFieldName must be exactly $intMax characters long.");
                       Return $strValidate;
                  } Else {
                       fillError("$strFieldName must be between $intMin and $intMax characters in length.");
                       Return $strValidate;
                  }
              }
          }
      } Else {
          Return $strValidate;
      }
  }

  Function validateChoice($strFieldName, $strValidate) {
      global $strError;
      $strValidate = strip_tags($strValidate);
      If ($strValidate == "") {
           fillError("$strFieldName is required.");
      } Else {
           Return $strValidate;
      }
  }

  Function validateEmail($strFieldName, $strValidate, $bolRequired) {
      global $strError;
      $strValidate = trim(strtolower(strip_tags($strValidate)));

      If ($bolRequired == TRUE OR $strValidate != "") {
          If ($strValidate=="") {
              fillError("$strFieldName is required.");
              Return $strValidate;
          } Else {
              $Pos = strpos($strValidate, "@", 1);
              If ($Pos===FALSE) {
                  fillError("$strFieldName is not in the correct format.");
                  Return $strValidate;
              } Else {
                  $Pos2 = strpos($strValidate, ".", ($Pos+2));
                  If ($Pos2===FALSE) {
                      fillError("$strFieldName is not in the correct format.");
                      Return $strValidate;

                   } Else {
                       $intField = strlen($strValidate);
                       If ($intField>60) {
                            fillError("$strFieldName may not be more than 60 characters long.");
                            Return $strValidate;
                        } Else {
                            Return $strValidate;
                        }
                   }
              }
          }
      } Else {
          Return $strValidate;
      }
  }

  Function validateNumber($strFieldName, $strValidate, $intMin, $intMax, $bolRequired) {
      global $strError;
      $strValidate = Trim(strip_tags($strValidate));
      $strValidate = str_replace(" ", "", $strValidate);
      $strValidate = str_replace(")", "", $strValidate);
      $strValidate = str_replace("(", "", $strValidate);
      /* the below line removes negative numbers */
      //$strValidate = str_replace("-", "", $strValidate);

      If ($bolRequired == TRUE OR $strValidate != "") {
          If ($strValidate=="") {
              fillError("$strFieldName is required.");
              Return $strValidate;
          } Else {
              $intField = strlen($strValidate);
              If (($intField >= $intMin) AND ($intField <= $intMax)) {
                  If (is_numeric($strValidate)===TRUE) {
                       Return $strValidate;
                  } Else {
                       fillError("$strFieldName must be purely numeric.");
                       Return $strValidate;
                  }
              } Else {
                  If ($intMin==$intMax) {
                       fillError("$strFieldName must be exactly $intMax.");
                       Return $strValidate;
                  } Else {
                       fillError("$strFieldName must be between $intMin and $intMax.");
                       Return $strValidate;
                  }
              }
          }
      } Else {
          Return $strValidate;
      }
  }

  Function validateExactNumber($strFieldName, $strValidate, $intMin, $intMax, $bolRequired, $intDecimals="") {
      global $strError;
      $strValidate = Trim(strip_tags($strValidate));
      If ($bolRequired == TRUE OR $strValidate != "") {
          If ($strValidate == "") {
              fillError("$strFieldName is required.");
          } ElseIf (is_numeric($strValidate) === FALSE) {
              fillError("$strFieldName must be purely numeric.");
          } ElseIf (($strValidate < $intMin) OR ($strValidate > $intMax)) {
              fillError("$strFieldName must be between $intMin and $intMax.");
           # } ElseIf (strstr($strValidate, ".")) {
           #     If strstr(strstr($strValidate, "."), ".")
           #     fillError("too many decimals...");
          } ElseIf ($intDecimals !== "") {
              $decimalPlaces = strlen(strstr($strValidate, "."));
              If ($decimalPlaces > ($intDecimals+1)) {
                  If ($intDecimals == 0) {
                      fillError("$strFieldName must be a whole number between $intMin and $intMax.");
                  } Else {
                      fillError("$strFieldName may have no more than $intDecimals digits after the decimal.");
                  }
              } ElseIf ($intDecimals == 0) {
                  $strValidate = round($strValidate);
              }
          }
      }
      Return $strValidate;
  }

  Function validateIP($fieldSuffix, $bolRequired, $formType="POST", $requireAllParts=TRUE) {
      global $strError, $HTTP_POST_VARS;

      $ip1  = "txtIP1".$fieldSuffix;
      $ip2  = "txtIP2".$fieldSuffix;
      $ip3  = "txtIP3".$fieldSuffix;
      $ip4  = "txtIP4".$fieldSuffix;

      If ($formType == "GET") {
          global $HTTP_GET_VARS;
          $ip1  = Trim(strip_tags($HTTP_GET_VARS[$ip1]));
          $ip2  = Trim(strip_tags($HTTP_GET_VARS[$ip2]));
          $ip3  = Trim(strip_tags($HTTP_GET_VARS[$ip3]));
          $ip4  = Trim(strip_tags($HTTP_GET_VARS[$ip4]));
      } Else {
          $ip1  = Trim(strip_tags($HTTP_POST_VARS[$ip1]));
          $ip2  = Trim(strip_tags($HTTP_POST_VARS[$ip2]));
          $ip3  = Trim(strip_tags($HTTP_POST_VARS[$ip3]));
          $ip4  = Trim(strip_tags($HTTP_POST_VARS[$ip4]));
      }

      $ip1 = str_replace(".", "", $ip1);
      $ip2 = str_replace(".", "", $ip2);
      $ip3 = str_replace(".", "", $ip3);
      $ip4 = str_replace(".", "", $ip4);

      If ($bolRequired OR (($ip1 OR $ip2 OR $ip3 OR $ip4) AND $requireAllParts)) {
          $ipRequired = TRUE;
      }

      If ($ipRequired AND (($ip1=="") OR ($ip2=="") OR ($ip3=="") OR ($ip4==""))) {
          fillError("Please specify <u>all</u> parts of the IP Address.");
      }

      $strIP1  = validateExactNumber("Each part of the IP Address", $ip1, 0, 255, $ipRequired, 0);
      $strIP2  = validateExactNumber("Each part of the IP Address", $ip2, 0, 255, $ipRequired, 0);
      $strIP3  = validateExactNumber("Each part of the IP Address", $ip3, 0, 255, $ipRequired, 0);
      $strIP4  = validateExactNumber("Each part of the IP Address", $ip4, 0, 255, $ipRequired, 0);

      If ($strIP1 OR $strIP2 OR $strIP3 OR $strIP4) {
          return $strIP1.".".$strIP2.".".$strIP3.".".$strIP4;
      } Else {
          return "";
      }
  }

  Function buildIP($value, $fieldSuffix) {
     If ($value) {
         $dot1 = strpos($value, ".", 0);
         $dot2 = strpos($value, ".", ($dot1+1));
         $dot3 = strpos($value, ".", ($dot2+1));
     }

     $strIP1 = substr($value, 0, $dot1);
     $strIP2 = substr($value, ($dot1+1), (($dot2-$dot1)-1));
     $strIP3 = substr($value, ($dot2+1), (($dot3-$dot2)-1));
     $strIP4 = substr($value, ($dot3+1));
?>
     <input type='text' name='txtIP1<?=$fieldSuffix; ?>' value='<?=$strIP1; ?>' size='3' maxlength='3'> <b>.</b> 
     <input type='text' name='txtIP2<?=$fieldSuffix; ?>' value='<?=$strIP2; ?>' size='3' maxlength='3'> <b>.</b>
     <input type='text' name='txtIP3<?=$fieldSuffix; ?>' value='<?=$strIP3; ?>' size='3' maxlength='3'> <b>.</b>
     <input type='text' name='txtIP4<?=$fieldSuffix; ?>' value='<?=$strIP4; ?>' size='3' maxlength='3'>
<?
  }

  Function buildPhone($varNameSuffix, $phoneVal) {
      If ($phoneVal != "") {
         $phone1 = substr($phoneVal, 0, 3);
         $phone2 = substr($phoneVal, 3, 3);
         $phone3 = substr($phoneVal, 6, 4);
      }
      echo "( <input size='3' maxlength='3' type='text' name='txtPhone1".$varNameSuffix."' value='$phone1'> ) ";
      echo "<input size='3' maxlength='3' type='text' name='txtPhone2".$varNameSuffix."' value='$phone2'> - ";
      echo "<input size='4' maxlength='4' type='text' name='txtPhone3".$varNameSuffix."' value='$phone3'>\n";
  }

  Function validatePhone($strFieldName, $varNameSuffix, $bolRequired) {
      global $strError, $HTTP_POST_VARS;

      $phone1 = "txtPhone1".$varNameSuffix;
      $phone2 = "txtPhone2".$varNameSuffix;
      $phone3 = "txtPhone3".$varNameSuffix;
      $phone1 = Trim(strip_tags($HTTP_POST_VARS[$phone1]));
      $phone2 = Trim(strip_tags($HTTP_POST_VARS[$phone2]));
      $phone3 = Trim(strip_tags($HTTP_POST_VARS[$phone3]));

	  $phoneVal = $phone1.$phone2.$phone3;

      If ($phoneVal != "") { 
         If (is_numeric($phoneVal)===TRUE) {
             $phoneLen = strlen($phoneVal); 
             If ($phoneLen != 10) {
                 fillError("$strFieldName is missing digits.");
                 Return $phoneVal;              
             } Else {
                 Return $phoneVal;
             }
         } Else {
            fillError("$strFieldName must be completely numeric.");
            Return $phoneVal;
         }
     } ElseIf ($bolRequired) {
         fillError("$strFieldName is required.");
         Return $phoneVal;
     } 
  }

  # For use when comparing a "user-formatted date" (mm/dd/yyyy) with a date in the db
  ## Note - you don't need this to insert a date into the db - for that, use: date("Ymd", $dateVal);
  Function validateDate($fieldName, $dateVal, $intMin, $intMax, $bolRequired) {
      global $strError;

      If ($dateVal == "mm/dd/yyyy") {
            $dateVal = "";
      }

      If ($dateVal != "") {
            $dateVal = str_replace(".", "/", $dateVal);
            $dateVal = str_replace("-", "/", $dateVal);
            $tempDate = $dateVal;
            $tempDate = str_replace("/", "", $tempDate);

            If (is_numeric($tempDate)) {
                  $intLoc = strpos($dateVal, "/");
                  If ($intLoc == 2) {
                      $strMonth = substr($dateVal, 0, 2);
                  } ElseIf ($intLoc == 1) {
                      $strMonth = "0".substr($dateVal, 0, 1);
                  } Else {
                      fillError("$fieldName was not input in a valid format.");
                      Return "$dateVal";
                  }

                  $intLoc2 = strpos($dateVal, "/", ($intLoc + 1));
                  If ($intLoc2 == 4 AND $intLoc == 1) {
                      $strDay = substr($dateVal, 2, 2);
                  } ElseIf ($intLoc2 == 5) {
                      $strDay = substr($dateVal, 3, 2);
                  } ElseIf ($intLoc2 == 3) {
                      $strDay = "0".substr($dateVal, 2, 1);
                  } Else {
                      fillError("$fieldName was not input in a valid format.");
                      Return "$dateVal";
                  }

                  $strYear = substr($dateVal, ($intLoc2+1), 4);
                  If (strlen($strYear) != 4) {
                       fillError("$fieldName requires a four-digit year.");
                       Return "$dateVal";
                  } ElseIf (($strYear > $intMax) OR ($strYear < $intMin)) {
                       fillError("$fieldName cannot be before $intMin or later than $intMax.");
                       Return "$dateVal";
                  }

                  Return $dateVal;
            } Else {
                  fillError("$fieldName must not contain letters or symbols (aside from /).");
                  Return "$dateVal";
            }
      } ElseIf ($bolRequired == TRUE) {
            fillError("$fieldName is required.");
            Return "";
      }
  }

  function todayDate()
  {
    $str="";
    // return MM/DD/YYYY of today's current date:
    $str=date("m/d/Y");
    return $str;
  }

  # For use when retrieving a date from the db to display
  Function displayDate($dateVal) {
      If ($dateVal) {
            $dateVal  = str_replace("-", "", $dateVal);
            $strDay   = substr($dateVal, 6, 2);
            $strMonth = substr($dateVal, 4, 2);
            $strYear  = substr($dateVal, 0, 4);
            $dateVal  = "$strMonth/$strDay/$strYear";
      }
      Return $dateVal;
  }

  # For use when retrieving a date from the db to display
  Function displayDateTime($dateVal) {
      If ($dateVal) {
            $dateVal   = str_replace("-", "", $dateVal);
            $strDay    = substr($dateVal, 6, 2);
            $strMonth  = substr($dateVal, 4, 2);
            $strYear   = substr($dateVal, 0, 4);

            $strHour   = substr($dateVal, 9, 2);
            $strMinute = substr($dateVal, 12, 2);
            $dateVal   = "$strMonth/$strDay/$strYear, $strHour:$strMinute";
      }
      Return $dateVal;
  }

  /* keep if we need later */
  function printDate2($secsEpoc)
  {
    //return date("m/j/Y - h:i a",$secsEpoc);
    return date("m/j/Y",$secsEpoc);
  }

  # For inserting or updating 'mm/dd/yyyy' dates in db, or comparing with dates in the db.
  # Returns a string, "NULL", if $dateVal is empty.
  function printDate($dbDate)
  {
    if(!$dbDate)return "";
    $strYear =substr($dbDate,0,4);
    $strMonth=substr($dbDate,4,2);
    $strDay  =substr($dbDate,6,2);
    return $strMonth."/".$strDay."/".$strYear;
  }
  Function dbDate($dateVal) {
      $dateVal = makeNull($dateVal, FALSE);
      If ($dateVal != "" AND $dateVal != "NULL") {
          $intLoc = strpos($dateVal, "/");
          If ($intLoc == 2) {
              $strMonth = substr($dateVal, 0, 2);
          } Else {
              $strMonth = "0".substr($dateVal, 0, 1);
          }

          $intLoc2 = strpos($dateVal, "/", ($intLoc + 1));
          If ($intLoc2 == 4 AND $intLoc == 1) {
              $strDay = substr($dateVal, 2, 2);
          } ElseIf ($intLoc2 == 5) {
              $strDay = substr($dateVal, 3, 2);
          } Else {
              $strDay = "0".substr($dateVal, 2, 1);
          }

          $strYear = substr($dateVal, ($intLoc2+1), 4);
          //$dateVal = "'".$strYear.$strMonth.$strDay."'";
          $dateVal = $strYear.$strMonth.$strDay;
      }
      Return $dateVal;
  }

  Function buildDate($fieldName, $dateVal) {
      If ($dateVal == "" OR $dateVal == "0000-00-00" OR $dateVal == "NULL") {
          echo "<input type='text' name='$fieldName' size='10' value='mm/dd/yyyy' onClick=\"this.value=''\">";
      } Else {
          echo "<input type='text' name='$fieldName' size='10' value='$dateVal'>";
      }
  }

  Function declareError($bolBold) {
      global $strError;
      $b1="";$b2="";
      If ($strError != "") {
          If ($bolBold) { $b1="<b>";$b2="</b>"; }
          echo "<table cellpadding=10 cellspacing=0 class=warn><tr><td align=center>$strError</td></tr></table>\n";
      }
  }

  Function declareErrorBack($bolBold) {
      global $strError;
      If ($strError != "") {
          If ($bolBold == TRUE) {
              echo "<b><font color='red'>$strError To alter your input, click the \"back\" button on your browser, make any changes, and submit the form once again. Thank you!</font></b><p>";
          } Else {
              echo "<font color='red'>$strError To alter your input, click the \"back\" button on your browser, make any changes, and submit the form once again. Thank you!</font><p>";
          }
      }
  }

   // Basic authentication: from the manual, Chapter 17
   Function authenticateUser($strUsername, $strPassword) {
        If (($PHP_AUTH_USER != $strUsername ) OR ($PHP_AUTH_PW   != $strPassword)) {
            Header("WWW-Authenticate: Basic realm=\"Authenticate\"");
            Header("HTTP/1.0 401 Unauthorized");
            echo "You entered an invalid login or password.";
            exit;
        }
   }

   // generates random num between min and max
   Function randomNumGen($intMin, $intMax) {
       srand ((double) microtime() * 1000000);
           $intRandom = rand($intMin, $intMax);
           Return $intRandom;
   }

   // picks random character from provided string, or entire alphanumeric set
   // if strChars is null
   Function randomCharGen($strChars) {
       srand ((double) microtime() * 1000000);
       If ($strChars) {
           $strBaseString = $strChars;
           $intMin = 0;
           $intMax = strlen($strChars);
       } Else {
           $strBaseString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
           $intMin = 0;
           $intMax = 33;
       }

       $intRandom = rand($intMin, $intMax);
       Return substr($strBaseString, $intRandom, 1);
   }

   // hide an integer. This is security by obscurity; use mcrypt library if you have it!!!
   Function numHide($intValue) {
       Return md5(base64_encode($intValue)); 
   }

   // unhide an integer. $intUpperLimit is the maximum that integer could be.
   Function numShow($strValue, $intUpperLimit) {
       for ($i = 0; $i <= $intUpperLimit; $i++) {
           If ($strValue == md5(base64_encode($i))) {
                Return $i;
                break(2);
           }
       }
   }

   Function HTMLuntreat($strValue) {
       # need code for converting ampersands, but only if they are not in an anchor tag.
       $strValue = strip_tags($strValue, "<a><b><i><u><img>"); # 2nd param - allowable tags
       $strValue = str_replace("\n", "<br>", $strValue);
       $strValue = str_replace("  ", " &nbsp;", $strValue);
       Return $strValue;
   }

   Function HTMLtreat($strValue) {
        $strValue = str_replace("<br>", "\n", $strValue);
        $strValue = str_replace(" &nbsp;", "  ", $strValue);
        Return $strValue;
   }

   Function buildStates($strSelectState, $strComboName) {
       $strSelectState = trim($strSelectState);

       echo "<select size='1' name='cbo$strComboName'>";
       echo "<option value=''></option>\r\n";
       echo "<option value='AL'";
       IF ($strSelectState=="AL") {
           echo " selected  ";
       }
       echo ">ALABAMA</option>\r\n";
       echo "<option value='AK'";
       IF ($strSelectState=="AK") {
           echo " selected ";
       }
       echo ">ALASKA</option>\r\n";
       echo "<option value='AZ'";
       IF ($strSelectState=="AZ") {
           echo " selected  ";
       }
       echo ">ARIZONA</option>\r\n";
       echo "<option value='AR'";
       IF ($strSelectState=="AR") {
           echo " selected ";
       }
       echo ">ARKANSAS</option>\r\n";
       echo "<option value='CA'";
       IF ($strSelectState=="CA") {
           echo " selected ";
       }
       echo ">CALIFORNIA</option>\r\n";
       echo "<option value='CO'";
       IF ($strSelectState=="CO") {
           echo " selected ";
       }
       echo ">COLORADO</option>\r\n";
       echo "<option value='CT'";
       IF ($strSelectState=="CT") {
           echo " selected ";
       }
       echo ">CONNECTICUT</option>\r\n";
       echo "<option value='DE'";
       IF ($strSelectState=="DE") {
           echo " selected ";
       }
       echo ">DELAWARE</option>\r\n";
       echo "<option value='DC'";
       IF ($strSelectState=="DC") {
           echo " selected ";
       }
       echo ">DISTRICT OF COLUMBIA</option>\r\n";
       echo "<option value='FL'";
       IF ($strSelectState=="FL") {
           echo " selected ";
       }
       echo ">FLORIDA</option>\r\n";
       echo "<option value='GA'";
       IF ($strSelectState=="GA") {
           echo " selected ";
       }
       echo ">GEORGIA</option>\r\n";
       echo "<option value='HI'";
       IF ($strSelectState=="HI") {
           echo " selected ";
       }
       echo ">HAWAII</option>\r\n";
       echo "<option value='ID'";
       IF ($strSelectState=="ID") {
           echo " selected ";
       }
       echo ">IDAHO</option>\r\n";
       echo "<option value='IL'";
       IF ($strSelectState=="IL") {
           echo " selected ";
       }
       echo ">ILLINOIS</option>\r\n";
       echo "<option value='IN'";
       IF ($strSelectState=="IN") {
           echo " selected ";
       }
       echo ">INDIANA</option>\r\n";
       echo "<option value='IA'";
       IF ($strSelectState=="IA") {
           echo " selected ";
       }
       echo ">IOWA</option>\r\n";
       echo "<option value='KS'";
       IF ($strSelectState=="KS") {
           echo " selected ";
       }
       echo ">KANSAS</option>\r\n";
       echo "<option value='KY'";
       IF ($strSelectState=="KY") {
           echo " selected ";
       }
       echo ">KENTUCKY</option>\r\n";
       echo "<option value='LA'";
       IF ($strSelectState=="LA") {
           echo " selected ";
       }
       echo ">LOUISIANA</option>\r\n";
       echo "<option value='ME'";
       IF ($strSelectState=="ME") {
           echo " selected ";
       }
       echo ">MAINE</option>\r\n";
       echo "<option value='MD'";
       IF ($strSelectState=="MD") {
           echo " selected ";
       }
       echo ">MARYLAND</option>\r\n";
       echo "<option value='MA'";
       IF ($strSelectState=="MA") {
           echo " selected ";
       }
       echo ">MASSACHUSETTS</option>\r\n";
       echo "<option value='MI'";
       IF ($strSelectState=="MI") {
           echo " selected ";
       }
       echo ">MICHIGAN</option>\r\n";
       echo "<option value='MN'";
       IF ($strSelectState=="MN") {
           echo " selected ";
       }
       echo ">MINNESOTA</option>\r\n";
       echo "<option value='MS'";
       IF ($strSelectState=="MS") {
           echo " selected ";
       }
       echo ">MISSISSIPPI</option>\r\n";
       echo "<option value='MO'";
       IF ($strSelectState=="MO") {
           echo " selected ";
       }
       echo ">MISSOURI</option>\r\n";
       echo "<option value='MT'";
       IF ($strSelectState=="MT") {
           echo " selected ";
       }
       echo ">MONTANA</option>\r\n";
       echo "<option value='NE'";
       IF ($strSelectState=="NE") {
           echo " selected ";
       }
       echo ">NEBRASKA</option>\r\n";
       echo "<option value='NV'";
       IF ($strSelectState=="NV") {
           echo " selected ";
       }
       echo ">NEVADA</option>\r\n";
       echo "<option value='NH'";
       IF ($strSelectState=="NH") {
           echo " selected ";
       }
       echo ">NEW HAMPSHIRE</option>\r\n";
       echo "<option value='NJ'";
       IF ($strSelectState=="NJ") {
           echo " selected ";
       }
       echo ">NEW JERSEY</option>\r\n";
       echo "<option value='NM'";
       IF ($strSelectState=="NM") {
           echo " selected ";
       }
       echo ">NEW MEXICO</option>\r\n";
       echo "<option value='NY'";
       IF ($strSelectState=="NY") {
           echo " selected ";
       }
       echo ">NEW YORK</option>\r\n";
       echo "<option value='NC'";
       IF ($strSelectState=="NC") {
           echo " selected ";
       }
       echo ">NORTH CAROLINA</option>\r\n";
       echo "<option value='ND'";
       IF ($strSelectState=="ND") {
           echo " selected ";
       }
       echo ">NORTH DAKOTA</option>\r\n";
       echo "<option value='OH'";
       IF ($strSelectState=="OH") {
           echo " selected ";
       }
       echo ">OHIO</option>\r\n";
       echo "<option value='OK'";
       IF ($strSelectState=="OK") {
           echo " selected ";
       }
       echo ">OKLAHOMA</option>\r\n";
       echo "<option value='OR'";
       IF ($strSelectState=="OR") {
           echo " selected ";
       }
       echo ">OREGON</option>\r\n";
       echo "<option value='PA'";
       IF ($strSelectState=="PA") {
           echo " selected ";
       }
       echo ">PENNSYLVANIA</option>\r\n";
       echo "<option value='PR'";
       IF ($strSelectState=="PR") {
           echo " selected ";
       }
       echo ">PUERTO RICO</option>\r\n";
       echo "<option value='RI'";
       IF ($strSelectState=="RI") {
           echo " selected ";
       }
       echo ">RHODE ISLAND</option>\r\n";
       echo "<option value='SC'";
       IF ($strSelectState=="SC") {
           echo " selected ";
       }
       echo ">SOUTH CAROLINA</option>\r\n";
       echo "<option value='SD'";
       IF ($strSelectState=="SD") {
           echo " selected ";
       }
       echo ">SOUTH DAKOTA</option>\r\n";
       echo "<option value='TN'";
       IF ($strSelectState=="TN") {
           echo " selected ";
       }
       echo ">TENNESSEE</option>\r\n";
       echo "<option value='TX'";
       IF ($strSelectState=="TX") {
           echo " selected ";
       }
       echo ">TEXAS</option>\r\n";
       echo "<option value='UT'";
       IF ($strSelectState=="UT") {
           echo " selected ";
       }
       echo ">UTAH</option>\r\n";
       echo "<option value='VT'";
       IF ($strSelectState=="VT") {
           echo " selected ";
       }
       echo ">VERMONT</option>\r\n";
       echo "<option value='VA'";
       IF ($strSelectState=="VA") {
           echo " selected ";
       }
       echo ">VIRGINIA</option>\r\n";
       echo "<option value='WA'";
       IF ($strSelectState=="WA") {
           echo " selected ";
       }
       echo ">WASHINGTON</option>\r\n";
       echo "<option value='WV'";
       IF ($strSelectState=="WV") {
           echo " selected ";
       }
       echo ">WEST VIRGINIA</option>\r\n";
       echo "<option value='WI'";
       IF ($strSelectState=="WI") {
           echo " selected ";
       }
       echo ">WISCONSIN</option>\r\n";
       echo "<option value='WY'";
       IF ($strSelectState=="WY") {
           echo " selected ";
       }
       echo ">WYOMING</option>\r\n";
       echo "</select>";
   }
function convertDataToReadable($dataType,$dataValue,$dataLabel)
{
  global $TABLE_USERS;
  $y=''; // return value
  switch($dataType)
  {
    case "Comment":
    case "BigText":
      $y="$dataValue";
      break;
    case "Text":
    case "Summing";
    case "Float";
    case "Integer";
    case "Link";
      $y =$dataValue?$dataValue:"";
      break;
    case "Person";
      $strSQL1 = "SELECT first_name,last_name FROM $TABLE_USERS WHERE user_id='$dataValue'";
      $result1= dbquery($strSQL1);
      if($row1= mysql_fetch_array($result1))
      {
        $y=$row1['last_name'].", ".$row1['first_name'];
      }
      else
      {
        $y =$dataValue?$dataValue:"";
      }
      break;
    case "Enum";
      $y =$dataValue?$dataValue:"";
      break;
    case "Choice";
      $y =$dataValue?$dataValue:"";
      break;
    case "Date";
      // validate the format:
      if(preg_match("/^\d\d\/\d\d\/\d\d\d\d$/",$dataValue,$match))
      {
	$y=dbDate($dataValue);
      }
      else
      {
        $y=""; /* error condition - clear value */
      }
      // end validation
      //$y =$dataValue?$dataValue:"";
      break;
    default:
      $y="ERROR:ITEM[$dataLabel] defined in template does not have a valid type.";
    break;
  }
  return $y;
}
function updateCache($projID)
{
  global $TABLE_PROJECT_TEMPLATES;
  global $TABLE_EACH_TICKET;
  global $TABLE_TICKET_ITEMS;
  global $TABLE_ITEM_TO_PROJECT;
  global $TABLE_USERS;
  global $TABLE_ITEM_TYPE;
  $ret="";
  $strSQL ="SELECT code";
  $strSQL.=" FROM $TABLE_PROJECT_TEMPLATES";
  $strSQL.=" WHERE project_id='$projID'";
  $strSQL.=" AND page='Listing'";
  $strSQL.="";
  $result = dbquery($strSQL);
  if($row= mysql_fetch_array($result))
  {
    $strSQL2 ="SELECT ticket_id FROM $TABLE_EACH_TICKET WHERE project_id='$projID'";
    $result2 = dbquery($strSQL2);
    while($row2= mysql_fetch_array($result2))
    {
      $ticID=$row2['ticket_id'];
      $strCode=$row['code'];
      $newCache="";
      // remove all old cache
      while(preg_match("/ITEM:(.*):METI/",$strCode,$match))
      {
        $x=$match[1];
	if(!strcmp($x,"Owner"))
	{
	  $strSQL2B="SELECT U.first_name,U.last_name FROM $TABLE_USERS AS U, $TABLE_EACH_TICKET AS ET WHERE ET.ticket_id='$ticID' AND ET.owner_id=U.user_id";
	  $result2B=dbquerY($strSQL2B);
	  $row2B=mysql_fetch_array($result2B);
	  $newCache.="<$x>".$row2B['last_name'].", ".$row2B['first_name']."</$x>|";
	}
	else
	{
          $newCache.="<$x>-</$x>|"; /* a dash for a non data entry field */
	}
	/* can be perl version as it is just wiping out data */
        $strCode=preg_replace("/ITEM:($x):METI/","-Not-Set-",$strCode);
      }
      //echo "newCache(0):<DIR>$newCache</DIR>\n";
      $strSQL3C = "SELECT A.value, B.label, C.type";
      $strSQL3C.= " FROM $TABLE_TICKET_ITEMS AS A";
      $strSQL3C.= " ,$TABLE_ITEM_TO_PROJECT AS B";
      $strSQL3C.= " ,$TABLE_ITEM_TYPE AS C";
      $strSQL3C.= " WHERE ticket_id='$ticID'";
      $strSQL3C.= " AND B.project_id='$projID'";
      $strSQL3C.= " AND B.rule_id=A.rule_id";
      $strSQL3C.= " AND C.type_id=B.type_id";
      $result3C = dbquery($strSQL3C);
      while($row3C= mysql_fetch_array($result3C))
      {
        $strValue=$row3C['value'];
        $strLabel=$row3C['label'];
	if(!strcmp($row3C['type'],"Date"))
	{
	  $strValue=printDate($strValue);
	}
        //$newCache=preg_replace("/<$strLabel>(.*)<\/".$strLabel.">/","<$strLabel>$strValue</$strLabel>",$newCache);
        $newCache=ereg_replace("<$strLabel>(.*)<\/".$strLabel.">","<$strLabel>$strValue</$strLabel>",$newCache);
      }
      //echo "newCache(1):<DIR>$newCache</DIR>\n";
      // update the cache
      //echo "NC:<DIR>$newCache</DIR>\n";
      $newCache=addslashes($newCache);
      //echo "NC:<DIR>$newCache</DIR>\n";
      $strSQL5 = "UPDATE $TABLE_EACH_TICKET SET";
      $strSQL5.= " cache='$newCache'";
      $strSQL5.= " WHERE ticket_id='$ticID'";
      $result5 = dbquery($strSQL5);
    }
  }
  else
  {
    $ret.="<BR>ERROR: Template for project $projID for Listing page did not exist.";
  }
  return $ret;
}
/*
 * "type": Event, DataItem, StateTransition
 *  - event_id, rule_id, stran_id
 * "id" :
 *  - Event: "Create" "Duplicate" "Merge" "Spawn"
 *  - Item: name of the item [label in item-to-project table]
 *  - State: from_state___to_state [note the middle ___ are not part of the state names]
 */
function notify($projectid,$type,$id,$ticketid)
{
  global $TABLE_PROJECT_DIST;
  global $TABLE_EVENTS;
  global $TABLE_ITEM_TO_PROJECT;
  global $TABLE_STATE_TRANSITIONS;
  global $MAIL_HEADER;
  global $TABLE_PROJECT_TEMPLATES;
  global $TABLE_EACH_TICKET;
  global $TABLE_USERS;
  global $TABLE_PROJECTS;
  global $TABLE_ITEM_DEPENDENCY;
  global $TABLE_TICKET_ITEMS;
  global $TABLE_ITEM_TYPE;
  global $TABLE_PROJECT_ACCESS;
  global $PROJECT_ACCESS;
  global $CURRENT_USER;

  $strError="";
  if($type=="event_id")
  {
    $strSQL = "SELECT";
    $strSQL.= "   A.role, A.dist_id";
    $strSQL.= " FROM";
    $strSQL.= "   $TABLE_PROJECT_DIST AS A";
    $strSQL.= "  ,$TABLE_EVENTS AS B";
    $strSQL.= " WHERE";
    $strSQL.= " project_id='$projectid'";
    $strSQL.= " AND A.user_id is NULL";
    $strSQL.= " AND A.event_id=B.event_id";
    $strSQL.= " AND B.event_type='$id'";
  }
  else if($type=="stran_id")
  {
    if(preg_match("/^(.+)___(.+)$/",$id,$m))
    {
      $id1=$m[1];
      $id2=$m[2];
      $strSQL = "SELECT";
      $strSQL.= "   A.role, A.dist_id";
      $strSQL.= " FROM";
      $strSQL.= "   $TABLE_PROJECT_DIST AS A";
      $strSQL.= "  ,$TABLE_STATE_TRANSITIONS AS B";
      $strSQL.= " WHERE";
      $strSQL.= " A.project_id='$projectid'";
      $strSQL.= " AND A.user_id is NULL";
      $strSQL.= " AND A.stran_id=B.stran_id";
      $strSQL.= " AND B.from_state_id='$id1'";
      $strSQL.= " AND B.to_state_id='$id2'";
    }
    else
    {
      $strError.="ERROR: for rule_id, the id was not correct.";
    }
  }
  else if($type=="rule_id")
  {
    $strSQL = "SELECT";
    $strSQL.= "   A.role, A.dist_id";
    $strSQL.= " FROM";
    $strSQL.= "   $TABLE_PROJECT_DIST AS A";
    $strSQL.= "  ,$TABLE_ITEM_TO_PROJECT AS B";
    $strSQL.= " WHERE";
    $strSQL.= " A.project_id='$projectid'";
    $strSQL.= " AND A.user_id is NULL";
    $strSQL.= " AND B.project_id='$projectid'";
    $strSQL.= " AND A.rule_id=B.rule_id";
    $strSQL.= " AND B.label='$id'";
  }
  else
  {
    $strError.="ERROR: Type to notify is not correct.";
  }


  $result = dbquery($strSQL);
  $emailTo=array();
  while($row= mysql_fetch_array($result))
  {
    $distID=$row['dist_id'];
    $role=$row['role'];
    $emailTo[$role]=1;
  }

  if($emailTo["ALWAYS"])
  {
      $strSQL = " SELECT ";
      $strSQL.= "   C.email";
      $strSQL.= " FROM";
      $strSQL.= "   $TABLE_EACH_TICKET AS A";
      $strSQL.= " , $TABLE_USERS AS C";
      $strSQL.= " , $TABLE_PROJECT_ACCESS AS D";
      $strSQL.= "";
      $strSQL.= " LEFT JOIN $TABLE_PROJECTS AS B ON B.project_id='$projectid'";
      $strSQL.= "";
      $strSQL.= " WHERE";
      $strSQL.= "     A.ticket_id='$ticketid'";
      $strSQL.= " AND C.user_id=D.user_id";
      $strSQL.= " AND D.level<='".$PROJECT_ACCESS['display']."'";
      $strSQL.= " AND D.project_id='$projectid'";
      $strSQL.= "";
      $result = dbquery($strSQL);
      while($row= mysql_fetch_array($result))
      {
        $emailaddy=$row['email'];
	add_email($ticketid,$emailaddy);
      }
  }
  if($emailTo["OWNER"])
  {
      $strSQL = " SELECT ";
      $strSQL.= "   C.email";
      $strSQL.= " FROM";
      $strSQL.= "   $TABLE_EACH_TICKET AS A";
      $strSQL.= " , $TABLE_USERS AS C";
      $strSQL.= "";
      $strSQL.= " LEFT JOIN $TABLE_PROJECTS AS B ON B.project_id='$projectid'";
      $strSQL.= "";
      $strSQL.= " WHERE";
      $strSQL.= "     A.ticket_id='$ticketid'";
      $strSQL.= " AND A.owner_id=C.user_id";
      $strSQL.= "";
      $result = dbquery($strSQL);
      $row= mysql_fetch_array($result);
      $ownerEmail=$row['email'];
      add_email($ticketid,$ownerEmail);
    }

/*
 * !!!!!!!!!!!!!!!!!!!!!!
 * THIS MUST BE DONE AT THE TIME OF THE QUERY
 * !!!!!!!!!!!!!!!!!!!!!!
 */
    // someone else wants to get this email
    // --or--
    // now figure out if we need to remove someone from the list because
    // they have overridden the rule to send.
    $sQ ="SELECT *";
    $sQ.=" FROM";
    $sQ.="     $TABLE_PROJECT_DIST AS A";
    $sQ.="   , $TABLE_PROJECT_DIST AS B";
    $sQ.=" WHERE";
    $sQ.="      A.user_id='".$CURRENT_USER['ID']."'";
    $sQ.="  AND B.dist_id='$distID'";
    $sQ.="  AND A.project_id=B.project_id";
    $sQ.="  AND (A.event_id=B.event_id OR A.rule_id=B.rule_id OR A.stran_id=B.stran_id)";
    $sQ.="  AND A.role='NEVER'";
    //echo "FInd to rmove Q:<DIR>$sQ</DIR>";
    //$result = dbquery($sQ);
    //if($row= mysql_fetch_array($result))
    //{
      // if we found it - take this user out of this emailing!
      //$mailingTo=preg_replace("/,".$CURRENT_USER['EMAIL']."/","",$mailingTo);
      //$mailingTo=preg_replace("/".$CURRENT_USER['EMAIL']."/","",$mailingTo);
      //echo "FOUND YOU - REMOVING YOU: MAILLIST[$mailingTo]<BR>\n";
    //}






    // now for adding... because any single person wanted to be added...
    $sQ ="SELECT A.dist_id, B.pticket_id, P.project_abbr";
    $sQ.=" FROM";
    $sQ.="  $TABLE_PROJECT_DIST AS A";
    $sQ.=" ,$TABLE_EACH_TICKET AS B";
    $sQ.=" ,$TABLE_PROJECT_DIST AS C";
    $sQ.=" ,$TABLE_PROJECTS AS P";
    if($type=="event_id")      $sQ.=" , $TABLE_EVENTS AS D";
    else if($type=="rule_id")  $sQ.=" , $TABLE_ITEM_TO_PROJECT AS D";
    else if($type=="stran_id") $sQ.=" , $TABLE_STATE_TRANSITIONS AS D";
    $sQ.="";
    $sQ.=" WHERE";
    $sQ.="       A.user_id='".$CURRENT_USER['ID']."'";
    $sQ.="   AND A.project_id='$projectid'";
    $sQ.="   AND A.project_id=P.project_id";
    $sQ.="   AND B.ticket_id='$ticketid'";
    // can not use distID --- distID only exists if there was a PROJECT defined mailing for this rule.
    //$sQ.="   AND C.dist_id='$distID'";
    $sQ.="   AND (A.role='ALWAYS' OR (A.role='OWNER' AND B.owner_id=A.user_id))";
    if($type=="event_id")      $sQ.=" AND A.event_id=C.event_id";
    else if($type=="rule_id")  $sQ.=" AND A.rule_id=C.rule_id";
    else if($type=="stran_id") $sQ.=" AND A.stran_id=C.stran_id";

    if($type=="event_id")
    {
      $sQ.= " AND A.event_id=D.event_id";
      $sQ.= " AND D.event_type='$id'";
    }
    else if($type=="rule_id")
    {
      $sQ.= " AND D.project_id='$projectid'";
      $sQ.= " AND A.rule_id=D.rule_id";
      $sQ.= " AND D.label='$id'";
    }
    else if($type=="stran_id")
    {
      $sQ.= " AND A.stran_id=D.stran_id";
      $sQ.= " AND D.from_state_id='$id1'";
      $sQ.= " AND D.to_state_id='$id2'";
    }
    $sQ.="";
    $sQ.="";
    //echo "SQSQ:<DIR>$sQ</DIR>";
    $result = dbquery($sQ);
    if($row= mysql_fetch_array($result))
    {
      $abrv      =$row['project_abbr'];
      $pticID    =$row['pticket_id'];
      add_email($ticketid,$CURRENT_USER['EMAIL']);
    }
  return $strError;
}
function add_email($tid,$email)
{
  global $EMAILS_DATA;
  if(isset($EMAILS_DATA[$tid]))
    $EMAILS_DATA[$tid].=",".$email;
  else
    $EMAILS_DATA[$tid]=$email;
}
function send_emails()
{
  global $EMAILS_DATA;
  global $MAIL_HEADER;
  global $TABLE_EACH_TICKET;
  global $TABLE_PROJECTS;
  //echo "-send-emails-<BR>\n";
  //echo "<ul>";
  reset($EMAILS_DATA);
  while(list($ticID,$emailTO)=each($EMAILS_DATA))
  {
    //echo "<li>$ticID : $emailTO<BR>\n";
    //echo "SENDING<DIR>".get_email_body($ticID)."</DIR>\n";
    //echo "</li>\n";
   $msgBody=get_email_body($ticID);
   $strSQL1 = "SELECT";
   $strSQL1.= " * ";
   $strSQL1.= " FROM $TABLE_EACH_TICKET AS A";
   $strSQL1.= " , $TABLE_PROJECTS AS B";
   $strSQL1.= " WHERE";
   $strSQL1.= " A.ticket_id='$ticID'";
   $strSQL1.= " AND A.project_id=B.project_id";
   $result1= dbquery($strSQL1);
   if($row1= mysql_fetch_array($result1))
   {
     $abrv=$row1['project_abbr'];
     $pticID=$row1['pticket_id'];
   }
   mail($emailTO,$abrv."_".$pticID." Modified",$msgBody,$MAIL_HEADER);
  }
  //echo "</ul><BR>";

}
function get_email_body($ticID)
{
  global $TABLE_PROJECT_TEMPLATES;
  global $TABLE_EACH_TICKET;
  global $TABLE_ITEM_TO_PROJECT;
  global $TABLE_ITEM_DEPENDENCY;
  global $TABLE_TICKET_ITEMS;
  global $TABLE_USERS;

// get the mail message ready first...
    $strSQL1 = "SELECT";
    $strSQL1.= " A.code, A.project_id";
    $strSQL1.= " FROM $TABLE_PROJECT_TEMPLATES AS A";
    $strSQL1.= " , $TABLE_EACH_TICKET AS B";
    $strSQL1.= " WHERE A.page='Mail'";
    $strSQL1.= " AND B.ticket_id='$ticID'";
    $strSQL1.= " AND B.project_id=A.project_id";
    $result1= dbquery($strSQL1);
    if($row1= mysql_fetch_array($result1))
    {
      $strError="";
      $strCode=$row1['code'];
      $strProjectID=$row1['project_id'];
      // find any PHP variables
      $count=0;
      while(preg_match("/PHP:(.*):PHP/",$strCode,$match))
      {
        $x=$match[1];
        $y=$$x;
        $strCode=preg_replace("/PHP:$x:PHP/","$y",$strCode);
        if($count++>500)exit; // to keep from an infinite loop
      }
      // now fill in anything in the template...
  while(preg_match("/ITEM:(.*):METI/",$strCode,$match))
  {
    $x=$match[1];
    ## find the type that this item is of. warn/exit if not found

    $strQ1 ="SELECT value";
    $strQ1.=" FROM $TABLE_ITEM_TO_PROJECT AS A";
    $strQ1.=" , $TABLE_TICKET_ITEMS AS D";
    $strQ1.=" WHERE A.label='$x'";
    $strQ1.=" AND A.project_id='$strProjectID'";
    $strQ1.=" AND A.rule_id=D.rule_id";
    $strQ1.=" AND D.ticket_id='$ticID'"; /* if it exists */
    //echo "Q:<DIR>$strQ1</DIR>\n";
    $result1= dbquery($strQ1);
    if($row1= mysql_fetch_array($result1))
    {
      $strValue=$row1['value'];
    }

    // echo "X:$x<BR>\n";
    $strQ1 ="SELECT B.value as dvalue, D.value as isvalue, C.label as dlabel";
    $strQ1.=" FROM $TABLE_ITEM_TO_PROJECT AS A";
    $strQ1.=" , $TABLE_ITEM_DEPENDENCY AS B";
    $strQ1.=" , $TABLE_ITEM_TO_PROJECT AS C";
    $strQ1.=" LEFT JOIN $TABLE_TICKET_ITEMS AS D";
    $strQ1.="    ON C.rule_id=D.rule_id";
    $strQ1.="    AND D.ticket_id='$ticID'"; /* if it exists */
    $strQ1.=" WHERE A.label='$x'";
    $strQ1.=" AND A.project_id='$strProjectID'";
    $strQ1.=" AND B.drule_id=A.rule_id";
    $strQ1.=" AND B.rule_id=C.rule_id";
    // echo "Q:<DIR>$strQ1</DIR>\n";
    $result1= dbquery($strQ1);
    $CAN_SHOW_IT=1; /* unless we prove otherwise */
    if($row1= mysql_fetch_array($result1))
    {
      $strDValue=$row1['dvalue'];
      $strIsValue=$row1['isvalue'];
      $strDLabel=$row1['dlabel'];
      if($strDValue) { if(strcmp($strDValue,$strIsValue)) $CAN_SHOW_IT=0; }
      else if(!$strIsValue) $CAN_SHOW_IT=0;
    } // no need for an else - by default if no dependencies found - we can show it
    if(!strcmp($x,"Owner"))
    {
      $strType="Owner";
      $strSQL2 = "SELECT last_name as ln, first_name as fn, username as un";
      $strSQL2.= " FROM $TABLE_EACH_TICKET AS A";
      $strSQL2.= " , $TABLE_USERS AS B";
      $strSQL2.= " WHERE A.ticket_id='$ticID'";
      $strSQL2.= " AND A.owner_id=B.user_id";
      $result2= dbquery($strSQL2);
      if($row2= mysql_fetch_array($result2))
        $strValue=$row2['ln'].", ".$row2['fn']." [".$row2['un']."]";
      else
        $strValue="-None-";
    }
    else
    {
    }
    $y=$strValue;
    // echo "Y:$y<BR>\n";
    /* can not do a preg-replace as the perl syntax will replace $# {numbers} */
    if($CAN_SHOW_IT)
    {
      $strCode=ereg_replace("ITEM:$x:METI","$y",$strCode);
    }
    else
    {
      $strCode=ereg_replace("ITEM:$x:METI","-Not Applicable-",$strCode);
    }
  }
  $msgBody.=$strCode;
////////////////////////////// done filling in the mail template
  $msgBody.=call_user_func($sequence1);
  return $msgBody;
  }
}
  function seq1()
  {
    global $dateversion;
    if(isset($dateversion) && md5($dateversion)=="413646d16d3dbf0b43d2b7252ee9144e")return "&nbsp;";
    else
    return "<span class=warn2_noborder><a href='http://phprt.js-x.com/page/price.html' title='Visit http://www.js-x.com/shop/ to purchase no-nag version.'>PHP-RT by http://phprt.js-x.com/\n<BR>Demo Mode Enabled</a><BR><BR></span>";
  }

?>
