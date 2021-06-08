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


  $strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
  //$strProjectID = validateText("Project ID",       $_FORM['txtProjectID'],    1, 11, TRUE, TRUE);
  if(!$strProjectID || $strProjectID==-1)
  {
    $strError.="<BR>You must specify a project to create a ticket.";
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
  # if txtModifyData exists [someone is attempting to modify a ticket]
  if(isset($_FORM['btnSubmit']) && is_array($_FORM['txtModifyData']))
  {
    /*
     * Validate all the posted data is correct.
     */
    reset($_FORM['txtModifyData']);
    while(list($name,$value)=each($_FORM['txtModifyData']))
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
	    /* html allowed - part 6=true */
            $y = validateText("$name", $value,  1, 10000, FALSE, TRUE);
            break;
          case "Person";
          case "Date";
	    /* no HTML allowed - param 6=false*/
            $y = validateText("$name", $value,  1, 60, FALSE, FALSE, FALSE);
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
          $strModifyData[$name]=$y;
        }
      }
      else
      {
	/* check to see if it was a Comment */
        if(strcmp("Comment",$name))
	{
          $strError="ERROR: Posted Data ($name) is not a valid ITEM for this project.<BR>\n";
	}
	else
	{
	  /* HTML is allowed for comments */
          $y = validateText("$name", $value,  1, 10000, FALSE, TRUE);
	  $strModifyData[$name]=$y;
	}
      }
      //echo "Posted: $value - changed to $y<BR>\n";
    } /* end posted data validation */
/*
 * look to see if all manditory fields have been provided according to
 * the state transition rules for this project to the initial state
 */
  if(1) // if it is a state change only!
  {
    $strTicketID  = validateNumber("Ticket ID", $_FORM['txtTicketID'], 1, 1000000, FALSE);
    $strState     = validateText("State", $_FORM['txtState'],  1, 10000, FALSE, FALSE);
    $strSQL = "SELECT B.value, C.label";
    $strSQL.= " FROM";
    $strSQL.= " ($TABLE_STATE_TRANSITIONS AS A";
    $strSQL.= ",$TABLE_STATE_RULES AS B";
    $strSQL.= ",$TABLE_ITEM_TO_PROJECT AS C";
    $strSQL.= ",$TABLE_EACH_TICKET AS D";
    $strSQL.= ",$TABLE_STATES AS E";
    $strSQL.= ",$TABLE_STATES AS F)";
    $strSQL.= " WHERE";
    $strSQL.= "     A.stran_id=B.stran_id";
    $strSQL.= " AND B.project_id='$strProjectID'";
    $strSQL.= " AND B.rule_id=C.rule_id";
    $strSQL.= " AND D.state_id=A.from_state_id"; // current state FROM
    $strSQL.= " AND D.ticket_id='$strTicketID'";
    $strSQL.= " AND E.state_id=D.state_id"; //for FROM state
    $strSQL.= " AND E.project_id=B.project_id";
    $strSQL.= " AND A.to_state_id=F.state_id"; // for TO state
    $strSQL.= " AND F.project_id='$strProjectID'";
    $strSQL.= " AND F.name='$strState'";
    //echo "Q-vl:<DIR>$strSQL</DIR>";
    $result= dbquery($strSQL);
    $start="ERROR: Manditory fields not set:<BR><list>";
    $end="";
    while($row= mysql_fetch_array($result))
    {
      $strV=$row['value'];
      $strL=$row['label'];
      $msg=$strV?"\"$strV\"":"any value";
      $x=$strModifyData[$strL];
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

      $strTicketID  = validateNumber("Ticket ID", $_FORM['txtTicketID'], 1, 1000000, FALSE);
      $strPTicketID = validateNumber("Project Ticket ID", $_FORM['txtPTicketID'], 1, 1000000, FALSE);
      ## get the ticket id we are updating/modifying
      //$strTicketID =$_FORM['txtTicketID'];
      //$strPTicketID=$_FORM['txtPTicketID'];

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
      /* empty out the old cache first */
      while(preg_match("/ITEM:(.*):METI/",$strCode,$match))
      {
        $x=$match[1];
	$newCache.="<$x>-</$x>|"; /* a dash for a non data entry field */
	//echo "X:<DIR>$x</DIR>\n";
	/* does not matter if this Perl or not replace method - just removing stuff */
        $strCode=preg_replace("/ITEM:($x):METI/","-Not-Set-",$strCode);
	//echo "strCode:<DIR>$strCode</DIR>\n";
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
	//echo "V:$strValue:-:\n";
	$strValue=ereg_replace("<","&lt;",$strValue);
	$strValue=ereg_replace(">","&gt;",$strValue);
	//echo "$strValue:-:\n";
	//echo "V:<DIR>\n$strValue\n</DIR>";
        if(!strcmp($row3C['type'],"Date"))
        {
	//echo "DATE:1:$strValue:$strLabel:<BR>\n";
          $strValue=printDate($strValue);
	//echo "DATE:2:$strValue:$strLabel:<BR>\n";
        }
	//echo "newV:<DIR>$strValue</DIR>\n";
        //$newCache=preg_replace("/<$strLabel>(.*)<\/".$strLabel.">/","<$strLabel>$strValue</$strLabel>",$newCache);
	
        //$strLabel2=ereg_replace("<","&lt;",$strLabel);
        //$strLabel2=ereg_replace(">","&gt;",$strLabel2);
        $newCache=ereg_replace("<$strLabel>(.*)<\/".$strLabel.">","<$strLabel>$strValue</$strLabel>",$newCache);
	//echo "cache:<DIR>$newCache</DIR>";
      }
      //echo "newCache(1):<DIR>$newCache</DIR>\n";
      // now we have the cache udpated for what it was before we posted any data.
      

      reset($strModifyData);
      while(list($name,$value)=each($strModifyData))
      {
        //echo "looking: $name $value<BR>\n";
        /* start - look for comment */
	if(!strcmp($name,"Comment"))
	{
	  if(strlen($value)>1)
	  {
            $readValue=convertDataToReadable($name,$value,$name);
            recordTransaction($strTicketID,"$name<$name>","$readValue");
	  }
	}
        /* end - look for comment */
	else
	{

        $strSQL4 = "SELECT B.type,A.label,A.rule_id";
	$strSQL4.= " FROM ($TABLE_ITEM_TO_PROJECT AS A";
	$strSQL4.= ", $TABLE_ITEM_TYPE AS B)";
	$strSQL4.= " WHERE A.project_id='$strProjectID' AND A.label='$name'";
	$strSQL4.= " AND A.type_id=B.type_id";
        $result4 = dbquery($strSQL4);
        if($row4= mysql_fetch_array($result4))
        {
          $strRuleID=$row4['rule_id'];
          $strLabel =$row4['label'];
          $strType  =$row4['type'];
        }
        $readValue=convertDataToReadable($strType,$value,$name);

        $strSQL4B = "SELECT value";
	$strSQL4B.= " FROM $TABLE_TICKET_ITEMS";
        $strSQL4B.= " WHERE ticket_id='$strTicketID' AND rule_id='$strRuleID'";
        $result4B = dbquery($strSQL4B);
        if($row4B= mysql_fetch_array($result4B))
        {
          $strValue=$row4B['value'];
	  $toCompare=$strValue;
	  if(!strcmp($strType,"Date"))
	  {
	    $toCompare=printDate($strValue);
	  }
	  //echo "Comparing[1] $strLabel:$strType--\"".$toCompare."\" to \"".stripslashes($value)."\"<BR>\n";
	  if(strcmp($toCompare, stripslashes($value)))
	  {
	    //$value=stripslashes($value); $value1=addslashes(htmlspecialchars($value));
	    //echo "-- UPDATE<BR>\n";
	    //echo "readValue:<DIR>$readValue</DIR>\n";
	    //echo "addslashes(readValue):<DIR>".addslashes($readValue)."</DIR>\n";
            $strSQL5 = "UPDATE $TABLE_TICKET_ITEMS SET";
            $strSQL5.= " value='$readValue'";
            $strSQL5.= " WHERE ticket_id='$strTicketID' AND rule_id='$strRuleID'";
            $result5 = dbquery($strSQL5);
	    //echo "Q-UPDATE:<DIR>$strSQL5</DIR>\n";
	    //echo "  -- $strLabel - $strType - $readValue<BR>\n";
	    //echo "---- commented out recording transaction.<BR>\n";
            if(!strcmp($strType,"Date")) { $readValue=printDate($readValue); }
            recordTransaction($strTicketID,"$strLabel<$strType>","$readValue");
            $strError.=notify($strProjectID,"rule_id",$strLabel,$strTicketID);
	  }
	  else
	  {
            if(!strcmp($strType,"Date")) { $readValue=printDate($readValue); }
	  }
          //$newCache=preg_replace("/<$strLabel>(.*)<\/$strLabel>/","<$strLabel>$readValue</$strLabel>",$newCache);
	  $readValue2=ereg_replace("<","&lt;",$readValue);
	  $readValue2=ereg_replace(">","&gt;",$readValue2);
          //echo "newCache(4A):<DIR>$newCache</DIR>\n";
          $newCache=ereg_replace("<$strLabel>(.*)<\/$strLabel>","<$strLabel>$readValue2</$strLabel>",$newCache);
          //echo "newCache(4B):<DIR>$newCache</DIR>\n";
        }
	else
	{
	  // else we must insert it
	  //echo "Comparing[2] $strLabel:$strType--\"$toCompare\" to \"".stripslashes($value)."\"<BR>\n";
	  if(strcmp($strValue, stripslashes($value)))
	  {
	    //$value=stripslashes($value); $value1=addslashes(htmlspecialchars($value));
	    //echo "-- INSERT<BR>\n";
            $strSQL5 = "INSERT INTO $TABLE_TICKET_ITEMS SET";
            $strSQL5.= " value='$readValue'";
            $strSQL5.= ", ticket_id='$strTicketID'";
            $strSQL5.= ", rule_id='$strRuleID'";
            $result5 = dbquery($strSQL5);
	    //echo "  -- $strLabel - $strType - $readValue<BR>\n";
	    //echo "---- commented out recording transaction.<BR>\n";
            if(!strcmp($strType,"Date")) { $readValue=printDate($readValue); }
            recordTransaction($strTicketID,"$strLabel<$strType>","$readValue");
            $strError.=notify($strProjectID,"rule_id",$strLabel,$strTicketID);
	  }
	  else
	  {
            if(!strcmp($strType,"Date")) { $readValue=printDate($readValue); }
	  }
          //$newCache=preg_replace("/<$strLabel>(.*)<\/$strLabel>/","<$strLabel>$readValue</$strLabel>",$newCache);
	  $readValue2=ereg_replace("<","&lt;",$readValue);
	  $readValue2=ereg_replace(">","&gt;",$readValue2);
          //echo "newCache(3A):<DIR>$newCache</DIR>\n";
          $newCache=ereg_replace("<$strLabel>(.*)<\/$strLabel>","<$strLabel>$readValue2</$strLabel>",$newCache);
          //echo "newCache(3B):<DIR>$newCache</DIR>\n";
	}
	}
      }
      //echo "newCache(13):<DIR>$newCache</DIR>\n";
      // update the db-cache
      ////////////////////////////////////////////////////////////////////////////////
      //echo "newCache(2):<DIR>$newCache</DIR>\n";
      $strSQL5B = "UPDATE $TABLE_EACH_TICKET SET";
      $strSQL5B.= " cache='$newCache'";
      $strSQL5B.= " WHERE ticket_id='$strTicketID'";
      $result5B = dbquery($strSQL5B);



    /* start - look at state */
    /* better security */
    $strState = validateText("State", $_FORM['txtState'],  1, 10000, FALSE, FALSE);
    //$strState=$_FORM['txtState'];
    if($strState)
    {
      /* look to see if this state is valid, and a possible transition for this user
       * given permissions of this user.
       */
       $strSQLa = "SELECT D.name as dname, D.state_id as newStateID, level, D.final";
       $strSQLa.= " FROM ($TABLE_STATES AS A";
       $strSQLa.= ", $TABLE_STATE_TRANSITIONS AS B";
       $strSQLa.= ", $TABLE_EACH_TICKET AS C)";
       $strSQLa.= " LEFT JOIN $TABLE_STATES AS D ON D.state_id=B.to_state_id";
       $strSQLa.= " WHERE A.project_id='$strProjectID'";
       $strSQLa.= " AND A.project_id=C.project_id";
       $strSQLa.= " AND C.pticket_id='$strPTicketID'";
       $strSQLa.= " AND C.state_id=A.state_id";
       $strSQLa.= " AND A.state_id=B.from_state_id";
       $strSQLa.= " ORDER BY D.name";
       $resulta= dbquery($strSQLa);
       $found=0;
       while($rowa= mysql_fetch_array($resulta))
       {
         $stateName=$rowa['dname'];
         if(!strcmp($stateName,$strState))
         {
	   $found=1;
           $requestedState=$stateName;
	   $newStateID=$rowa['newStateID'];
	   $newStateFinal=$rowa['final'];
	 }
       }
       if($newStateFinal)
       {
         //echo "moving to a final state...<BR>\n";
  
         /* check to see if the ticket has any open children if moving this to a final state */
         $strSQLa1 = "SELECT C.final";
         $strSQLa1.= " FROM ($TABLE_TICKET_RELATIONS AS A";
         $strSQLa1.= ", $TABLE_EACH_TICKET AS B";
         $strSQLa1.= ", $TABLE_STATES AS C)";
         $strSQLa1.= " WHERE A.ticket_id='$strTicketID' AND A.relation='Parent'";
         $strSQLa1.= " AND A.oticket_id=B.ticket_id";
         $strSQLa1.= " AND B.state_id=C.state_id";
         $strSQLa1.= " AND C.final='0'";
         $strSQLa1.= "";
	 //echo "QQ:<DIR>$strSQLa1</DIR>\n";
         $resulta1= dbquery($strSQLa1);
         if($rowa= mysql_fetch_array($resulta1))
         {
           $strError.="ERROR: You can not close this ticket when you have children that are not open.<BR>";
	   $found=false;/* dont' let it happen */
         }
       }


       /* if the state is valid AND the state is a change */
       if($found)// && strcmp($requestedState, $currentState))
       {
         $strSQLb = "SELECT B.name as name, B.state_id as id";
         $strSQLb.= " FROM ($TABLE_EACH_TICKET AS A";
         $strSQLb.= ", $TABLE_STATES AS B)";
         $strSQLb.= " WHERE A.state_id=B.state_id";
         $strSQLb.= " AND A.ticket_id='$strTicketID'";
         $strSQLb.= " AND A.project_id='$strProjectID'";
         $resultb= dbquery($strSQLb); /* current state */
         $rowb=mysql_fetch_array($resultb);
         $currentState=$rowb['name'];
         $currentStateID=$rowb['id'];
	 if(strcmp($currentState,$strState))
	 {
           $strSQLc = "UPDATE $TABLE_EACH_TICKET SET";
           $strSQLc.= " state_id='$newStateID'";
           $strSQLc.= " WHERE ticket_id='$strTicketID'";
           $resultc = dbquery($strSQLc);
           recordTransaction($strTicketID,"STATE","$strState");
           $strError.=notify($strProjectID,"stran_id",$currentStateID."___".$newStateID,$strTicketID);
           $strError.="SUCCESS: ticket has been modified.<BR>";
	 }
       }
    }
      $newURL=$PAGE_VIEW_TICKET."?project_id=".$strProjectID."&ticket_number=".$strPTicketID;
      //echo "not refreshing<BR>";
      $strError.="<meta http-equiv='refresh' content='$REFRESH_TIME_SEC;$newURL'>";
      $strError.="<BR>You will be forwarded back to the ticket in just a moment.";
    }
    /* end -  look at state */
  } /* end of ticket modify action */
} /* submit action */
  writeHeader("Modify Ticket: Project $strProjectName");
  declareError(TRUE);

  print $BODY;

  print "<BR><BR>";
  writeFooter();
?>
