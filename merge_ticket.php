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


// found out what the real ticket id is
// also find out if this ticket has reached its final state - then we can not edit it!
    $strSQL1 = "SELECT A.ticket_id, B.final ";
    $strSQL1.= " FROM ($TABLE_EACH_TICKET AS A";
    $strSQL1.= " , $TABLE_STATES AS B)";
    $strSQL1.= " WHERE A.pticket_id='$TicketNum'";
    $strSQL1.= " AND A.project_id='$ProjectID'";
    $strSQL1.= " AND A.state_id=B.state_id";
    $result1= dbquery($strSQL1);
    if($row1= mysql_fetch_array($result1))
    {
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

if(isset($_FORM['mergeinto']))
{
  // a request to add a ticket relation of the specified type
  //echo "Request to add a relate to : ".$_FORM['addticket']."<BR>\n";
  if(!strcmp($strAction,"merge"))
  {
    //echo "you want to merge this ticket.";

// mergeticket
  $strMergeTicket = validateNumber("Merge Ticket ID", $_FORM['mergeticket'], 1, 1000000, TRUE);

// verify you can merge this ticket into the requested ticket.
$strSQL ="SELECT";
$strSQL.=" B.ticket_id, B.pticket_id, C.project_abbr";
$strSQL.=" ,B.cache";
$strSQL.=" FROM";
$strSQL.=" ($TABLE_PROJECT_ACCESS AS A";
$strSQL.=", $TABLE_EACH_TICKET AS B";
$strSQL.=", $TABLE_PROJECTS AS C";
$strSQL.=", $TABLE_STATES AS D)";
$strSQL.=" WHERE";
$strSQL.=" A.user_id='".$CURRENT_USER['ID']."'";
$strSQL.=" AND A.level<='".$PROJECT_ACCESS['manipulate']."'";
$strSQL.=" AND B.project_id=A.project_id";
$strSQL.=" AND B.project_id=C.project_id";
$strSQL.=" AND B.state_id=D.state_id"; // not closed
$strSQL.=" AND D.final<>'1'"; // not closed
$strSQL.=" AND B.ticket_id<>'$TicketID'"; // not this ticket
$strSQL.=" AND B.ticket_id<>'$TicketID'"; // not this ticket
$strSQL.=" AND B.ticket_id='$strMergeTicket'"; // not this ticket
$strSQL.=" AND B.eticket_id is NULL"; // make sure the ticket we merge into is not already merged.
$strSQL.=" ORDER BY C.project_abbr, B.pticket_id";
//echo "Q:<DIR>$strSQL</DIR>";
$result= dbquery($strSQL);
if($row=mysql_fetch_array($result))
{
  //echo "F:".$row['ticket_id']."-".$row['pticket_id']."<BR>";
  $strSQL = "UPDATE $TABLE_EACH_TICKET SET";
  $strSQL.= " eticket_id='".$row['ticket_id']."'";
  $strSQL.= " WHERE ticket_id='$TicketID'";
  //echo "Q:<DIR>$strSQL</DIR>";
  $result = dbquery($strSQL);
  $strError.="<BR>SUCCESS.  Ticket Merged.";

  // save the old ticket number in the transaction
  recordTransaction($row['ticket_id'],"merge",$TicketNum);
  recordTransaction($row['ticket_id'],"mergedata",(showTransactions($TicketID)));
  $strError.=notify($ProjectID,"event_id","MERGE",$row['ticket_id']);


  // send the user to the new page.
  $newURL=$PAGE_VIEW_TICKET."?project_id=".$ProjectID."&ticket_number=".$row['pticket_id'];
  $strError.="<meta http-equiv='refresh' content='$REFRESH_TIME_SEC;$newURL'>";


}
else
{
  $strError.="<BR>ERROR: Ticket selected can not be merged into choosen target ticket.";
}



    /*
    // child to add
    $x=$_FORM['addticket'];
    $strSQL ="SELECT * FROM $TABLE_TICKET_RELATIONS";
    $strSQL.=" WHERE";
    $strSQL.=" (oticket_id='$TicketID' AND ticket_id='$x')";
    $strSQL.=" OR (ticket_id='$TicketID' AND oticket_id='$x')";
    //echo "Q1:<DIR>$strSQL</DIR>\n";
    $result= dbquery($strSQL);
    if($rowTR=mysql_fetch_array($result))
    {
      $strError.="ERROR: You can not add this relation because one already exists between these 2 tickets.";
    }
    else
    {
      $strSQL ="INSERT INTO $TABLE_TICKET_RELATIONS SET ";
      $strSQL.=" ticket_id='$TicketID'";
      $strSQL.=",oticket_id='$x'";
      $strSQL.=",relation='Parent'";
      //echo "Q2:<DIR>$strSQL</DIR>\n";
      $result= dbquery($strSQL);
    }
    */
  }
}

}

// here is where you would perform action if some is posted.
// if an error occurs, just update $strError.="..."


if(!$strError)
{

$headerMSG="";
$SQLlogic="";
if(!strcmp($strAction,"merge"))
{
  // we want to assign other tickets as children to this ticket.
  //echo "making children<BR>\n";
  $headerMSG="Merging ticket $PTicketID into another ticket.";
  //$SQLlogic=" (A.ticket_id='$TicketID' AND A.relation='Parent')";
}


$found=0;
$rowC=0;
$BODY.="<form action='$PHP_SELF' method=POST>";
$BODY.="<input type=hidden name='project_id' value='$ProjectID'>";
$BODY.="<input type=hidden name='ticket_number' value='$TicketNum'>";
$BODY.="<input type=hidden name='action' value='$strAction'>";

$BODY.="<table cellpadding=2 cellspacing=0 class=warn>";
$BODY.="<tr class=rowh><td align=center>TICKET MERGING - # ";
$BODY.="<a class='header' href='$PAGE_VIEW_TICKET?ticket_number=$TicketNum&project_id=$ProjectID'>";
$BODY.="$TicketNum";
$BODY.="</a>";
$BODY.="</td></tr>";
$BODY.="<tr>";
$BODY.="<td valign=top>"; // start of page RIGHT COLUMN

$BODY.="<table cellpadding=2 cellspacing=0 class=wrap2>";
$BODY.="<tr class=rowh><td align=center>Select Ticket to Merge Into</td></tr>\n";
$BODY.="<tr><td>";
$BODY.="<select name='mergeticket' size=15 class=forms>";
// make a possibly HUGE select list of all tickets that this ticket can be merged into.
// owner must have manipulate access to BOTH projects
// order by PROJECT and then by ticket ID
// state can not be final. ticket can not be ourself.
$strSQL ="SELECT";
$strSQL.=" B.ticket_id, B.pticket_id, C.project_abbr";
$strSQL.=" ,B.cache";
$strSQL.=" FROM";
$strSQL.=" ($TABLE_PROJECT_ACCESS AS A";
$strSQL.=", $TABLE_EACH_TICKET AS B";
$strSQL.=", $TABLE_PROJECTS AS C";
$strSQL.=", $TABLE_STATES AS D)";
$strSQL.=" WHERE";
$strSQL.=" A.user_id='".$CURRENT_USER['ID']."'";
$strSQL.=" AND A.level<='".$PROJECT_ACCESS['manipulate']."'";
$strSQL.=" AND B.project_id=A.project_id";
$strSQL.=" AND B.project_id=C.project_id";
$strSQL.=" AND B.state_id=D.state_id"; // not closed
$strSQL.=" AND D.final<>'1'"; // not closed
$strSQL.=" AND B.ticket_id<>'$TicketID'"; // not this ticket
$strSQL.=" AND B.eticket_id is NULL"; // make sure the ticket we merge into is not already merged.
$strSQL.=" ORDER BY C.project_abbr, B.pticket_id";
//echo "Q:<DIR>$strSQL</DIR>";
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
$BODY.="<input class=form_button type=submit name='mergeinto' value='Merge Ticket'>";
$BODY.="</td></tr>\n";
$BODY.="</table>"; // end the inner right table

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
