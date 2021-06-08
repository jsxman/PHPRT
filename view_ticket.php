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

  //$ProjectID = validateText("Project ID",       $_FORM['project_id'],    1, 11, TRUE, TRUE);
  // this is the ticket# shown... 
  //$TicketNum = validateText("Ticket Number",    $_FORM['ticket_number'], 1, 11, TRUE, TRUE);

  /* these are numbers...*/
  $ProjectID = validateNumber("Project ID",   $_FORM['project_id'],    1, 1000000, TRUE);
  // this is the ticket# shown... 
  $TicketNum = validateNumber("Ticket Number",$_FORM['ticket_number'], 1, 1000000, TRUE);

$strSQL = "SELECT A.itar_flag, B.protected, A.restricted, A.not_restricted, A.switch_warning";
$strSQL.= " FROM $TABLE_DB_INFO AS A";
$strSQL.= " LEFT JOIN $TABLE_PROJECTS AS B ON B.project_id='$ProjectID'";
$strSQL.= " WHERE A.itar_flag='1'";
$result= dbquery($strSQL);
if($row= mysql_fetch_array($result))
{
 $itarFlag=$row['itar_flag'];
 $isProtected=$row['protected'];
 $msgMerge=$row['switch_warning'];
 $msgNR=$row['not_restricted'];
 $msgR=$row['restricted'];
}

  if(!$ProjectID || !$TicketNum)
  {
    $strError.="<BR>You must specify a project and a ticket.";
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

  $BODY =call_user_func($sequence1);

// found out what the real ticket id is
// also find out if this ticket has reached its final state - then we can not edit it!
    $strSQL1 = "SELECT A.eticket_id, A.ticket_id, B.final ";
    $strSQL1.= " FROM ($TABLE_EACH_TICKET AS A";
    $strSQL1.= " , $TABLE_STATES AS B)";
    $strSQL1.= " WHERE A.pticket_id='$TicketNum'";
    $strSQL1.= " AND A.project_id='$ProjectID'";
    $strSQL1.= " AND A.state_id=B.state_id";
    $result1= dbquery($strSQL1);
    if($row1= mysql_fetch_array($result1))
    {
      $ETicketID=$row1['eticket_id'];
      $TicketID=$row1['ticket_id'];
      $TicketIsClosed=$row1['final'];
      $CAN_EDIT=$TicketIsClosed?false:true;
    }
    else
    {
      $strError="ERROR: Ticket does not exist.";
    }

// if this Eticket is not ticket id, it was merged. dont show it. forward to the new one.
if($ETicketID && $ETicketID<>$TicketID)
{
  $strError.="NOTICE: The ticket you requested was merged into another ticket.";
    $strSQL1 = "SELECT A.project_id, A.pticket_id";
    $strSQL1.= " FROM $TABLE_EACH_TICKET AS A";
    $strSQL1.= " WHERE A.ticket_id='$ETicketID'";
    $result1= dbquery($strSQL1);
    if($row1= mysql_fetch_array($result1))
    {
      $newProjectID=$row1['project_id'];
      $newPTicketID=$row1['pticket_id'];
      $newURL=$PAGE_VIEW_TICKET."?project_id=".$newProjectID."&ticket_number=".$newPTicketID;
      $strError.="<meta http-equiv='refresh' content='$REFRESH_TIME_SEC;$newURL'>";
      $strError.="<BR>Forwarding you to that ticket now.";
    }
    else
    {
      $strError.="<BR>ERROR: Ticket it was merged into can not be found.";
    }
}


  // obtain the templates to view tickets in this project ['View' and 'History']
if(!$strError)
{

$strError="No Template Defined to View Ticket Information in this Project.";
$strCode="";
$strSQL1 = "SELECT code FROM $TABLE_PROJECT_TEMPLATES WHERE page='View' AND project_id='$ProjectID'";
$result1= dbquery($strSQL1);
if($row1= mysql_fetch_array($result1))
{
  $strError="";
  $strCode=$row1['code'];
  // find any PHP variables
  $count=0;
  while(preg_match("/PHP:(.*):PHP/",$strCode,$match))
  {
    $x=$match[1];
    $y=$$x;
    $strCode=preg_replace("/PHP:$x:PHP/","$y",$strCode);
    if($count++>500)exit; // to keep from an infinite loop
  }
  //echo "Ticket is closed=$TicketIsClosed<BR>\n";
  $CALENDAR_SET=0;// set to 0 -- if it gets set to 1 or more then we know to print out the date stuff later
  while(preg_match("/ITEM:(.*):METI/",$strCode,$match))
  {
    $x=$match[1];
    ## find the type that this item is of. warn/exit if not found

    //echo "X:$x<BR>\n";
    $strQ1 ="SELECT B.value as dvalue, D.value as isvalue, C.label as dlabel";
    $strQ1.=" FROM ($TABLE_ITEM_TO_PROJECT AS A";
    $strQ1.=" , $TABLE_ITEM_DEPENDENCY AS B";
    $strQ1.=" , $TABLE_ITEM_TO_PROJECT AS C)";
    $strQ1.=" LEFT JOIN $TABLE_TICKET_ITEMS AS D";
    $strQ1.="    ON C.rule_id=D.rule_id";
    $strQ1.="    AND D.ticket_id='$TicketID'"; /* if it exists */
    $strQ1.=" WHERE A.label='$x'";
    $strQ1.=" AND A.project_id='$ProjectID'";
    $strQ1.=" AND B.drule_id=A.rule_id";
    $strQ1.=" AND B.rule_id=C.rule_id";
    //echo "Q:<DIR>$strQ1</DIR>\n";
    $result1= dbquery($strQ1);
    $CAN_SHOW_IT=1; /* unless we prove otherwise */
    if($row1= mysql_fetch_array($result1))
    {
      $strDValue=$row1['dvalue'];
      $strIsValue=$row1['isvalue'];
      $strDLabel=$row1['dlabel'];
      //echo "Dependendy exists:[$x]-&gt;[$strDLabel] with dvalue of[$strDValue] and value is[$strIsValue]\n";
      if($strDValue)
      {
        if(!strcmp($strDValue,$strIsValue))
	{
	  //echo "-can show it-\n";
	}
	else
	{
	  //echo "-can NOT show it-\n";
	  $CAN_SHOW_IT=0;
	}
      }
      else
      {
        if($strIsValue)
	{
	  //echo "-can:2 show it-\n";
	}
	else
	{
	  //echo "-can:2 NOT show it-\n";
	  $CAN_SHOW_IT=0;
	}
      }
    }
    //echo "[$CAN_SHOW_IT]<BR>\n";

    if(!strcmp($x,"Owner"))
    {
      $strType="Owner";
      $strSQL2 = "SELECT last_name as ln, first_name as fn, username as un";
      $strSQL2.= " FROM ($TABLE_EACH_TICKET AS A";
      $strSQL2.= " , $TABLE_USERS AS B)";
      $strSQL2.= " WHERE A.ticket_id='$TicketID'";
      $strSQL2.= " AND A.owner_id=B.user_id";
      $result2= dbquery($strSQL2);
      if($row2= mysql_fetch_array($result2))
        $strValue=$row2['ln'].", ".$row2['fn']." [".$row2['un']."]";
      else
        $strValue="-None-";
    }
    else if(strcmp($x,"Comment"))
    {
      //echo "not a comment<BR>\n";
      // not a Comment
      $strSQL2 = "SELECT C.value AS value, B.type as type ";
      $strSQL2.= " FROM ($TABLE_ITEM_TO_PROJECT AS A";
      $strSQL2.= " , $TABLE_ITEM_TYPE AS B )";
      $strSQL2.= " LEFT JOIN $TABLE_TICKET_ITEMS AS C";
      $strSQL2.= " ON C.ticket_id='$TicketID' AND C.rule_id=A.rule_id ";
      $strSQL2.= " WHERE A.type_id=B.type_id";
      $strSQL2.= " AND A.label='$x' AND A.project_id='$ProjectID'";
      //echo "Q:<DIR>$strSQL2</DIR>\n";
      $result2= dbquery($strSQL2);
      if($row2= mysql_fetch_array($result2))
      {
        //$strValue=stripslashes($row2['value']);
        $strValue=$row2['value'];
	//echo "READ:<DIR>$strValue</DIR>";
        $strType =$row2['type'];
      }
    }
    else
    {
      // this is a comment
      //echo "is a comment<BR>\n";
      $strType="Comment";
      $strValue="";
    }
    //echo "TYPE:$strType:val:$strValue:<BR>\n";
    switch($strType)
    {
      case "Owner":
          $y=$strValue;
        break;
      case "Comment":
          if($CAN_EDIT)
            $y="<textarea class=forms rows=10 cols=80 name='txtModifyData[$x]'></textarea>";
          else
            $y=$strValue;
        break;
      case "BigText":
          if($CAN_EDIT)
            $y="<textarea class=forms rows=10 cols=80 name='txtModifyData[$x]'>$strValue</textarea>";
          else
            $y=$strValue;
        break;
      case "Text":
      case "Summing";
      case "Float";
      case "Integer";
      case "Link";
	  //echo "before:<DIR>$strValue</DIR>\n";
	  // this is here to enable a quote to appear in the text box
          $strValue=ereg_replace("\"","&quot;",$strValue);
	  //echo "after:<DIR>$strValue</DIR>\n";
          if($CAN_EDIT)
            $y="<input size=80 type=text name='txtModifyData[$x]' class=forms value=\"$strValue\">";
          else
            $y=$strValue;
        break;
      case "Person";
          if($CAN_EDIT)
          {
            $y ="<select class=forms name='txtModifyData[$x]'>\n";
            $strSQL3 = "SELECT * FROM ($TABLE_USERS AS A, $TABLE_PROJECT_ACCESS AS B) WHERE A.user_id=B.user_id AND B.project_id='$ProjectID'";
            $result3= dbquery($strSQL3);
            $temp=false;
            while($row3= mysql_fetch_array($result3))
            {
              $_U_I=$row3['user_id'];
              $_U_F=$row3['first_name'];
              $_U_L=$row3['last_name'];
              $_U_U=$row3['username'];
	      $t="$_U_L, $_U_F";
              $ck=strcmp($t,$strValue)?"":" selected ";
	      if($ck)$temp=true;
              //$y.="<option $ck value='$_U_I'>$_U_L, $_U_F [$_U_U]</option>\n";
              $y.="<option $ck value='$t'>$_U_L, $_U_F [$_U_U]</option>\n";
            }
            if(!$temp)$y.="<option selected value=''>-None-</option>\n";
	    else      $y.="<option value=''>-None-</option>\n";
            $y.="</select>\n";
          }
          else
            $y=$strValue;
        break;
      case "Enum";
          if($CAN_EDIT)
          {
            $y ="<select class=forms name='txtModifyData[$x]'>\n";
            $y.="<option value=''>-None-</option>\n";
            $strSQL3 = "SELECT * FROM ($TABLE_ITEM_ENUMS AS A";
	    $strSQL3.= ", $TABLE_ITEM_TO_PROJECT AS B)";
	    $strSQL3.= " WHERE A.rule_id=B.rule_id AND B.label like '$x' AND B.project_id='$ProjectID' ORDER BY A.the_order,A.value";
            $result3= dbquery($strSQL3);
            while($row3= mysql_fetch_array($result3))
            {
              $_R_I=$row3['rule_id'];
              $_R_F=$row3['value'];
              $ck=($_R_F==$strValue)?" selected ":"";
              $y.="<option $ck value='$_R_F'>$_R_F</option>\n";
            }
            $y.="</select>";
          }
          else
            $y=$strValue;
        break;
      case "Choice";
          if($CAN_EDIT)
          {
	    $y="";
	    $f=0;
            $strSQL3 = "SELECT * FROM ($TABLE_ITEM_ENUMS AS A";
	    $strSQL3.= ", $TABLE_ITEM_TO_PROJECT AS B)";
	    $strSQL3.= " WHERE A.rule_id=B.rule_id";
	    $strSQL3.= " AND B.label like '$x'";
	    $strSQL3.= " AND B.project_id='$ProjectID'";
	    $strSQL3.= " ORDER BY A.the_order,A.value";
            $result3= dbquery($strSQL3);
            while($row3= mysql_fetch_array($result3))
            {
              $_R_I=$row3['rule_id'];
              $_R_F=$row3['value'];
              $ck=($_R_F==$strValue)?" CHECKED ":"";
	      if(!strcmp($_R_F,$strValue))$f=1;
              $y.="<input $ck type=radio class=forms name='txtModifyData[$x]' value='$_R_F'>$_R_F \n";
            }
            $ck=(!$f)?" CHECKED ":"";
            $y.="<input $ck type=radio class=forms name='txtModifyData[$x]' value=''>-None- \n";
          }
          else
            $y=$strValue;
        break;
      case "Date";
	  $strValue=printDate($strValue);
	  $y="";
          if($CAN_EDIT && $CAN_SHOW_IT)
{
            //$y="[not defined yet'$strValue']$strType:$x";
//---------- start
$CALENDAR_SET++;
$y.="<input value='$strValue' class=forms type='text' name='txtModifyData[$x]' id='date_input_$CALENDAR_SET' readonly='1'>\n";
$y.="<img src='date/img.gif' id='date_trigger_$CALENDAR_SET' style='cursor: pointer; border: 1px solid red;' title='Choose Date' onmouseover='this.style.background=\"red\";' onmouseout='this.style.background=\"\"'>\n";
//---------- end
}
          else
            $y="[not defined yet'$strValue']$strType:$x";
        break;
      default:
        $y="ERROR:ITEM[$x] defined in template does not have a valid type.";
        break;
    }
    /* can not do a preg-replace as the perl syntax will replace $# {numbers} */
    if($CAN_SHOW_IT)
    {
      $strCode=ereg_replace("ITEM:$x:METI","$y",$strCode);
    }
    else
    {
      $strCode=ereg_replace("ITEM:$x:METI","-Not Applicable-",$strCode);
    }
  }
}

if(!$strError)
{
if($CALENDAR_SET)
{
$BODY.="<link rel='stylesheet' type='text/css' media='all' href='date/calendar.css'>\n";
$BODY.="<script type='text/javascript' src='date/calendar.js'></script>\n";
$BODY.="<script type='text/javascript' src='date/calendar-en.js'></script>\n";
$BODY.="<script type='text/javascript' src='date/calendar-setup.js'></script>\n";
}
$BODY.="<form class=forms name='form1' method='POST' action='$PAGE_MODIFY_TICKET'>";
$BODY.="<input type=hidden name='txtProjectID' value='$ProjectID'>";
$BODY.="<input type=hidden name='txtTicketID'  value='$TicketID'>";
$BODY.="<input type=hidden name='txtPTicketID' value='$TicketNum'>";


$BODY.="<br>";
$BODY.="$strCode";
for($counter=1;$counter<=$CALENDAR_SET;$counter++)
{
$BODY.="<script type='text/javascript'>\n";
$BODY.="Calendar.setup({\n";
$BODY.="inputField     :    'date_input_$counter',     // id of the input field\n";
$BODY.="ifFormat       :    '%m/%d/%Y',      // format of the input field\n";
$BODY.="button         :    'date_trigger_$counter',  // trigger for the calendar (button ID)\n";
$BODY.="align          :    'B2',           // alignment (defaults to 'Bl')\n";
$BODY.="singleClick    :    true\n";
$BODY.="});\n";
$BODY.="</script>\n";
}
$BODY.="<table><tr><td class=normal align=center>";
$strSQLa = "SELECT D.final, D.name as dname, level FROM ($TABLE_STATES AS A";
$strSQLa.= ", $TABLE_STATE_TRANSITIONS AS B";
$strSQLa.= ", $TABLE_EACH_TICKET AS C)";
$strSQLa.= " LEFT JOIN $TABLE_STATES AS D ON D.state_id=B.to_state_id";
$strSQLa.= " WHERE A.project_id='$ProjectID'";
$strSQLa.= " AND A.project_id=C.project_id";
$strSQLa.= " AND C.pticket_id='$TicketNum'";
$strSQLa.= " AND C.state_id=A.state_id";
$strSQLa.= " AND A.state_id=B.from_state_id";
$strSQLa.= " ORDER BY D.name";
$resulta= dbquery($strSQLa);
$strSQLb = "SELECT B.name as name";
$strSQLb.= " FROM ($TABLE_EACH_TICKET AS A";
$strSQLb.= ", $TABLE_STATES AS B)";
$strSQLb.= " WHERE A.state_id=B.state_id";
$strSQLb.= " AND A.pticket_id='$TicketNum'";
$strSQLb.= " AND A.project_id='$ProjectID'";
$resultb= dbquery($strSQLb);
$rowb=mysql_fetch_array($resultb);

if($CAN_EDIT)
{
$BODY.="State: <select class=forms name='txtState'>\n";
$BODY.="<option SELECTED value='".$rowb['name']."'>Keep: ".$rowb['name']."</option>\n";
while($rowa= mysql_fetch_array($resulta))
{
  if($rowa['level']>=$strLevel)
  {
    $extra="";if($rowa['final'])$extra="* ";
    $extra2=$rowa['dname'];if($rowa['final'])$extra2="* This will close this ticket. ";
    $BODY.="<option value='".$rowa['dname']."' title='$extra2'>".$extra.$rowa['dname']."</option>\n";
  }
}
$BODY.="</select>\n";
$BODY.="<input type=submit accesskey='s' name='btnSubmit' value='Update Ticket' class=form_button>";
}
else
{
$BODY.="State: \"".$rowb['name']."\" - <span class=warn>You can not edit this ticket.</span>";
}
$BODY.="</td></tr>";
$BODY.="</table>";
$BODY.="</form>";
}
}

/* start - ticket relations */
$BODY.="<BR>\n";
$BODY.= "<table class=wrap2 cellpadding=2 cellspacing=0 width='85%'>";
$BODY.= "<tr class=rowh><td colspan=2 align=center>Ticket Relations</td>";
/* find out who is related to this ticket */
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
$strSQL.= " (A.ticket_id='$TicketID' OR A.oticket_id='$TicketID')";
$strSQL.= " AND B.ticket_id=A.ticket_id";
$strSQL.= " AND B.state_id=C.state_id";
$strSQL.= " AND D.ticket_id=A.oticket_id";
$strSQL.= " AND D.state_id=E.state_id";
$strSQL.= " AND B.project_id=F.project_id";
$strSQL.= " AND D.project_id=G.project_id";
$strSQL.= " ORDER BY A.relation, A.ticket_id, A.oticket_id";
//echo "Q:<DIR>$strSQL</DIR>\n";
$result= dbquery($strSQL);
$prevRelation="";
$found=0;
$rowC=0;
while($rowTR=mysql_fetch_array($result))
{
  $rowC=($rowC+1)%2;
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
  // find out if the tickets are Related or Parent,
  if(strcmp($strRelation,"Related"))
  {
    // must be PARENT
    // then find out which left or right ticket is this ticket
    if($strData['left']['ticket_id']==$TicketID)
    {
      // we are the PARENT
      $strRelation="This ticket's Children";
    }
    else
    {
      // we are the CHILD
      $strRelation="This tickets' Parents";
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
    if($strData['right']['ticket_id']==$TicketID)
    {
      // we are related and we want to have our ticket be the LEFT ticket for processing below
      $strTemp=$strData['right'];
      $strData['right']=$strData['left'];
      $strData['left']=$strTemp;
    }
  }
  if($DEBUG)
  {
    reset($strData);
    while(list($name1,$value1)=each($strData))
    {
      echo "$name1:<DIR>";
      reset($value1);
      while(list($name2,$value2)=each($value1))
      {
        echo "$name2=\"$value2\",\n";
      }
      echo "</DIR>";
    }
  }


  if(strcmp($prevRelation,$strRelation))
  {
    // if not the first time here
    if($found)
    {
      $BODY.= "</td>";
      $BODY.= "</tr>\n";
    }
    // new relation
    $prevRelation=$strRelation;
    $BODY.= "<tr class=row$rowC>";
    $BODY.= "<td>$strRelation:</td>";
    $BODY.= "<td>";
    // we can link to this ticket if we have access to the project it is in
    $can_link_to=($strData['right']['access_level']<=$PROJECT_ACCESS['display'])?1:0;
    // if this ticket is closed we want to show a special messsage
    $finalChar=($strData['right']['is_final'])?"<span title='Ticket Closed' class=note>*</span>":"";
    $BODY.=$finalChar;
    if($can_link_to)
    {
      $BODY.= "<a href='$PHP_SELF";
      $BODY.= "?project_id=$ProjectID";
      $BODY.= "&ticket_number=".$strData['right']['pticket_id']."'";
      $BODY.= " title='".$strData['right']['state']."'>";
    }
    $BODY.= $strData['right']['proj_abbr']."_".$strData['right']['pticket_id'];
    if($can_link_to)
    {
      $BODY.= "</a>";
    }
  }
  else
  {
    // same relation as before
    $BODY.= ", ";
    // we can link to this ticket if we have access to the project it is in
    $can_link_to=($strData['right']['access_level']<=$PROJECT_ACCESS['display'])?1:0;
    // if this ticket is closed we want to show a special messsage
    $finalChar=($strData['right']['is_final'])?"<span title='Ticket Closed' class=note>*</span>":"";
    $BODY.=$finalChar;
    if($can_link_to)
    {
      $BODY.= "<a href='$PHP_SELF";
      $BODY.= "?project_id=$ProjectID";
      $BODY.= "&ticket_number=".$strData['right']['pticket_id']."'";
      $BODY.= " title='".$strData['right']['state']."'>";
    }
    $BODY.= $strData['right']['proj_abbr']."_".$strData['right']['pticket_id'];
    if($can_link_to)
    {
      $BODY.= "</a>";
    }
  }
  $found=1;
}
if($found)
{
  $BODY.= "</td>";
  $BODY.= "</tr>\n";
  $msg="";
}
else
{
  $msg="This ticket is not related to any other tickets";
  $BODY.= "<tr class=row0><td align=center colspan=2>$msg</td></tr>\n";
}
$BODY.= "<tr class=warn><td colspan=2 align=center class=normal>";
$BODY.= " Change: <input class=form_button type=button value='Children' onclick='relate(\"child\")'>";
$BODY.= " <input class=form_button type=button value='Parents' onclick='relate(\"parent\")'>";
$BODY.= " <input class=form_button type=button value='Related' onclick='relate(\"related\")'>";
$BODY.= "</td></tr>\n";
$BODY.= "";
$BODY.= "</tr></table>\n";
if(isset($msgMerge) && $msgMerge)
  $BODY.= "<table class=warn2 align=center><tr><td>$msgMerge</td></tr></table>\n";
$BODY.= "<table class=wrap2 cellpadding=2 cellspacing=0 width='85%'>";
$BODY.= "<tr class=rowh><td colspan=2 align=center>";
$BODY.= " <input class=form_button type=button value='Spawn' onclick='replicate(\"spawn\");'>";
// only non-closed tickets can be duplicated.
// and tickets that can be edited by this user
if(!$TicketIsClosed && $CAN_EDIT)
{
  $BODY.= " <input class=form_button type=button value='Duplicate' onclick='replicate(\"duplicate\")'>";
  $BODY.= " <input class=form_button type=button value='Merge' onclick='merge(\"merge\")'>";
}
$BODY.= "</td>";
$BODY.= "</tr></table>\n";
$BODY.= "
<script>
function merge(i)
{
  document.location=\"$PAGE_MERGE?action=\"+i+\"&ticket_number=$TicketNum&project_id=$ProjectID\";
}
function replicate(i)
{
  document.location=\"$PAGE_REPLICATE?action=\"+i+\"&ticket_number=$TicketNum&project_id=$ProjectID\";
}
function relate(i)
{
  document.location=\"$PAGE_RELATES?action=\"+i+\"&ticket_number=$TicketNum&project_id=$ProjectID\";
}
</script>
";
/* end   - ticket relations */


/// now for the 2nd template:
if(!$strError)
{

if(0) // removed the history template...
{
$strError="No Template Defined to View History of a Ticket in this Project.";
$strCode="";
$strSQL1 = "SELECT code FROM $TABLE_PROJECT_TEMPLATES WHERE page='History' AND project_id='$ProjectID'";
$result1= dbquery($strSQL1);
if($row1= mysql_fetch_array($result1))
{
  $strError="";
  $strCode=$row1['code'];
  while(preg_match("/ITEM:(.*):METI/",$strCode,$match))
  {
    $x=$match[1];
    ## find the type that this item is of. warn/exit if not found
    $strSQL2 = "SELECT B.type as type FROM ($TABLE_ITEM_TO_PROJECT AS A, $TABLE_ITEM_TYPE AS B) WHERE A.type_id=B.type_id AND A.label='$x' AND A.project_id='$ProjectID'";
    $result2= dbquery($strSQL2);
    if($row2= mysql_fetch_array($result2))
    {
      $strType=$row2['type'];
    }
    switch($strType)
    {
      case "Comment":
      case "BigText":
          $y="<textarea class=forms rows=10 cols=80 name='txtModifyData[$x]'></textarea>";
        break;
      case "Text":
      case "Summing";
      case "Float";
      case "Integer";
      case "Link";
        $y="<input size=80 type=text name='txtModifyData[$x]' class=forms>";
        break;
      case "Person";
        $y ="<select class=forms name='txtModifyData[$x]'>\n";
        $strSQL3 = "SELECT * FROM ($TABLE_USERS AS A, $TABLE_PROJECT_ACCESS AS B) WHERE A.user_id=B.user_id AND B.project_id='$ProjectID'";
        $result3= dbquery($strSQL3);
        while($row3= mysql_fetch_array($result3))
        {
          $_U_I=$row3['user_id'];
          $_U_F=$row3['first_name'];
          $_U_L=$row3['last_name'];
          $_U_U=$row3['username'];
          $ck=($_U_I==$CURRENT_USER['ID'])?" selected ":"";
          $y.="<option $ck value='$_U_I'>$_U_L, $_U_F [$_U_U]</option>\n";
        }
        $y.="</select>\n";
        break;
      case "Enum";
        $y ="<select class=forms name='txtModifyData[$x]'>\n";
        $strSQL3 = "SELECT * FROM ($TABLE_ITEM_ENUMS AS A, $TABLE_ITEM_TO_PROJECT AS B) WHERE A.rule_id=B.rule_id AND B.label like '$x' AND B.project_id='$ProjectID' ORDER BY A.the_order,A.value";
        $result3= dbquery($strSQL3);
        while($row3= mysql_fetch_array($result3))
        {
          $_R_I=$row3['rule_id'];
          $_R_F=$row3['value'];
          $y.="<option value='$_R_I'>$_R_F</option>";
        }
        $y.="</select>";
        break;
      case "Choice";
        $y ="<select class=forms name='txtModifyData[$x]'>\n";
        $strSQL3 = "SELECT *";
	$strSQL3.= " FROM ($TABLE_ITEM_ENUMS AS A";
	$strSQL3.= ", $TABLE_ITEM_TO_PROJECT AS B)";
	$strSQL3.= " WHERE A.rule_id=B.rule_id";
	$strSQL3.= " AND B.label like '$x'";
	$strSQL3.= " AND B.project_id='$ProjectID'";
	$strSQL3.= " ORDER BY A.the_order,A.value";
        $result3= dbquery($strSQL3);
        while($row3= mysql_fetch_array($result3))
        {
          $_R_I=$row3['rule_id'];
          $_R_F=$row3['value'];
          $y.="<option value='$_R_I'>$_R_F</option>";
        }
        $y.="</select>";
      case "Date";
        $y="[not defined yet]$strType:$x";
        break;
      default:
        $y="ERROR:ITEM[$x] defined in template does not have a valid type.";
        break;
    }
    $strCode=preg_replace("/ITEM:$x:METI/","$y",$strCode);
  }
}
}

  if(!$strError)
  {
    $BODY.="<br>";
    $BODY.=showTransactions($TicketID);
    /* disabled the bottom template for transactions for now -- why would it ever be used? */
    if(0 && $CAN_EDIT)
    {
      $BODY.="<BR><BR><table align=center  border='0' cellpadding='2'>";
      $BODY.="<tr><td>";
      $BODY.="$strCode";
      $BODY.="</td></tr>";
      $BODY.="<tr><td align=center><input type=submit accesskey='c' name='btnSubmit' value='Add Comment' class=form_button></td></tr>";
      $BODY.="</table>";
    }
  }
}


writeHeader();
declareError(TRUE);
?>
<BR>



<?

if(isset($itarFlag) && $itarFlag)
{
  echo "<table align=center class=warn2><tr><td>";
  if($isProtected)
    echo $msgR;
  else
    echo $msgNR;
  echo "</td></tr></table>\n";
}

if(!$strError)
{
  print $BODY;
}

writeFooter();
?>
<pre>
