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

  @include("config/global.inc.php");

  if(isset($_FORM['activate']))
  {
    $strActivate = validateText("Code", $_FORM['activate'], 1, 80, TRUE, FALSE);
    //search for an account that has this code and is disabled.
    //then enable the account and remove the code from the db
    $strSQL = "SELECT U.user_id";
    $strSQL.= " FROM $TABLE_USERS AS U";
    $strSQL.= " WHERE U.activated_account='0' AND U.reactivate='$strActivate'";
    //echo "Q:<DIR>$strSQL</DIR>\n";
    $result = dbquery($strSQL);
    if($row = mysql_fetch_array($result))
    {
      $strSQL2 = "UPDATE $TABLE_USERS";
      $strSQL2.= " SET activated_account='1', reactivate = NULL, succ_badpass='0'";
      $strSQL2.= " WHERE user_id = ".$row['user_id'];
      $result2 = dbquery($strSQL2);
      $strError.="SUCCESS: Account is now active again.";
    }
  }
  else if (isset($_FORM['btnSubmit']))
  {
    $strUserName = validateText("Username", $_FORM['txtUserName'], 3, 20, TRUE, FALSE);
    $strPassword = validateText("Password", $_FORM['txtPassword'], 6, 10, TRUE, FALSE);

    if ($strError == "")
    {
        if($DEBUG){echo "strError-null(login)<BR>\n";}
        $strPassword = md5($strPassword);
        $strSQL1 = "SELECT U.num_logins, U.username, U.user_id, U.activated_account, U.db_admin, U.guest";
        $strSQL1.= " FROM $TABLE_USERS AS U";
        $strSQL1.= " WHERE U.username='$strUserName' AND U.password='$strPassword'";
        $result1 = dbquery($strSQL1);
        $row1 = mysql_fetch_array($result1);
	$IS_GUEST=isset($row['guest'])?$row['guest']:0;
        if ($row1['user_id'] && $row1['user_id'] != "" && $row1['activated_account'])
        {
            if($DEBUG){echo "user exists and account active(login)";}

            $_SESSION['userID']   = $row1['user_id'];
            $_SESSION['time']     = time();
            $_SESSION['dbAdmin']  = $row1['db_admin'];
            $_SESSION['security'] = 1;

            $num_logins=1+$row1['num_logins'];
            $strSQL2 = "UPDATE $TABLE_USERS";
            $strSQL2.= " SET num_logins='$num_logins', last_login=".date("U");
            $strSQL2.= " ,succ_badpass='0'";
            $strSQL2.= " WHERE user_id = '".$row1['user_id']."'";
            $result2 = dbquery($strSQL2);

            $strSQL2B ="INSERT INTO $TABLE_ACCESSTIMES (user_id,login,logout) VALUES ('".$_SESSION['userID']."',".date("U").",NULL)";
            $result2B = dbquery($strSQL2B);


	    /* check to see when a password times out - and then see if this password has expired.
	     * if it has - then generate a new one and email it to them... and tell them
	     */
             $strSQL1A = "SELECT A.pswd_exp_time, B.passworddate, B.email, B.guest";
             $strSQL1A.= " FROM $TABLE_USERS AS B";
             $strSQL1A.= " LEFT JOIN $TABLE_DB_INFO AS A ON A.pswd_exp_time > '0'";
             $strSQL1A.= " WHERE B.user_id='".$row1['user_id']."'";
	     //echo "Q:<DIR>$strSQL1A</DIR>";
             $result1A = dbquery($strSQL1A);
             $row1A = mysql_fetch_array($result1A);
	     $IS_GUEST=isset($row1A['guest'])?$row1A['guest']:0;
	     $pET=$row1A['pswd_exp_time'];  // stored as DAYS in DB
	     $pETS=$pET*60*60*24; // convert to seconds
	     $pUEX=$row1A['passworddate']; // time password was set
	     $pDelta=date("U")-$pUEX; // seconds since the EPOC - pUEX
	     $strEmail=$row1A['email'];
	     //echo "PD=$pDelta , pETS=$pETS<BR>\n";
	     /* only do this if it is not a guest account */
	     //echo "GUEST=$IS_GUEST=<BR>";
	     //echo "pET=$pET and pUEX=$pUEX and pDelta=$pDelta and pETS=$pETS<BR>\n";
	     // 10000            1120748875        990301  -- 36000000
	     // if $pET=0 then it is deactivated as a feature
	     if(!$IS_GUEST && $pET && ($pDelta>$pETS))
	     {
	       $strError= "Your password has expired.<BR>  A new one has been created and emailed to you.<BR>  You must use this new password next time you log in.<BR>  You are now logged in and can continue.<BR><BR>";
	       $strError.= "<BR><BR>To continue - <a href='$PAGE_INDEX'>click here</a>.";
	       //$strError.="<meta http-equiv='refresh' content='$REFRESH_TIME_SEC;$newURL'>";
               $new_password="";
               $strTempString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
               for ($i = 0; $i < 8; $i++)
               {
                 $intPos = rand(0, 33);
                 $strTempChar = substr($strTempString, $intPos, 1);
                 $new_password.=$strTempChar;
               }
	       $strSQLU ="UPDATE $TABLE_USERS SET";
	       $strSQLU.=" password='".md5($new_password)."'";
	       $strSQLU.=" , passworddate='".date(U)."'";
	       $strSQLU.=" WHERE user_id='".$row1['user_id']."'";
               $resultU = dbquery($strSQLU);

               $msgBody = "Your password expired - new one generated.<BR>\n";
               $msgBody.= "From: ".makeHomeURL()."<BR>\n";
               $msgBody.= "Your username is: $strUserName<BR>\n";
               $msgBody.= "Your Password is: $new_password<BR>\n";
               if($strEmail)
               {
               mail($strEmail,"Password Expired: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);
               $strError.= "<BR>Password sent via email to $strEmail.";
               }
               else
               {
               mail($adminEmail,"PHP RT User Password Expired: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);
               $strError.= "<BR>Password sent via email to $adminEmail.";
               }
	       $LOGGED_IN=1;



	     }
	     //echo "DB-Timeout: ".$row1A['pswd_exp_time']."<BR>\n";
	     //echo " -- seconds --: ".(60*60*$row1A['pswd_exp_time'])."<BR>\n";
	     //echo "User-PW Date: ".$row1A['passworddate']."<BR>\n";
	     //echo "Now Date: ".date("U")."<BR>\n";
	     //echo "DELTA:".(date("U")-$row1A['passworddate'])."<BR>\n";
	     //exit;

            if (!$strError)
            {
                if(!$strRedir){$strRedir="index.php";}
                if($DEBUG)
                {
                  echo "UserID=".$_SESSION['userID']."<BR>\n";
                  echo "security=".$_SESSION['security']."<BR>\n";
                  echo "Redirect to: $strRedir<BR>\n";
                }
                header ("Location: $strRedir");
                exit;
            }
        }
        else
        {
            $strError="";
            $strSQL3 = "SELECT username, num_badpass, succ_badpass, activated_account, email, guest";
            $strSQL3.= " FROM $TABLE_USERS";
            $strSQL3.= " WHERE username='$strUserName'";
            $result3 = dbquery($strSQL3);
            $row3=mysql_fetch_array($result3);
	    $IS_GUEST=$row3['guest'];
            if($row3['username'] !="")
            {
              // User exists - just bad pass or account not activated.
              if(!$row3['activated_account'])
              {
                $reason="3"; // acount disabled
              }
              else
              {
                // $strError.="<BR>Bad Password\n";
                $reason="4A"; // bad password
                $succ_bad=1+$row3['succ_badpass'];
                $bad_pass=1+$row3['num_badpass'];
                $temp="";
		/* only if it is not a guest account */
                if(!$IS_GUEST && ($succ_bad>=$MAX_BAD_PASS))
                {
                  $reason="4B"; // bad password
                  $activateCode="";
                  $strTempString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
                  for ($i = 0; $i < 10; $i++)
                  {
                    $intPos = rand(0, 33);
                    $strTempChar = substr($strTempString, $intPos, 1);
                    $activateCode.=$strTempChar;
                  }
		  $md5ActivateCode=md5($activateCode);
                  $temp=", activated_account='0', reactivate='$md5ActivateCode'";


$aLink=makeHomeURL()."?activate=".$md5ActivateCode;
$msgBody = "Your account has become disabled due to too many successive bad password attempts.";
$msgBody.= "You must reactivate your account.<BR>\n";
$msgBody.= "Click this link to do this now.  You will not be able to log in until you do.<BR>\n";
$msgBody.= " <a href='$aLink'>$aLink</a><BR><BR>";
$msgBody.= "From: ".makeHomeURL()."<BR>\n";
$msgBody.= "Your Username: $strUsername<BR>\n";
$msgBody.= "If you did not cause action to recieve this notice, please notify $adminEmail.";

mail($row3['email'],"PHP RT Account Disabled: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);
mail($adminEmail,"User [$strUserName] Account Disabled: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);

                }
                $strSQL4 ="UPDATE $TABLE_USERS";
                $strSQL4.= " SET succ_badpass='$succ_bad', last_badpass=".date("U");
                $strSQL4.= " ,num_badpass='$bad_pass' $temp";
                $strSQL4.= " WHERE username = '$strUserName'";
                $result4 = dbquery($strSQL4);
              }
            }
            else
            {
              $reason="5"; // username not valid
            }
            $strError = "You can not log in with that username/password. (0x3$reason)";
        }
    }
  }

  writeHeader("");

  if(!isset($strError))$strError="";
  switch ($strError) {
    case "timeout":
        $strError = ""; // clear
        fillError("Your session has timed out. Please log in again.");
        break;
    case "security":
        $strError = ""; // clear
        fillError("Sorry, you do not have rights to that page.");
        $intNote = 1;
        break;
    case "login":
        $strError = ""; // clear
        fillError("Please log in.");
        break;
    case "":
        $strError = "";
        break;
  }

  If ($strError != "") {
      echo $strError."<BR>\n";
  }
if(!isset($LOGGED_IN) || !$LOGGED_IN)
{
?>

<script>
function myLoad()
{
document.form1.txtUserName.focus();
}
onload=myLoad;
function pword()
{
  var _f=document.form1;
  if(_f.txtPassword1.value!='')
  {
    _f.txtPassword.value=_f.txtPassword1.value;
    _f.txtPassword1.value='';
  }
  return true;
}
</script>
<form name="form1" method="POST" action="login.php" onsubmit="pword(this.form)">
<input type=hidden name="txtPassword">
  <br><table align=center border='0' cellspacing=0 cellpadding=0>
    <tr><td colspan=3 align=center><?echo call_user_func($sequence1);?></td></tr>
    <tr>
      <td class=forms_login >Username:</td>
      <td class=forms_login width=10>&nbsp;</td>
      <td class=forms_login ><input class=forms_login type="text" name="txtUserName" value="<?echo isset($strUserName)?$strUserName:"";?>" size="20"></td>
    </tr>
    <tr>
      <td class=forms_login >Password:</td>
      <td class=forms_login width=10>&nbsp;</td>
      <td class=forms_login ><input class=forms_login type="password" name="txtPassword1" size="20"></td>
    </tr>
    <tr height=10><td colspan=3></td></tr>
    <tr>
      <td class=form_login colspan=3 align=center>
         <input type="hidden" name="strRedir" value="<?echo isset($strRedir)?$strRedir:"";?>">
         <input class=form_button type="submit" value="Submit" name="btnSubmit">
         <input class=form_button type="reset" value="Reset" name="reset">
      </td>
    </tr>
  </table><br>
  <?
       if(!isset($strRedir))$strRedir="";
       if(!isset($intNote))$intNote=0;
       If (!$strRedir OR $intNote) {
		   $strRedir = "http://".makeHomeURL("login.php")."/index.php";
       }
  ?>
</form>

<a href='forgotPW.php'><font size='-1'>Forgot your password?</font></a>

<BR><BR>

<?
}
  writeFooter();
?>
