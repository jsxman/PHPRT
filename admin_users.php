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

  include("config/global.inc.php");
  checkPermissions(1, $SESSION_TIMEOUT); // user logged in? - keep session alive...

  /* more security */
  //echo $_FORM['txtUserID']."T_ID<BR>\n";
  $strUserID = validateNumber("User ID", $_FORM['txtUserID'], -1, 1000000, FALSE);
  $strGuest  = validateNumber("Guest Flag", $_FORM['txtGuest'], 0, 1, FALSE);
  //echo "$strUserID=ID<BR>\n";

  // non admins go bye-bye
  // users (admins) trying to edit their own profile go bye-bye (this prevents a database from getting no admins)
  /* more security - changed next line */
  // if(!$CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['ID']==$_FORM['txtUserID'])
  if(!$CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['ID']==$strUserID)
  {
    redirect("$PAGE_EDIT_USER");
    exit;
  }

  // Either:
  // Creating New User (txtUserID==-1 AND a Username exists
  // -OR-
  // Editing a User (btnSubmit exists) AND USER ID exists

  if ($_FORM['deleteSubmit'])
  { 
    /* more security - changed next line */
    //if($_FORM['txtUserID'] && $_FORM['txtUserID']!=$CURRENT_USER['ID'])
    if($_FORM['txtUserID'] && $strUserID!=$CURRENT_USER['ID'])
    {
      //$strUserID       = validateText("User ID", $_FORM['txtUserID'], 1, 20, TRUE, FALSE);
      $strUserID = validateNumber("User ID", $_FORM['txtUserID'], 1, 1000000, TRUE);

      $strSQL2="DELETE FROM $TABLE_USERS WHERE user_id='$strUserID'";
      $result2=dbquery($strSQL2);
      $strSQL3="DELETE FROM $TABLE_ACCESSTIMES WHERE user_id='$strUserID'";
      $result3=dbquery($strSQL3);
      $strSQL4="DELETE FROM $TABLE_PROJECT_ACCESS WHERE user_id='$strUserID'";
      $result4=dbquery($strSQL4);
      $strSQL5="DELETE FROM $TABLE_TICKET_DIST WHERE user_id='$strUserID'";
      $result5=dbquery($strSQL5);
      $strSQL6="DELETE FROM $TABLE_PROJECT_DIST WHERE user_id='$strUserID'";
      $result6=dbquery($strSQL6);
      $strError = "User ($strUserID) )Deleted.<BR>";
      $strError.= "(User / AccessTimes / ProjectAccesses / Ticket & Project Distributions)";
      $strUserID=NULL; // back to the default page
    }
  }
  else if ($strUserID==-1 && $_FORM['txtUsername']) // creating a new user
  {
    $strUserID     = validateNumber("User ID", $_FORM['txtUserID'], 1, 1000000, TRUE);
    //$strUserID       = validateText("User ID", $_FORM['txtUserID'], 1, 20, TRUE, FALSE);
    $strUsername     = validateText("Username", $_FORM['txtUsername'], 1, 40, TRUE, FALSE);
    $strFirstName    = validateText("First Name", $_FORM['txtFirstName'], 2, 40, TRUE, FALSE);
    $strLastName     = validateText("Last Name", $_FORM['txtLastName'], 2, 40, TRUE, FALSE);
    $strEmail        = validateEmail("Email Address", $_FORM['txtEmail'], FALSE);
    $strPhone        = validateText("Phone Number", $_FORM['txtPhone'], 4, 25, FALSE, FALSE);
    $strPassword1    = validateText("Password1", $_FORM['txtPassword1'], 6, 20, FALSE, FALSE);
    $strPassword2    = validateText("Password2", $_FORM['txtPassword2'], 6, 20, FALSE, FALSE);
    $strFrameMode    = validateText("Frame Mode", $_FORM['txtFrameMode'], 1, 1, FALSE, FALSE);
    $strView         = validateText("View", $_FORM['txtView'], 1, 40, FALSE, FALSE);
    $strCorder       = validateText("Column Order", $_FORM['txtCorder'], 1, 40, FALSE, FALSE);
    $strRorder       = validateText("Row Order", $_FORM['txtRorder'], 1, 40, FALSE, FALSE);
    $strDBadmin      = validateText("DB Admin Flag", $_FORM['txtDBadmin'], 1, 1, FALSE, FALSE);
    $strNotify       = validateText("Notify User On Chagne Flag", $_FORM['txtNotify'], 1, 1, FALSE, FALSE);

    $new_password="";
    $strTempString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
    for ($i = 0; $i < 8; $i++)
    {
      $intPos = rand(0, 33);
      $strTempChar = substr($strTempString, $intPos, 1);
      $new_password.=$strTempChar;
    }

    /*
     * We must determine if there is already a user by this name taken.
     */
    $strSQL6A ="SELECT user_id FROM $TABLE_USERS WHERE username='$strUsername'";
    $result6A=dbquery($strSQL6A);
    if($row6A=mysql_fetch_array($result6A))
    {
      $strError.="<BR>ERROR: That username '$strUsername' is already taken.<BR>\n";
    }
    else
    {
      $strSQL6 ="INSERT INTO $TABLE_USERS SET ";
      $strSQL6.="  username='$strUsername'";
      $strSQL6.=", first_name='$strFirstName'";
      $strSQL6.=", last_name='$strLastName'";
      $strSQL6.=", email='$strEmail'";
      $strSQL6.=", phone='$strPhone'";
      $strSQL6.=", password='".md5($new_password)."'";
      $strSQL6.=", passworddate='".date(U)."'";
      $strSQL6.=", activated_account='1'";
      $strSQL6.=", db_admin='$strDBadmin'";
      $strSQL6.=", account_create_date='".date(U)."'";
      $result6=dbquery($strSQL6);
      $strSQL7 ="SELECT user_id FROM $TABLE_USERS WHERE username='$strUsername'";
      $result7=dbquery($strSQL7);
      $row7=mysql_fetch_array($result7);
      $strUserID=$row7['user_id']; // so bottom of this page will show this user just created

      $strError.= "<BR>User ID $strUserID Created.";
      $msgBody = "From: ".makeHomeURL()."<BR>\n";
      $msgBody.= "Your Username: $strUsername<BR>\n";
      $msgBody.= "Your Name: $strFirstName $strLastName<BR>\n";
      $msgBody.= "Your Email Address: $strEmail<BR>\n";
      $msgBody.= "Your Phone number is: $strPhone<BR>\n";
      $msgBody.= "Your Password is: $new_password<BR>\n";
      $msgBody.= "Your View is: $strView<BR>\n";
      $msgBody.= "Your Frame Mode is: $strFrameMode<BR>\n";
      $msgBody.= "If you did not request this change, please notify $adminEmail.";
      if($strEmail)
      {
        mail($strEmail,"PHP RT User Profile Edit: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);
        $strError.= "<BR>Password sent via email to $strEmail.";
      }
      else
      {
        mail($adminEmail,"PHP RT User Profile Edit: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);
        $strError.= "<BR>Password sent via email to $adminEmail.";
      }
    }
  }
  else if ($_FORM['btnSubmit'] && $_FORM['txtUserID'])
  {
    // if data was submitted ... to edit a user ID
    if($DEBUG)print "Recieved Submit<BR>\n";
    $strUserID     = validateNumber("User ID", $_FORM['txtUserID'], 1, 1000000, FALSE);
    //$strUserID       = validateText("User ID", $_FORM['txtUserID'], 1, 20, TRUE, FALSE);
    $strUsername     = validateText("Username", $_FORM['txtUsername'], 1, 40, TRUE, FALSE);
    $strFirstName    = validateText("First Name", $_FORM['txtFirstName'], 2, 40, TRUE, FALSE);
    $strLastName     = validateText("Last Name", $_FORM['txtLastName'], 2, 40, TRUE, FALSE);
    $strEmail        = validateEmail("Email Address", $_FORM['txtEmail'], FALSE);
    $strPhone        = validateText("Phone Number", $_FORM['txtPhone'], 4, 25, FALSE, FALSE);
    $strPassword1    = validateText("Password1", $_FORM['txtPassword1'], 6, 20, FALSE, FALSE);
    $strPassword2    = validateText("Password2", $_FORM['txtPassword2'], 6, 20, FALSE, FALSE);
    //$strFrameMode    = validateText("Frame Mode", $_FORM['txtFrameMode'], 1, 1, FALSE, FALSE);
    //$strView         = validateText("View", $_FORM['txtView'], 1, 40, FALSE, FALSE);
    //$strCorder       = validateText("Column Order", $_FORM['txtCorder'], 1, 40, FALSE, FALSE);
    //$strRorder       = validateText("Row Order", $_FORM['txtRorder'], 1, 40, FALSE, FALSE);
    $strDBadmin      = validateText("DB Admin Flag", $_FORM['txtDBadmin'], 1, 1, FALSE, FALSE);
    $strNotify       = validateText("Notify User On Change Flag", $_FORM['txtNotify'], 1, 1, FALSE, FALSE);
    $strGuest        = validateNumber("Guest Flag", $_FORM['txtGuest'], 0, 1, FALSE);
    if ($strError == "")
    {
      if ($strPassword1 != $strPassword2)
      {
        $strError = "When you change your password, you must have both fields match.<BR>Password update not performed.";
      }
      else
      {
        if($DEBUG)print "Processing update request...<BR>\n";
        $strSQL0 = "SELECT email FROM $TABLE_USERS WHERE user_id='$strUserID'";
        $result0 = dbquery($strSQL0);
        $row0    = mysql_fetch_array($result0);
        $strOldEmail=$row0['email'];

        $strSQL1 = "UPDATE $TABLE_USERS SET";
        $strSQL1.= " first_name='$strFirstName'";
        $strSQL1.= ", last_name='$strLastName'";
        $strSQL1.= ", email='$strEmail'";
        $strSQL1.= ", phone='$strPhone'";
        $strSQL1.= ", frame_mode='$strFrameMode'";
        $strSQL1.= ", c_order='$strCorder'";
        $strSQL1.= ", r_order='$strRorder'";
        $strSQL1.= ", db_admin='$strDBadmin'";
        $strSQL1.= ", guest='$strGuest'";
        $strSQL1.= " WHERE user_id='$strUserID'";
        $result1 = dbquery($strSQL1);
        $strError = "This account has been updated successfully.";

        if($strNotify)
        {
          $msgBody = "From: ".makeHomeURL()."<BR>\n";
          $msgBody.= "Your Username: ".$row2['username']."<BR>\n";
          $msgBody.= "Your Name: $strFirstName $strLastName<BR>\n";
          $msgBody.= "Your Email Address: $strEmail<BR>\n";
          $msgBody.= "Your Phone number is: $strPhone<BR>\n";
          $msgBody.= "Your View is: $strView<BR>\n";
          //$msgBody.= "Your Column Order is: $strCorder<BR>\n";
          //$msgBody.= "Your Row Order is: $strRorder<BR>\n";
          //$msgBody.= "Your Frame Mode is: $strFrameMode<BR>\n";
          $msgBody.= "If you did not request this change, please notify $adminEmail.";
          mail($strEmail,"PHP RT User Profile Edit: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);
          $strError.="<BR>Mail notice has been sent to the user: $strEmail";
  
          if ($strEmail != $strOldEmail && $strOldEmail)
          {
            mail($strOldEmail, "PHP RT User Profile Edit: ".date("m-d-Y"), $msgBody, $MAIL_HEADER);
            $strError.="<BR>And to: $strOldEmail";
          }
        }
      }
    }
  } 
  else if($_FORM['createSubmit'])
  {
    // echo "ID now -1<BR>\n";
    $strUserID="-1";
  }
  else
  {
    if($DEBUG)print "No Submit<BR>";
    //$strUserID       = validateText("User ID", $_FORM['txtUserID'], 1, 20, FALSE, FALSE);
    $strUserID = validateNumber("User ID", $_FORM['txtUserID'], 1, 1000000, FALSE);

    if(!$strUserID)
    {
      // admin must select a user(id) to edit
      writeHeader("Select a user to Edit");
      declareError(TRUE);

      if($_FORM['searchSubmit'])
      {
        $TOP_SELECT ="<form class=forms name='form2' onsubmit='return(document.form2.txtUserID.selectedIndex>0);' method='POST' action='".$_SERVER['PHP_SELF']."'>";
        $TOP_SELECT.="<br><table class=wrap cellpadding='0' cellspacing=0><tr><td>";
        $TOP_SELECT.="<table class=forms border='0' cellpadding='2'>";
        $TOP_SELECT.="<tr><td colspan=2 align=center>Choose a user</td></tr>";
        $TOP_SELECT.="<tr> <td >Username:</td> <td ><select name='txtUserID' class=forms>";
        $TOP_SELECT.="<option value=''>Choose a User</option>\n";
        $BOT_SELECT.=" </select></td>\n </tr>\n";
        $BOT_SELECT.="<tr><td colspan=2 align=center>";
        $BOT_SELECT.="<input class=form_button type='submit' value='Edit' name='searchSubmit'> ";
        $BOT_SELECT.="<input class=form_button type='reset' value='Reset' name='reset'> ";
        $BOT_SELECT.="<input class=form_button id=b1 type='button' onclick=\"if(this.form.txtUserID.selectedIndex==0)return;this.form.delSub2.tag=1;document.getElementById('b2').className='form_button';document.getElementById('b1').className='form_button2';\" value='Step 1 Delete' name='delSub1'>";
        $BOT_SELECT.="<input class=form_button2 id=b2 type='button' onclick=\"if(this.tag){this.form.deleteSubmit.value=1;this.form.submit();}\" tag=0 value='Delete User' name='delSub2'>";
        $BOT_SELECT.="<input type=hidden name='deleteSubmit' value='0'>";
        $BOT_SELECT.="</td></tr></table>";
        $BOT_SELECT.="</td></tr></table>";
        $BOT_SELECT.=" </form>\n";
  
        $temp="";
        $strQ2 ="SELECT user_id,username,first_name,last_name from $TABLE_USERS WHERE";
        /* for better security */
        $strEmail        = validateEmail("Email Address", $_FORM['txtEmail'], FALSE);
        $strUsername     = validateText("Username", $_FORM['txtUsername'], 1, 40, TRUE, FALSE);
        $strFirstName    = validateText("First Name", $_FORM['txtFirstName'], 2, 40, TRUE, FALSE);
        $strLastName     = validateText("Last Name", $_FORM['txtLastName'], 2, 40, TRUE, FALSE);
        if(strlen($strEmail)    >1) { $strQ2.=" $temp email like \"%".$strEmail."%\"";          $temp="AND"; }
        if(strlen($strUsername) >1) { $strQ2.=" $temp username like \"%".$strUsername."%\"";    $temp="AND"; }
        if(strlen($strFirstName)>1) { $strQ2.=" $temp first_name like \"%".$strFirstName."%\""; $temp="AND"; }
        if(strlen($strLastName) >1) { $strQ2.=" $temp last_name like \"%".$strLastName."%\"";   $temp="AND"; }
        if(!$temp) // we know we are looking for at least one attribute...
        {
          $strQ2="SELECT user_id,username,first_name,last_name FROM $TABLE_USERS";
        }
        $strQ2.=" ORDER BY username";
        if($resultQ2=dbquery($strQ2))
        {
          print $TOP_SELECT;
          while($rowQ2=mysql_fetch_array($resultQ2))
          {
            print "<option value='".$rowQ2['user_id']."'>";
            print $rowQ2['username']." (".$rowQ2['user_id'].")  - ".$rowQ2['last_name'].", ".$rowQ2['first_name'];
            print "</option>\n";
          }
          print $BOT_SELECT;
        }
      }
    
      // search for user to edit by:
      //  - firstname, lastname, username, emailaddress, userid
?>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<table cellpadding=0 cellspacing=0 class=wrap><tr><td>
  <br><table class=forms border='0' cellpadding='2'>
    <tr><td colspan=2 align=center>Enter Data Below to Search for Users to Select From</td></tr>
    <tr>
      <td >Username:</td>
      <td ><input class=forms type="text" name="txtUsername" value="" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td >First Name:</td>
      <td ><input class=forms type="text" name="txtFirstName" value="" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td >Last Name:</td>
      <td ><input class=forms type="text" name="txtLastName" value="" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td >Email:</td>
      <td ><input class=forms type="text" name="txtEmail" value="" size="40" maxlength="50"></td>
    </tr>
    <tr><td colspan=2 align=center>
      <input class=form_button type="submit" value="Search" name="searchSubmit">
      <input class=form_button type="reset" value="Reset" name="reset">
    </td></tr></table>
  </td></tr></table>
</form>

<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<table align=center class=forms cellspacing=0 cellpadding=3>
<tr><td align=center><input class=form_button type=submit value="Create New User" name="createSubmit"></td></tr>
</table>
</form>
<?
  print "<br><BR>";
  writeFooter();
      exit;
    }
  }
  if($strUserID && $strUserID!=-1)
  {
    $strSQL2 = "SELECT * FROM $TABLE_USERS WHERE user_id=".$strUserID;
    $result2= dbquery($strSQL2);
    $row2= mysql_fetch_array($result2);

    $strUsername       = $row2['username'];
    $strFirstName      = $row2['first_name'];
    $strLastName       = $row2['last_name'];
    $strEmail          = $row2['email'];
    $strPhone          = $row2['phone'];
    $strPassworddate   = $row2['passworddate'];
    $strFrameMode      = $row2['frame_mode']; if($strFrameMode)$SEL_YES="SELECTED";else $SEL_NO="SELECTED";
    $strView           = $row2['view'];
    $strCorder         = $row2['c_order'];
    $strRorder         = $row2['r_order'];
    $strAccountCreated = $row2['account_create_date'];
    $strDBAdmin        = $row2['db_admin'];
    $strSuccBadPass    = $row2['succ_badpass'];
    $strNumBadPass     = $row2['num_badpass'];
    $strNumLogouts     = $row2['num_logouts'];
    $strNumLogins      = $row2['num_logins'];
    $strLastBadPass    = $row2['last_badpass'];
    $strLastLogout     = $row2['last_logout'];
    $strLastLogin      = $row2['last_login'];
    $strDBadmin        = $row2['db_admin']; if($strDBadmin)$ADMIN_YES="SELECTED";else $ADMIN_NO="SELECTED";
    $strGuest          = $row2['guest'];  if($strGuest)$GUEST_YES="CHECKED";else $GUEST_NO="CHECKED";
  }

  writeHeader("Admin Users");
  declareError(TRUE);

if($strUserID)
{
  $tempStrUserId=$strUserID;
  if($tempStrUserId==-1){$tempStrUserId="N/A";}
  if(!$GUEST_YES)$GUEST_NO="CHECKED";
?>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtUserID" value="<?=$strUserID;?>">
  <br><table class=forms border='0' cellpadding='2'>
    <tr><td >Username: (<?=$tempStrUserId;?>)</td><td><input type=text name="txtUsername" value="<?=$strUsername;?>"></td></tr>
    <tr><td>This user is a GUEST ONLY</td>
        <td>Guest:<input <?=$GUEST_YES;?> type=radio name="txtGuest" value="1">
            Real User:<input <?=$GUEST_NO;?> type=radio name="txtGuest" value=""></td></tr>
    <tr><td >This User is a Database Admin:</td>
        <td>
          <select name="txtDBadmin" class=forms><option <?=$ADMIN_NO;?> value="0">No</option><option <?=$ADMIN_YES;?> value="1">Yes</option></select>
        </td></tr>
<? if($strUserID!=-1) { ?>
    <tr><td >Account Created:</td><td><?=printDate2($strAccountCreated);?></td></tr>
    <tr><td >Password Last Changed:</td><td><?=printDate2($strPassworddate);?></td></tr>
    <tr><td >Last Login:</td><td><?=printDate2($strLastLogin);?> - Total (<?=$strNumLogins;?>)</td></tr>
    <tr><td >Last Logout:</td><td><?=printDate2($strLastLogout);?> - Total (<?=$strNumLogouts;?>)</td></tr>
    <tr><td >Last Bad Password Attempt:</td><td><?=printDate2($strLastBadPass);?>  - Total (<?=$strSuccBadPass;?>/<?=$strNumBadPass;?>)</td></tr>
<? } ?>
    <tr>
      <td >First Name:</td>
      <td ><input class=forms type="text" name="txtFirstName" value="<?echo $strFirstName;?>" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td >Last Name:</td>
      <td ><input class=forms type="text" name="txtLastName" value="<?echo $strLastName;?>" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td >Email:</td>
      <td ><input class=forms type="text" name="txtEmail" value="<?echo $strEmail;?>" size="40" maxlength="50"></td>
    </tr>
<? if($strUserID!=-1 && $CURRENT_USER['ID'] ==$strUserID) { ?>
    <tr>
      <td >Password:</td><td><input type=password class=forms name="txtPassword1" size="40" maxlength="20"></td>
    </tr>
    <tr>
      <td >Verify Password:</td><td><input type=password class=forms name="txtPassword2" size="40" maxlength="20"></td>
    </tr>
<? } ?>
    <tr>
      <td >Phone Number:</td><td><input type=text class=forms name="txtPhone" size="20" value="<?=$strPhone;?>" maxlength="20"></td>
    </tr>
<? if($strUserID!=-1) { ?>
    <tr>
      <td >Notify User on Change:</td><td><input type=checkbox class=forms name="txtNotify" value='1'>Yes if checked.</td>
    </tr>
<? } ?>
    <tr><td colspan=2 align=center>
      <input class=form_button type="submit" value="Commit Changes" name="btnSubmit">
      <input class=form_button type="reset" value="Reset" name="reset">
<? if($strUserID!=-1) { ?>
      <input class=form_button id=b1 type="button" onclick="this.form.delSub2.tag=1;document.getElementById('b2').className='form_button';document.getElementById('b1').className='form_button2';" value="Step 1 Delete" name="delSub1">
      <input class=form_button2 id=b2 type="button" onclick="if(this.tag){this.form.deleteSubmit.value=1;this.form.submit();}" tag=0 value="Delete User" name="delSub2">
      <input type=hidden name="deleteSubmit" value="0">
<? } ?>
    </td></tr></table>
</form>

<?
}
  print "<br><BR>";
  writeFooter();
?>
