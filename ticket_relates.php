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

  Include("config/global.inc.php");
  checkPermissions(1, $SESSION_TIMEOUT); // if not logged in, or session has timed out...

  $ProjectID = validateNumber("Project ID", $_FORM['project_id'], 1, 1000000, TRUE);
  //$ProjectID = validateText("Project ID",       $_FORM['project_id'],    1, 11, TRUE, TRUE);
  // this is the ticket# shown... 
  $TicketNum = validateNumber("Ticket Number", $_FORM['ticket_number'], 1, 1000000, TRUE);
  //$TicketNum = validateText("Ticket Number",    $_FORM['ticket_number'], 1, 11, TRUE, TRUE);
  $strAction = validateText("Relate Action",    $_FORM['action'], 1, 11, TRUE, TRUE);
  if(!$ProjectID || !$TicketNum)
  {
    $strError.="<BR>You must specify a project and a ticket.";
  }
  else
  {
    if(!$strAction)
      $strError.="<BR>You must have an action specified.";
  }

  $CAN_EDIT=false; // by default no

  // determine if this user has access level to view this ticket.
  if(!$strError)
  {
    $strLevel=$CURRENT_USER['PROJECT_LEVEL'][$ProjectID];
    if($strLevel>$PROJECT_ACCESS['display'])
    {
      $strError="ERROR: You do not have permission to view a ticket in this project.";
    }
    else if($strLevel>$PROJECT_ACCESS['manipulate'])
    {
      $CAN_EDIT=true;
    }
  }

  $_SESSION['LastProject']=$ProjectID;

  $BODY ="";

// test
//echo "Proj=$ProjectID PTicket=$TicketNum<BR>\n";
// end test


// found out what the real ticket id is
// also find out if this ticket has reached its final state - then we can not edit it!
    $strSQL1 = "SELECT A.ticket_id, B.final ,C.project_abbr";
    $strSQL1.= " FROM ($TABLE_EACH_TICKET AS A";
    $strSQL1.= " , $TABLE_STATES AS B)";
    $strSQL1.= " LEFT JOIN $TABLE_PROJECTS AS C ON C.project_id=A.project_id";
    $strSQL1.= " WHERE A.pticket_id='$TicketNum'";
    $strSQL1.= " AND A.project_id='$ProjectID'";
    $strSQL1.= " AND A.state_id=B.state_id";
    $result1= dbquery($strSQL1);
    if($row1= mysql_fetch_array($result1))
    {
      $ProjectAbbr=$row1['project_abbr'];
      $TicketID=$row1['ticket_id'];
      $TicketIsClosed=$row1['final'];
      $CAN_EDIT=$TicketIsClosed?false:true;
    }
    else
    {
      $strError="ERROR: Ticket does not exist.";
    }

  // obtain the templates to view tickets in this project ['View' and 'History']
if(!$strError)
{

// there is no template for this page.
// try to build this page using $BODY.="...."
// if an error occurs, set $strError
if(isset($_FORM['deleterelate']))
{
  // a request to remove a relation was made.
  //echo "Request to remove relate to : ".$_FORM['deleterelate']."<BR>\n";
  if(!strcmp($strAction,"child"))
  {
    $x=$_FORM['deleterelate'];
    $strSQL ="DELETE FROM $TABLE_TICKET_RELATIONS WHERE ";
    $strSQL.=" ticket_id='$TicketID'";
    $strSQL.=" AND oticket_id='$x'";
    $strSQL.=" AND relation='Parent'";
    $result= dbquery($strSQL);

    $strSQLB ="SELECT ";
    $strSQLB.="  A.project_id as  pid, A.pticket_id as  tid, B.project_abbr as  tpa ";
    $strSQLB.=" ,C.project_id as opid, C.pticket_id as otid, D.project_abbr as otpa ";
    $strSQLB.=" FROM ";
    $strSQLB.=" ($TABLE_EACH_TICKET AS A";
    $strSQLB.=" , $TABLE_PROJECTS AS B)";
    $strSQLB.=" LEFT JOIN $TABLE_EACH_TICKET AS C ON C.ticket_id='$x'";
    $strSQLB.=" LEFT JOIN $TABLE_PROJECTS AS D ON C.project_id=D.project_id";
    $strSQLB.=" WHERE";
    $strSQLB.=" A.ticket_id='$TicketID' AND A.project_id=B.project_id";
    //echo "Q-B:<DIR>$strSQLB</DIR>\n";
    $resultB= dbquery($strSQLB);
    $rowB=mysql_fetch_array($resultB);
    recordTransaction($TicketID,"RELATE","Deleted parent relate of ticket ".$rowB['otpa']."_".$rowB['otid']);
    recordTransaction($x,"RELATE","Deleted child relate of ticket ".$rowB['tpa']."_".$rowB['tid']);
    $strError.=notify($rowB['pid'],"event_id","RELATE",$TicketID);
    $strError.=notify($rowB['opid'],"event_id","RELATE",$x);
  }
  else if(!strcmp($strAction,"parent"))
  {
    $x=$_FORM['deleterelate'];
    $strSQL ="DELETE FROM $TABLE_TICKET_RELATIONS WHERE ";
    $strSQL.=" oticket_id='$TicketID'";
    $strSQL.=" AND ticket_id='$x'";
    $strSQL.=" AND relation='Parent'";
    $result= dbquery($strSQL);

    $strSQLB ="SELECT ";
    $strSQLB.="  A.project_id as  pid, A.pticket_id as  tid, B.project_abbr as  tpa ";
    $strSQLB.=" ,C.project_id as opid, C.pticket_id as otid, D.project_abbr as otpa ";
    $strSQLB.=" FROM ";
    $strSQLB.=" ($TABLE_EACH_TICKET AS A";
    $strSQLB.=" , $TABLE_PROJECTS AS B)";
    $strSQLB.=" LEFT JOIN $TABLE_EACH_TICKET AS C ON C.ticket_id='$x'";
    $strSQLB.=" LEFT JOIN $TABLE_PROJECTS AS D ON C.project_id=D.project_id";
    $strSQLB.=" WHERE";
    $strSQLB.=" A.ticket_id='$TicketID' AND A.project_id=B.project_id";
    //echo "Q-B:<DIR>$strSQLB</DIR>\n";
    $resultB= dbquery($strSQLB);
    $rowB=mysql_fetch_array($resultB);
    recordTransaction($TicketID,"RELATE","Deleted child relate of ticket ".$rowB['otpa']."_".$rowB['otid']);
    recordTransaction($x,"RELATE","Deleted parent relate of ticket ".$rowB['tpa']."_".$rowB['tid']);
    $strError.=notify($rowB['pid'],"event_id","RELATE",$TicketID);
    $strError.=notify($rowB['opid'],"event_id","RELATE",$x);
  }
  else
  {
    $x=$_FORM['deleterelate'];
    $strSQL ="DELETE FROM $TABLE_TICKET_RELATIONS WHERE ";
    $strSQL.=" ( (oticket_id='$TicketID' AND ticket_id='$x')";
    $strSQL.="   OR (ticket_id='$TicketID' AND oticket_id='$x') )";
    $strSQL.=" AND relation='Related'";
    $result= dbquery($strSQL);

    $strSQLB ="SELECT ";
    $strSQLB.="  A.project_id as  pid, A.pticket_id as  tid, B.project_abbr as  tpa ";
    $strSQLB.=" ,C.project_id as opid, C.pticket_id as otid, D.project_abbr as otpa ";
    $strSQLB.=" FROM ";
    $strSQLB.=" ($TABLE_EACH_TICKET AS A";
    $strSQLB.=" , $TABLE_PROJECTS AS B)";
    $strSQLB.=" LEFT JOIN $TABLE_EACH_TICKET AS C ON C.ticket_id='$x'";
    $strSQLB.=" LEFT JOIN $TABLE_PROJECTS AS D ON C.project_id=D.project_id";
    $strSQLB.=" WHERE";
    $strSQLB.=" A.ticket_id='$TicketID' AND A.project_id=B.project_id";
    //echo "Q-B:<DIR>$strSQLB</DIR>\n";
    $resultB= dbquery($strSQLB);
    $rowB=mysql_fetch_array($resultB);
    recordTransaction($TicketID,"RELATE","Deleted relate to ticket ".$rowB['otpa']."_".$rowB['otid']);
    recordTransaction($x,"RELATE","Deleted relate to ticket ".$rowB['tpa']."_".$rowB['tid']);
    $strError.=notify($rowB['pid'],"event_id","RELATE",$TicketID);
    $strError.=notify($rowB['opid'],"event_id","RELATE",$x);
  }
}

if(isset($_FORM['addrelate']))
{
  // a request to add a ticket relation of the specified type
  //echo "Request to add a relate to : ".$_FORM['addticket']."<BR>\n";
  if(!strcmp($strAction,"child"))
  {
    // child to add
    $x=$_FORM['addticket'];
    $strSQL ="SELECT ";
    $strSQL.=" *";
    $strSQL.=" FROM $TABLE_TICKET_RELATIONS AS A";
    $strSQL.=" WHERE";
    $strSQL.=" (A.oticket_id='$TicketID' AND A.ticket_id='$x')";
    $strSQL.=" OR (A.ticket_id='$TicketID' AND A.oticket_id='$x')";
    //echo "Q1:<DIR>$strSQL</DIR>\n";
    $result= dbquery($strSQL);
    if($rowTR=mysql_fetch_array($result))
    {
      $strError.="ERROR: You can not add this relation because one already exists between these 2 tickets.";
    }
    else
    {
      $strSQLB ="SELECT ";
      $strSQLB.="  A.project_id as  pid, A.pticket_id as  tid, B.project_abbr as  tpa ";
      $strSQLB.=" ,C.project_id as opid, C.pticket_id as otid, D.project_abbr as otpa ";
      $strSQLB.=" FROM ";
      $strSQLB.=" ($TABLE_EACH_TICKET AS A";
      $strSQLB.=" , $TABLE_PROJECTS AS B)";
      $strSQLB.=" LEFT JOIN $TABLE_EACH_TICKET AS C ON C.ticket_id='$x'";
      $strSQLB.=" LEFT JOIN $TABLE_PROJECTS AS D ON C.project_id=D.project_id";
      $strSQLB.=" WHERE";
      $strSQLB.=" A.ticket_id='$TicketID' AND A.project_id=B.project_id";
      //echo "Q-B:<DIR>$strSQLB</DIR>\n";
      $resultB= dbquery($strSQLB);
      $rowB=mysql_fetch_array($resultB);

      $strSQL ="INSERT INTO $TABLE_TICKET_RELATIONS SET ";
      $strSQL.=" ticket_id='$TicketID'";
      $strSQL.=",oticket_id='$x'";
      $strSQL.=",relation='Parent'";
      //echo "Q2:<DIR>$strSQL</DIR>\n";
      $result= dbquery($strSQL);
      recordTransaction($TicketID,"RELATE","Parent of ticket ".$rowB['otpa']."_".$rowB['otid']);
      recordTransaction($x,"RELATE","Child of ticket ".$rowB['tpa']."_".$rowB['tid']);
      $strError.=notify($rowB['pid'],"event_id","RELATE",$TicketID);
      $strError.=notify($rowB['opid'],"event_id","RELATE",$x);
    }
  }
  else if(!strcmp($strAction,"parent"))
  {
    // parent to add
    $x=$_FORM['addticket'];
    $strSQL ="SELECT ";
    $strSQL.=" *";
    $strSQL.=" FROM $TABLE_TICKET_RELATIONS AS A";
    $strSQL.=" WHERE";
    $strSQL.=" (A.oticket_id='$TicketID' AND A.ticket_id='$x')";
    $strSQL.=" OR (A.ticket_id='$TicketID' AND A.oticket_id='$x')";
    //echo "Q1:<DIR>$strSQL</DIR>\n";
    $result= dbquery($strSQL);
    if($rowTR=mysql_fetch_array($result))
    {
      $strError.="ERROR: You can not add this relation because one already exists between these 2 tickets.";
    }
    else
    {
      $strSQLB ="SELECT ";
      $strSQLB.="  A.project_id as  pid, A.pticket_id as  tid, B.project_abbr as  tpa ";
      $strSQLB.=" ,C.project_id as opid, C.pticket_id as otid, D.project_abbr as otpa ";
      $strSQLB.=" FROM ";
      $strSQLB.=" ($TABLE_EACH_TICKET AS A";
      $strSQLB.=" , $TABLE_PROJECTS AS B)";
      $strSQLB.=" LEFT JOIN $TABLE_EACH_TICKET AS C ON C.ticket_id='$x'";
      $strSQLB.=" LEFT JOIN $TABLE_PROJECTS AS D ON C.project_id=D.project_id";
      $strSQLB.=" WHERE";
      $strSQLB.=" A.ticket_id='$TicketID' AND A.project_id=B.project_id";
      //echo "Q-B:<DIR>$strSQLB</DIR>\n";
      $resultB= dbquery($strSQLB);
      $rowB=mysql_fetch_array($resultB);

      $strSQL ="INSERT INTO $TABLE_TICKET_RELATIONS SET ";
      $strSQL.=" oticket_id='$TicketID'";
      $strSQL.=",ticket_id='$x'";
      $strSQL.=",relation='Parent'";
      //echo "Q2:<DIR>$strSQL</DIR>\n";
      $result= dbquery($strSQL);
      recordTransaction($TicketID,"RELATE","Child of ticket ".$rowB['otpa']."_".$rowB['otid']);
      recordTransaction($x,"RELATE","Parent of ticket ".$rowB['tpa']."_".$rowB['tid']);
      $strError.=notify($rowB['pid'],"event_id","RELATE",$TicketID);
      $strError.=notify($rowB['opid'],"event_id","RELATE",$x);
    }
  }
  else
  {
    // relation to add
    $x=$_FORM['addticket'];
    $strSQL ="SELECT ";
    $strSQL.=" * ";
    $strSQL.=" FROM $TABLE_TICKET_RELATIONS AS A";
    $strSQL.=" WHERE";
    $strSQL.=" (A.oticket_id='$TicketID' AND A.ticket_id='$x')";
    $strSQL.=" OR (A.ticket_id='$TicketID' AND A.oticket_id='$x')";
    //echo "Q1:<DIR>$strSQL</DIR>\n";
    $result= dbquery($strSQL);
    $rowTR=mysql_fetch_array($result);
    if($rowTR)
    {
      $strError.="ERROR: You can not add this relation because one already exists between these 2 tickets.";
    }
    else
    {
      $strSQLB ="SELECT ";
      $strSQLB.="  A.project_id as  pid, A.pticket_id as  tid, B.project_abbr as  tpa ";
      $strSQLB.=" ,C.project_id as opid, C.pticket_id as otid, D.project_abbr as otpa ";
      $strSQLB.=" FROM ";
      $strSQLB.=" ($TABLE_EACH_TICKET AS A";
      $strSQLB.=" , $TABLE_PROJECTS AS B)";
      $strSQLB.=" LEFT JOIN $TABLE_EACH_TICKET AS C ON C.ticket_id='$x'";
      $strSQLB.=" LEFT JOIN $TABLE_PROJECTS AS D ON C.project_id=D.project_id";
      $strSQLB.=" WHERE";
      $strSQLB.=" A.ticket_id='$TicketID' AND A.project_id=B.project_id";
      //echo "Q-B:<DIR>$strSQLB</DIR>\n";
      $resultB= dbquery($strSQLB);
      $rowB=mysql_fetch_array($resultB);

      $strSQL ="INSERT INTO $TABLE_TICKET_RELATIONS SET ";
      $strSQL.=" ticket_id='$TicketID'";
      $strSQL.=",oticket_id='$x'";
      $strSQL.=",relation='Related'";
      //echo "Q2:<DIR>$strSQL</DIR>\n";
      $result= dbquery($strSQL);
      recordTransaction($TicketID,"RELATE","Related to ticket ".$rowB['otpa']."_".$rowB['otid']);
      recordTransaction($x,"RELATE","Related to ticket ".$rowB['tpa']."_".$rowB['tid']);
      $strError.=notify($rowB['pid'],"event_id","RELATE",$TicketID);
      $strError.=notify($rowB['opid'],"event_id","RELATE",$x);
    }
  }
}

}

// here is where you would perform action if some is posted.
// if an error occurs, just update $strError.="..."


if(!$strError)
{

$headerMSG="";
$SQLlogic="";
if(!strcmp($strAction,"child"))
{
  // we want to assign other tickets as children to this ticket.
  //echo "making children<BR>\n";
  $headerMSG="This Ticket's Children";
  $SQLlogic=" (A.ticket_id='$TicketID' AND A.relation='Parent')";
}
else if(!strcmp($strAction,"parent"))
{
  // we want to assign other tickets as parents to this ticket.
  //echo "making parents<BR>\n";
  $headerMSG="This Ticket's Parents";
  $SQLlogic=" (A.oticket_id='$TicketID' AND A.relation='Parent')";
}
else
{
  // we want to make a non-parent/child relation
  //echo "relating to others<BR>\n";
  $headerMSG="Ticket's Related";
  $SQLlogic=" (A.ticket_id='$TicketID' OR A.oticket_id='$TicketID') AND A.relation='Related'";
}


$strSQL = "SELECT";
$strSQL.= " A.relation";
$strSQL.= ",A.ticket_id as lticket_id, A.oticket_id as rticket_id";
$strSQL.= ",B.pticket_id as lpticket_id, D.pticket_id as rpticket_id";
$strSQL.= ",C.name as lname,  C.final as lfinal";
$strSQL.= ",E.name as rname, E.final as rfinal";
$strSQL.= ",F.project_abbr as labbr, G.project_abbr as rabbr";
$strSQL.= ",H.level as llevel";
$strSQL.= ",I.level as rlevel";
$strSQL.= " FROM";
$strSQL.= " ($TABLE_TICKET_RELATIONS AS A";
$strSQL.= ", $TABLE_EACH_TICKET AS B";
$strSQL.= ", $TABLE_STATES AS C";
$strSQL.= ", $TABLE_EACH_TICKET AS D";
$strSQL.= ", $TABLE_STATES AS E";
$strSQL.= ", $TABLE_PROJECTS AS F";
$strSQL.= ", $TABLE_PROJECTS AS G)";
$strSQL.= " LEFT JOIN $TABLE_PROJECT_ACCESS AS H";
$strSQL.= "    ON H.project_id=B.project_id";
$strSQL.= "    AND H.user_id='".$CURRENT_USER['ID']."'";
$strSQL.= " LEFT JOIN $TABLE_PROJECT_ACCESS AS I";
$strSQL.= "    ON I.project_id=D.project_id";
$strSQL.= "    AND I.user_id='".$CURRENT_USER['ID']."'";
$strSQL.= " WHERE";
$strSQL.= $SQLlogic;
$strSQL.= " AND B.ticket_id=A.ticket_id";
$strSQL.= " AND B.state_id=C.state_id";
$strSQL.= " AND D.ticket_id=A.oticket_id";
$strSQL.= " AND D.state_id=E.state_id";
$strSQL.= " AND B.project_id=F.project_id";
$strSQL.= " AND D.project_id=G.project_id";
$strSQL.= " ORDER BY A.relation, A.ticket_id, A.oticket_id";

$result= dbquery($strSQL);
$found=0;
$rowC=0;
$BODY.="<table cellpadding=2 cellspacing=0 class=warn>";
$BODY.="<tr class=rowh><td colspan=2 align=center>TICKET RELATIONS - # ";
$BODY.="<a class='header' href='$PAGE_VIEW_TICKET?ticket_number=$TicketNum&project_id=$ProjectID'>";
$BODY.=$ProjectAbbr."_";
$BODY.="$TicketNum";
$BODY.="</a>";
$BODY.="</td></tr>";
$BODY.="<tr><td valign=top>"; /* start of left column */
$BODY.="<form action='$PHP_SELF' method=POST>";
$BODY.="<input type=hidden name='project_id' value='$ProjectID'>";
$BODY.="<input type=hidden name='ticket_number' value='$TicketNum'>";
$BODY.="<input type=hidden name='action' value='$strAction'>";
$BODY.="<table cellpadding=2 cellspacing=0 class=wrap2>";
$BODY.="<tr class=rowh><td colspan=2 align=center>$headerMSG</td></tr>";
$BODY.="<tr class=tiny><td colspan=2 align=center>Click the checkbox to remove the relationship.</td></tr>";
while($rowTR=mysql_fetch_array($result))
{
$rowC=(($rowC+1)%2)+1;
$strRelation =$rowTR['relation'];
$strData=array();
$strData['left']=array();
$strData['left']['ticket_id']   =$rowTR['lticket_id'];
$strData['left']['pticket_id']  =$rowTR['lpticket_id'];
$strData['left']['state']       =$rowTR['lname'];
$strData['left']['is_final']    =$rowTR['lfinal'];
$strData['left']['proj_abbr']   =$rowTR['labbr'];
$strData['left']['access_level']=$rowTR['llevel'];
$strData['right']=array();
$strData['right']['ticket_id']   =$rowTR['rticket_id'];
$strData['right']['pticket_id']  =$rowTR['rpticket_id'];
$strData['right']['state']       =$rowTR['rname'];
$strData['right']['is_final']    =$rowTR['rfinal'];
$strData['right']['proj_abbr']   =$rowTR['rabbr'];
$strData['right']['access_level']=$rowTR['rlevel'];



// this would check to see if this ticket is related already.
//   it is not used here because it could be related PARENT-CHILD or RELATED
//   -- this will give them an error on a post if they try...
//if (!$strData['left']['ticket_id']==$TicketID &&
//    !$strData['right']['ticket_id']==$TicketID)




if(strcmp($strRelation,"Related"))
{
  if($strData['left']['ticket_id']==$TicketID)
  {
    // we are the PARENT
    $strRelation="is a child.";
  }
  else
  {
    // we are the CHILD
    $strRelation="is a parent.";
    $strTemp=$strData['right'];
    $strData['right']=$strData['left'];
    $strData['left']=$strTemp;
  }
}
else
{
  // must be RELATED
  // then find out which left or right ticket is this ticket
  //if($strOTicketID==$TicketID)
  $strRelation="is related.";
  if($strData['right']['ticket_id']==$TicketID)
  {
    // we are related and we want to have our ticket be the LEFT ticket for processing below
    $strTemp=$strData['right'];
    $strData['right']=$strData['left'];
    $strData['left']=$strTemp;
  }
}



//echo "found: ".$strData['right']['ticket_id']."<BR>\n";
$BODY.="<tr class=row$rowC><td><input class=forms onclick='this.form.submit()' name='deleterelate' type=checkbox value='".$strData['right']['ticket_id']."'>".$strData['right']['proj_abbr']."_".$strData['right']['pticket_id']." $strRelation</td></tr>";
}
$BODY.="</table><BR>\n";
$BODY.="</td><td valign=top>"; // start of page RIGHT COLUMN

$BODY.="<table cellpadding=2 cellspacing=0 class=wrap2>";
$BODY.="<tr class=rowh><td align=center>Select To Relate</td></tr>\n";
$BODY.="<tr><td>";
$BODY.="<select name='addticket' size=15 class=forms>";
// make a possibly HUGE select list of all tickets that can be related to this ticket.
// owner must have manipulate access to BOTH projects
// order by PROJECT and then by ticket ID
$strSQL ="SELECT";
$strSQL.=" B.ticket_id, B.pticket_id, C.project_abbr";
$strSQL.=" ,B.cache";
$strSQL.=" FROM";
$strSQL.=" ($TABLE_PROJECT_ACCESS AS A";
$strSQL.=", $TABLE_EACH_TICKET AS B";
$strSQL.=", $TABLE_PROJECTS AS C)";
$strSQL.=" WHERE";
$strSQL.=" A.user_id='".$CURRENT_USER['ID']."'";
$strSQL.=" AND A.level<='".$PROJECT_ACCESS['manipulate']."'";
$strSQL.=" AND B.project_id=A.project_id";
$strSQL.=" AND B.project_id=C.project_id";
$strSQL.=" ORDER BY C.project_abbr, B.pticket_id";
$result= dbquery($strSQL);
while($row=mysql_fetch_array($result))
{
  $x="";
  if(preg_match("/<.*?>(.*?)<.*>/",$row['cache'],$match))
  {
   $x=$match[1];
  }


  $BODY.="<option value='".$row['ticket_id']."'>".$row['project_abbr']."_".$row['pticket_id']." : $x</option>\n";
}
$BODY.="</select>";
$BODY.="</td></tr>";
$BODY.="<tr class=row1>";
$BODY.="<td align=center>";
$BODY.="<input class=form_button type=submit name='addrelate' value='Add Related Ticket'>";
$BODY.="</td></tr>\n";
$BODY.="</table>"; // end the inner right table
$BODY.="</td></tr>";
$BODY.="<tr><td colspan=2 align=center>";
$BODY.="</form>";

$BODY.="<form action='$PAGE_VIEW_TICKET' method=POST>";
$BODY.="<input type=hidden name=ticket_number value='$TicketNum'>";
$BODY.="<input type=hidden name=project_id value='$ProjectID'>";
$BODY.="<table align=center><tr><td><input class=form_button type=submit value='Back to Ticket ".$ProjectAbbr."_$TicketNum'></td></tr></table>";
$BODY.="</form>";

$BODY.="</td></tr></table>"; // end of bigger 2 column page table

}


writeHeader();
declareError(TRUE);
?>
<BR>

<?

if(!$strError)
{
  print $BODY;
}

writeFooter();
?>
