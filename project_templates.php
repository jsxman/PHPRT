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

  /* this will remember the project as last project */
  if(isset($_FORM['txtProjectID']))
    $_SESSION['LastProject']=$_FORM['txtProjectID'];


  if(0 && $_FORM['txtDelID'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
  {
    ## delete a transition
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, TRUE);
    //$strProjectID = validateText("Project ID",    $_FORM['txtProjectID'], 1, 11, TRUE, TRUE);
    $strDelID     = validateNumber("Transition ID",  $_FORM['txtDelID'],    1, 1000, TRUE);
    if(!$strError)
    {
      $strSQL1="DELETE FROM $TABLE_STATE_TRANSITIONS WHERE stran_id='$strDelID'";
      $result1=dbquery($strSQL1);
      $strError="State Transition [ID=$strDelID] has been deleted.";
    }
  }

  if(0 && $_FORM['txtDeleteState'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
  {
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, TRUE);
    //$strProjectID = validateText("Project ID",       $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
    $strStateID = validateText("State ID",       $_FORM['txtDeleteState'],    1, 11, TRUE, TRUE);
    if(!$strError)
    {
      $strSQL1="DELETE FROM $TABLE_STATES WHERE state_id='$strStateID'";
      $result1=dbquery($strSQL1);

      $strSQL3 ="SELECT * FROM $TABLE_STATE_TRANSITIONS WHERE from_state_id='$strStateID' OR to_state_id='$strStateID'";
      $result3=dbquery($strSQL3);
      while($row3=mysql_fetch_array($result3))
      {
        $stran_id=$row3['stran_id'];
        $strSQL4="DELETE FROM $TABLE_STATE_RULES WHERE stran_id='$stran_id'";
        $result4=dbquery($strSQL4);
      }

      $strSQL2="DELETE FROM $TABLE_STATE_TRANSITIONS WHERE from_state_id='$strStateID' OR to_state_id='$strStateID'";
      $result2=dbquery($strSQL2);
      $strError="State #$strStateID deleted successfully.";
    }
  }


  if(0 && $_FORM['btnAddTransition'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
  {
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, TRUE);
    //$strProjectID  = validateText("Project ID",    $_FORM['txtProjectID'], 1, 11, TRUE, TRUE);
    $strFromID     = validateNumber("From State",  $_FORM['txtFromID'],    1, 1000, TRUE);
    $strToID       = validateNumber("To State",    $_FORM['txtToID'],      1, 1000, TRUE);
    $strLevel      = validateNumber("Level?",      $_FORM['txtLevel'],     0, 1000, TRUE);
    $strSQL3 ="SELECT * FROM $TABLE_STATES WHERE state_id='$strFromID'";
    $result3=dbquery($strSQL3);
    $row3=mysql_fetch_array($result3);
    if($row3['final']) $strError="ERROR: You can not have a state transition from a final state.";

    $strSQL3 ="SELECT * FROM $TABLE_STATE_TRANSITIONS WHERE from_state_id='$strFromID' AND to_state_id='$strToID'";
    $result3=dbquery($strSQL3);
    $row3=mysql_fetch_array($result3);
    if($row3) $strError="ERROR: You already have a to/from transition deifned for these 2 states.";
    
    if(!$strError)
    {
      $strSQL1 ="INSERT INTO $TABLE_STATE_TRANSITIONS SET ";
      $strSQL1.="  from_state_id='$strFromID'";
      $strSQL1.=", to_state_id='$strToID'";
      $strSQL1.=", level='$strLevel'";
      $result1=dbquery($strSQL1);
      $strError="This new state transition has been added successfully.";
    }
  }

  if($_FORM['btnSubmit']=="Edit Template" && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
  {
    if($_FORM['btnCommit'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
    {
      $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, TRUE);
      //$strProjectID   = validateText("Project ID",       $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
      $strTemplateID  = validateText("Template ID",       $_FORM['txtTemplateID'], 0, 40, FALSE, FALSE);
      $strTemplate    = validateText("Template Type",       $_FORM['txtTemplate'], 2, 40, TRUE, TRUE);
      $strCode        = $_FORM['txtCode']; //validateText("Code",       $_FORM['txtCode'], 2, 100000, FALSE, FALSE);

      if(!$strError)
      {
        $strSQL5 ="SELECT * FROM $TABLE_PROJECT_TEMPLATES WHERE project_id='$strProjectID' AND page='$strTemplate'";
        $result5=dbquery($strSQL5);
        if($row5=mysql_fetch_array($result5))
        {
          ## doing an update
          $strSQL6 ="UPDATE $TABLE_PROJECT_TEMPLATES SET ";
          $strSQL6.="  page='$strTemplate'";
          $strSQL6.=", project_id='$strProjectID'";
          $strSQL6.=", code='$strCode'";
          $strSQL6.=" WHERE project_id='$strProjectID' AND page='$strTemplate'";
          $result6=dbquery($strSQL6);
          $strSQL7 ="SELECT template_id FROM $TABLE_PROJECT_TEMPLATES WHERE page='$strTemplate' AND project_id='$strProjectID'";
          $result7=dbquery($strSQL7);
          $row7=mysql_fetch_array($result7);
          $strTemplateID=$row7['template_id'];
          $strError = "This template $strTemplate (ID = $strTemplateID) has been updated successfully.";
        }
        else
        {
          ## doing an insert
          $strSQL6 ="INSERT INTO $TABLE_PROJECT_TEMPLATES SET ";
          $strSQL6.="  page='$strTemplate'";
          $strSQL6.=", project_id='$strProjectID'";
          $strSQL6.=", code='$strCode'";
          $result6=dbquery($strSQL6);
          $strSQL7 ="SELECT template_id FROM $TABLE_PROJECT_TEMPLATES WHERE page='$strTemplate' AND project_id='$strProjectID'";
          $result7=dbquery($strSQL7);
          $row7=mysql_fetch_array($result7);
          $strTemplateID=$row7['template_id'];
          $strError = "This template $strTemplate (ID = $strTemplateID) has been inserted successfully.";
        }
        $strError.=updateCache($strProjectID);
      }
    } 
    else
    {
      $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, TRUE);
      //$strProjectID   = validateText("Project ID",   $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
      $strTemplate    = validateText("Template",     $_FORM['txtTemplate'], 2, 40, TRUE, TRUE);
      $strCode="";
      $strTemplateID="";
      $strSQL ="SELECT template_id,code FROM $TABLE_PROJECT_TEMPLATES WHERE page='$strTemplate' AND project_id='$strProjectID'";
      $result=dbquery($strSQL);
      if($row=mysql_fetch_array($result))
      {
        $strCode=$row['code'];
        $strTemplateID=$row['template_id'];
      }
$item_list="These are ITEMS you have in your project [type-in-brackets]:<BR>";
$strSQL8 ="SELECT * FROM ($TABLE_ITEM_TO_PROJECT AS A,$TABLE_ITEM_TYPE AS B) WHERE A.project_id='$strProjectID' AND A.type_id=B.type_id";
$result8=dbquery($strSQL8);
while($row8=mysql_fetch_array($result8))
{
  $item_list.=$row8['label']."[".$row8['type']."]<BR>";
}
// Comment is a built in item
$item_list.="Comment [Text]<BR>";
// Owner is a built in item
$item_list.="Owner [Person]<BR>";

$help="Insert your HTML and where you want the item to appear type <nobr>ITEM:<i>ItemName</i>:METI</nobr>";
$strError.="<form class=forms name='form2' method='POST' action='".$_SERVER['PHP_SELF']."'>";
$strError.="<input type=hidden name='txtProjectID' value='$strProjectID'>";
$strError.="<input type=hidden name='txtTemplateID' value='$strTemplateID'>";
$strError.="<input type=hidden name='txtTemplate' value='$strTemplate'>";
$strError.="<input type=hidden name='btnSubmit' value='Edit Template'>";
$strError.="<table cellpadding=0 cellspacing=0 class=wrap>";
$strError.="<tr>";
$strError.="<td>Edit Template '$strTemplate'";
$strError.="<BR><BR>";
$strError.="$help";
$strError.="</td>";
$strError.="<td><textarea name='txtCode' rows=35 cols=50>$strCode</textarea></td>";
$strError.="<td>$item_list</td>";
$strError.="</tr>";
$strError.="<tr><td align=center colspan=3><input class=form_button type=submit value='Commit Changes' name='btnCommit'></td></tr>";
$strError.="</table>";
$strError.="</form>";
    }
  }
  else
  {
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
    //$strProjectID = validateText("Project ID", $_FORM['txtProjectID'], 1, 11, FALSE, FALSE);
    if(!$strProjectID)
    {
      // admin must select a project(id) to work with Items
      writeHeader("TEMPLATES: First select a project.");
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
        $BOT_SELECT.="<input class=form_button type='submit' value='Edit Templates' name='searchSubmit'> ";
        $BOT_SELECT.="<input class=form_button type='reset' value='Reset' name='reset'> ";
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
          $temp4=" WHERE A.user_id='".$CURRENT_USER['ID']."' AND A.project_id=P.project_id AND A.level>=".$PROJECT_ACCESS['admin'];
        }
        $strQ2 ="SELECT P.project_id,P.project_name from $TABLE_PROJECTS AS P $temp2 WHERE";
        if(strlen($_FORM['txtName'])  >1) { $strQ2.=" $temp P.project_name      like \"%".$_FORM['txtName']."%\"";   $temp="AND"; }
        if(strlen($_FORM['txtEmail']) >1) { $strQ2.=" $temp P.mail_alias        like \"%".$_FORM['txtEmail']."%\"";  $temp="AND"; }
        if(strlen($_FORM['txtAbbrev'])>1) { $strQ2.=" $temp P.project_abbr      like \"%".$_FORM['txtAbbrev']."%\""; $temp="AND"; }
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
    <tr><td colspan=2 align=center>This form will allow you to search for the project to edit Items:</td></tr>
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
<BR><BR>

<?
  print "<BR><BR>";
  writeFooter();
      exit;
    }
  }
  if($strProjectID && $strProjectID!=-1)
  {
    $strSQL2 = "SELECT * FROM $TABLE_PROJECTS WHERE project_id='$strProjectID'";
    $result2= dbquery($strSQL2);
    $row2= mysql_fetch_array($result2);

    $strName       = $row2['project_name'];
    $strAbbrev     = $row2['project_abbr'];
  }

  writeHeader("Edit Project Templates");
  declareError(TRUE);

if($strProjectID)
{
## if we get here - then the user has selected the Project to edit/delete/add States into/from ##

?>

<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtProjectID" value="<?=$strProjectID;?>">
<br>
<table class=forms border='0' cellpadding='2'>
    <tr>
      <td >Project Name [Alias]:</td>
      <td ><?=$strName;?> [<?=$strAbbrev;?>]</td>
    </tr>
</table>
<table class=wrap border='0' cellpadding='2'>
<tr>
<td>Edit Template:</td>
<td>
<select class=forms name="txtTemplate">
<option value="">Choose</option>
<option value='Create'>Create</option>
<!--
<option value='History'>History</option>
-->
<option value='View'>View</option>
<option value='Listing'>Listing</option>
<option value='Mail'>Mail</option>
</select>
</td>
</tr>
<tr><td colspan=2 align=center>
<input class=form_button type="submit" value="Edit Template" name="btnSubmit">
<input class=form_button type="reset" value="Reset" name="reset">
</td></tr>
</table>
</form>
<?
}
  print "<BR><BR>";
  writeFooter();
?>
