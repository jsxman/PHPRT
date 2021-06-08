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
  if($_FORM['btnSubmit']=="Perform Action" && ( $CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['PROJECT_ADMIN'][$strProjectID]))
  {
    $strUserID    = validateNumber("User ID",     $_FORM['txtUserID'], 1, 1000, TRUE);
    $strLevel     = validateNumber("Access Level",$_FORM['txtLevel'], 1, 1000, FALSE);

    if(!isset($strLevel) || $strLevel=="" || $strLevel==0)
    {
      $strSQL = "DELETE FROM $TABLE_PROJECT_ACCESS WHERE user_id='$strUserID' AND project_id='$strProjectID'";
      $result= dbquery($strSQL);
      $strError="User Access has been removed.";
    }

    if(!$strError)
    {
      $strSQL = "SELECT * FROM $TABLE_PROJECT_ACCESS WHERE user_id='$strUserID' AND project_id='$strProjectID'";
      $result= dbquery($strSQL);
      if($row= mysql_fetch_array($result))
      {
        ## if the project and user ids exist in this table -- update
        $strSQL1 = "UPDATE $TABLE_PROJECT_ACCESS SET";
        $strSQL1.= " project_id='$strProjectID'";
        $strSQL1.= ", user_id='$strUserID'";
        $strSQL1.= ", level='$strLevel' WHERE project_id='$strProjectID' AND user_id='$strUserID'";
        $result1 = dbquery($strSQL1);
        $strError = "This user-access has been updated successfully.";
      }
      else
      {
        $strSQL1 = "INSERT INTO $TABLE_PROJECT_ACCESS SET";
        $strSQL1.= " project_id='$strProjectID'";
        $strSQL1.= ", user_id='$strUserID'";
        $strSQL1.= ", level='$strLevel'";
        $result1 = dbquery($strSQL1);
        $strError = "This user-access has been updated successfully.";
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
      writeHeader("First select a project to admin the User-Access.");
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
        $BOT_SELECT.="<input class=form_button type='submit' value='Admin Users' name='searchSubmit'> ";
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

  writeHeader("Edit Project User Accesses");
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
<td class=normal>Edit User Access to this Project:</td>
<td>
<select class=forms name="txtUserID">
<option value="">Choose</option>
<?
$strSQL = "SELECT A.user_id as UID,A.username,A.last_name,A.first_name,B.level FROM $TABLE_USERS AS A ";
$strSQL.= "LEFT JOIN $TABLE_PROJECT_ACCESS AS B ON A.user_id=B.user_id AND B.project_id='$strProjectID' ";
$strSQL.= " ORDER BY A.last_name";
$result= dbquery($strSQL);
while($row= mysql_fetch_array($result))
{
  $strLevel = $row['level'];
  $strFName = $row['first_name'];
  $strLName = $row['last_name'];
  $strUName = $row['username'];
  $strUserID= $row['UID'];
  $x=$REVERSE_PROJECT_ACCESS[$strLevel]?$REVERSE_PROJECT_ACCESS[$strLevel]:$REVERSE_PROJECT_ACCESS[''];
  echo "<option value='$strUserID'>$strLName, $strFName [$strUName] ($x)</option>\n";
}
?>
</select>
<select class=forms name="txtLevel">
<?
reset($PROJECT_ACCESS);
while(list($name,$value)=each($PROJECT_ACCESS))
{
 echo "<option value='$value'>$name</option>";
}
?>
</select>
</td>
</tr>
    <tr><td colspan=2 align=center>
      <input class=form_button type="submit" value="Perform Action" name="btnSubmit">
      <input class=form_button type="reset" value="Reset" name="reset">
    </td></tr>
</table>
<BR>
</form>

<?
}
  print "<BR><BR>";
  writeFooter();
?>
