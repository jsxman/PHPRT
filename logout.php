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

    Include("config/global.inc.php");
    checkPermissions(1, $SESSION_TIMEOUT); // logged in? - keep session going for a bit more


    if($_SESSION['userID'])
    {
      $strSQL1 ="SELECT num_logouts FROM $TABLE_USERS WHERE user_id='$userID'";
      $result1 = dbquery($strSQL1);
      $row1    = mysql_fetch_array($result1);
      $num_lo  =1+$row1['num_logouts'];
      $strSQL2 ="UPDATE $TABLE_USERS";
      $strSQL2.= " SET num_logouts=$num_lo, last_logout=".date("U");
      $strSQL2.= " WHERE user_id ='$userID'";
      $result2 = dbquery($strSQL2);

      $strSQL2B ="INSERT INTO $TABLE_ACCESSTIMES (user_id,login,logout) VALUES ('$userID',NULL,".date(U).")";

      $result2B = dbquery($strSQL2B);

    }

    //$CURRENT_USER=null;
    //session_destroy();
    endSession();

    writeHeader("Logout Complete");

    echo "<BR><table class=message align=center><tr><td>";
    echo "You have successfully logged out.";
    echo "</td></tr></table>\n<BR>";

    writeFooter();
?>
