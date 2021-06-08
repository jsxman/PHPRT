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

/* a utility function */
$lastCB_RED=13;
$lastCB_GRN=11;
$lastCB_BLU=8;
$previousCB=array();
function base10toHex($i)
{
  switch($i)
  {
    case "10";
      $r="A";break;
    case "11";
      $r="B";break;
    case "12";
      $r="C";break;
    case "13";
      $r="D";break;
    case "14";
      $r="E";break;
    case "15";
      $r="F";break;
    default;
      $r=$i; break;
  }
  return $r;
}
function nextBG($item)
{
  global $previousCB;
  global $lastCB_RED;
  global $lastCB_GRN;
  global $lastCB_BLU;
  $CB_STEP=3;
  if(preg_match("/(\d+):(\d+):(\d+)/",$previousCB[$item],$m))
  {
    $RED=$m[1];
    $GRN=$m[2];
    $BLU=$m[3];
  }
  else
  {
    $previousCB[$item]=$lastCB_RED.":".$lastCB_GRN.":".$lastCB_BLU;
    $RED=$lastCB_RED;
    $GRN=$lastCB_GRN;
    $BLU=$lastCB_BLU;
    $lastCB_BLU=$lastCB_BLU+$CB_STEP;
    if($lastCB_BLU>15)
    {
      $lastCB_BLU=8;
      $lastCB_GRN=$lastCB_GRN+$CB_STEP;
      if($lastCB_GRN>15)
      {
        $lastCB_GRN=8;
        $lastCB_RED=$lastCB_RED+$CB_STEP;
        if($lastCB_RED>15)
        {
          $lastCB_RED=8;
        }
      }
    }
  }
  $ret="style='background-color:#".base10toHex($RED).base10toHex($GRN).base10toHex($BLU).";'";
  return $ret;
}

$PROJECTS_TO_VIEW=array();

$strProjectID = validateNumber("Project ID", $_FORM['txtProjectID'], 1, 1000000, FALSE);
//$strProjectID = validateText("Project ID",    $_FORM['txtProjectID'],    1, 11, FALSE, FALSE);
#see if a specific project has been specified
if($strProjectID && $strProjectID!=-1)
{
  # -> if so, see if this user can view tickets in this project
  $strLevel=$PROJECT_ACCESS['none'];
  $strSQL = "SELECT A.level, B.project_name";
  $strSQL.= " FROM ($TABLE_PROJECT_ACCESS AS A";
  $strSQL.= ", $TABLE_PROJECTS AS B)";
  $strSQL.= " WHERE A.user_id='".$CURRENT_USER['ID']."'";
  $strSQL.= " AND B.project_id='$strProjectID'";
  $strSQL.= " AND A.project_id='$strProjectID'";
  $result= dbquery($strSQL);
  if($row= mysql_fetch_array($result))
  {
    $strLevel=$row['level'];
    $strName =$row['project_name'];
  }
  if($strLevel<=$PROJECT_ACCESS['display'])
  {
    $PROJECTS_TO_VIEW[$strProjectID]=1;
    $PROJECT_NAMES[$strProjectID]=$strName;
    $_SESSION['LastProject']=$strProjectID;
  }
  else
  {
    $strError="ERROR: You do not have permission to view this project.<BR>\n";
  }
}
else
{
  #if no project specified, determine which projects this user can view
  $strSQL = "SELECT B.project_id, B.project_name, A.level";
  $strSQL.= " FROM ($TABLE_PROJECT_ACCESS AS A";
  $strSQL.= " , $TABLE_PROJECTS AS B)";
  $strSQL.= " WHERE A.project_id=B.project_id";
  $strSQL.= " AND A.user_id='".$CURRENT_USER['ID']."'";
  $result= dbquery($strSQL);
  $FOUND=0;
  while($row= mysql_fetch_array($result))
  {
    $FOUND=1;
    $strLevel=$row['level'];
    $strProjectName=$row['project_name']; // not currently used
    $strProjectID=$row['project_id'];
    $PROJECTS_TO_VIEW[$strProjectID]=1;
    $PROJECT_NAMES[$strProjectID]=$strProjectName;
  }
  if(!$FOUND)
  {
    $strError="ERROR: You do not have permission to view any project.<BR>\n";
  }
  unset($_SESSION['LastProject']);
}

if(!isset($_FORM['filter_state']) || !$_FORM['filter_State'])
{
  $_FORM['filter_State']="___active___";
}

$FILTER_MESSAGE="&nbsp;";
if (isset($_FORM['resetfilters']) && $_FORM['resetfilters'])
{
  // dump the SESSION filters
  unset($_SESSION['filters']);
}
else if(isset($_FORM['newfilters']) && $_FORM['newfilters'])
{
  $FILTER_MESSAGE="You have filting active.";
  // dump filters, then store new filters into SESSION
  $keep=array();
  reset($_FORM);
  while(list($name,$value)=each($_FORM))
  {
    if(preg_match("/^filter_(.*)/",$name,$match) && strlen($value))
    {
      $x=$match[1];
      $x=preg_replace("/_/"," ",$x); // undo the nasty that puts in the _ for all spaces
      $_FORM["filter_".$x]=$value;
      $keep[$x]=array();
      $keep[$x]['filter']=$value;
    }
  }
  reset($_FORM);
  while(list($name,$value)=each($_FORM))
  {
    if(preg_match("/^isnot_(.*)/",$name,$match))
    {
      $x=$match[1];
      if($keep[$x])
      {
        $keep[$x]['isnot']=$value;
      }
    }
  }
  reset($keep);
  $_SESSION['filters']=serialize($keep);
  while(list($name,$value)=each($keep))
  {
    $x1=$value['filter'];
    $x2=$value['isnot'];
  }
}
else
{
  // need to pull filters from the SESSION into the $_FORM object
  if(isset($_SESSION['filters']))
  {
    $FILTER_MESSAGE="You have filting active.";
    $restore=unserialize($_SESSION['filters']);
    while(list($name,$value)=each($restore))
    {
      $x1=$value['filter'];
      $x2=$value['isnot'];
      $x3="filter_".$name;
      $x4="isnot_".$name;
      $_FORM[$x3]=$x1;
      $_FORM[$x4]=$x2;
    }
  }
}

writeHeader();
declareError(TRUE);
?>
<BR>

<?

echo "\n<script>function ov(a,id) { document.getElementById(id).className=\"row\"+a; }</script>\n";
if(!$strError)
{
  $FOUND=0;

  if(isset($_FORM['colorby']) && $_FORM['colorby'])
  {
    $_SESSION['colorby']=serialize($_FORM['colorby']);
  }
  else if(!$_SESSION['colorby'])
  {
    $_SESSION['colorby']=serialize("Ticket"); /* default color by is ticket - alternate row */
  }
  $COLOR_BY=unserialize($_SESSION['colorby']);
  //echo "CB:$COLOR_BY<BR>\n";

  // work with the SESSION to save the nocolumn request
  if(isset($_FORM['resetnocolumns']) && $_FORM['resetnocolumns'])
  {
    unset($_SESSION['nocolumn']);
  }
  else if(isset($_FORM['nocolumn']) && $_FORM['nocolumn'])
  {
    $strNC1 = "|".validateText("NoColumns",    $_FORM['nocolumn'],    1, 250, FALSE, FALSE)."|";
    $strNC2 ="";
    if(isset($_SESSION['nocolumn']))
    {
      $strNC2=unserialize($_SESSION['nocolumn']);
    }
    $strNC3=preg_replace("/\|\|/","|",$strNC1.$strNC2);
    $_SESSION['nocolumn']=serialize($strNC3);
  }
  if(!isset($_SESSION['nocolumn']))
  {
    //$_SESSION['nocolumn']=0;
    $strNoColumn="";
  }
  else
  {
    $strNoColumn = unserialize($_SESSION['nocolumn']);
  }
  //$strNoColumn = "|State|Subject And|Category|"; // just for testing
  //echo "strNoColumn=$strNoColumn<BR>\n";

  // work with SESSION to save the sort order and restore it.
  if(isset($_FORM['resetorder']) && $_FORM['resetorder'])
  {
    unset($_SESSION['sorting']);
  }
  else if(isset($_FORM['order']) && $_FORM['order'])
  {
    $_SESSION['sorting']=serialize($_FORM['order']);
  }
  else if(isset($_SESSION['sorting']))
  {
    $_FORM['order']=unserialize($_SESSION['sorting']);
  }
  /* added for security */
  if(!isset($_FORM['order']))$_FORM['order']="";
  $strOrder = validateText("ORDER",    $_FORM['order'],    1, 250, FALSE, FALSE);
  //$order=$_FORM['order']; // format: HEADER1:ASC|HEADER2:DESC|HEADER3:ASC
  $order=$strOrder; // format: HEADER1:ASC|HEADER2:DESC|HEADER3:ASC
  //echo "ORDER:$order<BR>\n";
  $aryOrder=split("\|",$order); //split different orders
  $THE_ORDER=array();
  $preserve_order=array();

  $orderBy='';// this is used in the query to determine which item is used to sort.

  for($i=0;$i<count($aryOrder);$i++)
  {
    //echo "ARO:".$aryOrder[$i].":<BR>\n";
    if(strlen($aryOrder[$i])>2)
    {
      $aryOrder2=split(":",$aryOrder[$i]);
      //echo "A:0{".$aryOrder[0]."} 1{".$aryOrder2[1]."}<BR>\n";

      /* protect from security intrusion */
      //if(!preg_match("/^\w+$/",$aryOrder2[0],$match)) { $aryOrder2[0]="";}
      if(!preg_match("/^\w+$/",$aryOrder2[1],$match)) { $aryOrder2[1]="ASC";}
      //echo "B:0{".$aryOrder[0]."} 1{".$aryOrder2[1]."}<BR>\n";
      //echo "--".strcmp($aryOrder2[1],"ASC")."--".strcmp($aryOrder2[1],"DESC")."--<BR>\n";
      if(!strcmp($aryOrder2[1],"ASC") || !strcmp($aryOrder2[1],"DESC"))
      {
        // its okay
	//echo "-Y-";
      }
      else
      {
	//echo "-N[".$aryOrder[1]."]-";
        // something entered that was a NONO
        $aryOrder2[1]="ASC";
      }


      $THE_ORDER[$aryOrder2[0]]=$aryOrder2[1];
      $preserve_order[count($preserve_order)]=$aryOrder2[0];
      if(!$orderBy)$orderBy=$aryOrder2[0];
    }
  }
  // go through each project that this user is to see on this page.
  // above selected either the 1 project that was requested, or all
  // that this user has access to see [display or better access leve]
  // we just toggle the order
  //function nextOrder($i) { return $i?(strcmp($i,"ASC")?"ASC":"DESC"):"DESC"; }
  function nextOrder($i) { return $i?(strcmp($i,"DESC")?"DESC":"ASC"):"ASC"; }
  function toggleOrder($i)
  {
    global $preserve_order;
    global $THE_ORDER;
    if(strlen($THE_ORDER[$i])>2) 
    {
      $THE_ORDER[$i]=nextOrder($THE_ORDER[$i]);
    }
  }
  function URLOrder($i,$order)
  {
    global $preserve_order;
    global $THE_ORDER;
    $str="";
    $found=0;
    for($j=0;$j<count($preserve_order);$j++)
    {
      $key=$preserve_order[$j];
      $value=$THE_ORDER[$key];
      if(strlen($key)>2 && strlen($value)>2)
      {
        if(!strcmp($i,$key)) // if this one is in the list, toggle its value
	{
	  $value=$order?$order:nextOrder($value);
	  $found=1;
	}
        $str.="|$key:$value";
      }
    }
    // if it was not in the list -- add it as last item default ASC
    if(!$found)
    {
      if($order)
        $str.="|$i:".$order;
      else
        $str.="|$i:".nextOrder("");
    }
    return "order=".substr($str,1,strlen($str));
  }
  function MySQLOrder()
  {
    global $preserve_order;
    global $THE_ORDER;
    $str=" ORDER BY ";
    $comma="";
    $itemCount=0;
    for($i=0;$i<count($preserve_order);$i++)
    {
      $key=$preserve_order[$i];
      $value=$THE_ORDER[$key];
      if(strlen($key)>2 && strlen($value)>2)
      {
	if(!strcmp($key,"Ticket"))
	{
	  $str.=$comma." A.ticket_id ".$value;
	}
	else if(!strcmp($key,"State"))
	{
	  $str.=$comma." B.name ".$value;
	}
	else // it must be an item
	{
	  //$str.=$comma." D.value ".$value;
	  $str.=$comma." S".$itemCount." ".$value;
	  $itemCount++;
	}
      }
      $comma=",";
    }
    if(!strcmp($str," ORDER BY "))
    {
      $str.=" A.ticket_id";
    }
    return $str;
  }
  function MysqlSearch()
  {
    global $preserve_order;
    global $THE_ORDER;
    $str="";
    $itemCount=0;
    for($i=0;$i<count($preserve_order);$i++)
    {
      $key=$preserve_order[$i];
      $value=$THE_ORDER[$key];
      if(strlen($key)>2 && strlen($value)>2)
      {
	if(strcmp($key,"Ticket")&&strcmp($key,"State"))
	{
	  $str.= " , MAX(IF(F.label='$key',D.value,'')) AS S$itemCount ";
	  $itemCount++;
	}
      }
    }
    return $str;
  }
  function sortingByMessage($projectID,$projName)
  {
    $msg="";
    $str1="";
    global $preserve_order;
    global $THE_ORDER;
    global $PHP_SELF;
    global $FILTER_MESSAGE;
    global $strNoColumn;
    global $COLOR_BY;
    $strCB=$COLOR_BY=="Ticket"?"":"You have color coding on by \"$COLOR_BY\"";
    $comma="";
    for($i=0;$i<count($preserve_order);$i++)
    {
      $key=$preserve_order[$i];
      $value=$THE_ORDER[$key];
      if(strlen($key)>2 && strlen($value)>2)
      {
	$lookup=strcmp($value,"DESC")?"UP":"DOWN";
        $msg.=$comma."$key $lookup";
      }
      $comma=",";
    }
    $resetSort="<a class=tiny href='$PHP_SELF?view=View&txtProjectID=$projectID&resetorder=1'>Reset Sorting</a>";
    $resetFilter="";
    if(strcmp($FILTER_MESSAGE,"&nbsp;"))
      $resetFilter="<a class=tiny href='javascript:document.resetfilterform.submit()'>Reset Filtering</a>";
    if(strcmp($strNoColumn,"") || strcmp($msg,"") || strcmp($FILTER_MESSAGE,"&nbsp;") || strcmp($strCB,""))
    {
      $str1 ="<table class=warn width='90%'>";
      $rowspan=0;
      if($resetFilter)
      {
        $rowspan=1;if($msg)$rowspan++;if($strNoColumn)$rowspan++;if($strCB)$rowspan++;
        $str1.="<tr class=tiny>";
        $str1.="<td width='33%'>You have Filtering active</td>";
	$str1.="<td width='33%' align=center rowspan=$rowspan valign=middle class=header>Project $projName</td>";
        $str1.="<td width='33%' align=right>$resetFilter</td>";
        $str1.="</tr>";
      }
      if($strNoColumn)
      {
        $resetNoColumns="RESET NO COLUMNS";
        $resetNoColumns="<a class=tiny href='$PHP_SELF?view=View&txtProjectID=$projectID&resetnocolumns=1'>Restore All Columns</a>";
        $str1.="<tr class=tiny>";
        $str1.="<td width='33%'>You have columns removed.</td>";
	if(!$rowspan)
	{
	  $rowspan++;
	  if($msg) $rowspan++;if($strCB)$rowspan++;
	  $str1.="<td width='33%' align=center rowspan=$rowspan valign=middle class=header>Project $projName</td>";
	}
        $str1.="<td  width='33%' align=right>$resetNoColumns</td>";
        $str1.="</tr>";
      }
      if($msg)
      {
        $str1.="<tr class=tiny>";
        $str1.="<td width='33%'>You have sorting active</td>";
	if(!$rowspan)
	{
	  $rowspan++;
	  if($strCB)$rowspan++;
	  $str1.="<td width='33%' align=center rowspan=$rowspan valign=middle class=header>Project $projName</td>";
	}
        $str1.="<td  width='33%' align=right>$resetSort</td>";
        $str1.="</tr>";
      }
      if($strCB)
      {
        $str1.="<tr class=tiny>";
        $str1.="<td width='33%'>$strCB</td>";
	if(!$rowspan)
	{
	  $str1.="<td width='33%' align=center valign=middle class=header>Project $projName</td>";
	}
	$resetCB="<a class=tiny href='$PHP_SELF?view=View&txtProjectID=$projectID&colorby=Ticket'>Reset Color By</a>";
        $str1.="<td  width='33%' align=right>$resetCB</td>";
        $str1.="</tr>";
      }
      $str1.="</table>";
    }
    else
    {
      $str1 ="<table class=warn width='90%'>";
      $str1.="<tr class=tiny>";
      $str1.="<td align=center class=header>Project $projName</td>";
      $str1.="</tr>";
      $str1.="</table>";
    }
    return $str1;
  }
  function NumberOfOrder($i,$o)
  {
    global $preserve_order;
    global $THE_ORDER;
    $str="";
    $place=0;
    if(isset($i) && isset($THE_ORDER[$i]))
    {
      if(!strcmp($THE_ORDER[$i],$o))
      {
        for($c=0;$c<count($preserve_order);$c++)
        {
          $key=$preserve_order[$c];
          $value=$THE_ORDER[$key];
          if(!strcmp($i,$key)&&!strcmp($o,$value))
          {
            $place=$c+1;
          }
        }
        if($place)
          $str.="<span class=tinyh>$place</span>";
      }
    }
    return $str;
  }
  function OrderArrow($l,$o)
  {
    $str="hollow";
    global $preserve_order;
    global $THE_ORDER;
    global $PHP_SELF;
    for($i=0;$i<count($preserve_order);$i++)
    {
      $key=$preserve_order[$i];
      $value=$THE_ORDER[$key];
      if(!strcmp($l,$key)&&!strcmp($o,$value))
      {
        $str="solid";
      }
    }
    return $str;
  }
  function FilterSearch($projID)
  {
    global $_FORM;
    global $TABLE_ITEM_TO_PROJECT;
    global $FILTER_COUNT;
    $str="";
    // get a list of all items in this project
    $strSQL= "SELECT A.label";
    $strSQL.=" FROM $TABLE_ITEM_TO_PROJECT AS A";
    $strSQL.=" WHERE A.project_id='$projID'";
    $result= dbquery($strSQL);
    while($row= mysql_fetch_array($result))
    {
      // go through each item and see if a filter data was posted for it.
      $label=$row['label'];
      // don't know why - but all spaces must get changed to underscores
      $label2=preg_replace("/ /","_",$label);
      $temp1="filter_".$label2;
      $temp2="isnot_".$label2;
      $temp3=isset($_FORM[$temp1])?$_FORM[$temp1]:0;
      $temp4=isset($_FORM[$temp2])?$_FORM[$temp2]:0;
      // if it was, then create the filter for it
      if($temp3)
      {
        //$str.= " , MAX(IF(F.label='$key',D.value,'')) AS F$FILTER_COUNT ";
        //$str.= " , MAX(IF('$label'=F.label AND D.value REGEXP \"$temp3\" ,1,0)) AS F$FILTER_COUNT ";
        //$str.= " , MAX(IF(D.value like '%$temp3%' ,1,0)) AS F$FILTER_COUNT ";
	if(!strcmp($temp4,"NOT"))
	{
	  // this is a NOT search
          $str.= " , MAX(IF(F.label='$label' AND D.value NOT REGEXP '$temp3' ,1,0)) AS F$FILTER_COUNT ";
	}
	else
	{
	  // check to see if it a > < = <= >= for MATH or DATE compare, if it is "" NULL
	  // then it is an IS search
	  if(!strcmp($temp4,""))
	  {
	    // this is an IS search
            $str.= " , MAX(IF(F.label='$label' AND D.value REGEXP '$temp3' ,1,0)) AS F$FILTER_COUNT ";
	  }
	  else
	  {
            $ways=array("E"=>"=", "NE"=>"<>", "GT"=>">", "LT"=>"<", "GTE"=>">=", "LTE"=>"<=");
            $temp5=$ways[$temp4];
	    //echo "MATH or DATE - how to know the difference?: $temp2:$temp4:$temp5<BR>";
            if(preg_match("/^\d\d\/\d\d\/\d\d\d\d$/",$temp3,$match))
	    {
	      $temp3=dbDate($temp3);
	      //echo "TEMP3:<HR>$temp3<HR>\n";
	    }
            $str.= " , MAX(IF(F.label='$label' AND D.value $temp5 '$temp3' ,1,0)) AS F$FILTER_COUNT ";
	  }
	}
        $FILTER_COUNT++;
      }
    }
    return $str;
  }
  function createFilterWhere($projID)
  {
    global $_FORM;
    global $TABLE_ITEM_TO_PROJECT;
    $str="";
    $filterState=$_FORM['filter_State'];
    if($filterState)
    {
      $ISNOT=isset($_FORM['isnot_State'])?$_FORM['isnot_State']:"";
      if(strcmp($ISNOT,"NOT")) { $sign="="; } else { $sign="<>"; }
      if(!strcmp($filterState,"___active___"))
      {
        if(strcmp($ISNOT,"NOT")) { $sign="<>"; } else { $sign="="; }
        // a filter for active requests
	$str.=" AND B.final $sign '1'";
      }
      else if(!strcmp($filterState,"___any___"))
      {
	//$str.=" AND B.final $sign '1'";
	// do nothing... we will then get all states...
      }
      else
      {
        $str.=" AND B.name $sign '$filterState' ";
      }
    }

    // look for filter_Owner
    $filterOwner=isset($_FORM['filter_Owner'])?$_FORM['filter_Owner']:0;
    if($filterOwner)
    {
      $ISNOT=$_FORM['isnot_Owner'];
      if($ISNOT)
      {
        $str.=" AND (U.first_name NOT LIKE '%$filterOwner%' AND U.last_name NOT LIKE '%$filterOwner%')";
      }
      else
      {
        $str.=" AND (U.first_name LIKE '%$filterOwner%' OR U.last_name LIKE '%$filterOwner%')";
      }
    }
    // end filter_Owner

    //------------ start finding any other filter items that were posted
    if(0) /* we do items in another place */
    {
      $strSQL = "SELECT label";
      $strSQL.= " FROM $TABLE_ITEM_TO_PROJECT";
      $strSQL.= " WHERE ";
      $strSQL.= " project_id='$projID'";
      $result= dbquery($strSQL);
      while($row= mysql_fetch_array($result))
      {
        $label=$row['label'];
        $temp1="filter_".$label;
        $temp2="isnot_".$label;
        $temp3=$_FORM[$temp1];
        $temp4=$_FORM[$temp2];
          if($_FORM[$temp1])
          {
	  // this only works for fields that are in the cache -displayed on the ticket list page
	  // we need a way to search the fields that are not displayed.
	  // like an OR to this field here - and if we do that we might as well not do this one
	  // here.
        }
      }
    }
    return $str;
  }
  echo call_user_func($sequence1);
  reset($PROJECTS_TO_VIEW);
  while(list($name,$value)=each($PROJECTS_TO_VIEW))
  {
    $ROW_COUNT=0;
    $NUMCOLS=0; // count how many columns we have here
    echo sortingByMessage($name,$PROJECT_NAMES[$name]);
    echo "<table class=wrap2 cellpadding=3 cellspacing=0 width='90%'>\n";
    echo "<tr class=rowh>";

    if(!preg_match("/\|(Ticket)\|/",$strNoColumn,$match))
    {
    $NUMCOLS++;
    echo "<td>";
    echo " <a title='Reset ColorBy' href='$PHP_SELF?view=View&txtProjectID=$name&colorby=Ticket'><img border=0 alt='Hide Column' src='images/lil_c.gif'></a> ";
    echo NumberOfOrder("Ticket","ASC");
    echo "<a title='UP' href='$PHP_SELF?view=View&txtProjectID=$name&".URLOrder("Ticket","ASC")."'>";
    echo "<img border=0 alt='UP' src='images/arrows/arrow_".OrderArrow("Ticket","ASC")."_up.gif'>";
    echo "</a>";
    echo "Ticket";
    echo "<a title='DOWN' href='$PHP_SELF?view=View&txtProjectID=$name&".URLOrder("Ticket","DESC")."'>";
    echo "<img border=0 alt='DOWN' src='images/arrows/arrow_".OrderArrow("Ticket","DESC")."_down.gif'>";
    echo "</a>";
    echo NumberOfOrder("Ticket","DESC");
    echo " <a title='Hide Column' href='$PHP_SELF?view=View&txtProjectID=$name&nocolumn=Ticket'><img border=0 alt='Hide Column' src='images/lil_x.gif'></a>";
    echo "</td>";
    }

    if(!preg_match("/\|(State)\|/",$strNoColumn,$match))
    {
    $NUMCOLS++;
    echo "<td>";
    echo " <a title='ColorBy State' href='$PHP_SELF?view=View&txtProjectID=$name&colorby=State'><img border=0 alt='Hide Column' src='images/lil_c.gif'></a> ";
    echo NumberOfOrder("State","ASC");
    echo "<a title='UP' href='$PHP_SELF?view=View&txtProjectID=$name&".URLOrder("State","ASC")."'>";
    echo "<img border=0 alt='UP' src='images/arrows/arrow_".OrderArrow("State","ASC")."_up.gif'>";
    echo "</a>";
    echo "State";
    echo "<a title='DOWN' href='$PHP_SELF?view=View&txtProjectID=$name&".URLOrder("State","DESC")."'>";
    echo "<img border=0 alt='DOWN' src='images/arrows/arrow_".OrderArrow("State","DESC")."_down.gif'>";
    echo "</a>";
    echo NumberOfOrder("State","DESC");
    echo " <a title='Hide Column' href='$PHP_SELF?view=View&txtProjectID=$name&nocolumn=State'><img border=0 alt='Hide Column' src='images/lil_x.gif'></a>";
    echo "</td>";
    }

    // grab the page template for the ticket listing for the specific project
    /* this will make the column headers */
    $strSQL3 = "SELECT code";
    $strSQL3.= " FROM $TABLE_PROJECT_TEMPLATES";
    $strSQL3.= " WHERE project_id='$name'";
    $strSQL3.= " AND page='Listing'";
    $result3= dbquery($strSQL3);
    $row3= mysql_fetch_array($result3);
    $strCode=$row3['code'];
    $hdCode=$strCode;
    $columnsColorBy=array();
    $columnsOrder=array(); $columnCount=0;
    while(preg_match("/ITEM:(.*):METI/",$hdCode,$match))
    {
      $NUMCOLS++;
      $x=$match[1];
      //$y ="<b><a href='$PHP_SELF?view=View&txtProjectID=$name&".URLOrder($x)."'>";
      $y=""; // start over each time
      $y.= " <a title='ColorBy $x' href='$PHP_SELF?view=View&txtProjectID=$name&colorby=$x'><img border=0 alt='Hide Column' src='images/lil_c.gif'></a> ";
      $y.= NumberOfOrder("$x","ASC");
      $y.= "<a title='UP' href='$PHP_SELF?view=View&txtProjectID=$name&".URLOrder("$x","ASC")."'>";
      $y.= "<img border=0 alt='UP' src='images/arrows/arrow_".OrderArrow("$x","ASC")."_up.gif'>";
      $y.= "</a>";
      $y.= "$x";
      $y.= "<a title='DOWN' href='$PHP_SELF?view=View&txtProjectID=$name&".URLOrder("$x","DESC")."'>";
      $y.= "<img border=0 alt='DOWN' src='images/arrows/arrow_".OrderArrow("$x","DESC")."_down.gif'>";
      $y.= "</a>";
      $y.= NumberOfOrder("$x","DESC");
      $y.= " <a title='Hide Column' href='$PHP_SELF?view=View&txtProjectID=$name&nocolumn=$x'><img border=0 alt='Hide Column' src='images/lil_x.gif'></a>";
      //$y.="</a></b>";
      if(preg_match("/\|($x)\|/",$strNoColumn,$match))
      {
        $y="";
        $columnsOrder[$columnCount]=$x; /* keep for later knowing which columnds to remove */
      }
      //echo "checking :$COLOR_BY:$x:<BR>\n";
      if(preg_match("/($x)/",$COLOR_BY,$match))
      {
        $columnsColorBy[$columnCount]=$x; /* keep for later knowing which columnds to remove */
	//echo "CB-Column:$columnCount<BR>\n";
      }
      $columnCount++;
      $hdCode=preg_replace("/ITEM:$x:METI/","$y",$hdCode);
    }
    echo $hdCode;
    echo "</tr>";


    // get each ticket that is in this specific project.
    // currently it is hard-coded to NOT get any ticket that is in its final state
    // this filtering will be changed [PHPRT_14]
    $FOUND=0; // used to know if we can show any tickets for this project


    // in oder to have the order of the rows selectable,
    // this must be combined with the nested query below
    // that finds individually each value/name pair.
    $strSQL = "SELECT";
    $strSQL.= " A.ticket_id, A.project_id, A.pticket_id, A.eticket_id, A.state_id, A.owner_id";
    $strSQL.= " , A.cache as cache";
    $strSQL.= " , B.name as state";
    $strSQL.= " , C.project_abbr as abbr";
    $strSQL.= " , F.label, D.value"; // testing only
    $strSQL.= MysqlSearch();
    $FILTER_COUNT=0; $strSQL.= FilterSearch($name);
    $strSQL.= " FROM ($TABLE_EACH_TICKET AS A";
    $strSQL.= ", $TABLE_STATES AS B";
    $strSQL.= ", $TABLE_PROJECTS AS C";
    $strSQL.= ", $TABLE_USERS AS U)";
    $strSQL.= " LEFT JOIN $TABLE_TICKET_ITEMS AS D ON D.ticket_id=A.ticket_id";
    $strSQL.= " LEFT JOIN $TABLE_ITEM_TO_PROJECT AS F ON F.rule_id=D.rule_id";
    $strSQL.= " LEFT JOIN $TABLE_ITEM_TYPE AS E ON E.type_id=F.type_id";
    $strSQL.= " WHERE A.project_id='$name'";
    $strSQL.= " AND A.eticket_id is NULL"; // do not show merged tickets 
    $strSQL.= " AND A.owner_id=U.user_id";
    $strSQL.= " AND B.state_id=A.state_id";
    $strSQL.= " AND C.project_id=A.project_id";
    $strSQL.= createFilterWhere($name); //filter state only
    $strSQL.= " GROUP BY A.ticket_id";
    $strSQL.= MySQLOrder();
    //echo "DEBUG:Q:<DIR>$strSQL</DIR>\n";
    $result= dbquery($strSQL);

    /*
     * go through each ticktet 
     */
    while($row= mysql_fetch_array($result))
    {
      // if we have filtering enabled, check for only rows with filter flag set
      $FILTER_OUT=0;
      $NUMBER_MATCHED=0;
      if($FILTER_COUNT>0)
      {
        for($c=0;$c<$FILTER_COUNT;$c++)
	{
	  //if(!$row["F$c"])$FILTER_OUT=1;
	  if($row["F$c"])
	  {
	    //echo "F$c matched=[".$row["F$c"]."]<BR>";
	    $NUMBER_MATCHED++;
	  }
	}
	if(strcmp($_FORM['filterandor'],"AND"))
	{
	  // its an OR
	  //  -- if none were matched, then filter this out
	  if(!$NUMBER_MATCHED)$FILTER_OUT=1;
	}
	else
	{
	  // its an AND
	  //  -- if the number of filters requested was matched then its okay
	  if($FILTER_COUNT<>$NUMBER_MATCHED)$FILTER_OUT=1;
	}
      }
      if(!$FILTER_OUT)
      {
      $FOUND=1; // we found at least one ticket in this project
      $strTicketID   =$row['ticket_id'];
      $strProjectID  =$row['project_id'];
      $strPTicketID  =$row['pticket_id'];
      $strETicketID  =$row['eticket_id'];
      $strStateID    =$row['state_id'];
      $strOwnerID    =$row['owner_id'];
      $strProjectAbbr=$row['abbr'];
      $strState      =$row['state'];
      //$strValue      =$row['value'];
      //$strType       =$row['type'];
      //$strLabel      =$row['label'];
      $strCache      =$row['cache'];
      // turn the cache into HTML for the ticket listing.
      // since for now we have no prefs for table order, use the order they are in the data
      $strLine="";
      if(strlen($strCache)>1)
      {
        $aryCache=split("\|",$strCache);
	$counter=0;
        for($i=0;$i<count($aryCache);$i++)
        {
          if(preg_match("/>(.*)</",$aryCache[$i],$match))
	  {
	    $x=$match[1];
            //if(!preg_match("/\|($x)\|/",$strNoColumn,$match))
            /* mar: may-31-2007 changed to check if exists */
	    //WAS:if(!$columnsOrder[$counter])
	    if(!isset($columnsOrder[$counter]))
              $strLine.= "<td>$x</td>";
	    else
              $strLine.= "<td></td>";
	    /* mar: may-31-2007 changed to check if exists */
	    //WAS:if($columnsColorBy[$counter])
	    if(isset($columnsColorBy[$counter]))
	      $colorByValue=$x;
	  }
	  $counter++;
        }
      }

      $row_over='ov';
      if(!isset($r))$r=0;
      if(!isset($temp_row))$temp_row=0;
        $temp_row++;
        $r=$r%2;$r++;
	if(!strcmp($COLOR_BY,"State"))
	  $temp1BG=$strState;
	else
	  $temp1BG=isset($colorByValue)?$colorByValue:"";
        $temp2BG="";
	if(strcmp($COLOR_BY,"Ticket"))
	  $temp2BG=nextBG($temp1BG);
        echo "<tr $temp2BG id='row$temp_row' onmouseover='ov(\"$row_over\",\"row$temp_row\")' onmouseout='ov($r,\"row$temp_row\")' class=row$r onclick='document.location=\"view_ticket.php?project_id=$strProjectID&ticket_number=$strPTicketID\"'>";
        if(!preg_match("/\|(Ticket)\|/",$strNoColumn,$match))
          echo "<td><a href='view_ticket.php?project_id=$strProjectID&ticket_number=$strPTicketID'>".$strProjectAbbr."_$strPTicketID</a></td>";
        if(!preg_match("/\|(State)\|/",$strNoColumn,$match))
          echo "<td>$strState</td>";
      echo $strLine."</tr>\n";
      $ROW_COUNT++;
      }
    }
    if(!$FOUND)
    {
    }
    echo "<tr><td class=tiny align=left colspan=$NUMCOLS>$ROW_COUNT rows displayed.</td></tr>\n";
    echo "</table><BR><BR>\n";
    $FOUND=0; // reset for the next project
  }


function isNot($v,$t)
{
  global $_FORM;
  $str="";
  $v1=preg_replace("/ /","_",$v);
  $x1="isnot_".$v1;
  $x2=isset($_FORM[$x1])?$_FORM[$x1]:"";
  $str.="<select name='isnot_$v' class=forms-filter>";
  switch($t)
  {
    case "Summing";
    case "Float";
    case "Integer";
    case "Date";
      $ways=array("E","NE","GT","LT","GTE","LTE");
      $ways2=array("E"=>"=", "NE"=>"<>", "GT"=>">", "LT"=>"<", "GTE"=>">=", "LTE"=>"<=");
      for($i=0;$i<count($ways);$i++)
      {
        if(strcmp($x2,$ways[$i])) { $ck=""; }
	else { $ck=" SELECTED "; }
        $str.="<option $ck value='".$ways[$i]."'>$v ".$ways2[$ways[$i]]."</option>";
      }
      break;
    case "BigText":
    case "Text":
    case "Link";
    case "Person";
    case "Enum";
    case "Choice";
      $abc_t1=isset($_FORM["isnot_$v"])?$_FORM["isnot_$v"]:0;
      $ck1=$abc_t1?"":" SELECTED ";
      $ck2=$abc_t1?" SELECTED ":"";
      $str.="<option $ck1 value=''>$v is</option>";
      $str.="<option $ck2 value='NOT'>$v is not</option>";
      break;
    default:
      $str="ERROR:ITEM TYPE[$t] not defined.";
      break;
  }
  $str.="</select>";
  return $str;
}
function filterState()
{
  global $TABLE_STATES;
  global $PROJECTS_TO_VIEW;
  global $_FORM;
  $str="";
  // if its a state - list the special all active item
    $v="State";
    $str.="<select name='filter_$v' class=forms-filter>";
    $temp1=$_FORM["filter_State"];
    $cked=strcmp($temp1,"___any___")?"":" SELECTED ";
    $str.="<option $cked value='___any___'>Any</option>";
    $cked=strcmp($temp1,"___active___")?"":" SELECTED ";
    $str.="<option $cked value='___active___'>Active</option>";
    // go get all possible choices
    $strSQL = "SELECT *";
    $strSQL.= " FROM $TABLE_STATES";
    // for each project we have listed on this page
    $strSQL.= " WHERE ";
    $OR="";
    reset($PROJECTS_TO_VIEW);
    while(list($projID,$value)=each($PROJECTS_TO_VIEW))
    {
      $strSQL.= $OR." project_id='$projID'";
      $OR=" OR ";
    }
    $strSQL.=" ORDER BY name";
    $result= dbquery($strSQL);
    while($row= mysql_fetch_array($result))
    {
      $x=$row['name'];
      $cked=strcmp($temp1,"$x")?"":" SELECTED ";
      $str.="<option $cked value='$x'>$x</option>";
    }
    $str.="</select>";
  return $str;
}
function filterItem($i,$t)
{
  global $_FORM;
  global $TABLE_ITEM_ENUMS;
  global $TABLE_ITEM_TYPE;
  global $TABLE_ITEM_TO_PROJECT;
  global $CALENDAR_SET;
  $temp1=isset($_FORM["filter_".$i])?$_FORM["filter_".$i]:"";
  $y="";
  switch($t)
  {
    case "Summing";
    case "Float";
    case "Integer";
    case "BigText":
    case "Text":
    case "Link";
    case "Person";
      $y ="<input size=8 class=forms-filter type=text value='$temp1' name='filter_$i'>";
      break;
    case "Enum";
    case "Choice";
      $strQ ="SELECT ";
      $strQ.=" A.value";
      $strQ.=" FROM";
      $strQ.=" ($TABLE_ITEM_ENUMS AS A";
      $strQ.=", $TABLE_ITEM_TYPE AS B";
      $strQ.=", $TABLE_ITEM_TO_PROJECT AS C)";
      $strQ.=" WHERE";
      $strQ.=" A.rule_id=C.rule_id";
      $strQ.=" AND B.type_id=C.type_id";
      $strQ.=" AND C.label='$i'";
      $strQ.=" AND B.type='$t'";
      $strQ.=" ORDER BY A.the_order";
      $result= dbquery($strQ);
      $i2=preg_replace("/ /","_",$i);
      $y="<select class=forms-filter name='filter_$i2'>";
      $y.="<option value=''></option>";
      while($row= mysql_fetch_array($result))
      {
        $x=$row['value'];
	$ck="";
	if(!strcmp($temp1,$x))$ck=" SELECTED ";
        $y.="<option $ck value='$x'>$x</option>";
      }
      $y.="</select>";
      break;
    case "Date";
      $y ="";
//---------- start
if(!isset($CALENDAR_SET))$CALENDAR_SET=0;
$CALENDAR_SET++;
$y.="<input size=8 value='$temp1' class=forms type='text' name='filter_$i' id='date_input_$CALENDAR_SET' readonly='1'>\n";
$y.="<img src='date/img.gif' id='date_trigger_$CALENDAR_SET' style='cursor: pointer; border: 1px solid red;' title='Choose Date' onmouseover='this.style.background=\"red\";' onmouseout='this.style.background=\"\"'>\n";
//---------- end
      break;
    default:
      $y="ERROR:ITEM[$dataLabel] defined in template does not have a valid type.";
      break;
  }
  return $y;
}

// put this here in case we will have a calendar-date in the filter box
    echo "<link rel='stylesheet' type='text/css' media='all' href='date/calendar.css'>\n";
    echo "<script type='text/javascript' src='date/calendar.js'></script>\n";
    echo "<script type='text/javascript' src='date/calendar-en.js'></script>\n";
    echo "<script type='text/javascript' src='date/calendar-setup.js'></script>\n";



    $HOW_WIDE=3; /* this must be 3 or more */
    $LOC=0;
    // show the filter box:
    echo "<form action='$PHP_SELF' method=POST>\n";
    echo "<table border=1 cellspacing=0 width=80% class=wrap2>\n";
    // stay with a project if a project has been choosen
    if(isset($_FORM['txtProjectID']) && $_FORM['txtProjectID'])
      echo "<input type=hidden name='txtProjectID' value='".$_FORM['txtProjectID']."'>\n";
    echo "<tr class=rowh><td align=center colspan=$HOW_WIDE>Ticket List Filter Box</td></tr>\n";
    //$LOC++;
    $classRow=0;
    echo "<tr class=row$classRow>";
    $classRow=($classRow+1)%2;
    echo "<td align=left>".isNot("State","Enum").filterState()."</td>\n"; $LOC++;
    echo "<td align=left>".isNot("Owner","Enum").filterItem('Owner','Person')."</td>\n"; $LOC++;
    $PROJECT_ITEMS=array();
    // query to find all items of projects listed on this page
    $strSQL = "SELECT A.label, B.type";
    $strSQL.= " FROM ($TABLE_ITEM_TO_PROJECT AS A";
    $strSQL.= " , $TABLE_ITEM_TYPE AS B)";
    // for each project we have listed on this page
    $strSQL.= " WHERE A.type_id=B.type_id AND (";
    $OR="";
    reset($PROJECTS_TO_VIEW);
    while(list($projID,$value)=each($PROJECTS_TO_VIEW))
    {
      $strSQL.= $OR." project_id='$projID'";
      $OR=" OR ";
    }
    $strSQL.=")";
    $strSQL.=" GROUP BY A.label";
    $strSQL.=" ORDER BY A.label";
    $result= dbquery($strSQL);
    while($row= mysql_fetch_array($result))
    {
      if(!$LOC)
      {
        echo "<tr class=row$classRow>";
	$classRow=($classRow+1)%2;
      }
      $label=$row['label'];
      $type=$row['type'];
      echo "<td>".isNot($label,$type).filterItem($label,$type)."</td>";
      $LOC=($LOC+1)%$HOW_WIDE;
      if(!$LOC){echo "</tr>\n";}
    }
    if($LOC)
    {
      for($i=$LOC;$i<$HOW_WIDE;$i++)
      {
        echo "<td>&nbsp;</td>";
      }
    }
    echo "</tr>\n";
    echo "<tr><td class=normal align=center colspan=$HOW_WIDE>Combined Logic Setting:";
    echo "<select class=forms name=filterandor>";
    $abc_temp1=isset($_FORM['filterandor'])?$_FORM['filterandor']:"";
    $ck1=strcmp($abc_temp1,"AND")?"":" SELECTED ";
    $ck2=strcmp($abc_temp1,"OR") ?"":" SELECTED ";
    echo "<option $ck1 value='AND'>AND</option>";
    echo "<option $ck2 value='OR'>OR</option>";
    echo "</select>";
    echo "</td></tr>";
    echo "<tr><td align=center colspan=$HOW_WIDE>";
    echo "<input name='newfilters' accesskey='s' type=submit class=form_button value='Filter Now'>";
    echo "<input name='resetfilters' type=button onclick='document.resetfilterform.submit();' class=form_button value='Reset Filters'>";
    echo "</td></tr>";
    echo "</table>\n";
for($counter=1;$counter<=$CALENDAR_SET;$counter++)
{
    echo "<script type='text/javascript'>\n";
    echo "Calendar.setup({\n";
    echo "inputField     :    'date_input_$counter',     // id of the input field\n";
    echo "ifFormat       :    '%m/%d/%Y',      // format of the input field\n";
    echo "button         :    'date_trigger_$counter',  // trigger for the (button ID)\n";
    echo "align          :    'B2',           // alignment (defaults to 'Bl')\n";
    echo "singleClick    :    true\n";
    echo "});\n";
    echo "</script>\n";
}

    echo "</form>";
    echo "<form action='$PHP_SELF' method=POST name=resetfilterform><input type=hidden name=resetfilters value=1></form>\n";



}
writeFooter();
?>
