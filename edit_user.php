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

  if ($_FORM['btnSubmit'])
  {
      if($DEBUG)print "Recieved Submit<BR>\n";
      $strUserID = validateNumber("User ID", $_FORM['txtUserID'], 1, 1000000, TRUE);
      //$strUserID       = validateText("User ID", $_FORM['txtUserID'], 1, 20, TRUE, FALSE);
      $strFirstName    = validateText("First Name", $_FORM['txtFirstName'], 2, 40, TRUE, FALSE);
      $strLastName     = validateText("Last Name", $_FORM['txtLastName'], 2, 40, TRUE, FALSE);
      $strEmail        = validateEmail("Email Address", $_FORM['txtEmail'], FALSE);
      $strPhone        = validateText("Phone Number", $_FORM['txtPhone'], 4, 25, TRUE, FALSE);
      $strPassword1    = validateText("Password1", $_FORM['txtPassword1'], 6, 20, FALSE, FALSE);
      $strPassword2    = validateText("Password2", $_FORM['txtPassword2'], 6, 20, FALSE, FALSE);
      $strFrameMode    = validateText("Frame Mode", $_FORM['txtFrameMode'], 1, 1, FALSE, FALSE);
      $strView         = validateText("View", $_FORM['txtView'], 1, 40, FALSE, FALSE);
      $strCorder       = validateText("Column Order", $_FORM['txtCorder'], 1, 40, FALSE, FALSE);
      $strRorder       = validateText("Row Order", $_FORM['txtRorder'], 1, 40, FALSE, FALSE);

      /* do not allow user guest to do anything on this page */
      if(!strcmp($CURRENT_USER['GUEST'],"1"))
      {
        $strError.="ERROR: User Guest can not change anything on this page.";
      }

      if ($strError == "")
      {
           if($DEBUG)print "No strError -- processing data...<BR>";
           if ($strUserID!=$_SESSION['userID'])
           {
                $strError = "You can only look at your own profile.";
           }
           else
           {


                $strSQL = "SELECT pswd_alpha FROM $TABLE_DB_INFO";
                $result = dbquery($strSQL);
                $row    = mysql_fetch_array($result);
                $strPassAlpha=$row['pswd_alpha'];
                if ($strPassword1 != $strPassword2)
                {
                     $strError = "When you change your password, you must have both fields match.<BR>Password update not performed.";
                }
		else if ($strPassAlpha && $strPassword1 &&
		         (!preg_match("/[\w]/",$strPassword1,$m1) ||
			 !preg_match("/[^\w]/",$strPassword1,$m2)))
		{
		  $strError.="ERROR: You must enter a password that contains both Alpha and non-Alpha characters.";
		}
                else
                {
                     if($DEBUG)print "Processing update request...<BR>\n";
                     $strSQL0 = "SELECT email FROM $TABLE_USERS WHERE user_id='".$_SESSION['userID']."'";
                     $result0 = dbquery($strSQL0);
                     $row0    = mysql_fetch_array($result0);
                     $strOldEmail=$row0['email'];

                     $strSQL1 = "UPDATE $TABLE_USERS";
                     $strSQL1.= " SET first_name='$strFirstName', last_name='$strLastName', email='$strEmail', phone='$strPhone', frame_mode='$strFrameMode'";
                     $strSQL1.= " , c_order='$strCorder', r_order='$strRorder'";
                     if($strPassword1)
                     {
                        $strSQL1.=" , password='".md5($strPassword1)."'";
                        $strSQL1.=" , passworddate='".date(U)."'";
                     }
                     $strSQL1.= " WHERE user_id='".$_SESSION['userID']."'";
                     $result1 = dbquery($strSQL1);
                     $strError = "Your account has been updated successfully.";

                     $msgBody = "From: ".makeHomeURL()."<BR>\n";
                     //$msgBody.= "Your Username: ".$row2['username']."<BR>\n";
                     //echo "U-".$CURRENT_USER['USERNAME']."-<BR>\n";
                     $msgBody.= "Your Username: ".$CURRENT_USER['USERNAME']."<BR>\n";
                     $msgBody.= "Your Name: $strFirstName $strLastName<BR>\n";
                     $msgBody.= "Your Email Address: $strEmail<BR>\n";
                     $msgBody.= "Your Phone number is: $strPhone<BR>\n";
                     if($strPassword1)
                     {
                       $msgBody.= "Your Password was changed to: $strPassword1<BR>\n";
                     }
		     /* these are not used */
                     //$msgBody.= "Your View is: $strView<BR>\n";
                     //$msgBody.= "Your Column Order is: $strCorder<BR>\n";
                     //$msgBody.= "Your Row Order is: $strRorder<BR>\n";
                     //$msgBody.= "Your Frame Mode is: $strFrameMode<BR>\n";
                     $msgBody.= "<BR><BR><BR>If you did not request this change, please notify $adminEmail.";
                     mail($strEmail,"PHP RT User Profile Edit: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);

                     if ($strEmail != $strOldEmail && $strOldEmail)
                     {
                         mail($strOldEmail, "PHP RT User Profile Edit: ".date("m-d-Y"), $msgBody, $MAIL_HEADER);
                     }
                }
           }
      }
  } 
  else
  {
    $strUserID = validateNumber("User ID", $_FORM['txtUserID'], 1, 1000000, FALSE);
    if($strUserID && $strUserID<>$_SESSION['userID'])
    {
      $strError.="ERROR: You are not looking at your stuff.";
      $NOT_ME=true;
    }
    else
    {
      $NOT_ME=false;
      $strUserID=$_SESSION['userID'];
    }
  }
  if(!$strError)
  {
$strOptIn  = validateNumber("OptIn", $_FORM['txtIn'], 1, 1000000, FALSE);
$strOptOut = validateNumber("OptIn", $_FORM['txtOut'], 1, 1000000, FALSE);
if($strOptOut)
{
  //echo "Opting out: $strOptOut<BR>";
  $strSQL = "SELECT * FROM $TABLE_PROJECT_DIST WHERE dist_id='$strOptOut'";
  $result = dbquery($strSQL);
  $row    = mysql_fetch_array($result);

  $strSQL = "INSERT $TABLE_PROJECT_DIST";
  $strSQL.= " SET";
  $strSQL.= "  project_id='".$row['project_id']."'";
  if($row['event_id'])
    $strSQL.= "  , event_id='".$row['event_id']."'";
  else
    $strSQL.= "  , event_id=NULL";
  $strSQL.= "  , user_id='".$CURRENT_USER['ID']."'";
  if($row['rule_id'])
    $strSQL.= "  , rule_id='".$row['rule_id']."'";
  else
    $strSQL.= "  , rule_id=NULL";
  if($row['stran_id'])
    $strSQL.= "  , stran_id='".$row['stran_id']."'";
  else
    $strSQL.= "  , stran_id=NULL";
  $strSQL.= " , role='NEVER'";
  //echo "Q2:<DIR>$strSQL</DIR>";
  $result = dbquery($strSQL);
}
else
{
  //echo "sIN:$strOptIn<BR>";
  $strSQL = "DELETE FROM $TABLE_PROJECT_DIST WHERE dist_id='$strOptIn' AND user_id='".$CURRENT_USER['ID']."'";
  $result = dbquery($strSQL);
}
  } // end of the opt-in and opt-out



// start person overriding
if(!$strError)
{
$strDproject = validateNumber("Dproject", $_FORM['txtDproject'], 1, 1000000, FALSE);
$strAddD     = validateNumber("addD",     $_FORM['addD'], 1, 1000000, FALSE);
$strDevent   = validateNumber("Devent",   $_FORM['txtDevent'], 1, 1000000, FALSE);
$strDitem    = validateNumber("Ditem",    $_FORM['txtDitem'], 1, 1000000, FALSE);
$strDstate   = validateNumber("Dstate",   $_FORM['txtDstate'], 1, 1000000, FALSE);
$strDemail   = validateNumber("Demail",   $_FORM['txtDemail'], 1, 1000000, FALSE);
  //echo "received:$strAddD:$strDproject:$strDevent:$strDitem:$strDstate:$strDemail<BR>";
// do we have access to this project?
$sQ2="SELECT level";
$sQ2.=" FROM $TABLE_PROJECT_ACCESS";
$sQ2.=" WHERE project_id='$strDproject'";
$sQ2.="   AND user_id='".$CURRENT_USER['ID']."'";
$sQ2.="   AND level<='".$PROJECT_ACCESS['display']."'";
$rs2= dbquery($sQ2);
if($rw2= mysql_fetch_array($rs2))
{
  if($strAddD)
  {
    if($strDevent)
    {
      if($strDemail)
      {
        echo "Adding event";
$sQ ="INSERT INTO $TABLE_PROJECT_DIST";
$sQ.=" SET project_id='$strDproject'";
$sQ.=" , event_id='$strDevent'";
$sQ.=" ,role='$strDemail'";
$sQ.=" ,user_id='".$CURRENT_USER['ID']."'";
$rs= dbquery($sQ);
      }
      else
      {
        $strError.="ERROR: You have to specify your role.";
      }
    }
    else if($strDitem)
    {
      if($strDemail)
      {
        echo "Adding item";
$sQ ="INSERT INTO $TABLE_PROJECT_DIST";
$sQ.=" SET project_id='$strDproject'";
$sQ.=" , rule_id='$strDitem'";
$sQ.=" ,role='$strDemail'";
$sQ.=" ,user_id='".$CURRENT_USER['ID']."'";
$rs= dbquery($sQ);
      }
      else
      {
        $strError.="ERROR: You have to specify your role.";
      }
    }
    else if($strDstate)
    {
      if($strDemail)
      {
        echo "Adding state";
$sQ ="INSERT INTO $TABLE_PROJECT_DIST";
$sQ.=" SET project_id='$strDproject'";
$sQ.=" , stran_id='$strDstate'";
$sQ.=" ,role='$strDemail'";
$sQ.=" ,user_id='".$CURRENT_USER['ID']."'";
$rs= dbquery($sQ);
      }
      else
      {
        $strError.="ERROR: You have to specify your role.";
      }
    }
    else
    {
      $strErr.="ERROR: You wanted to add what?";
    }
  }
  else
  {
    $strError.="ERROR: Project must be specified";
  }
}
}
// end person overriding



  $strSQL2 = "SELECT * FROM $TABLE_USERS WHERE user_id='$strUserID'";
  $result2= dbquery($strSQL2);
  $row2= mysql_fetch_array($result2);

  $strUsername       = $row2['username'];
  $strFirstName      = $row2['first_name'];
  $strLastName       = $row2['last_name'];
  $strEmail          = $row2['email'];
  $strPhone          = $row2['phone'];
  $strPassworddate   = $row2['passworddate'];
  $strFrameMode      = $row2['frame_mode']; if($strFrameMode)$SEL_YES="SELECTED";else $SEL_NO="SELECTED";
  $strView           = $row2['view'];
  $strCorder         = $row2['c_order'];
  $strRorder         = $row2['r_order'];
  $strAccountCreated = $row2['account_create_date'];
  $strDBAdmin        = $row2['db_admin'];
  $strSuccBadPass    = $row2['succ_badpass'];
  $strNumBadPass     = $row2['num_badpass'];
  $strNumLogouts     = $row2['num_logouts'];
  $strNumLogins      = $row2['num_logins'];
  $strLastBadPass    = $row2['last_badpass'];
  $strLastLogout     = $row2['last_logout'];
  $strLastLogin      = $row2['last_login'];

  if($NOT_ME)
  {
    writeHeader("Profile Page: $strLastName, $strFirstName");
  }
  else
  {
    writeHeader("Your Profile");
  }
  declareError(TRUE);
?>
<script>
function chkpas()
{
  ret=true;
  f=document.form1;
  p1=f.txtPassword1.value;
  p2=f.txtPassword2.value;
  if(p1!=p2){alert("Passwords must match.");ret=false;}
  return ret;
}
</script>
<form onsubmit='return chkpas()' class=forms name="form1" method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<input type=hidden name="txtUserID" value="<?=$_SESSION['userID'];?>">
  <br><table class=forms border='0' cellpadding='2'>
    <tr><td >Username:</td><td><?=$strUsername;?></td></tr>
<?
if($strDBAdmin)
{
print "   <tr><td >You are a Database Admin:</td><td>Yes</td></tr>";
}
?>
    <tr><td >Account Created:</td><td><?=printDate2($strAccountCreated);?></td></tr>
    <tr><td >Password Last Changed:</td><td><?=printDate2($strPassworddate);?></td></tr>
    <tr><td >Last Login:</td><td><?=printDate2($strLastLogin);?> - Total (<?=$strNumLogins;?>)</td></tr>
    <tr><td >Last Logout:</td><td><?=printDate2($strLastLogout);?> - Total (<?=$strNumLogouts;?>)</td></tr>
    <tr><td >Last Bad Password Attempt:</td><td><?=printDate2($strLastBadPass);?>  - Total (<?=$strSuccBadPass;?>/<?=$strNumBadPass;?>)</td></tr>
<?if(!$NOT_ME){?>
    <tr>
      <td >First Name:</td>
      <td ><input class=forms type="text" name="txtFirstName" value="<?echo $strFirstName;?>" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td >Last Name:</td>
      <td ><input class=forms type="text" name="txtLastName" value="<?echo $strLastName;?>" size="40" maxlength="40"></td>
    </tr>
    <tr>
      <td >Email:</td>
      <td ><input class=forms type="text" name="txtEmail" value="<?echo $strEmail;?>" size="40" maxlength="50"></td>
    </tr>
    <tr>
      <td >Password:</td><td><input type=password class=forms name="txtPassword1" size="40" maxlength="20"></td>
    </tr>
    <tr>
      <td >Verify Password:</td><td><input type=password class=forms name="txtPassword2" size="40" maxlength="20"></td>
    </tr>
    <tr> <!-- does not show up anywhere -->
      <td >Phone Number:</td><td><input type=text class=forms name="txtPhone" size="20" value="<?=$strPhone;?>" maxlength="20"></td>
    </tr>
    <tr><td colspan=2 align=center>
      <input class=form_button type="submit" value="Commit Changes" name="btnSubmit">
      <input class=form_button type="reset" value="Reset" name="reset">
    </td></tr>
<?}else{?>
    <tr> <td >First Name:</td> <td ><?=$strFirstName;?></td> </tr>
    <tr> <td >Last Name:</td> <td ><?=$strLastName;?></td> </tr>
    <tr> <td >Email:</td> <td ><?=$strEmail;?></td> </tr>
    <tr> <td >Phone Number:</td><td><?=$strPhone;?></td> </tr>
<?}?>
  </table><br>
</form>

<form class=forms method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<table class=bckgnd border='0' cellspacing=1 cellpadding='2'>
<tr class=warn2><td colspan=7 align=center>Project Distribution Settings</td></tr>
<tr class=rowh>
<td align=center>Project</td>
<td align=center>Event</td>
<td align=center>Data Item</td>
<td align=center>State Transition</td>
<td align=center>Email To</td>
<td align=center>Back to Project Default</td>
<td align=center>Override Default</td>
</tr>
<?
$strSQLt = "SELECT *, A.rule_id as arule_id, A.stran_id as astran_id,A.role as a_role,PU.role as pu_role,F.name as fname, T.name as tname, P.project_name, PU.dist_id as pudist, A.dist_id as dist";
$strSQLt.= " FROM ($TABLE_PROJECT_DIST AS A";
$strSQLt.= " , $TABLE_PROJECTS AS P)";
$strSQLt.= " LEFT JOIN $TABLE_EVENTS AS E ON A.event_id=E.event_id";
$strSQLt.= " LEFT JOIN $TABLE_ITEM_TO_PROJECT AS I ON A.rule_id=I.rule_id";
$strSQLt.= " LEFT JOIN $TABLE_STATE_TRANSITIONS AS S ON A.stran_id=S.stran_id";
$strSQLt.= " LEFT JOIN $TABLE_STATES AS F ON S.from_state_id=F.state_id";
$strSQLt.= " LEFT JOIN $TABLE_STATES AS T ON S.to_state_id=T.state_id";
$strSQLt.= " LEFT JOIN $TABLE_PROJECT_DIST AS PU";
$strSQLt.= "   ON PU.user_id='".$CURRENT_USER['ID']."'";
$strSQLt.= "   AND PU.project_id=A.project_id";
$strSQLt.= "   AND (PU.event_id=A.event_id";
$strSQLt.= "        OR PU.rule_id=A.rule_id";
$strSQLt.= "        OR PU.stran_id=A.stran_id)";
$strSQLt.= " WHERE P.project_id=A.project_id";
$strSQLt.= " AND A.user_id is NULL"; // we don't care about user settings on this admin page
$strSQLt.= " ORDER BY P.project_name,tname,fname,label,event_type";
//echo "Q:<DIR>$strSQLt</DIR>";
$resultt= dbquery($strSQLt);
$r=0;
$last="";
while($rowt= mysql_fetch_array($resultt))
{
  $strPName    = $rowt['project_name'];
  $strDistID   = $rowt['dist'];
  $strPUDistID = $rowt['pudist'];
  $strEventID  = $rowt['event_id']; $strEventType=$rowt['event_type'];
  $strRuleID   = $rowt['arule_id'];  $strRuleLabel=$rowt['label'];
  $strSTranID  = $rowt['astran_id']; 
  if($strSTranID)$strStateLabel=$rowt['fname']." to ".$rowt['tname'];
  $strStateLabel=($strSTranID)?$rowt['fname']." to ".$rowt['tname']:"";
  $strRole     = $rowt['a_role'];
  $strPURole     = $rowt['pu_role'];
  if($last<>$strPName)
  {
    $r=($r+1)%2;
    $last=$strPName;
  }
  if($strPUDistID)
    echo "<tr class=rowxx>"; // grey out the non-active rows
  else
    echo "<tr class=row$r>";
  echo "<td>$strPName</td>";
  echo "<td>$strEventType</td>";
  echo "<td>$strRuleLabel</td>";
  echo "<td>$strStateLabel</td>";
  echo "<td>$strRole</td>";
  if(!$strPUDistID)
  {
    echo "<td align=center>-</td>";
    echo "<td align=center><input type=submit class=form_button name='txtOut' value='$strDistID'></td>";
  }
  else
  {
    echo "<td align=center><input type=submit class=form_button name='txtIn' value='$strPUDistID'></td>";
    echo "<td align=center>-</td>";
  }
  echo "</tr>";
}
?>
</table>
</form>
<BR>
<form class=forms method="POST" action="<?=$_SERVER['PHP_SELF'];?>">
<table class=bckgnd border='0' cellspacing=1 cellpadding='2'>
<tr class=warn2><td colspan=6 align=center>Individual Distribution Settings</td></tr>
<tr class=rowh>
<td align=center>Project</td>
<td align=center>Event</td>
<td align=center>Data Item</td>
<td align=center>State Transition</td>
<td align=center>Email To Me</td>
<td align=center>Delete or Add</td>
</td>

<?
// find all distribution rules that this user has added that do not
// counter a project rule, then list them here so the user can
// see them, and also have a chance to remove them.
$sQ ="SELECT A.dist_id, A.role, C.project_name, D.event_type, E.label, A.stran_id, G.name as fname, H.name as tname";
$sQ.="";
$sQ.=" FROM";
$sQ.="    ($TABLE_PROJECT_DIST AS A";
$sQ.="  , $TABLE_PROJECT_DIST AS B";
$sQ.="  , $TABLE_PROJECTS AS C)";
$sQ.="  LEFT JOIN $TABLE_EVENTS AS D ON D.event_id=A.event_id";
$sQ.="  LEFT JOIN $TABLE_ITEM_TO_PROJECT AS E ON E.rule_id=A.rule_id";
$sQ.="  LEFT JOIN $TABLE_STATE_TRANSITIONS AS F ON F.stran_id=A.stran_id";
$sQ.="  LEFT JOIN $TABLE_STATES AS G ON G.state_id=F.from_state_id";
$sQ.="  LEFT JOIN $TABLE_STATES AS H ON H.state_id=F.to_state_id";
$sQ.=" WHERE";
$sQ.="       A.user_id='".$CURRENT_USER['ID']."'";
$sQ.="   AND B.user_id is NULL";
$sQ.="   AND A.project_id=C.project_id";
$sQ.="   AND A.role<>'NEVER'";
$sQ.="   GROUP BY A.dist_id";
$res= dbquery($sQ);
$r=0;
$l="";
while($row= mysql_fetch_array($res))
{
  if(strcmp($l,$row['project_name']))
  {
    $r=($r+1)%2;
    $l=$row['project_name'];
  }
  echo "<tr class=row$r>";
  echo "<td>".$row['project_name']."</td>";
  echo "<td align=center>".$row['event_type']."</td>";
  echo "<td align=center>".$row['label']."</td>";
  if($row['stran_id'])
    echo "<td align=center>".$row['fname']." to ".$row['tname']."</td>";
  else
    echo "<td>&nbsp;</td>";
  echo "<td align=center>".$row['role']."</td>";
  //echo "<td><input class=form_button type=submit value='".$row['dist_id']."' name='delmydist'></td>";
  echo "<td align=center><input class=form_button type=submit value='".$row['dist_id']."' name='txtIn'></td>";
  echo "</tr>";
}
?>




<tr class=warn>
 <td>
  <select name='txtDproject' class=forms>
<?
  $sQ ="SELECT A.project_id, B.project_name";
  $sQ.=" FROM";
  $sQ.="     ($TABLE_PROJECT_ACCESS AS A";
  $sQ.="   , $TABLE_PROJECTS AS B)";
  $sQ.=" WHERE";
  $sQ.="       A.user_id='".$CURRENT_USER['ID']."'";
  $sQ.="   AND A.level<='".$PROJECT_ACCESS['display']."'";
  $sQ.="   AND A.project_id=B.project_id";
  echo "sQ:<DIR>$sQ</DIR>";
  $res= dbquery($sQ);
  while($rw= mysql_fetch_array($res))
  {
    echo "<option value='".$rw['project_id']."'>".$rw['project_name']."</option>";
  }
?>
  </select>
 </td>
 <td>
  <select name='txtDevent' class=forms>
    <option value=''>-None-</option>
<?
  $sQ ="SELECT event_id, event_type";
  $sQ.=" FROM";
  $sQ.="     $TABLE_EVENTS";
  $sQ.=" ORDER BY event_type";
  echo "sQ:<DIR>$sQ</DIR>";
  $res= dbquery($sQ);
  while($rw= mysql_fetch_array($res))
  {
    echo "<option value='".$rw['event_id']."'>".$rw['event_type']."</option>";
  }
?>
  </select>
 </td>
 <td>
  <select name='txtDitem' class=forms>
    <option value=''>-None-</option>
<?
  $sQ ="SELECT A.rule_id, A.label, C.project_abbr";
  $sQ.=" FROM";
  $sQ.="     ($TABLE_ITEM_TO_PROJECT AS A";
  $sQ.="   , $TABLE_PROJECT_ACCESS AS B";
  $sQ.="   , $TABLE_PROJECTS AS C)";
  $sQ.=" WHERE";
  $sQ.="       B.user_id='".$CURRENT_USER['ID']."'";
  $sQ.="   AND B.level<='".$PROJECT_ACCESS['display']."'";
  $sQ.="   AND A.project_id=B.project_id";
  $sQ.="   AND B.project_id=C.project_id";
  $sQ.=" ORDER BY C.project_abbr, A.label";
  echo "sQ:<DIR>$sQ</DIR>";
  $res= dbquery($sQ);
  while($rw= mysql_fetch_array($res))
  {
    echo "<option value='".$rw['rule_id']."'>(".$rw['project_abbr'].") ".$rw['label']."</option>";
  }
?>
  </select>
 </td>
 <td>
  <select name='txtDstate' class=forms>
    <option value=''>-None-</option>
<?
  $sQ ="SELECT A.stran_id, D.name as fname, E.name as tname, C.project_abbr";
  $sQ.=" FROM";
  $sQ.="     ($TABLE_STATE_TRANSITIONS AS A";
  $sQ.="   , $TABLE_PROJECT_ACCESS AS B";
  $sQ.="   , $TABLE_PROJECTS AS C";
  $sQ.="   , $TABLE_STATES AS D";
  $sQ.="   , $TABLE_STATES AS E)";
  $sQ.=" WHERE";
  $sQ.="       B.user_id='".$CURRENT_USER['ID']."'";
  $sQ.="   AND B.level<='".$PROJECT_ACCESS['display']."'";
  $sQ.="   AND B.project_id=C.project_id";
  $sQ.="   AND A.from_state_id=D.state_id";
  $sQ.="   AND D.project_id=C.project_id";
  $sQ.="   AND A.to_state_id=E.state_id";
  $sQ.="   AND E.project_id=C.project_id";
  $sQ.=" ORDER BY C.project_abbr, D.name, E.name";
  echo "sQ:<DIR>$sQ</DIR>";
  $res= dbquery($sQ);
  while($rw= mysql_fetch_array($res))
  {
    echo "<option value='".$rw['stran_id']."'>(".$rw['project_abbr'].") ".$rw['fname']." - ".$rw['tname']."</option>";
  }
?>
  </select>
 </td>
 <td>
  <select name='txtDemail' class=forms>
    <option value='ALWAYS'>ALWAYS</option>
    <option value='OWNER'>OWNER</option>
  </select>
 </td>
 <td align=center>
   <input type=submit value='Add' name='addD' class=form_button>
 </td>
</tr>
</table>
</form>
<?
  writeFooter();
?>
