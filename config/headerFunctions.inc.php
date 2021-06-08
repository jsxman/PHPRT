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


function writeHeader($strPageTitle = "")
{
  global $SCRIPT_NAME;
  global $CURRENT_USER;
  global $secureAdmin;
  global $TABLE_USERS;
  global $TABLE_PROJECT_ACCESS;
  global $TABLE_PROJECTS;
  global $TABLE_PROJECT_ACCESS;
  global $urlPrefix;
  global $pageName;
  global $PAGE_CREATE_TICKET;
  global $PROJECT_ACCESS;
  global $PAGE_ADMIN_ACCESS;
  global $PAGE_PROJ_DIST;

  if ($strPageTitle == "")
  {
     $strPageTitle = "JS-X.com - PHP RT";
  }
  else
  {
     $showHeader = TRUE;
  }
  $urlPrefix = "http";

?>
<HTML>
<HEAD>
<TITLE><? echo $strPageTitle; ?></TITLE>

<SCRIPT LANGUAGE="javascript">
<!--
function warn_on_submit(msg)
{
        if (!confirm(msg)) {
                alert("No changes made.");
                return false;
        }
}
//-->
</script>
<?
include("styles_css.php");
?>
</HEAD>
<BODY>
<TABLE class=main_wrap cellpadding='2' cellspacing='0' width='100%'>
<tr class=page_menu>
<td width=35%><?=date("M d, Y h:i a",time());?></td>
<td width=30% class=page_title>PHP RT</td>
<td width=35% class=page_menu align=right>
<?
$Msg="Welcome: Guest [ <a class=page_menu href='login.php'>Login</a> ]";
if($CURRENT_USER['ID'])
{
  $Msg= "Welcome:&nbsp;".$CURRENT_USER['FIRST_NAME']."&nbsp;".$CURRENT_USER['LAST_NAME']."&nbsp;";
  $Msg.="[&nbsp;";
  $Msg.="<a class=page_menu href='edit_user.php'>Profile</a>&nbsp;";
  $Msg.="|&nbsp;";
  $Msg.="<a class=page_menu href='logout.php'>Logout</a>&nbsp;";
  $Msg.="]\n";
}
print $Msg;

print "</td></tr></table>";


print "<table class=link_box cellpadding=2 cellspacing=0 width='100%'>";
if(!isset($CURRENT_USER['IS_A_DB_ADMIN']))$CURRENT_USER['IS_A_DB_ADMIN']=0;
if(!isset($CURRENT_USER['IS_A_PROJECT_ADMIN']))$CURRENT_USER['IS_A_PROJECT_ADMIN']=0;
if ($CURRENT_USER['IS_A_DB_ADMIN'])
{
  print "<tr class=admindb><td colspan=3 valign=middle class=admin_links>Database Administration Links: ";
  print " <a class=admin_links title='Edit Users' href='admin_users.php'>Users</a>";
  print " | <a class=admin_links title='Edit Projects' href='admin_projects.php'>Projects</a>";
  print " | <a class=admin_links title='Edit This Database' href='admin_database.php'>Database</a>";
  print " | <a class=admin_links href='$PAGE_ADMIN_ACCESS'>AccessTimes</a>";
  print "</td></tr>";
}
if ($CURRENT_USER['IS_A_DB_ADMIN'] || $CURRENT_USER['IS_A_PROJECT_ADMIN'])
{
  print "<tr class=adminpr><td colspan=3 valign=middle class=admin_links>Project Administration Links: ";
  $temp="";
  if($CURRENT_USER['IS_A_DB_ADMIN'])
  {
    $temp="| ";
    print " <a class=admin_links href='admin_projects.php'>Admin Projects</a>";
  }
  //print " $temp<a class=admin_links href='project_events.php'>Events</a>";
  print " $temp";
  print " <a class=admin_links title='Edit Items' href='project_items.php'>Items</a>";
  print " | <a class=admin_links title='Change Access and Levels' href='project_access.php'>Access</a>";
  print " | <a class=admin_links href='$PAGE_PROJ_DIST'>Distributions</a>";
  // print " | <a class=admin_links href='project_reminder.php'>Reminders</a>";
  //print " | Reminders";
  print " | <a class=admin_links title='Edit Project States' href='project_states.php'>States</a>";
  print " | <a class=admin_links title='Edit Project Templates' href='project_templates.php'>Templates</a>";
  print "</td></tr>";
}

if(!isset($_SESSION['LastProject']))$_SESSION['LastProject']="";
if(isset($CURRENT_USER['ID']) && $CURRENT_USER['ID'])
{

  $strSQL ="SELECT *";
  $strSQL.=" FROM $TABLE_PROJECTS AS A";
  $strSQL.=" , $TABLE_PROJECT_ACCESS AS B";
  $strSQL.=" WHERE A.project_id=B.project_id";
  $strSQL.=" AND B.user_id='".$CURRENT_USER['ID']."'";
  $strSQL.=" AND B.level <= '".$PROJECT_ACCESS['display']."'";
  $strSQL.=" ORDER BY project_name";
  $result=dbquery($strSQL);
  $_temp="";
  $numFound=0;
  while($row=mysql_fetch_array($result))
  {
    $numFound++;
    $ck = strcmp($_SESSION['LastProject'],$row['project_id'])?"":" SELECTED ";
    $_temp.="<option $ck value='".$row['project_id']."'>".$row['project_name']."</option>";
  }
  print "<tr><td valign=middle class=top_links>";
  /* make it GET so a user can refresh the page without the reposting message from the
   * web browser.
   */
  print "<form method=GET name='projects' action='index.php'>";
  print "<input type=submit name='view' class=form_button value=\"View\">&nbsp;";
  print "<select name='txtProjectID' class=forms>";
  if($numFound>1)
    print "<option value='0'>View All</option>";
  print $_temp;
  print "</select>";
  print "</form>";
}
?>
</td>
<td align=center>
<?if(isset($_SESSION['LastProject'])){?>
<form method=GET action='view_ticket.php'>
<input size=5 type=text class=forms value='' name='ticket_number'>
<input type=hidden name='project_id' value='<?=$_SESSION['LastProject'];?>'>
<input type=submit class=form_button value='View Ticket'>
</form>
<?}else echo "&nbsp;";?>
</td>
<td class=top_links align=right>
<?
if(isset($CURRENT_USER['ID']) && $CURRENT_USER['ID'])
{

  $strSQL ="SELECT *";
  $strSQL.=" FROM $TABLE_PROJECTS AS A";
  $strSQL.=" , $TABLE_PROJECT_ACCESS AS B";
  $strSQL.=" WHERE A.project_id=B.project_id";
  $strSQL.=" AND ((B.user_id='".$CURRENT_USER['ID']."'";
  $strSQL.="      AND B.level<='".$PROJECT_ACCESS['manipulate']."')";
  $strSQL.="      OR A.allowanyonecreate='1')";
  $strSQL.=" GROUP BY project_name";
  $strSQL.=" ORDER BY project_name";
  $result=dbquery($strSQL);
  $_temp="";
  $numFound=0;
  while($row=mysql_fetch_array($result))
  {
    $numFound++;
    $ck = strcmp($_SESSION['LastProject'],$row['project_id'])?"":" SELECTED ";
    $_temp.="<option $ck value='".$row['project_id']."'>".$row['project_name']."</option>";
  }
  print "<form method=POST name='projects' action='$PAGE_CREATE_TICKET'>";
  print "<select name='txtProjectID' class=forms>";
  if($numFound>1)
    print "<option value='0'>-Choose Project-</option>";
  print $_temp;
  print "</select>&nbsp;";
  print "<input type=submit name='create' class=form_button value=\"Create\">";
  print "</form>";
}
?>
</td></tr></table>

<table class=page_body align=center cellpadding=0 cellspacing=0 width='100%'><tr><td align=center><BR>
<?
  if (isset($showHeader) && $showHeader) {
      echo "<b>$strPageTitle</b><BR>\n";
  }
}
 
function writeFooter()
{

    /* before writing the footer - send any emails */
    send_emails();


    global $PAGE_TIME_START;
    global $PHPRT_VERSION;
    $x=microtime();
    $x-=$PAGE_TIME_START;
    $_temp=$x*1000;
    settype($_temp,"integer");
    $x=$_temp/1000;
    if($x<0)$x=0.001; /* just for wierd cases when time goes negative */


    echo " </TD></TR>";

    if(!isset($PAGE_INDEX))$PAGE_INDEX="index.php";
    $extra="";
    echo "<tr class=warn>";
    echo "<td class=normal align=right>";
    echo "<a href='$PAGE_INDEX?FONT_CHANGE=-3'>&lt;</a>";
    echo "<a href='$PAGE_INDEX?FONT_CHANGE=-2'>&lt;</a>";
    echo "<a href='$PAGE_INDEX?FONT_CHANGE=-1'>&lt;</a>";
    echo "Font Size";
    echo "<a href='$PAGE_INDEX?FONT_CHANGE=1'>&gt;</a>";
    echo "<a href='$PAGE_INDEX?FONT_CHANGE=2'>&gt;</a>";
    echo "<a href='$PAGE_INDEX?FONT_CHANGE=3'>&gt;</a>";
    echo "</td></tr>";
    echo "<tr class=wrap2><td class=normal align=right>".dbstat()." Page Load Time: $x seconds.</td></tr>";
    echo "</TABLE>";
    echo "<table class=cc align=center>";
    echo "<tr><td><a class=cc href='http://consulting.js-x.com/'>JS-X.com</a> <a class=cc href='http://phprt.js-x.com/'>PHP RT $PHPRT_VERSION</a></td></tr>";
    echo "</table>\n";
    echo "<BR><BR>\n";
    if(isset($SHOW_TODO) && $SHOW_TODO){showToDo();}
    echo "</BODY></HTML>";
}

?>
