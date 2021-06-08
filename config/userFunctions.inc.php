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


  // CURRENT_USER:
  //  ID
  //  FIRST_NAME
  //  LAST_NAME
  //  IS_A_DB_ADMIN
  //  IS_A_PROJECT_ADMIN
  //  PROJECT_ADMIN:
  //    PROJECT_ID=>T/F (FLAG FOR ADMIN)
  // variables based on this user
  if($_SESSION['userID'])
  {
    $CURRENT_USER['ID']=$_SESSION['userID'];
    $Q="SELECT * FROM $TABLE_USERS WHERE user_id='".$_SESSION['userID']."'";
    if($R=dbquery($Q))
    {
      if($r=mysql_fetch_array($R))
      {
        $CURRENT_USER['IS_A_DB_ADMIN']=($r['db_admin']?TRUE:FALSE);
        $CURRENT_USER['FIRST_NAME']=$r['first_name'];
        $CURRENT_USER['LAST_NAME']=$r['last_name'];
        $CURRENT_USER['USERNAME']=$r['username'];
        $CURRENT_USER['EMAIL']=$r['email'];
        $CURRENT_USER['PHONE']=$r['phone'];
        $CURRENT_USER['LAST_LOGIN']=$r['last_login'];
        $CURRENT_USER['LAST_LOGOUT']=$r['last_logout'];
        $CURRENT_USER['LAST_BADPASS']=$r['last_badpass'];
        $CURRENT_USER['ACTIVATED_ACCOUNT']=$r['activated_account'];
        $CURRENT_USER['DB_ADMIN']=$r['db_admin'];
        $CURRENT_USER['GUEST']=$r['guest'];
      }
    }
    $Q2="SELECT * FROM $TABLE_PROJECT_ACCESS WHERE user_id='".$_SESSION['userID']."'";
    if($R2=dbquery($Q2))
    {
      while($r2=mysql_fetch_array($R2))
      {
        if($r2['level']<=$PROJECT_ACCESS['admin'])
          $CURRENT_USER['IS_A_PROJECT_ADMIN']=TRUE;
        $CURRENT_USER['PROJECT_LEVEL'][$r2['project_id']]=$r2['level'];
      }
    }
  }

  # functions.inc.php *must* be included in a page before this file.

  Function buildUserSelect_notUsed($intUserID, $showSpare, $intObjectID="", $forceIndependent="") {
      global $accountID;
      global $TABLE_USERS;
      global $TABLE_ITEM;
      global $TABLE_CAT;
           If ($intObjectID == "") {
               $strSQL = "SELECT id, firstName, middleInit, lastName FROM $TABLE_USERS WHERE accountID=$accountID ORDER BY lastName";
               $spareText = "system";
           } Else {
               $strSQL = "SELECT DISTINCT s.id, s.firstName, s.middleInit, s.lastName FROM $TABLE_ITEM as h, $TABLE_USERS as s ";
               $strSQL .= "WHERE s.id=h.userID AND s.accountID=$accountID";
               $spareText = "part";
           }
           $strReturnString = "<select name='cboUser' size='1'>\n";
           $strReturnString .= "<option value=''>&nbsp;</option>\n";
           if ($showSpare) {
                $strReturnString .= "<option value='spare'>** Make this a spare $spareText **</option>\n";
                if ($intObjectID == "" OR $forceIndependent) {
                    $strReturnString .= "<option value='independent'>** Make this an independent system **</option>\n";
                }
                $showDivider = TRUE;
           }
           if ($intObjectID != "") {
                $strSQLx = "SELECT count(*) FROM $TABLE_ITEM WHERE sparePart='1' AND accountID=$accountID";
                $resultx = dbquery($strSQLx);
                $rowx = mysql_fetch_row($resultx);
                If ($rowx[0] > 0) {
                    $strReturnString .= "<option value='sparesystem'>** Assign to a spare system **</option>\n";
                    $showDivider = TRUE;
                }
                mysql_free_result($resultx);

                $strSQLx = "SELECT count(*) FROM $TABLE_ITEM WHERE sparePart='2' AND accountID=$accountID";
                $resultx = dbquery($strSQLx);
                $rowx = mysql_fetch_row($resultx);
                If ($rowx[0] > 0) {
                    $strReturnString .= "<option value='independentSystem'>** Assign to an independent system **</option>\n";
                    $showDivider = TRUE;
                }
                mysql_free_result($resultx);
           }

           If ($showDivider) {
               $strReturnString .= "<option value=''>&nbsp;</option>\n";
           }

           $result = dbquery($strSQL);
           while ($row = mysql_fetch_array($result)) {
                $strReturnString .= "<option value='".$row['id']."' ".writeSelected($row['id'], $intUserID).">";
                $strReturnString .= buildName($row["firstName"], $row["middleInit"], $row["lastName"], 0);
                $strReturnString .= "</option>\n";
           }
           $strReturnString .= "</select>\n";
      Return $strReturnString;
  }

  Function buildSystemSelect_notUsed($intUserID, $intSystemID) {
      global $accountID;
      global $TABLE_ITEM;
      global $TABLE_CAT;
      If ($intUserID == "sparesystem") { # build list of all spare systems
          $strSQL = "SELECT ht.type_desc, h.cost, h.pk_asset FROM $TABLE_CAT as ht, $TABLE_ITEM as h
            WHERE h.sparePart='1' AND h.type=ht.type_pk AND ht.accountID=$accountID
            ORDER BY ht.type_desc ASC";
      } ElseIf ($intUserID == "independentSystem") { # build list of all independent systems
          $strSQL = "SELECT ht.type_desc, h.cost, h.pk_asset FROM $TABLE_CAT as ht, $TABLE_ITEM as h
            WHERE h.sparePart='2' AND h.type=ht.type_pk AND ht.accountID=$accountID
            ORDER BY ht.type_desc ASC";
      } ElseIf ($intUserID != "") { # build list of all systems associated with userID
          $strSQL = "SELECT ht.type_desc, h.cost, h.pk_asset FROM $TABLE_CAT as ht, $TABLE_ITEM as h,
            tblSecurity as s WHERE s.id=h.userID AND h.type=ht.type_pk AND h.userID=$intUserID AND
            ht.accountID=$accountID ORDER BY ht.type_desc ASC";
      }
      
      If ($intUserID != "") {
          $result = dbquery($strSQL);

          $strReturnString = "<select name='cboSystem' size='1'>\n";
          $strReturnString .= "<option value=''>&nbsp;</option>\n";
          while ($row = mysql_fetch_array($result)) {
              $strReturnString .= "<option value='".$row['pk_asset']."' ".writeSelected($row['pk_asset'], $intSystemID).">";
              $strReturnString .= $row['type_desc']."&nbsp; - &nbsp;asset ID: ".$row['pk_asset']." &nbsp;-&nbsp; serial #: ".$row['cost'];
              $strReturnString .= "</option>\n";
          }
          $strReturnString .= "</select>\n";
          Return $strReturnString;
      }
  }

  // $intObjectID = the ID of target object, if anything OTHER than a system.
  Function buildUserSystemSelect_notUsed($intUserID, $intSystemID, $intObjectID = "") {
      global $SCRIPT_NAME, $fromSystem, $spare;
      If (!$intUserID) {
            If ($fromSystem) {
                $showSpare = TRUE;
            }
            echo "Select a user:<p>";
            echo "<form method='post' action='$SCRIPT_NAME'>";
            echo buildUserSelect($intUserID, $showSpare, $intObjectID);
            echo "<input type='hidden' name='objectID' value='$intObjectID'>";
            echo "<input type='hidden' name='fromSystem' value='$fromSystem'>";
            echo "<input type='hidden' name='spare' value='$spare'>";
            echo "<p><input type='submit' value='Submit' name='btnSubmit1'>";
            echo "</form>";
      } ElseIf (!$intSystemID AND ($intUserID != "spare")) {
            echo "Select a system:<p>";
            echo "<form method='post' action='$SCRIPT_NAME'>";
            echo buildSystemSelect($intUserID, $intSystemID);
            echo "<input type='hidden' name='objectID' value='$intObjectID'>";
            echo "<input type='hidden' name='cboUser' value='$intUserID'>";
            echo "<input type='hidden' name='fromSystem' value='$fromSystem'>";
            echo "<input type='hidden' name='spare' value='$spare'>";
            echo "<p><input type='submit' value='Submit' name='btnSubmit2'>";
            echo "</form>";
      }
  }
?>
