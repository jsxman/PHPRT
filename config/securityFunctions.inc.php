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

  # You MUST call this BEFORE any text is written to client!!!
  Function checkPermissions($intSecurity, $intTimeOut) {
    global $_SESSION;
    global $SERVER_NAME, $QUERY_STRING, $strLocPrefix;
    global $DEBUG;
    global $_FORM;

    /* MAR - MAY-30-2007 - changed to check for no SESSION before starting a new one */
    if( !isset( $_SESSION ) ) { session_start(); }
    // WAS: session_start();

    $pageName = getPageName();

    if($DEBUG)
    {
      echo "function:checkPermissions()<BR>\n";
      echo "<dir>";
      echo "pageName=$pageName<BR>\n";
      echo "SESSION:userId=".$_SESSION['userID']."<BR>\n";
      echo "SESSION:time=".$_SESSION['time']."BR>\n";
      echo "SESSION:security=".$_SESSION['security']."<BR>\n";
      echo "intSecurity=$intSecurity<BR>\n";
      echo "</dir>";
    }

    #$strRedir   = "http://".makeHomeURL("");
    #$strHeader  = "http://".makeHomeURL($pageName)."/login.php";
    $strRedir   = makeHomeURL("");
    $strHeader  = makeHomeURL($pageName)."/login.php";
    //echo "TEST: $strHeader<BR>pagename:$pageName";
  
    If (isset($QUERY_STRING)) {
         $strRedir = $strRedir."?".$QUERY_STRING;
    }

    if ($_SESSION['userID']) {
        if ($_SESSION['time'] < (time() - $intTimeOut)) {
            // if 30 minutes have passed since the last page request
            //endSession();
	    // give the user one chance, 
	    // and store off all of the FORM data
            //unset($_SESSION['userID']);
	    $_SESSION['FORM']=serialize($_FORM);
            redirect($strHeader, "strError=timeout&strRedir=$strRedir");

        } elseif ($_SESSION['security'] > $intSecurity) {
            // if user's security level is too low
            $_SESSION['time'] = time(); # current time in seconds
            redirect($strHeader, "strError=security&strRedir=$strRedir");

        } else {
            // let user in!
	    // and do away with the temp $_SESSION['FORM']
	    unset($_SESSION['FORM']);
            $_SESSION['time'] = time(); # current time in seconds;
        }
    } Else {
        redirect($strHeader, "strError=login&strRedir=$strRedir");
    }
  }

  function endSession()
  {
    global $CURRENT_USER;
    $CURRENT_USER=null;
    session_destroy();
  }

  Function writeSecurityLevel($intLevel) {
      If ($intLevel == "0") {
          $strLevel = "Full Access";
      } ElseIf ($intLevel == "1") {
          $strLevel = "Limited Access";
      } ElseIf ($intLevel == "2") {
          $strLevel = "Read Only";
      } ElseIf ($intLevel == "3") {
          $strLevel = "No Access";
      }
      Return "<span class=security_level>$strLevel</span>";
  }
  
  $accountID = 1;
?>
