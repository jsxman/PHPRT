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
  checkPermissions(1, $SESSION_TIMEOUT); // user logged in? - keep session alive for the SESSION_TIMEOUT


// if this is an db-admin or a project admin allow them to see this page, otherwise go away
if(!$CURRENT_USER['IS_A_DB_ADMIN'])
{
  header ("Location: index.php");
  exit;
}

if($_FORM['btnSubmit'])
{
  $strVersion     = validateText("Version Number",                    $_FORM['txtVersion'],0,5,FALSE, FALSE);
  $strEmail       = validateEmail("Mail Alias",                       $_FORM['txtEmail'], FALSE);
  $strExpire      = validateText("Passwords Expire (in days)",        $_FORM['txtExpire'], 1, 10, TRUE, FALSE);
  $strAlpha       = validateText("Password Must Contain non Numbers", $_FORM['txtAlpha'], 1, 1, TRUE, FALSE);
  $strDEmail      = validateText("Display Emails",                    $_FORM['txtDEmails'], 1, 1, TRUE, FALSE);
  $strEnable      = validateText("Enable ITAR Notices FLAG",          $_FORM['txtEnable'], 1, 1, TRUE , FALSE);
  $strLabel       = validateText("Restricted Label",                  $_FORM['txtLabel'], 1, 40, FALSE, FALSE);
  $strNotice      = validateText("Restricted Admin Notice",           $_FORM['txtNotice'], 1, 400, TRUE, FALSE);
  $strRestricted  = validateText("Restricted Text",                   $_FORM['txtRestricted'], 1, 400, TRUE, FALSE);
  $strNRestricted = validateText("Non Restricted Text",               $_FORM['txtNRestricted'], 1, 400, TRUE, FALSE);
  $strSwitch      = validateText("Switch Text",                       $_FORM['txtSwitch'], 1, 400, TRUE, FALSE);
  //$strEnter       = validateText("Entering Text",                     $_FORM['txtEnter'], 1, 400, FALSE, FALSE);
  //$strDE          = validateText("Data Entry Banner",                 $_FORM['txtDE'], 1, 400, FALSE, FALSE);
 
  $strSQL1 = "UPDATE $TABLE_DB_INFO SET";
  $strSQL1.= " mail_tag='$strEmail'";
  $strSQL1.= ", pswd_exp_time='$strExpire'";
  $strSQL1.= ", pswd_alpha='$strAlpha'";
  $strSQL1.= ", emails_show_up='$strDEmail'";
  $strSQL1.= ", itar_flag='$strEnable'";
  $strSQL1.= ", restricted_label='$strLabel'";
  $strSQL1.= ", restricted_admin_notice='$strNotice'";
  $strSQL1.= ", restricted='$strRestricted'";
  $strSQL1.= ", not_restricted='$strNRestricted'";
  $strSQL1.= ", switch_warning='$strSwitch'";
  $strSQL1.= ", entering_warning='$strEnter'";
  $strSQL1.= ", data_entry_banner='$strDE'";
  //$strSQL1.= " WHERE version_num='$strVersion'";
  //echo "Q:<DIR>$strSQL1</DIR>";
  $result1 = dbquery($strSQL1);
  $strError = "The database settings have been updated successfully.";
}

$strSQL2="SELECT * FROM $TABLE_DB_INFO";
$result2=dbquery($strSQL2);
$row2=mysql_fetch_array($result2);
//$strVersion     = $row2['version_num'];
$strEmail       = $row2['mail_tag'];
$strExpire      = $row2['pswd_exp_time'];
$strAlpha       = $row2['pswd_alpha'];if($strAlpha)$ALP_YES="SELECTED";else $ALP_NO="SELECTED";
$strDEmail      = $row2['emails_show_up'];if($strDEmail)$DEM_YES="SELECTED";else $DEM_NO="SELECTED";
$strEnable      = $row2['itar_flag'];if($strEnable)$ITR_YES="SELECTED";else $ITR_NO="SELECTED";
$strLabel       = $row2['restricted_label'];
$strNotice      = $row2['restricted_admin_notice'];
$strRestricted  = $row2['restricted'];
$strNRestricted = $row2['not_restricted'];
$strSwitch      = $row2['switch_warning'];
$strEnter       = $row2['entering_warning'];
$strDE          = $row2['data_entry_banner'];

writeHeader("Edit Database");
declareError(TRUE);


?>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
  <br><table class=forms border='0' cellpadding='2'>
    <tr>
      <td >PHP-RT Version:</td><td><?=$PHPRT_VERSION;?></td>
    </tr>
    <tr>
      <td >Mail Alias:</td>
      <td ><input class=forms type="text" name="txtEmail" value="<?echo $strEmail;?>" size="40" maxlength="64"></td>
    </tr>
    <tr>
      <td >Password Expire Time (Days):<BR>Note "0" turns off this feature</td>
      <td ><input class=forms type="text" name="txtExpire" value="<?echo $strExpire;?>" size="10" maxlength="10"></td>
    </tr>
    <tr>
      <td >Password Must Contain Both Alpha and Non-Alpha:</td>
      <td><select class=forms name="txtAlpha"><option <?=$ALP_YES;?> value="1">Yes</option><option <?=$ALP_NO;?> value="0">No</option></select></td>
    </tr>
<!--
    <tr>
      <td >Display Emails:</td>
      <td><select class=forms name="txtDEmails"><option <?=$DEM_YES;?> value="1">Yes</option><option <?=$DEM_NO;?> value="0">No</option></select></td>
    </tr>
-->
    <tr>
      <td >Enable Restricted Notices:</td>
      <td><select class=forms name="txtEnable"><option <?=$ITR_YES;?> value="1">Yes</option><option <?=$ITR_NO;?> value="0">No</option></select></td>
    </tr>
    <tr>
      <td >Restricted Label:</td><td><input type=text class=forms name="txtLabel" size="50" value="<?=$strLabel;?>" maxlength="40"></td>
    </tr>
    <tr>
      <td >Restricted Admin Notice:</td><td><textarea class=forms name="txtNotice" rows=4" cols="60"><?=$strNotice;?></textarea></td>
    </tr>
    <tr>
      <td >Restricted Banner:</td><td><textarea class=forms name="txtRestricted" rows=4" cols="60"><?=$strRestricted;?></textarea></td>
    </tr>
    <tr>
      <td >Not Restricted Banner:</td><td><textarea class=forms name="txtNRestricted" rows=4" cols="60"><?=$strNRestricted;?></textarea></td>
    </tr>
    <tr>
      <td >Merge Banner:</td><td><textarea class=forms name="txtSwitch" rows=4" cols="60"><?=$strSwitch;?></textarea></td>
    </tr>
<!--
    <tr>
      <td >Entering Text:</td><td><textarea class=forms name="txtEnter" rows=4" cols="60"><?=$strEnter;?></textarea></td>
    </tr>
    <tr>
      <td >Data Entry Banner:</td><td><input type=text class=forms name="txtDE" size=40 maxlength=64 value="<?=$strDE;?>"></td>
    </tr>
-->
    <tr><td colspan=2 align=center>
      <input class=form_button accesskey='s' type="submit" value="Commit Changes" name="btnSubmit">
      <input class=form_button type="reset" value="Reset" name="reset">
    </td></tr></table>
</form>

<?
  print "<BR><BR>";
  writeFooter();
?>
