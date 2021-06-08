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


  $strProjectID = validateNumber("Project ID", $_FORM['project_id'], 1, 1000000, TRUE);
  //$strProjectID = validateText("Project ID",    $_FORM['project_id'],    1, 11, TRUE, TRUE);
  $TicketNum = validateText("Ticket Number",    $_FORM['ticket_number'], 1, 11, TRUE, TRUE);
  $strAction = validateText("Relate Action",    $_FORM['action'], 1, 11, TRUE, TRUE);

  if(!$strProjectID || $strProjectID==-1 || !$strAction)
  {
    $strError.="<BR>You must specify a project and ticket in order to replicate a ticket.<BR>";
  }
  debug("Project [$strProjectID] specified.");


  /*
   * Determine if this user has access to modify tickets in this project:
   * $strProjectID
   *
   *  - Either user has access above manipulate, or
   *  - This project has the flag for anyone set to create a ticket [and this is a create ticket action]
   */
  if(!$strError)
  {
    $strSQL1 = "SELECT B.project_abbr, B.project_name, B.allowanyonecreate, A.level ";
    $strSQL1.= "FROM ($TABLE_PROJECT_ACCESS AS A, $TABLE_PROJECTS AS B )";
    $strSQL1.= "WHERE B.project_id='$strProjectID' ";
    $strSQL1.= "AND A.project_id='$strProjectID' ";
    $strSQL1.= "AND A.user_id='".$CURRENT_USER['ID']."'";
    $result1= dbquery($strSQL1);
    if($row1= mysql_fetch_array($result1))
    {
      $strProjectName = $row1['project_name'];
      $strProjectAbbr = $row1['project_abbr'];
      $strAnyone      = $row1['allowanyonecreate'];
      $strLevel       = $row1['level'];
      if($strLevel>$PROJECT_ACCESS['manipulate'] && !$strAnyone)
      {
        $strError="ERROR: You do not have permission to replicate a ticket in this project.";
      }
      else
      {
        $strSQL2 = "SELECT state_id,name FROM $TABLE_STATES WHERE project_id='$strProjectID' AND initial=1";
        $result2= dbquery($strSQL2);
        if($row2= mysql_fetch_array($result2)) {
          $strInitialState  =$row2['name'];
          $strInitialStateID=$row2['state_id'];
        }
        else
        {
	  // this is needed for spawn, but its good to give error regardless.
          $strError="ERROR: Your project does not have an initial state.";
        }
      }
    }
  } /* end check for permission to be here */

$BODY=""; /* clear the way */

/* query old ticket information */
$strSQL1 ="SELECT ET.ticket_id, ET.owner_id, ET.state_id, ET.cache, ET.pticket_id";
$strSQL1.=" ,S.name, S.initial, S.final";
$strSQL1.=" FROM";
$strSQL1.="   ($TABLE_EACH_TICKET AS ET";
$strSQL1.="  ,$TABLE_STATES AS S)";
$strSQL1.=" WHERE";
$strSQL1.="   ET.pticket_id='$TicketNum'";
$strSQL1.="   AND ET.project_id='$strProjectID'";
$strSQL1.="   AND S.state_id=ET.state_id";
$strSQL1.="";
$result1= dbquery($strSQL1);
//echo "Q:<DIR>$strSQL1</DIR>\n";
if($row1= mysql_fetch_array($result1))
{
 $strData['initial']=array();
 $strData['initial']['pticket_id']=$row1['pticket_id'];
 $strData['initial']['ticket_id'] =$row1['ticket_id'];
 $strData['initial']['owner_id']  =$row1['owner_id'];
 $strData['initial']['state_id']  =$row1['state_id'];
 $strData['initial']['cache']     =$row1['cache'];
 $strData['initial']['state_name']=$row1['name'];
 $strData['initial']['is_initial']=$row1['initial'];
 $strData['initial']['is_final']  =$row1['final'];

 $strSQL2 = "SELECT pticket_id FROM $TABLE_EACH_TICKET ";
 $strSQL2.= "WHERE project_id='$strProjectID' ORDER BY pticket_id DESC LIMIT 1";
 $result2= dbquery($strSQL2);
 if($row2= mysql_fetch_array($result2))
 {
  $strPTicketID=1+$row2['pticket_id']; ## just add one to the last one
 }
 else
 {
  $strPTicketID=1; ## none others exist, so this is the first
 }
}
else
{
  $strError.="ERROR: Can not find ticket you want to replicate.";
}


if(!$strError)
{
  if(!strcmp($strAction,"spawn")||!strcmp($strAction,"duplicate"))
  {


    # if we are to do a "spawn" or a "duplicate"...
    if(!strcmp($strAction,"spawn"))
    {
      // lets do a spawn
      //$BODY.="spawning<BR>";
      $newState=$strInitialStateID;
      //$BODY.=" - set state to $strInitialStateID [$strInitialState] for this project.<BR>";
    }
    else if(!strcmp($strAction,"duplicate"))
    {
      //$BODY.="duplicating<BR>";
      $newState=$strData['initial']['state_id'];
      //$BODY.=" - copy state of this ticket [$newState] to the new one.<BR>";
    }
    else
    {
      $strError.="ERROR: Not sure what your action is.";
    } /* end of spawn ticket modify action */

    // both are the same EXCEPT for STATE. SPAWN=initialState
    //$BODY.=" - create a new EACH_TICKET<BR>";
    //$BODY.=" -+ set project_id [$strProjectID]<BR>";
    //$BODY.=" -+ set pticket_id [$strPTicketID]<BR>";
    //$BODY.=" -+ set ownder_id [".$strData['initial']['owner_id']."]<BR>";
    //$BODY.=" -+ set cache<dir class=warn>[".$strData['initial']['cache']."]</dir><BR>";
    //$BODY.=" -+ new state [$newState]<BR>";
    //$BODY.=" -+ ** DO INSERT HERE **<BR>";
    //$BODY.=" -+ ** DO SELECT TO FIND TICKET_ID OF NEW TICKET HERE **<BR>";
    $strSQL1A ="INSERT INTO $TABLE_EACH_TICKET SET";
    $strSQL1A.="   project_id='$strProjectID'";
    $strSQL1A.="  ,pticket_id='$strPTicketID'";
    $strSQL1A.="  ,owner_id='".$strData['initial']['owner_id']."'";
    $strSQL1A.="  ,cache='".$strData['initial']['cache']."'";
    $strSQL1A.="  ,state_id='$newState'";
    $strSQL1A.="";
    //echo "Q-commented out:<DIR>$strSQL1A</DIR>\n";
    $result1A= dbquery($strSQL1A);


    // now find the ticket id that we just created
    $strSQL1B ="SELECT ticket_id";
    $strSQL1B.=" FROM";
    $strSQL1B.="   $TABLE_EACH_TICKET";
    $strSQL1B.=" WHERE";
    $strSQL1B.="  project_id='$strProjectID'";
    $strSQL1B.="  AND pticket_id='$strPTicketID'";
    $strSQL1B.="";
    $result1B= dbquery($strSQL1B);
    $row1B= mysql_fetch_array($result1B);
    $newTicketID=$row1B['ticket_id'];


    //$BODY.=" - copy all ITEMS<BR>";
    //$BODY.=" -+ for all ticket_ids of oldticket create new entry with new ticket_id<BR>";
    $strSQL2 ="SELECT rule_id, value";
    $strSQL2.=" FROM";
    $strSQL2.="   $TABLE_TICKET_ITEMS";
    $strSQL2.=" WHERE";
    $strSQL2.="   ticket_id='".$strData['initial']['ticket_id']."'";
    $strSQL2.="";
    $result2= dbquery($strSQL2);
    //echo "Q:<DIR>$strSQL2</DIR>\n";
    while($row2= mysql_fetch_array($result2))
    {
      $strSQL3 ="INSERT INTO $TABLE_TICKET_ITEMS SET";
      $strSQL3.="  ticket_id='$newTicketID'";
      $strSQL3.=" ,rule_id='".$row2['rule_id']."'";
      $strSQL3.=" ,value='".$row2['value']."'";
      $strSQL3.="";
      //$BODY.="commented out INSERT:<DIR>$strSQL3</DIR>";
      $result3= dbquery($strSQL3);
    }



    //$BODY.=" -+ <BR>";
    //$BODY.=" - copy all ticket relations<BR>";
    //$BODY.=" -+ for all ticket_ids of old ticket [ticket_id or oticket_id] create a new entry<BR>";
    //$BODY.=" -+ <BR>";
    //$BODY.=" - set these tickets are Related<BR>";
    //$BODY.=" -+ create a Related entry for this ticket and the new one<BR>";
    //$BODY.=" -+ <BR>";
    $strSQL4 ="SELECT ticket_id, oticket_id, relation";
    $strSQL4.=" FROM";
    $strSQL4.="   $TABLE_TICKET_RELATIONS";
    $strSQL4.=" WHERE";
    $strSQL4.="   ticket_id='".$strData['initial']['ticket_id']."'";
    $strSQL4.="   OR oticket_id='".$strData['initial']['ticket_id']."'";
    $strSQL4.="";
    $result4= dbquery($strSQL4);
    //echo "Q:<DIR>$strSQL4</DIR>\n";
    while($row4= mysql_fetch_array($result4))
    {
      //echo "1:".$row4['ticket_id']." -&gt; ".$row4['oticket_id']." AS ".$row4['relation']."<BR>\n";
      if($row4['ticket_id']==$strData['initial']['ticket_id'])
      {
        $row4['ticket_id']=$newTicketID;
      }
      if($row4['oticket_id']==$strData['initial']['ticket_id'])
      {
        $row4['oticket_id']=$newTicketID;
      }
      //echo "2:".$row4['ticket_id']." -&gt; ".$row4['oticket_id']." AS ".$row4['relation']."<BR>\n";
      $strSQL5 ="INSERT INTO $TABLE_TICKET_RELATIONS SET";
      $strSQL5.="  ticket_id='".$row4['ticket_id']."'";
      $strSQL5.=" ,oticket_id='".$row4['oticket_id']."'";
      $strSQL5.=" ,relation='".$row4['relation']."'";
      $strSQL5.="";
      //$BODY.="commented out INSERT:<DIR>$strSQL5</DIR>";
      $result5= dbquery($strSQL5);
    }
    // relate these to tickets:
    $strSQL6 ="INSERT INTO $TABLE_TICKET_RELATIONS SET";
    $strSQL6.="  ticket_id='".$strData['initial']['ticket_id']."'";
    $strSQL6.=" ,oticket_id='$newTicketID'";
    $strSQL6.=" ,relation='Related'";
    $strSQL6.="";
    //$BODY.="commented out INSERT:<DIR>$strSQL6</DIR>";
    $result6= dbquery($strSQL6);


    // now to do all the transactions:
    $strSQL7 ="SELECT *";
    $strSQL7.=" FROM";
    $strSQL7.="   $TABLE_TRANSACTIONS";
    $strSQL7.=" WHERE";
    $strSQL7.="   ticket_id='".$strData['initial']['ticket_id']."'";
    $strSQL7.="";
    $result7= dbquery($strSQL7);
    //echo "Q:<DIR>$strSQL7</DIR>\n";
    while($row7= mysql_fetch_array($result7))
    {
      $strSQL8 ="INSERT INTO $TABLE_TRANSACTIONS SET";
      $strSQL8.=" ticket_id='$newTicketID'";
      $strSQL8.=" ,eticket_id='$newTicketID'";
      $strSQL8.=" ,actor='".$row7['actor']."'";
      $strSQL8.=" ,type='".$row7['type']."'";
      $strSQL8.=" ,trans_data='".$row7['trans_data']."'";
      $strSQL8.=" ,trans_date='".$row7['trans_date']."'";
      $strSQL8.="";
      //$BODY.="commented out INSERT:<DIR>$strSQL8</DIR>";
      $result8= dbquery($strSQL8);
    }
    recordTransaction($strData['initial']['ticket_id'],"$strAction<from>",$strPTicketID);
    recordTransaction($newTicketID,"$strAction<to>",$strData['initial']['pticket_id']);
    $x=strcmp($strAction,"spawn")?"DUPLICATE":"SPAWN";
    $strError.=notify($strProjectID,"event_id",$x,$newTicketID);

    $newURL=$PAGE_VIEW_TICKET."?project_id=".$strProjectID."&ticket_number=".$strPTicketID;
    //$strError.="<meta http-equiv='refresh' content='$REFRESH_TIME_SEC;$newURL'>";
    $strError.="<BR>You will be forwarded to the new ticket in just a moment.";
  }
} /* submit action */

  writeHeader("Replicate Ticket: Project $strProjectName");
  declareError(TRUE);

  print $BODY;
  writeFooter();
?>
