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

  // non admins go bye-bye
  if(!$CURRENT_USER['IS_A_DB_ADMIN'] && !$CURRENT_USER['IS_A_PROJECT_ADMIN'])
  {
    redirect("index.php");
    exit;
  }

  if ($_FORM['deleteSubmit'])
  {
    if($_FORM['txtProjectID'])
    {
      //$strProjectID = validateText("Project ID", $_FORM['txtProjectID'], 1, 11, TRUE, FALSE);
      $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);

      $strSQL1="SELECT * FROM $TABLE_EACH_TICKET WHERE project_id='$strProjectID'";
      if($result1=dbquery($strSQL1))
      {
        if($row1=mysql_fetch_array($result1))
        {
          $strError="Deleting a project with any tickets is not allowed.  Rename the project, burry it. remove all access to it. but you can not delete it.";
        }
        else
        {
	  /* we can not delete EACH TICKET as they may be related to another project */
	  /* therefore we should not delete any of the items for those tickets */
          $strSQL1="DELETE FROM $TABLE_ITEM_TO_PROJECT WHERE project_id='$strProjectID'";
          $result1=dbquery($strSQL1);
          $strSQL1="DELETE FROM $TABLE_PROJECT_ACCESS WHERE project_id='$strProjectID'";
          $result1=dbquery($strSQL1);
          $strSQL1="DELETE FROM $TABLE_PROJECT_DIST WHERE project_id='$strProjectID'";
          $result1=dbquery($strSQL1);
          $strSQL1="DELETE FROM $TABLE_PROJECT_TEMPLATES WHERE project_id='$strProjectID'";
          $result1=dbquery($strSQL1);
          $strSQL1="DELETE FROM $TABLE_PROJECTS WHERE project_id='$strProjectID'";
          $result1=dbquery($strSQL1);
          $strSQL1="DELETE FROM $TABLE_REMINDERS WHERE project_id='$strProjectID'";
          $result1=dbquery($strSQL1);

          $strSQL1="SELECT * FROM $TABLE_STATE_RULES WHERE project_id='$strProjectID'";
          if($result1=dbquery($strSQL1))
          {
            while($row1=mysql_fetch_array($result1))
            {
              $strSQL2="DELETE FROM $TABLE_STATE_TRANSITIONS WHERE stran_id='".$row1['stran_id']."'";
              $result2=dbquery($strSQL2);
            }
          }
          
          $strSQL1="DELETE FROM $TABLE_STATE_RULES WHERE project_id='$strProjectID'";
          $result1=dbquery($strSQL1);
          $strSQL1="DELETE FROM $TABLE_STATES WHERE project_id='$strProjectID'";
          $result1=dbquery($strSQL1);
          $strError="Deleted project ($strProjectID)<BR>Item2Project, ProjectAccess, ProjectDistribution, ProjectTemplates, Projects, Reminders, StateRules, StateTransitioins, StateRules, States)";
        }
      }
      $strProjectID=NULL;
    }
  }
  else if ($_FORM['txtProjectID']==-1)
  {
    if($_FORM['txtName'] && $CURRENT_USER['IS_A_DB_ADMIN']) // creating a new project
    {
      //$strProjectID = validateText("Project ID", $_FORM['txtProjectID'], 1, 11, FALSE, FALSE);
      $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);

      $strName      = validateText("Project Name", $_FORM['txtName'], 1, 40, TRUE, FALSE);
      $strEmail     =   validateEmail("Mail Alias", $_FORM['txtEmail'], TRUE);
      $strAbbrev    = validateText("Project Abbreviation", $_FORM['txtAbbrev'], 3, 10, TRUE, FALSE);
      $strAnyone    = validateText("Anyone can create tickets FLAG", $_FORM['txtAnyone'], 1, 1, TRUE, FALSE);
      $strProtected = validateText("Project Protected FLAG", $_FORM['txtProtected'], 0, 1, TRUE, FALSE);

      $strSQL6 ="INSERT INTO $TABLE_PROJECTS SET ";
      $strSQL6.="  project_name='$strName'";
      $strSQL6.=", mail_alias='$strEmail'";
      $strSQL6.=", project_abbr='$strAbbrev'";
      $strSQL6.=", allowanyonecreate='$strAnyone'";
      $strSQL6.=", protected='$strProtected'";
      $result6=dbquery($strSQL6);
      $strSQL7 ="SELECT project_id FROM $TABLE_PROJECTS WHERE project_name='$strName'";
      $result7=dbquery($strSQL7);
      $row7=mysql_fetch_array($result7);
      $strProjectID=$row7['project_id']; // so bottom of this page will show this project just created
      $strError = "This project $strName ($strProjectID) has been inserted successfully.";
    } 
  }
  else if ($_FORM['btnSubmit'])
  {
    //if($_FORM['txtProjectID'] && ($CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
    if($_FORM['txtProjectID'] && ($CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$strProjectID]))
    {
      // if data was submitted ... to edit a user ID
      //$strProjectID = validateText("Project ID", $_FORM['txtProjectID'], 1, 11, TRUE, FALSE);
      $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
      $strName      = validateText("Project Name", $_FORM['txtName'], 1, 40, TRUE, FALSE);
      $strEmail     = validateEmail("Mail Alias", $_FORM['txtEmail'], TRUE);
      $strAbbrev    = validateText("Project Abbreviation", $_FORM['txtAbbrev'], 3, 10, TRUE, FALSE);
      $strAnyone    = validateText("Anyone can create tickets FLAG", $_FORM['txtAnyone'], 1, 1, TRUE, FALSE);
      $strProtected = validateText("Restricted Enabled FLAG", $_FORM['txtProtected'], 0, 1, TRUE, FALSE);

      if ($strError == "")
      {
        if($DEBUG)print "Processing update request...<BR>\n";

        $strSQL1 = "UPDATE $TABLE_PROJECTS SET";
        $strSQL1.= " mail_alias='$strEmail'";
        $strSQL1.= ", project_name='$strName'";
        $strSQL1.= ", allowanyonecreate='$strAnyone'";
        $strSQL1.= ", project_abbr='$strAbbrev'";
        $strSQL1.= ", protected='$strProtected'";
        $strSQL1.= " WHERE project_id='$strProjectID'";
        $result1 = dbquery($strSQL1);
        $strError = "This project has been updated successfully.";

      }
    }
  } 
  else if($_FORM['createSubmit'])
  {
    $strProjectID="-1";
  }
  else
  {
    //$strProjectID = validateText("Project ID", $_FORM['txtProjectID'], 1, 11, FALSE, FALSE);
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);

    if(!$strProjectID)
    {
      // admin must select a project(id) to edit
      writeHeader("Select a project to Edit");
      declareError(TRUE);

      if($_FORM['searchSubmit'])
      {
        $TOP_SELECT ="<form class=forms name='form2' method='POST' onsubmit='return(document.form2.txtProjectID.selectedIndex>0);' action='".$_SERVER['PHP_SELF']."'>";
        $TOP_SELECT.="<br><table class=wrap cellpadding='0' cellspacing=0><tr><td>";
        $TOP_SELECT.="<table class=forms border='0' cellpadding='2'>";
        $TOP_SELECT.="<tr><td colspan=2 align=center>Choose a project</td></tr>";
        $TOP_SELECT.="<tr> <td >Project:</td> <td ><select name='txtProjectID' class=forms>";
        $TOP_SELECT.="<option value=''>Choose a Project</option>\n";
        $BOT_SELECT.=" </select></td>\n </tr>\n";
        $BOT_SELECT.="<tr><td colspan=2 align=center>";
        $BOT_SELECT.="<input class=form_button type='submit' value='Edit' name='searchSubmit'> ";
        $BOT_SELECT.="<input class=form_button type='reset' value='Reset' name='reset'> ";
        $BOT_SELECT.="<input class=form_button id=b1 type='button' onclick=\"this.form.delSub2.tag=1;document.getElementById('b2').className='form_button';document.getElementById('b1').className='form_button2';\" value='Step 1 Delete' name='delSub1'>";
        $BOT_SELECT.="<input class=form_button2 id=b2 type='button' onclick=\"if(this.tag){this.form.deleteSubmit.value=1;this.form.submit();}\" tag=0 value='Delete User' name='delSub2'>";
        $BOT_SELECT.="<input type=hidden name='deleteSubmit' value='0'>";
        $BOT_SELECT.="</td></tr></table>";
        $BOT_SELECT.="</td></tr></table>";
        $BOT_SELECT.=" </form>\n";

        $temp="";
        $temp2="";
        $temp3="";
        if(!$CURRENT_USER['IS_A_DB_ADMIN'])
        {
          $temp2=", $TABLE_PROJECT_ACCESS as A";
          $temp3=" AND A.project_id=P.project_id AND A.level>=".$PROJECT_ACCESS['admin'];
          $temp4=" WHERE A.project_id=P.project_id AND A.level>=".$PROJECT_ACCESS['admin'];
        }
        $strQ2 ="SELECT P.project_id,P.project_name from $TABLE_PROJECTS AS P $temp2 WHERE";

        /* inserted next 3 lines to be more secure */
        $strName      = validateText("Project Name", $_FORM['txtName'], 1, 40, TRUE, FALSE);
        $strEmail     = validateEmail("Mail Alias", $_FORM['txtEmail'], TRUE);
        $strAbbrev    = validateText("Project Abbreviation", $_FORM['txtAbbrev'], 3, 10, TRUE, FALSE);

	/* changed these 3 lines to be more secure */
        //if(strlen($_FORM['txtName'])  >1) { $strQ2.=" $temp P.project_name      like \"%".$_FORM['txtName']."%\"";   $temp="AND"; }
        //if(strlen($_FORM['txtEmail']) >1) { $strQ2.=" $temp P.mail_alias        like \"%".$_FORM['txtEmail']."%\"";  $temp="AND"; }
        //if(strlen($_FORM['txtAbbrev'])>1) { $strQ2.=" $temp P.project_abbr      like \"%".$_FORM['txtAbbrev']."%\""; $temp="AND"; }
        if(strlen($strName)  >1) { $strQ2.=" $temp P.project_name      like \"%".$strName."%\"";   $temp="AND"; }
        if(strlen($strEmail) >1) { $strQ2.=" $temp P.mail_alias        like \"%".$strEmail."%\"";  $temp="AND"; }
        if(strlen($strAbbrev)>1) { $strQ2.=" $temp P.project_abbr      like \"%".$strAbbrev."%\""; $temp="AND"; }

        if(!$temp) // we know we are looking for at least one attribute...
        {
          $strQ2="SELECT P.project_id,P.project_name FROM $TABLE_PROJECTS AS P $temp2 $temp4 ORDER BY project_name"; 
        }
        else
        {
          $strQ2.=" $temp3 ORDER BY project_name";
        }
          if($resultQ2=dbquery($strQ2))
          {
            print $TOP_SELECT;
            while($rowQ2=mysql_fetch_array($resultQ2))
            {
              print "<option value='".$rowQ2['project_id']."'>";
              print $rowQ2['project_name']." (".$rowQ2['project_id'].")";
              print "</option>\n";
            }
            print $BOT_SELECT;
        }
     }
    
?>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<table cellpadding=0 cellspacing=0 class=wrap><tr><td>
  <br><table class=forms border='0' cellpadding='2'>
    <tr><td colspan=2 align=center>Enter Data Below to Search for Projects to Select From</td></tr>
    <tr>
      <td >Project Name:</td>
      <td ><input class=forms type="text" name="txtName" value="" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td >Project Abbreviation:</td>
      <td ><input class=forms type="text" name="txtAbbrev" value="" size="10" maxlength="10"></td>
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

<? if($CURRENT_USER['IS_A_DB_ADMIN']){ ?>
<form class=forms name="form1" method="POST" action="<?=$_SELF['PHP_SELF'];?>">
<table align=center class=forms cellspacing=0 cellpadding=3>
<tr><td><input class=form_button type=submit value="Create New Project" name="createSubmit"></td></tr>
</table>
</form>
<? } ?>

<?
  print "<BR><BR>";
  writeFooter();
      exit;
    }
  }
  if($strProjectID && $strProjectID!=-1)
  {
    $strSQL2 = "SELECT *";
    $strSQL2.= " FROM $TABLE_PROJECTS";
    $strSQL2.= " LEFT JOIN $TABLE_DB_INFO ON itar_flag='1'";
    $strSQL2.= " WHERE project_id='$strProjectID'";
    //echo "Q:<DIR>$strSQL2</DIR>";
    $result2= dbquery($strSQL2);
    $row2= mysql_fetch_array($result2);

    $strName       = $row2['project_name'];
    $strEmail      = $row2['mail_alias'];
    $strAbbrev     = $row2['project_abbr'];
    $strAnyone     = $row2['allowanyonecreate']; if($strAnyone)$SEL_YES="SELECTED";else $SEL_NO="SELECTED";
    $strProtected  = $row2['protected']; if($strProtected)$PRO_YES="SELECTED";else $PRO_NO="SELECTED";
  }

  /* more security */
  $strRebuildCache = validateText("Rebuild Cache", $_FORM['txtRebuildCache'], 1, 40, FALSE, FALSE);
  //if(isset($_FORM['txtRebuildCache']) && !strcmp($_FORM['txtRebuildCache'],"yes"))
  /* If we were asked to rebuild the cache for this project, then do it */
  if(!strcmp($strRebuildCache,"yes"))
  {
    updateCache($strProjectID);
  }
    $strSQL2A = "SELECT *";
    $strSQL2A.= " FROM $TABLE_DB_INFO ";
    $result2A= dbquery($strSQL2A);
    $row2A= mysql_fetch_array($result2A);
    $strRLabel     = $row2A['restricted_label'];
    $strNotice     = $row2A['restricted_admin_notice'];
    $strRestricted = $row2A['itar_flag']; /* is it turned on or off at the DB level?*/

  writeHeader("Edit Project");
  declareError(TRUE);

if($strProjectID)
{

?>

<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtProjectID" value="<?=$strProjectID;?>">
  <br><table class=forms border='0' cellpadding='2'>
    <tr>
      <td >Project Name:</td>
      <td ><input class=forms type="text" name="txtName" value="<?echo $strName;?>" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td >Mail Alias:</td>
      <td ><input class=forms type="text" name="txtEmail" value="<?echo $strEmail;?>" size="40" maxlength="120"></td>
    </tr>
    <tr>
      <td >Anyone Create:</td>
      <td><select class=forms name="txtAnyone"><option <?=$SEL_YES;?> value="1">Yes</option><option <?=$SEL_NO;?> value="0">No</option></select></td>
    </tr>
    <tr>
      <td >Project Abbreviation:</td><td><input type=text class=forms name="txtAbbrev" size="10" value="<?=$strAbbrev;?>" maxlength="10"></td>
    </tr>
    <!-- new to enable project admin to rebuild cache for this project -->
    <tr>
      <td >Rebuild Ticket Listing Cache:</td><td>
      <select name="txtRebuildCache"><option SELECTED value="">No</option><option value="yes">Yes</option></select></td>
    </tr>
<? if($strRestricted){?>
    <tr class=warn><td colspan=2 align=center><?=$strRLabel;?><BR><?=$strNotice;?></td></tr>
    <tr>
      <td >This Project Protected:</td><td>
      <select name="txtProtected"><option <?=$PRO_NO;?> value="">No</option><option <?=$PRO_YES;?> value="1">Yes</option></select></td>
    </tr>
<?}?>
    <tr><td colspan=2 align=center>
      <input class=form_button type="submit" value="Commit Changes" name="btnSubmit">
      <input class=form_button type="reset" value="Reset" name="reset">
<? if($strProjectID!=-1) { ?>
      <input class=form_button id=b1 type="button" onclick="this.form.delSub2.tag=1;document.getElementById('b2').className='form_button';document.getElementById('b1').className='form_button2';" value="Step 1 Delete" name="delSub1">
      <input class=form_button2 id=b2 type="button" onclick="if(this.tag){this.form.deleteSubmit.value=1;this.form.submit();}" tag=0 value="Delete User" name="delSub2">
      <input type=hidden name="deleteSubmit" value="0">
<? } ?>
    </td></tr></table>
</form>

<?
}
  print "<BR><BR>";
  writeFooter();
?>
