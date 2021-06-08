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

  $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
  $strBtnSubmit = validateText("Submit Button", $_FORM['btnSubmit'], 1, 40, FALSE, FALSE);
  $action=0;
  if(preg_match("/^Add (\w+) Rule$/",$strBtnSubmit,$m))
  {
    $action=$m[1];
    $strSubAction = validateText("SubAction", $_FORM['btnSubmit'], 1, 40, FALSE, FALSE);
    switch ($action)
    {
      case "Item";
          $strSubARole  = validateText("Role", $_FORM['txtDRole'], 1, 40, TRUE, FALSE);
          $strSubAID    = validateText("Data Item", $_FORM['txtRuleID'], 1, 40, TRUE, FALSE);
        break;
      case "State";
          $strSubARole  = validateText("Role", $_FORM['txtTRole'], 1, 40, TRUE, FALSE);
          $strSubAID    = validateText("State Transition", $_FORM['txtSTranID'], 1, 40, TRUE, FALSE);
	  // WAS-nomore::::in form of $From_ID."___".$To_ID
        break;
      case "Event";
          $strSubARole  = validateText("Role", $_FORM['txtERole'], 1, 40, TRUE, FALSE);
          $strSubAID    = validateText("Event", $_FORM['txtEventID'], 1, 40, TRUE, FALSE);
        break;
      default;
          $strError.="<BR>ERROR: You must supply data with request.";
        break;
    }
  }
  $strDelete = validateNumber("Distribution to Delete", $_FORM['txtDelete'], 1, 1000000, FALSE);
  if($strDelete &&
     ($CURRENT_USER['IS_A_DB_ADMIN'] ||
      $CURRENT_USER['PROJECT_ADMIN'][$strProjectID]))
  {
    //echo "deleting #:$strDelete:<BR>";
    $strSQL1 = "DELETE ";
    $strSQL1.= " FROM $TABLE_PROJECT_DIST";
    $strSQL1.= " WHERE project_id='$strProjectID'";
    $strSQL1.= "   AND dist_id='$strDelete'";
    $result1= dbquery($strSQL1);
    $strError.="Successfully deleted Distribution ID #$strDelete.";
  }
  else if($action && !$strError &&
     ($CURRENT_USER['IS_A_DB_ADMIN'] ||
      $CURRENT_USER['PROJECT_ADMIN'][$strProjectID]))
  {
    //echo "performing action.[$action] ($strSubARole, $strSubAID)";
    //echo "see if this already exists, then update role, otherwise insert the new rule";

    if($action=="Event")
    {
      $strSQL1 = "SELECT *";
      $strSQL1.= " FROM $TABLE_PROJECT_DIST";
      $strSQL1.= " WHERE project_id='$strProjectID'";
      $strSQL1.= "   AND event_id='$strSubAID'";
      $result1= dbquery($strSQL1);
      if($row1= mysql_fetch_array($result1))
      {
        //echo "already exists";
        $strSQL2 = "UPDATE $TABLE_PROJECT_DIST";
        $strSQL2.= " SET";
	$strSQL2.= "   role='$strSubARole'";
        $strSQL2.= " WHERE";
	$strSQL2.= "   project_id='$strProjectID'";
        $strSQL2.= "   AND event_id='$strSubAID'";
        $result2= dbquery($strSQL2);
      }
      else
      {
        //echo "new one";
        $strSQL2 = "INSERT INTO $TABLE_PROJECT_DIST";
        $strSQL2.= " SET";
	$strSQL2.= "   role='$strSubARole'";
	$strSQL2.= "  ,project_id='$strProjectID'";
        $strSQL2.= "  ,event_id='$strSubAID'";
        $result2= dbquery($strSQL2);
      }
    }
    else if($action=="Item")
    {
      $strSQL1 = "SELECT *";
      $strSQL1.= " FROM $TABLE_PROJECT_DIST";
      $strSQL1.= " WHERE project_id='$strProjectID'";
      $strSQL1.= "   AND rule_id='$strSubAID'";
      $result1= dbquery($strSQL1);
      if($row1= mysql_fetch_array($result1))
      {
        //echo "already exists";
        $strSQL2 = "UPDATE $TABLE_PROJECT_DIST";
        $strSQL2.= " SET";
	$strSQL2.= "   role='$strSubARole'";
        $strSQL2.= " WHERE";
	$strSQL2.= "   project_id='$strProjectID'";
        $strSQL2.= "   AND rule_id='$strSubAID'";
        $result2= dbquery($strSQL2);
      }
      else
      {
        //echo "new one";
        $strSQL2 = "INSERT INTO $TABLE_PROJECT_DIST";
        $strSQL2.= " SET";
	$strSQL2.= "   role='$strSubARole'";
	$strSQL2.= "  ,project_id='$strProjectID'";
        $strSQL2.= "  ,rule_id='$strSubAID'";
        $result2= dbquery($strSQL2);
      }
    }
    else if($action=="State")
    {
      //echo "-state-";
      $strSQL1 = "SELECT *";
      $strSQL1.= " FROM $TABLE_PROJECT_DIST";
      $strSQL1.= " WHERE project_id='$strProjectID'";
      $strSQL1.= "   AND stran_id='$strSubAID'";
      $result1= dbquery($strSQL1);
      if($row1= mysql_fetch_array($result1))
      {
        //echo "already exists";
        $strSQL2 = "UPDATE $TABLE_PROJECT_DIST";
        $strSQL2.= " SET";
	$strSQL2.= "   role='$strSubARole'";
        $strSQL2.= " WHERE";
	$strSQL2.= "   project_id='$strProjectID'";
        $strSQL2.= "   AND stran_id='$strSubAID'";
        $result2= dbquery($strSQL2);
      }
      else
      {
        //echo "new one";
        $strSQL2 = "INSERT INTO $TABLE_PROJECT_DIST";
        $strSQL2.= " SET";
	$strSQL2.= "   role='$strSubARole'";
	$strSQL2.= "  ,project_id='$strProjectID'";
        $strSQL2.= "  ,stran_id='$strSubAID'";
        $result2= dbquery($strSQL2);
      }
    }

  }
  else
  {
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
    //$strProjectID = validateText("Project ID", $_FORM['txtProjectID'], 1, 11, FALSE, FALSE);
    if(!$strProjectID)
    {
      // admin must select a project(id) to work with Items
      writeHeader("First select a project to admin the Distributions.");
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
        $BOT_SELECT.="<input class=form_button type='submit' value='Admin Distribution' name='searchSubmit'> ";
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
	/* for better security */
        $strName   = validateText("Name", $_FORM['txtName'], 1, 11, FALSE, FALSE);
        $strEmail  = validateText("Email", $_FORM['txtEmail'], 1, 11, FALSE, FALSE);
        $strAbbrev = validateText("Abbrev", $_FORM['txtAbbrev'], 1, 11, FALSE, FALSE);
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

    $strName   = $row2['project_name'];
    $strAbbrev = $row2['project_abbr'];
  }

  writeHeader("Edit Project Distributions");
  declareError(TRUE);

if($strProjectID)
{
## if we get here - then the user has selected the Project to edit/delete/add User-Access ##

?>

<BR>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtProjectID" value="<?=$strProjectID;?>">
<br>
<table class=forms border='0' cellpadding='2'>
    <tr>
      <td >Project Name: "<?=$strName;?>"</td>
      <td >Project Alias: "<?=$strAbbrev;?>"</td>
    </tr>
</table>
<BR>
<table class=wrap border='0' cellpadding='2'>
<tr>
<td class=normal>On Data Updates:</td>
<td>
<select class=forms name="txtRuleID">
<option value="">Choose Item</option>
<?
$strSQL = "SELECT rule_id, label";
$strSQL.= " FROM $TABLE_ITEM_TO_PROJECT";
$strSQL.= " WHERE project_id='$strProjectID'";
$strSQL.= " ORDER BY label";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strRuleID = $row['rule_id'];
  $strLabel  = $row['label'];
  echo "<option value='$strRuleID'>$strLabel</option>\n";
}
?>
</select>
</td>
<td>
<select class=forms name="txtDRole">
<option value="ALWAYS" SELECTED>Everyone Display or Higher Access</option>
<option value="OWNER">Owner Only</option>
</select>
</td>
<td>
<input class=form_button type=submit value="Add Item Rule" name="btnSubmit">
</td>
</tr>
<tr>
<td class=normal>On State Transitions:</td>
<td>
<select class=forms name="txtSTranID">
<option value="">Choose Transition</option>
<?
$strSQL = "SELECT";
$strSQL.= "   B.stran_id";
$strSQL.= " , C.state_id as fid";
$strSQL.= " , C.name as fname";
$strSQL.= " , D.state_id as tid";
$strSQL.= " , D.name as tname";
$strSQL.= " FROM";
$strSQL.= "   ($TABLE_STATE_TRANSITIONS AS B";
$strSQL.= " , $TABLE_STATES AS C";
$strSQL.= " , $TABLE_STATES AS D)";
$strSQL.= " WHERE";
$strSQL.= "     B.from_state_id=C.state_id";
$strSQL.= " AND C.project_id='$strProjectID'";
$strSQL.= " AND B.to_state_id=D.state_id";
$strSQL.= " AND D.project_id='$strProjectID'";
$strSQL.= " ORDER BY C.name";
//echo "Q:<DIR>$strSQL</DIR>";
$result= dbquery($strSQL);
$temp=array();
while($row= mysql_fetch_array($result))
{
  $strSTranID = $row['stran_id'];
  $strFID = $row['fid'];
  $strTID = $row['tid'];
  $strFName  = $row['fname'];
  $strTName  = $row['tname'];
  $x=$strFName."___".$strTName;
  if(!isset($temp[$x]))
  {
    //echo "<option value='".$strFID."___".$strTID."'>\"$strFName\" to \"$strTName\"</option>\n";
    echo "<option value='$strSTranID'>\"$strFName\" to \"$strTName\"</option>\n";
    $temp[$x]=1;
  }
}
?>
</select>
</td>
<td>
<select class=forms name="txtTRole">
<option value="ALWAYS" SELECTED>Everyone Display or Higher Access</option>
<option value="OWNER">Owner Only</option>
</select>
</td>
<td>
<input class=form_button type=submit value="Add State Rule" name="btnSubmit">
</td>
</tr>
<tr>
<td class=normal>On Events:</td>
<td>
<select class=forms name="txtEventID">
<option value="">Choose Event</option>
<?
$strSQL = "SELECT event_id, event_type";
$strSQL.= " FROM $TABLE_EVENTS";
$strSQL.= " ORDER BY event_type";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strEventID = $row['event_id'];
  $strType  = $row['event_type'];
  echo "<option value='$strEventID'>$strType</option>\n";
}
?>
</select>
</td>
<td>
<select class=forms name="txtERole">
<option value="ALWAYS" SELECTED>Everyone Display or Higher Access</option>
<option value="OWNER">Owner Only</option>
</select>
</td>
<td>
<input class=form_button type=submit value="Add Event Rule" name="btnSubmit">
</td>
</tr>
</table>
<BR>
</form>
<BR>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtProjectID" value="<?=$strProjectID;?>">
<table class=warn border='0' cellspacing=0 cellpadding='2'>
<tr><td>Event</td><td>Data Item</td><td>State Transition</td><td>Email To</td><td>Delete Dist Rule</td></tr>
<?
$strSQL = "SELECT *, F.name as fname, T.name as tname";
$strSQL.= " FROM $TABLE_PROJECT_DIST AS A";
$strSQL.= " LEFT JOIN $TABLE_EVENTS AS E ON A.event_id=E.event_id";
$strSQL.= " LEFT JOIN $TABLE_ITEM_TO_PROJECT AS I ON A.rule_id=I.rule_id";
$strSQL.= " LEFT JOIN $TABLE_STATE_TRANSITIONS AS S ON A.stran_id=S.stran_id";
$strSQL.= " LEFT JOIN $TABLE_STATES AS F ON S.from_state_id=F.state_id";
$strSQL.= " LEFT JOIN $TABLE_STATES AS T ON S.to_state_id=T.state_id";
$strSQL.= " WHERE A.project_id='$strProjectID'";
$strSQL.= " AND A.user_id is NULL"; // we don't care about user settings on this admin page
$strSQL.= " ORDER BY tname,fname,label,event_type";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strDistID   = $row['dist_id'];
  $strEventID  = $row['event_id']; $strEventType=$row['event_type'];
  $strRuleID   = $row['rule_id'];  $strRuleLabel=$row['label'];
  $strSTranID  = $row['stran_id']; if($strSTranID)$strStateLabel=$row['fname']." to ".$row['tname'];
  $strRole     = $row['role'];
  echo "<tr>";
  echo "<td>$strEventType</td>";
  echo "<td>$strRuleLabel</td>";
  echo "<td>$strStateLabel</td>";
  echo "<td>$strRole</td>";
  echo "<td align=center><input type=submit class=form_button name='txtDelete' value='$strDistID'></td>";
  echo "</tr>";
}
?>
</table>
</form>

<?
}
  print "<BR><BR>";
  writeFooter();
?>
