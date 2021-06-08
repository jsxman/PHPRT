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

  if ($_FORM['btnSubmit'])
  {
      $strEmail    = validateEmail("Email Address", $_FORM['txtEmail'], TRUE);
      $strUsername = validateText("Username",       $_FORM['txtUsername'], 3, 20, TRUE, FALSE);

      /* do not allow uese "guest" to change anything */
      if(!strcmp($strUsername,"guest"))
      {
        $strError= "ERROR: User 'guest' can not change the password.";
      }


      if (!$strError)
      {
           $strSQL1 = "SELECT user_id,email,username";
           $strSQL1.= " FROM $TABLE_USERS";
           $strSQL1.= " WHERE email='$strEmail' AND username='$strUsername'";
           $result1 = dbquery($strSQL1);
           $row1    = mysql_fetch_array($result1);
           if ($row1['user_id'] == "")
           {
                $strError = "A new password has been emailed to the account you provided.";
           }

           if (!$strError)
           {
                $strTempString = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
                for ($i = 0; $i < 8; $i++)
                {
                     $intPos = rand(0, 33);
                     $strTempChar = substr($strTempString, $intPos, 1);
                     $strPassword = $strPassword.$strTempChar;
                }

                $strPassword2 = md5($strPassword);
                $strSQL2 = "UPDATE $TABLE_USERS SET password='$strPassword2' WHERE user_id=".$row1['user_id'];
                $result2 = dbquery($strSQL2);

                $msgBody = "Your username is '".$row1['username']."' and your temporary password is '$strPassword'.";
                mail($row1['email'],"PHP RT Password Change: ".date("m-d-Y"),$msgBody,$MAIL_HEADER);

                $strError = "A new password has been emailed to the account you provided.<BR>";
                $strError = $strError."You will be able to change this new, temporary password to ";
                $strError = $strError."whatever you wish after logging in.";
           }
      }
  }

  writeHeader("Forgot Your Password?");
  declareError(TRUE);
?>


<form class=forms name="form1" method="POST" action="forgotPW.php">
Please enter your email address below:
  <br><table border='0' width='415' cellpadding='2'>
    <tr>
      <td class=forms >Username:</td>
      <td class=forms ><input class=forms type="text" name="txtUsername" value="<?echo $strUsername;?>" size="40" maxlength="50"></td>
    </tr>
    <tr>
      <td class=forms >Email Address:</td>
      <td class=forms ><input class=forms type="text" name="txtEmail" value="<?echo $strEmail;?>" size="40" maxlength="50"></td>
    </tr>
    <tr>
      <td colspan=2 align=center class=forms>
        <input type="hidden" value="Submit" name="btnSubmit">
        <input class=form_button type="submit" value="Submit" name="btnSubmit">
        <input class=form_button type="reset" value="Reset" name="reset">
      </td>
    </tr>
  </table><br>
</form>

<?
  writeFooter();
?>
