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


  if($_FORM['txtDelID'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
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
  if($_FORM['txtDelDID'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
  {
    ## delete a transition
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, TRUE);
    //$strProjectID = validateText("Project ID",    $_FORM['txtProjectID'], 1, 11, TRUE, TRUE);
    $strDelDID     = validateNumber("Transition ID",  $_FORM['txtDelDID'],    1, 1000, TRUE);
    if(!$strError)
    {
      $strSQL1="DELETE FROM $TABLE_STATE_RULES WHERE staterule_id='$strDelDID'";
      $result1=dbquery($strSQL1);
      $strError="State Transition Rule [ID=$strDelDID] has been deleted.";
    }
  }

  if($_FORM['txtDeleteState'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
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


  if($_FORM['btnAddTransition'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
  {
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, TRUE);
    //$strProjectID  = validateText("Project ID",    $_FORM['txtProjectID'], 1, 11, TRUE, TRUE);
    /* fromID can be NULL - only if toID is an initial state */
    $strFromID     = validateNumber("From State",  $_FORM['txtFromID'],    1, 1000, FALSE);
    $strToID       = validateNumber("To State",    $_FORM['txtToID'],      1, 1000, TRUE);
    $strLevel      = validateNumber("Level?",      $_FORM['txtLevel'],     0, 1000, TRUE);

    /* next 2 are for determining state transition rules */
    $strDItem      = validateNumber("Dependency Item ID",      $_FORM['txtDItem'],     0, 1000, FALSE);
    $strDValue     = validateText("Dependency Value",    $_FORM['txtDValue'], 1, 11, FALSE, FALSE);

    $strSQL3 ="SELECT * FROM $TABLE_STATES WHERE state_id='$strFromID'";
    $result3=dbquery($strSQL3);
    $row3=mysql_fetch_array($result3);
    if($row3['final']) $strError="ERROR: You can not have a state transition from a final state.";

    $TstrFromID=$strFromID?"='$strFromID'":" is NULL ";
    $TstrFromID2=$strFromID?"='$strFromID'":" = NULL ";
    // echo "TstrFromID=$TstrFromID<BR>";
    //$strSQL3 ="SELECT * FROM $TABLE_STATE_TRANSITIONS WHERE from_state_id='$strFromID' AND to_state_id='$strToID'";
    $strSQL3 ="SELECT *";
    $strSQL3.=" FROM $TABLE_STATE_TRANSITIONS";
    $strSQL3.=" WHERE from_state_id $TstrFromID";
    $strSQL3.=" AND to_state_id='$strToID'";
    // echo "Q3:<DIR>$strSQL3</DIR>";
    $result3=dbquery($strSQL3);
    $stateTran=0; // already exists
    if($row3=mysql_fetch_array($result3))
    {
      //$strError="ERROR: You already have a to/from transition deifned for these 2 states.";
      $stateTran=1; // already exists
    }
    // echo "stateTran=$stateTran<BR>";
    
    if(!$strError)
    {
      if(!$stateTran)
      {
        $strSQL1 ="INSERT INTO $TABLE_STATE_TRANSITIONS SET ";
        //$strSQL1.="  from_state_id='$strFromID'";
        $strSQL1.="  from_state_id $TstrFromID2";
        $strSQL1.=", to_state_id='$strToID'";
        $strSQL1.=", level='$strLevel'";
        // echo "Q1:<DIR>$strSQL1</DIR>";
        $result1=dbquery($strSQL1);
        $strError.="This new state transition has been added successfully.";
      }
      else
      {
        $strSQL1 ="UPDATE $TABLE_STATE_TRANSITIONS SET ";
        $strSQL1.=" level='$strLevel'";
        //$strSQL1.=" WHERE  from_state_id='$strFromID'";
        $strSQL1.=" WHERE  from_state_id $TstrFromID2";
        $strSQL1.=" AND  to_state_id='$strToID'";
        // echo "Q1-B:<DIR>$strSQL1</DIR>";
        $result1=dbquery($strSQL1);
        $strError.="This state transition has been updated successfully.";
      }

      /* what was the stran_id we just updated? */
      $strSQL1 ="SELECT stran_id FROM $TABLE_STATE_TRANSITIONS ";
      //$strSQL1.=" WHERE  from_state_id='$strFromID'";
      $strSQL1.=" WHERE  from_state_id $TstrFromID";
      $strSQL1.=" AND  to_state_id='$strToID'";
      // echo "Q1-C:<DIR>$strSQL1</DIR>";
      $result1=dbquery($strSQL1);
      $row1=mysql_fetch_array($result1);
      $strStranID=$row1['stran_id'];


      if($strDItem)
      {
        $strSQL2A ="SELECT * FROM $TABLE_STATE_RULES WHERE ";
        $strSQL2A.=" stran_id='$strStranID'";
        $strSQL2A.=" AND project_id='$strProjectID'";
        $strSQL2A.=" AND rule_id='$strDItem'";
	// echo "Q2A:<DIR>$strSQL2A</dir>";
        $result2A=dbquery($strSQL2A);
	$setVCode=($strDValue)?"value='$strDValue'":" value = NULL";
        if($row2A=mysql_fetch_array($result2A))
	{
          $strSQL2B ="UPDATE $TABLE_STATE_RULES SET ";
          //$strSQL2B.=" value='$strDValue'";
          $strSQL2B.=" $setVCode ";
          $strSQL2B.=" WHERE  stran_id='$strStranID'";
          $strSQL2B.=" AND rule_id='$strDItem'";
          $strSQL2B.=" AND project_id='$strProjectID'";
	  // echo "Q2B:<DIR>$strSQL2B</dir>";
          $result2B=dbquery($strSQL2B);
	}
	else
	{
          $strSQL2C ="INSERT INTO $TABLE_STATE_RULES SET ";
          $strSQL2C.="  project_id='$strProjectID'";
          $strSQL2C.=", stran_id='$strStranID'";
          $strSQL2C.=", rule_id='$strDItem'";
          //$strSQL2C.=", value='$strDValue'";
          $strSQL2B.=", $setVCode ";
	  // echo "Q2C:<DIR>$strSQL2C</dir>";
          $result2C=dbquery($strSQL2C);
	}
      }
    }
  }

  if($_FORM['btnSubmit']=="Perform Action" && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
  {
    if($_FORM['txtAddStateName'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
    {
      $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, TRUE);
      //$strProjectID       = validateText("Project ID",       $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
      $strAddStateName    = validateText("State Name",       $_FORM['txtAddStateName'], 2, 40, TRUE, TRUE);
      $strAddStateColor   = validateText("State Color",      $_FORM['txtAddStateColor'], 2, 40, TRUE, TRUE);
      $strAddStateInitial = validateText("Initial State?",   $_FORM['txtAddStateInitial'],  0, 1, FALSE, FALSE);
      $strAddStateFinal   = validateText("Final State?",     $_FORM['txtAddStateFinal'], 0, 1, FALSE, FALSE);
      if($strAddStateInitial && $strAddStateFinal)
      {
        $strError.="<BR>\nError: You can not have a state be both an initial and a final state.<BR>\n";
      }

      if(!$strError)
      {
        $strSQL5 ="SELECT * FROM $TABLE_STATES WHERE name like '$strAddStateName' AND project_id='$strProjectID'";
        $result5=dbquery($strSQL5);
        if($row5=mysql_fetch_array($result5))
        {
          $strError="ERROR: A state in this project already exists with that name. No Action Performed.<BR>\n";
        }
        else
        {
          $strSQL6 ="INSERT INTO $TABLE_STATES SET ";
          $strSQL6.="  name='$strAddStateName'";
          $strSQL6.=", project_id='$strProjectID'";
          $strSQL6.=", color='$strAddStateColor'";
          if(isset($strAddStateInitial) && $strAddStateInitial!="")
          {
            $strSQL6.=", initial='$strAddStateInitial'";
          }
          if(isset($strAddStateFinal) && $strAddStateFinal!="")
          {
            $strSQL6.=", final='$strAddStateFinal'";
          }
          $result6=dbquery($strSQL6);
          $strSQL7 ="SELECT * FROM $TABLE_STATES WHERE name like '$strAddStateName' AND project_id='$strProjectID'";
          $result7=dbquery($strSQL7);
          $row7=mysql_fetch_array($result7);
          $strStateID=$row7['state_id'];
          $strError = "This state $strAddStateName (ID = $strStateID) has been inserted successfully.";
        }
      }
    } 
    if($_FORM['txtEditState'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$_FORM['txtProjectID']]))
    {
      $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, TRUE);
      //$strProjectID   = validateText("Project ID",      $_FORM['txtProjectID'],  1, 11, TRUE, TRUE);
      $strEditState   = validateNumber("State to Edit", $_FORM['txtEditState'],  1, 1000, TRUE);
      if(!$strError)
      {
        if($_FORM['txtEditState2'])
        {
          ## commit changes
          $strStateName  = validateText("Name",   $_FORM['txtStateName'],   1, 11, TRUE, TRUE);
          $strStateColor = validateText("Color",  $_FORM['txtStateColor'],  1, 11, TRUE, TRUE);
          $strStateInitial = validateText("Inital?",  $_FORM['txtStateInitial'],  1, 11, FALSE, FALSE);
          $strStateFinal   = validateText("Final?",  $_FORM['txtStateFinal'],  1, 11, FALSE, FALSE);
          if($strStateInitial==1 && $strStateFinal==1)
          {
            $strError="Error: You can not have a state be both an initial and final state.";
          }
          $strSQL5 ="SELECT * FROM $TABLE_STATES WHERE name like '$strAddStateName' AND project_id='$strProjectID' AND state_id <> '$strEditState'";
          $result5=dbquery($strSQL5);
          if($row5=mysql_fetch_array($result5))
          {
            $strError.="<BR>\nERROR: Another state in this project already exists with that name. No Action Performed.<BR>\n";
          }
          if(!$strError)
          {
            $strSQL1 = "UPDATE $TABLE_STATES SET";
            $strSQL1.= " name='$strStateName'";
            $strSQL1.= ", color='$strStateColor'";
            if(isset($strStateInitial) && $strStateInitial!="") { $strSQL1.=", initial='1'"; }
            else { $strSQL1.=", initial = NULL"; }
            if(isset($strStateFinal) && $strStateFinal!="") { $strSQL1.=", final='1'"; }
            else { $strSQL1.=", final = NULL"; }
            $strSQL1.= " WHERE state_id='$strEditState'";
            $result1 = dbquery($strSQL1);
            $strError = "This item has been updated successfully.";
          }
        }
        else
        {
          $strSQL7 ="SELECT * FROM $TABLE_STATES WHERE state_id='$strEditState' AND project_id='$strProjectID'";
          $result7=dbquery($strSQL7);
          $row7=mysql_fetch_array($result7);
          $strStateName=$row7['name'];
          $strStateColor=$row7['color'];
          $strStateInitial=$row7['initial'];
          $_i_ck="";if($strStateInitial)$_i_ck="CHECKED";
          $strStateFinal=$row7['final'];
          $_f_ck="";if($strStateFinal)$_f_ck="CHECKED";
      writeHeader("Make your modifications to this State.");
      declareError(TRUE);
?>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtProjectID" value="<?=$strProjectID;?>">
<input type=hidden name="txtEditState" value="<?=$strEditState;?>">
<input type=hidden name="txtEditState2" value="<?=$strEditState;?>">
<input type=hidden name="btnSubmit" value="Perform Action">
<table class=wrap align=center>
<tr><td>Name:</td><td><input type=text name="txtStateName" value="<?=$strStateName;?>"></td></tr>
<tr><td>Color:</td><td><input type=text name="txtStateColor" value="<?=$strStateColor;?>"></td></tr>
<tr><td>Initial:</td><td><input type=checkbox <?=$_i_ck;?> value=1 name="txtStateInitial"</td></tr>
<tr><td>Final:</td><td><input type=checkbox <?=$_f_ck;?> value=1 name="txtStateFinal"></td></tr>
<tr><td colspan=2 align=center><input type=submit value="Commit Changes" class=form_button></td></tr>
</td></tr></table>
</form>
<?
  print "<BR><BR>";
  writeFooter();
  exit;
        }
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
      writeHeader("First select a project to create, edit or delete States with.");
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
        $BOT_SELECT.="<input class=form_button type='submit' value='Edit States' name='searchSubmit'> ";
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
  }

  writeHeader("Edit Project States");
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
<td>Edit State:</td>
<td>
<select class=forms name="txtEditState">
<option value="">Choose</option>
<?
$strSQL = "SELECT * FROM $TABLE_STATES WHERE project_id='$strProjectID' ORDER BY name";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strStateID = $row['state_id'];
  $strName  = $row['name'];
  echo "<option value='$strStateID'>$strName</option>\n";
}
?>
</select>
</td>
</tr>
<tr>
<td>Add State:</td>
<td>
[Name=<input type=text name="txtAddStateName">]
[Color=<select class=forms name="txtAddStateColor">
<option value="#000000">Black</option>
<option value="#0000FF">Blue</option>
<option value="#FF0000">Red</option>
<option value="#00FF00">Green</option>
<option value="#FFFF00">Yellow</option>
<option value="#FF00FF">Purple</option>
</select>]
[Initial:<input type=checkbox value=1 name="txtAddStateInitial">]
[Final:<input type=checkbox value=1 name="txtAddStateFinal">]
</td>
</tr>
    <tr><td colspan=2 align=center>
      <input class=form_button type="submit" value="Perform Action" name="btnSubmit">
      <input class=form_button type="reset" value="Reset" name="reset">
    </td></tr>
</table>
<BR><BR>
<table class=wrap border='0' cellpadding='2'>
<tr>
<td>Delete State:</td>
<td>
<select class=forms name="txtDeleteState">
<option value="">Choose</option>
<?
$strSQL = "SELECT * FROM $TABLE_STATES WHERE project_id='$strProjectID' ORDER BY name";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strStateID = $row['state_id'];
  $strName  = $row['name'];
  echo "<option value='$strStateID'>$strName</option>\n";
}
?>
</select>
      <input class=form_button id=b1 type="button" onclick="this.form.delSub2.tag=1;document.getElementById('b2').className='form_button';document.getElementById('b1').className='form_button2';" value="Step 1 Delete" name="delSub1">
      <input class=form_button2 id=b2 type="button" onclick="if(this.tag){this.form.deleteSubmit.value=1;this.form.submit();}" tag=0 value="Delete State" name="delSub2">
      <input type=hidden name="deleteSubmit" value="0">
</td>
</tr>

</table>
</form>
<BR><BR>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtProjectID" value="<?=$strProjectID;?>">
<input type=hidden name="txtDelID" value="">
<input type=hidden name="txtDelDID" value="">
<table class=wrap border='0' cellpadding='2'>
<tr><td align=center colspan=2><b>State Transitions</b></td></tr>
<?
$strSQL2 ="SELECT *";
$strSQL2.=" FROM ($TABLE_STATE_TRANSITIONS AS A";
$strSQL2.=" , $TABLE_STATES AS B)";
$strSQL2.=" WHERE (B.state_id=A.from_state_id";
$strSQL2.="        AND B.project_id='$strProjectID')";
$strSQL2.="       OR (A.from_state_id is NULL AND A.to_state_id=B.state_id AND B.project_id='$strProjectID')";
$strSQL2.=" ORDER BY B.name ";
//echo "Q2-FROM:<DIR>$strSQL2</DIR>";
$result2=dbquery($strSQL2);
while($row2=mysql_fetch_array($result2))
{
  $stran_id=$row2['stran_id'];
  $fromName=$row2['name'];
  $level=$row2['level'];
  $strSQL3 ="SELECT * FROM ($TABLE_STATE_TRANSITIONS AS A, $TABLE_STATES AS B) WHERE B.state_id=A.to_state_id AND B.project_id='$strProjectID' AND stran_id='$stran_id'";
  $result3=dbquery($strSQL3);
  $row3=mysql_fetch_array($result3);
  $toName=$row3['name'];


  $x=$REVERSE_PROJECT_ACCESS[$level]?$REVERSE_PROJECT_ACCESS[$level]:$REVERSE_PROJECT_ACCESS[''];
  if(!strcmp($fromName,$toName))
  {
    echo "<tr><td>From Ticket Creation to <b><i>$toName</i></b> [Level Does Not Apply from here]</td><td align=right> Delete #";
  }
  else
  {
    echo "<tr><td>From <b><i>$fromName</i></b> to <b><i>$toName</i></b> [$x]</td><td align=right> Delete #";
  }
  echo "<input class=form_button type=button onclick='this.form.txtDelID.value=$stran_id;this.form.submit();' value='$stran_id'>";
  echo "</td></tr>";

  /* find any state rules for this transisition and show them here... */
  $strSQL4 = "SELECT A.value, B.label, A.staterule_id";
  $strSQL4.= " FROM ($TABLE_STATE_RULES AS A";
  $strSQL4.= " , $TABLE_ITEM_TO_PROJECT AS B)";
  $strSQL4.= " WHERE A.project_id='$strProjectID'";
  $strSQL4.= " AND A.stran_id='".$row3['stran_id']."'";
  $strSQL4.= " AND A.rule_id=B.rule_id";
  $strSQL4.= " ORDER BY B.label";
  // echo "Q4:<DIR>$strSQL4</DIR>\n";
  $result4= dbquery($strSQL4);
  while($row4= mysql_fetch_array($result4))
  {
    $strV=$row4['value'];
    $strL=$row4['label'];
    $strI=$row4['staterule_id'];
    $temp=" must be set.";
    if($strV)
    {
      $temp=" must be set to the value of \"$strV\".";
    }
    echo "<tr><td> --&gt; Transition Rule: $strL - $temp</td>\n";
    echo "<td>Delete Rule #<input class=form_button type=button onclick='this.form.txtDelDID.value=$strI;this.form.submit();' value='$strI'></td></tr>";
  }
}
?>
<tr><td colspan=2>&nbsp;</td></tr>
<tr>
<td>
From 
<select class=forms name="txtFromID">
<option value="">Choose[null to open]</option>
<?
$strSQL = "SELECT * FROM $TABLE_STATES WHERE project_id='$strProjectID' ORDER BY name";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strStateID = $row['state_id'];
  $strName  = $row['name'];
  echo "<option value='$strStateID'>$strName</option>\n";
}
?>
</select>
 to
<select class=forms name="txtToID">
<option value="">Choose</option>
<?
$strSQL = "SELECT * FROM $TABLE_STATES WHERE project_id='$strProjectID' ORDER BY name";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strStateID = $row['state_id'];
  $strName  = $row['name'];
  echo "<option value='$strStateID'>$strName</option>\n";
}
?>
</select>
[Level=
<select class=forms name="txtLevel">
<?
reset($PROJECT_ACCESS);
while(list($name,$value)=each($PROJECT_ACCESS))
{
 echo "<option value='$value'>$name</option>";
 }
 ?>
</select>
]
<BR>
<?
// find all items that exist in this project
$strSQL = "SELECT label,rule_id FROM $TABLE_ITEM_TO_PROJECT WHERE project_id='$strProjectID' ORDER BY label";
$result= dbquery($strSQL);
$str="";
while($row= mysql_fetch_array($result))
{
 $r=$row['rule_id'];
 $l=$row['label'];
 $str.="<option value='$r'>$l</option>\n";
}
?>
Depends Upon Item <select class=forms name="txtDItem"><option SELECTED value=''>-None-</option><?=$str;?></select>
With Value <input name="txtDValue" class=forms size=10> [EmptyString denotes a value must be set] 
</td><td>
<input name="btnAddTransition" class=form_button type=submit value="Add Transition"></td></tr>
</table>
<table class=warn cellpaddin=0 cellspacing=0><tr><td align=center>Note: It is best to have ProjectLeads able to set off norm state changes and leave project admin level for 1 or 2 people.</td></tr></table>
</form>

<?
}
  print "<BR><BR>";
  writeFooter();
?>
