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
  if($_FORM['txtDelID'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$strProjectID]))
  {
    $strDelID     = validateNumber("Item Number",    $_FORM['txtDelID'],  1, 1000, TRUE);
    $strDelF      = validateNumber("First Item",     $_FORM['txtDelF'],  1, 1000, TRUE);
    $strDelD      = validateNumber("Second Item",    $_FORM['txtDelD'],  1, 1000, TRUE);
    $strDelV      = validateText("Dependency Value", $_FORM['txtDelV'],    1, 11, FALSE, FALSE);
    if(!$strError)
    {
      if(isset($strDelV) && $strDelV!="")
        $strSQL1="DELETE FROM $TABLE_ITEM_DEPENDENCY WHERE rule_id='$strDelF' AND drule_id='$strDelD' AND value like '$strDelV'";
      else
        $strSQL1="DELETE FROM $TABLE_ITEM_DEPENDENCY WHERE rule_id='$strDelF' AND drule_id='$strDelD' AND value IS NULL";
      $result1=dbquery($strSQL1);
      $strError="Item #$strDelID deleted successfully.";
    }
  }

  /* this will remember the project as last project */
  if(isset($_FORM['txtProjectID']))
    $_SESSION['LastProject']=$strProjectID;


  if($_FORM['btnAddItem'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$strProjectID]))
  {
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
    //$strProjectID     = validateText("Project ID",    $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
    $strFirstItem     = validateNumber("First Item",     $_FORM['txtFirstItem'],  1, 1000, TRUE);
    $strSecondItem    = validateNumber("Second Item",     $_FORM['txtSecondItem'],  1, 1000, TRUE);
    $strDependValue   = validateText("Dependency Value",    $_FORM['txtDependValue'],    1, 11, FALSE, FALSE);
    if(!$strError && $strFirstItem==$strSecondItem){$strError="Error: You can not have an item depend on itself.";}
    if(!$strError)
    {
      $strSQL1 ="INSERT INTO $TABLE_ITEM_DEPENDENCY SET ";
      $strSQL1.="  rule_id='$strFirstItem'";
      $strSQL1.=", drule_id='$strSecondItem'";
      if(isset($strDependValue) && $strDependValue!="")
      {
        $strSQL1.=", value='$strDependValue'";
      }
      $result1=dbquery($strSQL1);
      $strError="Your dependency has been added successfully.";
    }
  }

  if($_FORM['deleteSubmit'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$strProjectID]))
  { 
    $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
    //$strProjectID      = validateText("Project ID",    $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
    $strDeleteItem    = validateNumber("Item To Delete",     $_FORM['txtDeleteItem'],  1, 1000, TRUE);
    if(!$strError)
    {
      $strSQL1="DELETE FROM $TABLE_ITEM_DEPENDENCY WHERE rule_id='$strDeleteItem' OR drule_id='$strDeleteItem'";
      $result1=dbquery($strSQL1);
      $strSQL2="DELETE FROM $TABLE_ITEM_TO_PROJECT WHERE rule_id='$strDeleteItem'";
      $result2=dbquery($strSQL2);
      $strError.="Item [$strDeleteItem] has been deleted successfully."; // [with any dependicies removed].";
    }
  } 
  else if ($_FORM['btnSubmit']=="Perform Action")
  {
    if($_FORM['txtAddItemType'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$strProjectID]))
    {
      $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
      //$strProjectID      = validateText("Project ID",    $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
      $strAddItemLabel   = validateText("Item Label",    $_FORM['txtAddItemLabel'], 2, 40, TRUE, TRUE);
      $strAddItemType    = validateNumber("Item Type",     $_FORM['txtAddItemType'],  0, 1000, TRUE);
      $strAddItemDefault = validateText("Default Value", $_FORM['txtDefaultValue'], 0, 40, FALSE, FALSE);

      if(!$strError)
      {
        $strSQL5 ="SELECT rule_id FROM $TABLE_ITEM_TO_PROJECT WHERE type_id='$strAddItemType' AND project_id='$strProjectID' AND label LIKE '$strAddItemLabel'";
        $result5=dbquery($strSQL5);
        if($row5=mysql_fetch_array($result5))
        {
          $strError="ERROR: Item of this type with this name in this project already exists. No Action Performed.<BR>\n";
        }
        else
        {
          $strSQL6 ="INSERT INTO $TABLE_ITEM_TO_PROJECT SET ";
          $strSQL6.="  type_id='$strAddItemType'";
          $strSQL6.=", project_id='$strProjectID'";
          $strSQL6.=", label='$strAddItemLabel'";
          if(isset($strAddItemDefault) && $strAddItemDefault!="")
          {
            $strSQL6.=", default_value='$strAddItemDefault'";
          }
          $result6=dbquery($strSQL6);
          $strSQL7 ="SELECT rule_id FROM $TABLE_ITEM_TO_PROJECT WHERE type_id='$strAddItemType' AND project_id='$strProjectID' AND label LIKE '$strAddItemLabel'";
          $result7=dbquery($strSQL7);
          $row7=mysql_fetch_array($result7);
          $strRuleID=$row7['rule_id']; // so bottom of this page will show this project just created
          $strError = "This item $strAddItemLabel (ID = $strRuleID) has been inserted successfully.";
        }
      }
    } 
    if($_FORM['txtEditItem'] && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$strProjectID]))
    {
      $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
      //$strProjectID   = validateText("Project ID",    $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
      $strEditItem    = validateNumber("Item to Edit",     $_FORM['txtEditItem'],  1, 1000, TRUE);
      if(!$strError)
      {
        if($_FORM['txtEditItem2'] && !$_FORM['txtAddEnum'] && !$_FORM['txtDelEnum'])
        {
          ## commit changes
          $strItemLabel   = validateText("Label",    $_FORM['txtItemLabel'],    1, 11, TRUE, TRUE);
          $strItemDefault = validateText("Default",  $_FORM['txtItemDefault'],  1, 11, TRUE, TRUE);

          $strSQL1 = "UPDATE $TABLE_ITEM_TO_PROJECT SET";
          $strSQL1.= " label='$strItemLabel'";
          $strSQL1.= ", default_value='$strItemDefault'";
          $strSQL1.= " WHERE rule_id='$strEditItem'";
          $result1 = dbquery($strSQL1);
          $strError = "This item has been updated successfully.";
        }
        else
        {
echo "edit Enum:<BR>\n";
          if($_FORM['txtDelEnum'])
          {
echo "Deleting enum value $txtDelEnum<BR>\n";
            $strSQL1="DELETE FROM $TABLE_ITEM_ENUMS WHERE value='$txtDelEnum'";
            $result1=dbquery($strSQL1);
            $strError="Success in deleting Enum $txtDelEnum from Item.";
          }
          else if($_FORM['txtAddEnum'])
          {
            if(!$_FORM['txtaddenummove'])
            {
              $strAddEnum = validateText("Enum to Add",  $_FORM['txtAddEnum'],  1, 11, TRUE, TRUE);

              $strSQL6 ="INSERT INTO $TABLE_ITEM_ENUMS SET ";
              $strSQL6.="  the_order='1'";
              $strSQL6.=", rule_id='$strEditItem'";
              $strSQL6.=", value='$strAddEnum'";
              $result6=dbquery($strSQL6);
            }
            else
            {
              $strSQL6 = "UPDATE $TABLE_ITEM_ENUMS SET";
              $strSQL6.= " the_order='".$_FORM['txtaddenummove']."'";
              $strSQL6.= " WHERE rule_id='$strEditItem' AND value LIKE '".$_FORM['txtAddEnum']."'";
              $result6 = dbquery($strSQL6);
            }
            
          }
          $strSQL7 ="SELECT * FROM $TABLE_ITEM_TO_PROJECT AS A,$TABLE_ITEM_TYPE AS B WHERE A.rule_id='$strEditItem' AND A.type_id=B.type_id";
          $result7=dbquery($strSQL7);
          $row7=mysql_fetch_array($result7);
          $strItemLabel=$row7['label'];
          $strItemDefault=$row7['default_value'];
          $strItemType=$row7['type'];
      writeHeader("Make your modifications to this Item of type: $strItemType");
      declareError(TRUE);
?>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtProjectID" value="<?=$strProjectID;?>">
<input type=hidden name="txtEditItem" value="<?=$strEditItem;?>">
<input type=hidden name="txtEditItem2" value="<?=$strEditItem;?>">
<input type=hidden name="txtaddenummove" value="0">
<input type=hidden name="txtDelEnum" value="">
<input type=hidden name="btnSubmit" value="Perform Action">
<table class=wrap align=center>
<tr><td>Label:</td><td><input type=text name="txtItemLabel" value="<?=$strItemLabel;?>"></td></tr>
<tr><td>Default:</td><td><input type=text name="txtItemDefault" value="<?=$strItemDefault;?>"></td></tr>
<?
if($row7['type']=="Date")
{
  echo "<tr><td align=center colspan=2>Date-Type Default:<BR>Any non-zero/non-blank value for default indicates to default to today's date.</td></tr>\n";
}
if($row7['type']=="Person")
{
  // note this only indicates to default to person creating the ticket
  echo "<tr><td align=center colspan=2>Person-Type Default:<BR>Any non-zero/non-blank value for default indicates to default to current user.</td></tr>\n";
}
if($row7['type']=="Enum" || $row7['type']=="Choice") // added for Choice
{
$whichType=$row7['type'];
echo "<tr><td align=center colspan=2>$whichType-Type Default:<BR>This must match exactly with case sensitivity to an existing enumeration value.</td></tr>\n";

echo "<tr><td>$whichType:</td><td>";
echo "<table class=wrap>";
echo "<tr><td align=center>MOVE</td><td align=center>$whichType Value [Order]</td><td>DELETE</td></tr>\n";
$strSQL8 ="SELECT * FROM $TABLE_ITEM_ENUMS WHERE rule_id='$strEditItem' ORDER BY the_order,value";
$result8=dbquery($strSQL8);
while($row8=mysql_fetch_array($result8))
{
$up=1+$row8['the_order'];
$down=-1+$row8['the_order'];
echo "<tr><TD>";
echo "<a href='javascript:";
echo "document.form1.txtaddenummove.value=\"$up\";";
echo "document.form1.txtAddEnum.value=\"".$row8['value']."\";";
echo "document.form1.submit();'>+</a>";
echo "/";
echo "<a href='javascript:";
echo "document.form1.txtaddenummove.value=\"$down\";";
echo "document.form1.txtAddEnum.value=\"".$row8['value']."\";";
echo "document.form1.submit();'>-</a>";
echo "</td>";
echo "<TD>".$row8['value']." [".$row8['the_order']."]</td>";
echo "<td><a href='javascript:document.form1.txtDelEnum.value=\"".$row8['value']."\";document.form1.submit();'>Delete</a></td>";
echo "</tr>\n";
}

echo "<tr><td>ADD+</td><td><input type=text name=txtAddEnum></td><td>+ADD</tr></tr>";
echo "</table>";
echo "</td></tr>";

}
?>
<tr><td colspan=2 align=center><input type=submit value="Commit Changes" class=form_button></td></tr>
</td></tr></table>
</form>
<form class=forms name="form2" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtProjectID" value="<?=$strProjectID;?>">
<input class=form_button value="Return to Items" type=submit>
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
      writeHeader("First select a project to create, edit or delete Items with.");
      declareError(TRUE);

      if($_FORM['searchSubmit'])
      {
        $TOP_SELECT ="<form class=forms name='form2' method='POST' onsubmit='return(document.form2.txtProjectID.selectedIndex>0);' action='".$_SERVER['PHP_SELF']."'>";
        $TOP_SELECT.="<br><table class=wrap cellpadding='0' cellspacing=0><tr><td>";
        $TOP_SELECT.="<table class=forms border='0' cellpadding='2'>";
        $TOP_SELECT.="<tr><td colspan=2 align=center>Choose a Project</td></tr>";
        $TOP_SELECT.="<tr> <td >Project:</td> <td ><select name='txtProjectID' class=forms>";
        $TOP_SELECT.="<option value=''>Choose a Project</option>\n";
        $BOT_SELECT.=" </select></td>\n </tr>\n";
        $BOT_SELECT.="<tr><td colspan=2 align=center>";
        $BOT_SELECT.="<input class=form_button type='submit' value='Edit Items' name='searchSubmit'> ";
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
	  //echo "Q:<DIR>$strQ2</DIR>";
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
    $strEmail      = $row2['mail_alias'];
    $strAbbrev     = $row2['project_abbr'];
    $strAnyone     = $row2['allowanyonecreate']; if($strAnyone)$SEL_YES="SELECTED";else $SEL_NO="SELECTED";
  }

  writeHeader("Edit Project Items");
  declareError(TRUE);

if($strProjectID)
{
## if we get here - then the user has selected the Project to edit/delete/add Items into/from ##

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
<td>Edit Item:</td>
<td>
<select class=forms name="txtEditItem">
<option value="">Choose</option>
<?
$strSQL = "SELECT A.rule_id, A.label, B.type";
$strSQL.= " FROM ($TABLE_ITEM_TO_PROJECT AS A";
$strSQL.= " , $TABLE_ITEM_TYPE AS B)";
$strSQL.= " WHERE project_id='$strProjectID'";
$strSQL.= " AND A.type_id=B.type_id";
$strSQL.= " ORDER BY label";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strRuleID = $row['rule_id'];
  $strLabel  = $row['label'];
  $strType   = $row['type'];
  echo "<option value='$strRuleID'>$strLabel [$strType]</option>\n";
}
?>
</select>
</td>
</tr>
<tr>
<td>Add Item:</td>
<td>
[Type=<select class=forms name="txtAddItemType">
<option value="">Choose</option>
<?
$strSQL = "SELECT * FROM $TABLE_ITEM_TYPE ORDER BY type_id";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strType_ID = $row['type_id'];
  $strType    = $row['type'];
  echo "<option value='$strType_ID'>$strType</option>\n";
}
?>
</select>]
 [Label=<input class=forms name="txtAddItemLabel" type=text value="">]
 [DefaltValue=<input type=text name="txtDefaultValue">]
</td>
</tr>
    <tr><td colspan=2 align=center>
      <input class=form_button type="submit" value="Perform Action" name="btnSubmit">
      <input class=form_button type="reset" value="Reset" name="reset">
    </td></tr>
<tr><td colspan=2 align=center>Note: Default value only applies to items in your CREATE template.</td></tr>
</table>
<BR><BR>
<table class=wrap border='0' cellpadding='2'>
<tr>
<td>Delete Item:</td>
<td>
<select class=forms name="txtDeleteItem">
<option value="">Choose</option>
<?
$strSQL = "SELECT * FROM $TABLE_ITEM_TO_PROJECT WHERE project_id='$strProjectID' ORDER BY label";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strRuleID = $row['rule_id'];
  $strLabel  = $row['label'];
  echo "<option value='$strRuleID'>$strLabel</option>\n";
}
?>
</select>
      <input class=form_button id=b1 type="button" onclick="this.form.delSub2.tag=1;document.getElementById('b2').className='form_button';document.getElementById('b1').className='form_button2';" value="Step 1 Delete" name="delSub1">
      <input class=form_button2 id=b2 type="button" onclick="if(this.tag){this.form.deleteSubmit.value=1;this.form.submit();}" tag=0 value="Delete Item" name="delSub2">
      <input type=hidden name="deleteSubmit" value="0">
</td>
</tr>

</table>
</form>
<BR><BR>
<form class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtProjectID" value="<?=$strProjectID;?>">
<input type=hidden name="txtDelID" value="">
<input type=hidden name="txtDelF" value="">
<input type=hidden name="txtDelD" value="">
<input type=hidden name="txtDelV" value="">
<table class=wrap border='0' cellpadding='2'>
<tr><td align=center colspan=2><b>Item Dependencies</b></td></tr>
<?
$strSQL2 ="SELECT A.value as value,B.label as first, B.rule_id as first_id, C.label as second, C.rule_id as second_id FROM ($TABLE_ITEM_DEPENDENCY AS A, $TABLE_ITEM_TO_PROJECT AS B , $TABLE_ITEM_TO_PROJECT AS C) WHERE A.rule_id=B.rule_id AND A.drule_id=C.rule_id AND B.project_id='$strProjectID' AND C.project_ID='$strProjectID' ORDER BY A.rule_id";
$result2=dbquery($strSQL2);
$depnum=1;
while($row2=mysql_fetch_array($result2))
{
  $firstValue=$row2['value']?"=\"".$row2['value']."\"":"";
  $firstLabel=$row2['first'];
  $dependLabel=$row2['second'];
  echo "<tr><td><b><i>$dependLabel</i></b> depends upon <b><i>$firstLabel$firstValue</i></b></td><td> Delete #";
  echo "<input class=form_button type=button onclick='this.form.txtDelID.value=$depnum;this.form.txtDelF.value=this.form.del_first_$depnum.value;this.form.txtDelD.value=this.form.del_depend_$depnum.value;this.form.txtDelV.value=this.form.del_value_$depnum.value;this.form.submit();' value='$depnum'>";
  echo "<input type=hidden name='del_first_$depnum' value='".$row2['first_id']."'>";
  echo "<input type=hidden name='del_depend_$depnum' value='".$row2['second_id']."'>";
  echo "<input type=hidden name='del_value_$depnum' value='".$row2['value']."'>";
  echo "</td></tr>";
  $depnum++;
}
?>
<tr><td colspan=2>&nbsp;</td></tr>
<tr>
<td>
<select class=forms name="txtSecondItem">
<option value="">Choose</option>
<?
$strSQL = "SELECT * FROM $TABLE_ITEM_TO_PROJECT WHERE project_id='$strProjectID' ORDER BY label";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strRuleID = $row['rule_id'];
  $strLabel  = $row['label'];
  echo "<option value='$strRuleID'>$strLabel</option>\n";
}
?>
</select>
  depends upon
<select class=forms name="txtFirstItem">
<option value="">Choose</option>
<?
$strSQL = "SELECT * FROM $TABLE_ITEM_TO_PROJECT WHERE project_id='$strProjectID' ORDER BY label";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strRuleID = $row['rule_id'];
  $strLabel  = $row['label'];
  echo "<option value='$strRuleID'>$strLabel</option>\n";
}
?>
</select>
</td><td>Value=
<input class=forms type=text name="txtDependValue">
<input name="btnAddItem" class=form_button type=submit value="Add Dependency"></td></tr>
</table>
</form>

<?
}
  print "<BR><BR>";
  writeFooter();
?>
