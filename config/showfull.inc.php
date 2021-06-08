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

function buildlist ($assetID, $uid, $read_only) {
  global $rowStyle, $accountID, $spare, $SCRIPT_NAME;

?>

  <p><table border='0' cellspacing='0' cellpadding='4' width='100%'>
  <TR class='title'>
    <TD><b>Description</b></TD>
    <TD><b>Cost</b></TD></TR>

<?
  $iCount = 0;
  $rowStyle = "";
  While ($row = mysql_fetch_array($result)) {

     $description   = $row['description'];
     $cost          = $row["cost"];

     // Display all the peripherals belonging to this server
?>
    <TR class='<? echo alternateRowColor(); ?>'>
    <TD><? echo $description; ?></TD>
    <TD><? echo $cost; ?></TD></TR>
<?
  }
  
  echo "</table>";
  mysql_free_result($result);

?>
  <p><table border='0' cellpadding='4' cellspacing='0' width='100%'>
  <TR class='title'><TD><b>Cost</b></TD><TD><b>Description</b></TD></TR>
<?
  $iCount = 0;
  $rowStyle = "";
  while ($row = mysql_fetch_array($result)) {

     $int_S_id      = $row["id"];
     $strCost       = $row["cost"];
     $strDesc       = $row['description'];
?>
    <TR class='<? echo alternateRowColor(); ?>'>
    <TD><? echo $strCost; ?></TD>
    <TD><? echo $strDesc; ?></TD></TR>
<?
  }
  echo "</table>";
}
?>
