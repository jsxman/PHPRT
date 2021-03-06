<?php
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
if (!isset($license_once))
{ /* only include this once */
  if($DEBUG)echo "DEBUG: Running License Reader<BR>\n";

  $L_I_C=true;

 $LIC_FILE="license.key";

  if(file_exists($LIC_FILE))
  {
    if($DEBUG)echo "DEBUG: pulling in license key<BR>\n";
    @include($LIC_FILE);
  }
  else
  {
    if($DEBUG)echo "DEBUG: License File not found - Running DEMO Mode<BR>\n";
  }
  
  $license_once=true; /* keep from including more than once */
}
?>
