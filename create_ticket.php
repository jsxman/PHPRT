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


  //$strProjectID = validateText("Project ID",       $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
  $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);

  if(!$strProjectID || $strProjectID==-1)
  {
    $strError.="<BR>You must specify a project to create a ticket.";
  }
  debug("Project [$strProjectID] specified.");
  $_SESSION['LastProject']=$strProjectID;

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
        $strError="ERROR: You do not have permission to create a ticket in this project.";
      }
      else
      {
        $strSQL2 = "SELECT state_id,name FROM $TABLE_STATES WHERE project_id='$strProjectID' AND initial=1";
        $result2= dbquery($strSQL2);
        if($row2= mysql_fetch_array($result2))
        {
          $strInitialState  =$row2['name'];
          $strInitialStateID=$row2['state_id'];
        }
        else
        {
          $strError="ERROR: Your project does not have an initial state.";
        }
      }
    }
  } /* end check for permission to be here */

if(!$strError)
{


  # if txtCreateData exists [someone is attempting to create a ticket]
  if(isset($_FORM['btnSubmit']) && is_array($_FORM['txtCreateData']))
  {
    /*
     * Validate all the posted data is correct.
     */
    reset($_FORM['txtCreateData']);
    while(list($name,$value)=each($_FORM['txtCreateData']))
    {
      $strSQL1 = "SELECT B.type as type ";
      $strSQL1.= "FROM ($TABLE_ITEM_TO_PROJECT AS A, $TABLE_ITEM_TYPE AS B )";
      $strSQL1.= "WHERE A.type_id=B.type_id ";
      $strSQL1.= "AND A.label='$name' ";
      $strSQL1.= "AND A.project_id='$strProjectID'";
      $result1= dbquery($strSQL1);
      if($row1= mysql_fetch_array($result1))
      {
        $strType=$row1['type'];
        switch($strType)
        {
          case "BigText":
          case "Text":
          case "Link";
          case "Enum";
          case "Choice";
	    /* allow HTML - parm 6- true */
            $y = validateText("$name", $value,  1, 10000, FALSE, TRUE);
            break;
          case "Date";
          case "Person";
	    /* do not allow HTML - parm 6 = false */
            $y = validateText("$name", $value,  1, 60, FALSE, FALSE);
            break;
          case "Float";
          case "Summing";
          case "Integer";
            $y = validateNumber("$name",    $value,  1, 10000000000, FALSE);
            break;
          default:
            $y="ERROR:ITEM[$name] defined in template does not have a valid type.";
            break;
        }
        if(!$strError)
        {
          $strCreateData[$name]=$y;
        }
      }
      else
      {
        $strError="ERROR: Posted Data is not a valid ITEM for this project.<BR>\n";
      }
    } /* end posted data validation */
/*
 * look to see if all manditory fields have been provided according to
 * the state transition rules for this project to the initial state
 */
  if(1)
  {
    $strSQL = "SELECT B.value, C.label";
    $strSQL.= " FROM";
    $strSQL.= " ($TABLE_STATE_TRANSITIONS AS A";
    $strSQL.= ",$TABLE_STATE_RULES AS B";
    $strSQL.= ",$TABLE_ITEM_TO_PROJECT AS C)";
    $strSQL.= " WHERE A.from_state_id IS NULL";
    $strSQL.= " AND A.to_state_id='$strInitialStateID'";
    $strSQL.= " AND A.stran_id=B.stran_id";
    $strSQL.= " AND B.project_id='$strProjectID'";
    $strSQL.= " AND B.rule_id=C.rule_id";
    //echo "Q-vl:<DIR>$strSQL</DIR>";
    $result= dbquery($strSQL);
    $start="ERROR: Manditory fields not set:<BR><list>";
    $end="";
    while($row= mysql_fetch_array($result))
    {
      $strV=$row['value'];
      $strL=$row['label'];
      $msg=$strV?"\"$strV\"":"any value";
      $x=$strCreateData[$strL];
      //echo "strV=[$strV], x=[$x], strL=[$strL]<BR>";
      if($strV)
      {
        echo "FORM-DATA=\"$x\"<BR>";
        if(strcmp($x,$strV))
        {
          $strError.=$start."<li>\"$strL\" must be set to $msg.<BR>\n";
          $start="";
	  $end="</list>";
        }
      }
      else
      {
        if(strlen($x)<1)
        {
          $strError.=$start."<li>\"$strL\" must be set to $msg.<BR>\n";
          $start="";
	  $end="</list>";
        }
      }
    }
    $strError.=$end;
  } /* end looking for manditory data required to create ticket */
    if(!$strError)
    {
      $strError=""; ## so we can append to this string as we go...

      ## determine the next Project Ticket # to use
      $strSQL3 = "SELECT pticket_id FROM $TABLE_EACH_TICKET ";
      $strSQL3.= "WHERE project_id='$strProjectID' ORDER BY pticket_id DESC LIMIT 1";
      $result3= dbquery($strSQL3);
      if($row3= mysql_fetch_array($result3))
      {
        $strPTicketID=1+$row3['pticket_id']; ## just add one to the last, greatest value found
      }
      else
      {
        $strPTicketID=1; ## none others exist, so this is the first
      }

      $strSQL4 = "INSERT INTO $TABLE_EACH_TICKET SET";
      $strSQL4.= " project_id='$strProjectID'";
      $strSQL4.= ", pticket_id='$strPTicketID'";
      $strSQL4.= ", state_id='$strInitialStateID'";
      $strSQL4.= ", owner_id='".$CURRENT_USER['ID']."'";
      $result4 = dbquery($strSQL4);
      $strError.= "Ticket ID <b><i>".$strProjectAbbr."_".$strPTicketID."</i></b> created.<BR>";
      $strError.="State=$strInitialState<BR>";

      $strSQL5 = "SELECT ticket_id FROM $TABLE_EACH_TICKET WHERE project_id='$strProjectID' AND pticket_id='$strPTicketID'";
      $result5 = dbquery($strSQL5);
      if($row5= mysql_fetch_array($result5))
      {
        $strTicketID=$row5['ticket_id'];
      }
      recordTransaction($strTicketID,"STATE",$strInitialState);
      $strError.=notify($strProjectID,"event_id","CREATE",$strTicketID);

      // get the template for the ticket listing page for this project.
      $strSQL3B = "SELECT code";
      $strSQL3B.= " FROM $TABLE_PROJECT_TEMPLATES";
      $strSQL3B.= " WHERE project_id='$strProjectID'";
      $strSQL3B.= " AND page='Listing'";
      $result3B = dbquery($strSQL3B);
      $row3B= mysql_fetch_array($result3B);
      $strCode =$row3B['code'];
      // update the cache for any update here
      // go through each item to replace in the $strCode and update
      // the cache:
      // <name>value</name>|<name2>value2</name2>...
      $newCache="";
      while(preg_match("/ITEM:(.*):METI/",$strCode,$match))
      {
        $x=$match[1];
        // if the Owner type is in the cache -- update it
	if(!strcmp($x,"Owner"))
	{
	  $ownerString=$CURRENT_USER['LAST_NAME'].", ".$CURRENT_USER['FIRST_NAME'];
          $newCache.="<$x>$ownerString</$x>|"; /* a dash for a non data entry field */
	}
	else
	{
          $newCache.="<$x>-</$x>|"; /* a dash for a non data entry field */
	}
	/* this can be perl verison as it is just emptying out the data */
        $strCode=preg_replace("/ITEM:($x):METI/","-Not-Set-",$strCode);
      }

      //echo "newCache(0):<DIR>$newCache</DIR>\n";
      $strSQL3C = "SELECT A.value, B.label, C.type";
      $strSQL3C.= " FROM ($TABLE_TICKET_ITEMS AS A";
      $strSQL3C.= " ,$TABLE_ITEM_TO_PROJECT AS B";
      $strSQL3C.= " ,$TABLE_ITEM_TYPE AS C)";
      $strSQL3C.= " WHERE ticket_id='$strTicketID'";
      $strSQL3C.= " AND B.project_id='$strProjectID'";
      $strSQL3C.= " AND B.rule_id=A.rule_id";
      $strSQL3C.= " AND C.type_id=B.type_id";
      $result3C = dbquery($strSQL3C);
      while($row3C= mysql_fetch_array($result3C))
      {
        $strValue=$row3C['value'];
        $strLabel=$row3C['label'];
        if(!strcmp($row3C['type'],"Date"))
        {
          $strValue=printDate($strValue);
        }
        //$newCache=preg_replace("/<$strLabel>(.*)<\/".$strLabel.">/","<$strLabel>$strValue</$strLabel>",$newCache);
        $newCache=ereg_replace("<$strLabel>(.*)<\/".$strLabel.">","<$strLabel>$strValue</$strLabel>",$newCache);
      }


reset($strCreateData); #MAR 2/2005
      while(list($name,$value)=each($strCreateData)) #MAR 2/2005
      {
        $strSQL4 = "SELECT A.label,A.rule_id,B.type";
	$strSQL4.= " FROM $TABLE_ITEM_TO_PROJECT AS A";
	$strSQL4.= " , $TABLE_ITEM_TYPE AS B";
	$strSQL4.= " WHERE A.project_id='$strProjectID'";
	$strSQL4.= " AND A.label='$name'";
	$strSQL4.= " AND A.type_id=B.type_id";
        $result4 = dbquery($strSQL4);
        if($row4= mysql_fetch_array($result4))
        {
          $strRuleID=$row4['rule_id'];
          $strLabel =$row4['label'];
          $strType  =$row4['type'];
        }
        $readValue=convertDataToReadable($strType,$value,$name);

        $value=addslashes($value);
        $strSQL5 = "INSERT INTO $TABLE_TICKET_ITEMS SET";
        $strSQL5.= " ticket_id='$strTicketID'";
        $strSQL5.= ", rule_id='$strRuleID'";
        $strSQL5.= ", value='$readValue'";
	if(!strcmp($strType,"Date")) { $readValue=printDate($readValue); }
        if($value)
	{
          recordTransaction($strTicketID,"$strLabel<$strType> on $strInitialState","$readValue");
          $strError.=notify($strProjectID,"rule_id",$strLabel,$strTicketID);
	}
        //$newCache=preg_replace("/<$strLabel>(.*)<\/$strLabel>/","<$strLabel>$readValue</$strLabel>",$newCache);
        $newCache=ereg_replace("<$strLabel>(.*)<\/$strLabel>","<$strLabel>$readValue</$strLabel>",$newCache);
        $result5 = dbquery($strSQL5);
      }
      $strSQL5B = "UPDATE $TABLE_EACH_TICKET SET";
      $strSQL5B.= " cache='$newCache'";
      $strSQL5B.= " WHERE ticket_id='$strTicketID'";
      $result5B = dbquery($strSQL5B);

      $newURL=$PAGE_VIEW_TICKET."?project_id=".$strProjectID."&ticket_number=".$strPTicketID;
      $strError.="<meta http-equiv='refresh' content='$REFRESH_TIME_SEC;$newURL'>";


      
    }
  } /* end of ticket create action */

/*
 * If we have not done anything yet, then we should create the form for
 * the user to enter data to make a new ticket.
 */
if(!$strError)
{
$strError="No Template Defined to Create Tickets in this Project.";
$strCode="";
$strSQL1 = "SELECT code FROM $TABLE_PROJECT_TEMPLATES WHERE page='Create' AND project_id='$strProjectID'";
$result1= dbquery($strSQL1);
if($row1= mysql_fetch_array($result1))
{
  $strError="";
  $strCode=$row1['code'];

  // count the number of date types on this page
  $CALENDAR_SET=0;  // if set to 1 or more later then we will know to print out the date stuff
  while(preg_match("/ITEM:(.*):METI/",$strCode,$match))
  {
    $x=$match[1];
    ## find the type that this item is of. warn/exit if not found
    $strSQL2 = "SELECT B.type as type, A.default_value";
    $strSQL2.= " FROM ($TABLE_ITEM_TO_PROJECT AS A";
    $strSQL2.= " , $TABLE_ITEM_TYPE AS B)";
    $strSQL2.= " WHERE A.type_id=B.type_id";
    $strSQL2.= " AND A.label='$x'";
    $strSQL2.= " AND A.project_id='$strProjectID'";
    $result2= dbquery($strSQL2);
    if($row2= mysql_fetch_array($result2))
    {
      $strType   =$row2['type'];
      $strDefault=$row2['default_value'];
    }
    switch($strType)
    {
      case "BigText":
          $y="<textarea class=forms rows=10 cols=80 name='txtCreateData[$x]'>$strDefault</textarea>";
        break;
      case "Text":
      case "Summing";
      case "Float";
      case "Integer";
      case "Link";
        $y="<input size=80 type=text name='txtCreateData[$x]' value='$strDefault' class=forms>";
        break;
      case "Person";
        $y ="<select class=forms name='txtCreateData[$x]'>\n";
        $ck=($strDefault)?" selected ":"";
        $y.="<option $ck value=''>-None-</option>\n";
        $strSQL3 = "SELECT *";
	$strSQL3.= " FROM ($TABLE_USERS AS A";
	$strSQL3.= " , $TABLE_PROJECT_ACCESS AS B)";
	$strSQL3.= " WHERE A.user_id=B.user_id";
	$strSQL3.= " AND B.project_id='$strProjectID'";
        $result3= dbquery($strSQL3);
        while($row3= mysql_fetch_array($result3))
        {
          $_U_I=$row3['user_id'];
          $_U_F=$row3['first_name'];
          $_U_L=$row3['last_name'];
          $_U_U=$row3['username'];
	  // this next one will default a person type to this person
          $ck=($strDefault && $_U_I==$CURRENT_USER['ID'])?" selected ":"";
          $y.="<option $ck value='$_U_I'>$_U_L, $_U_F [$_U_U]</option>\n";
        }
        $y.="</select>\n";
        break;
      case "Enum";
        $y ="<select class=forms name='txtCreateData[$x]'>";
        $ck=($strDefault)?" selected ":"";
        $y.="<option $ck value=''>-None-</option>\n";
        $strSQL3 = "SELECT *";
	$strSQL3.= " FROM ($TABLE_ITEM_ENUMS AS A";
	$strSQL3.= " , $TABLE_ITEM_TO_PROJECT AS B)";
	$strSQL3.= " WHERE A.rule_id=B.rule_id";
	$strSQL3.= " AND B.label like '$x'";
	$strSQL3.= " AND B.project_id='$strProjectID'";
	$strSQL3.= " ORDER BY A.the_order,A.value";
        $result3= dbquery($strSQL3);
        while($row3= mysql_fetch_array($result3))
        {
          $_R_I=$row3['rule_id'];
          $_R_F=$row3['value'];
          $ck=($strDefault && $_R_F==$strDefault)?" selected ":"";
          $y.="<option $ck value='$_R_F'>$_R_F</option>";
        }
        $y.="</select>";
        break;
      case "Choice";
	$y="";
        $strSQL3 = "SELECT *";
	$strSQL3.= " FROM ($TABLE_ITEM_ENUMS AS A";
	$strSQL3.= ", $TABLE_ITEM_TO_PROJECT AS B)";
	$strSQL3.= " WHERE A.rule_id=B.rule_id";
	$strSQL3.= " AND B.label like '$x'";
	$strSQL3.= " AND B.project_id='$strProjectID'";
	$strSQL3.= " ORDER BY A.the_order,A.value";
        $result3= dbquery($strSQL3);
        while($row3= mysql_fetch_array($result3))
        {
          $_R_I=$row3['rule_id'];
          $_R_F=$row3['value'];
          $ck=($strDefault && $_R_F==$strDefault)?" CHECKED ":"";
          $y.="<input $ck type=radio class=forms name='txtCreateData[$x]' value='$_R_F'>$_R_F \n";
        }
        $ck=(!$strDefault)?" CHECKED ":"";
        $y.="<input $ck type=radio class=forms name='txtCreateData[$x]' value=''>-None- \n";
        break;
      case "Date";
        $y="";
	$CALENDAR_SET++;
	//$today=($strDefault)?todayDate():"";
	//echo "today:$today:$strDefault;<BR>\n";
	$y.="<input value='$today' class=forms type='text' name='txtCreateData[$x]' id='date_input_$CALENDAR_SET' readonly='1'>\n";
	$y.="<img src='date/img.gif' id='date_trigger_$CALENDAR_SET' style='cursor: pointer; border: 1px solid red;' title='Choose Date' onmouseover='this.style.background=\"red\";' onmouseout='this.style.background=\"\"'>\n";
        break;
      default:
        $y="ERROR:ITEM[$x] defined in template does not have a valid type.";
        break;
    }
    //$strCode=preg_replace("/ITEM:$x:METI/","$y",$strCode);
    $strCode=ereg_replace("ITEM:$x:METI","$y",$strCode);
  }
} /* end draw template to create page */

if(!$strError)
{
  $BODY ="";
if($CALENDAR_SET)
{
$BODY.="<link rel='stylesheet' type='text/css' media='all' href='date/calendar.css'>\n";
$BODY.="<script type='text/javascript' src='date/calendar.js'></script>\n";
$BODY.="<script type='text/javascript' src='date/calendar-en.js'></script>\n";
$BODY.="<script type='text/javascript' src='date/calendar-setup.js'></script>\n";
}
  $BODY.="<form class=forms name='form1' method='POST' action='".$_SERVER['PHP_SELF']."'>";
  $BODY.="<input type=hidden name='txtProjectID' value='$strProjectID'>";
  $BODY.="<br>".call_user_func($sequence1);
  $BODY.="<table class=wrap border='0' cellpadding='2'>";
  $BODY.="<tr><td>";
  $BODY.="$strCode";
  $BODY.="</td></tr>";
  $BODY.="<tr><td align=center>";
  $BODY.="<input type=submit accesskey='s' name='btnSubmit' value='Create Ticket' class=form_button>";
  $BODY.="</td></tr>";
  $BODY.="</table>";
  $BODY.="</form>";
//if($CALENDAR_SET)
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

}
}
}
  writeHeader("Create Ticket: Project $strProjectName");
  declareError(TRUE);

  print $BODY;
  writeFooter();
?>
