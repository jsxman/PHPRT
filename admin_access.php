<?
/**************************************************************************
 * Copyright(c) 2007, JS-X.com, All rights reserved.                      *
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
  $strUserID  = validateNumber("User ID", $_FORM['txtUserID'], 1, 1000000, FALSE);
  $strDelete1 = validateNumber("Unique ID", isset($_FORM['delete1'])?$_FORM['delet1']:"", 1, 1000000, FALSE);

//$strFirstName    = validateText("First Name", $_FORM['txtFirstName'], 2, 40, TRUE, FALSE);

  // only the DB ADMINs can get into this page... */
  if(!$CURRENT_USER['IS_A_DB_ADMIN'])
  {
    redirect($PAGE_INDEX);
    exit;
  }

  if (isset($_FORM['delete_all']) && $_FORM['delete_all'] && isset($strUserID) && $strUserID)
  { 
    //echo "-delete all $strUserID-";
    $strSQL3 = "DELETE FROM $TABLE_ACCESSTIMES";
    $strSQL3.= " WHERE user_id='".$strUserID."'";
    //echo "Q:<DIR>$strSQL3</DIR>\n";
    $result3= dbquery($strSQL3);
  }
  else if ($strDelete1 && $strUserID)
  {
    //echo "-delete 1: #".$strDelete1." $strUserID -";
    $strSQL3 = "DELETE FROM $TABLE_ACCESSTIMES";
    $strSQL3.= " WHERE access_id='".$strDelete1."'";
    //echo "Q:<DIR>$strSQL3</DIR>\n";
    $result3= dbquery($strSQL3);
  } 
  else
  {
    /* this is how we drill down to find which user you want information on */
    if(!$strUserID)
    {
      // admin must select a user(id) to edit
      writeHeader("Select a user to View Access Times");
      declareError(TRUE);

      if($_FORM['searchSubmit'])
      {
        $TOP_SELECT ="<form class=forms name='form2' onsubmit='return(document.form2.txtUserID.selectedIndex>0);' method='POST' action='".$_SERVER['PHP_SELF']."'>";
        $TOP_SELECT.="<br><table class=wrap cellpadding='0' cellspacing=0><tr><td>";
        $TOP_SELECT.="<table class=forms border='0' cellpadding='2'>";
        $TOP_SELECT.="<tr><td colspan=2 align=center>Choose a user</td></tr>";
        $TOP_SELECT.="<tr> <td >Username:</td> <td ><select name='txtUserID' class=forms>";
        $TOP_SELECT.="<option value=''>Choose a User</option>\n";
        if(!isset($BOT_SELECT))$BOT_SELECT="";
        $BOT_SELECT.=" </select></td>\n </tr>\n";
        $BOT_SELECT.="<tr><td colspan=2 align=center>";
        $BOT_SELECT.="<input class=form_button type='submit' value='View Access Times' name='searchSubmit'> ";
        $BOT_SELECT.="<input class=form_button type='reset' value='Reset' name='reset'> ";
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

<?
      exit;
    }
  }
  if($strUserID && $strUserID!=-1)
  {
    $strSQL2 = "SELECT *";
    $strSQL2.= " FROM $TABLE_USERS";
    $strSQL2.= " WHERE user_id=".$strUserID;
    $result2= dbquery($strSQL2);
    $row2= mysql_fetch_array($result2);

    $strUsername       = $row2['username'];
    $strFirstName      = $row2['first_name'];
    $strLastName       = $row2['last_name'];
    $strNumBadPass     = $row2['num_badpass'];
    $strNumLogouts     = $row2['num_logouts'];
    $strNumLogins      = $row2['num_logins'];
    $strSuccBadPass    = $row2['succ_badpass'];

    /* date types follow */
    $strPassworddate   = $row2['passworddate']?       date("F dS Y",$row2['passworddate']):"";
    $strAccountCreated = $row2['account_create_date']?date("F dS Y",$row2['account_create_date']):"";
    $strLastBadPass    = $row2['last_badpass']?       date("F dS Y",$row2['last_badpass']):"";
    $strLastLogout     = $row2['last_logout']?        date("F dS Y",$row2['last_logout']):"";
    $strLastLogin      = $row2['last_login']?         date("F dS Y",$row2['last_login']):"";

  }

  writeHeader("User Access Times");
  declareError(TRUE);

if($strUserID)
{
  $tempStrUserId=$strUserID;
  if($tempStrUserId==-1){$tempStrUserId="N/A";}
?>
  <br>
  <table class=wrap2 border='0' cellpadding='2'>
    <tr class=warn><td class=normal>User:</td><td class=normal><?=$strLastName;?>, <?=$strFirstName;?> [<?=$strUsername;?>]</td></tr>
    <tr><td class=normal>Account Created:</td><td class=normal><?=($strAccountCreated);?></td></tr>
    <tr><td class=normal>Password Last Changed:</td><td class=normal><?=($strPassworddate);?></td></tr>
    <tr><td class=normal>Last Login:</td><td class=normal><?=($strLastLogin);?> - Total (<?=$strNumLogins;?>)</td></tr>
    <tr><td class=normal>Last Logout:</td><td class=normal><?=($strLastLogout);?> - Total (<?=$strNumLogouts;?>)</td></tr>
    <tr><td class=normal>Last Bad Password Attempt:</td><td class=normal><?=($strLastBadPass);?>  - Total (<?=$strSuccBadPass;?>/<?=$strNumBadPass;?>)</td></tr>
    </table>
<BR>
<?
    $strSQL3 = "SELECT *";
    $strSQL3.= " FROM $TABLE_ACCESSTIMES";
    $strSQL3.= " WHERE user_id=".$strUserID;
    $strSQL3.= " ORDER BY access_id ASC";
    $result3= dbquery($strSQL3);
    echo "<form action='$PHP_SELF' method=POST>\n";
    echo "<input type=hidden name='txtUserID' value='$strUserID'>\n";
    echo "<table cellpadding=3 cellspacing=0 class=warn><tr class=rowh><td>Unique ID</td><td>Login Time</td><td>Logout Time</td><td>Delete?</td></tr>\n";
    $count=0;
    $rowC=0;
    while($row3= mysql_fetch_array($result3))
    {
      $count++;
      $rowC=($rowC+1)%2;
      $strAccessID=$row3['access_id'];
      $strLogin   =$row3['login'] ?date("F dS, Y : H:i:s",$row3['login'] ):"";
      $strLogout  =$row3['logout']?date("F dS, Y : H:i:s",$row3['logout']):"";
      echo "<tr class=row$rowC>";
      echo "<td class=normal>#".$strAccessID."</td>";
      echo "<td class=normal>$strLogin</td>";
      echo "<td class=normal>$strLogout</td>";
      echo "<td align=right><input name='delete1' type=submit value='$strAccessID' class='form_button'></td>";
      echo "</tr>\n";
    }
    if($count)
      echo "<tr><td colspan=4 align=right><input type=submit name='delete_all' value='Delete All For This User' class='form_button'></td></tr>\n";
    else
      echo "<tr><td colspan=4 align=center>No Access History Found for this user.</td></tr>\n";
    echo "</table></form>\n";
?>
</form>

<?
}
  print "<br><BR>";
  writeFooter();
?>
