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

//echo "FONT-1<BR>\n";
 if(isset($_SESSION['FONT_SIZE']))
 {
//echo "FONT-2<BR>\n";
   $FONT_SIZE=$_SESSION['FONT_SIZE'];
 }
 else
 {
//echo "FONT-3<BR>\n";
   $FONT_SIZE=6;
 }
global $_FORM;
 if(isset($_FORM['FONT_CHANGE']))
 {
//echo "FONT-4<BR>\n";
   $FONT_SIZE=$FONT_SIZE+$_FORM['FONT_CHANGE'];
   if($FONT_SIZE<2)$FONT_SIZE=2;
   if($FONT_SIZE>14)$FONT_SIZE=14;
 }
 $_SESSION['FONT_SIZE']=$FONT_SIZE;

 $FONT_SIZE_PLUS_1 =$FONT_SIZE+ 1;
 $FONT_SIZE_PLUS_2 =$FONT_SIZE+ 2;
 $FONT_SIZE_PLUS_3 =$FONT_SIZE+ 3;
 $FONT_SIZE_PLUS_4 =$FONT_SIZE+ 4;
 $FONT_SIZE_PLUS_5 =$FONT_SIZE+ 5;
 $FONT_SIZE_PLUS_6 =$FONT_SIZE+ 6;
 $FONT_SIZE_PLUS_7 =$FONT_SIZE+ 7;
 $FONT_SIZE_PLUS_8 =$FONT_SIZE+ 8;
 $FONT_SIZE_PLUS_9 =$FONT_SIZE+ 9;
 $FONT_SIZE_PLUS_10=$FONT_SIZE+10;
?>
<style>
body
  {
  background-color : #DEF;
  color            : #000000;
  font-family      : verdana,sans-serif;
  font-size        : <?=$FONT_SIZE_PLUS_6;?>px;
  vertical-align   : middle;
  margin-top       : 0em;
  padding          : 0em;
  scrollbar-arrow-color       : #FF0000;
  scrollbar-base-color        : #000000;
  scrollbar-dark-shadow-color : #AAAA88;
  scrollbar-face-color        : #EEEECC;
  scrollbar-highlight-color   : #888844;
  scrollbar-shadow-color      : #000000;
  }

.page_body
{
  background-color    : #EFF;
  color               : #000;
  border-style        : groove;
  border-color        : #000;
  border-top-width    : 0px;
  border-right-width  : 1px;
  border-left-width   : 1px;
  border-bottom-width : 2px;
}

TR.title { font-size: <?=$FONT_SIZE_PLUS_8;?>px; color: #000; font-weight: bold; background-color: #AAD;}
TH       { font-size: <?=$FONT_SIZE_PLUS_6;?>px; color: #000; background-color: #AAD; }
TR.rowh  { font-weight: bold; color: #DEF; font-size: <?=$FONT_SIZE_PLUS_6;?>px; background-color: #012; }
.tinyh   { font-weight: bold; color: #DEF; font-size: <?=$FONT_SIZE_PLUS_2;?>px;}
TR.row0  { color: #000; font-size: <?=$FONT_SIZE_PLUS_4;?>px; background-color: #DEF; }
TR.row1  { color: #000; font-size: <?=$FONT_SIZE_PLUS_4;?>px; background-color: #FFF; }
TR.row2  { color: #000; font-size: <?=$FONT_SIZE_PLUS_4;?>px; background-color: #FFA; }
TR.row3  { color: #000; font-size: <?=$FONT_SIZE_PLUS_4;?>px; background-color: #CB3; }
TR.rowxx { color: #000; font-size: <?=$FONT_SIZE_PLUS_4;?>px; background-color: #888; }
TR.rowov { color: #000; font-size: <?=$FONT_SIZE_PLUS_4;?>px; background-color: #CB3 ! important; }
TR.tiny  { color: #000; font-size: <?=$FONT_SIZE_PLUS_2;?>px;}
TD.normal{ color: #000; font-size: <?=$FONT_SIZE_PLUS_2;?>px; }
TD.tiny  { color: #000; font-size: <?=$FONT_SIZE_PLUS_2;?>px; border: solid #000 1px; font-weight: bold;}
TD.header{ font-size: <?=$FONT_SIZE_PLUS_6;?>px ! important; }
TABLE.bckgnd{ background-color: #000 ! important; }
A.header { font-weight: bold; color: #DEF; font-size: <?=$FONT_SIZE_PLUS_6;?>px; }
A.tiny   { color: #000; font-size: <?=$FONT_SIZE_PLUS_2;?>px;}
A.tiny:link    { color: #040; font-size: <?=$FONT_SIZE_PLUS_2;?>px;}
A.tiny:active  { color: #040; font-size: <?=$FONT_SIZE_PLUS_2;?>px;}
A.tiny:hover   { color: #400; font-size: <?=$FONT_SIZE_PLUS_2;?>px;}
A.tiny:visited { color: #004; font-size: <?=$FONT_SIZE_PLUS_2;?>px;}
TR.admindb  { color: #000; background-color: #FA8;}
TR.adminpr  { color: #000; background-color: #8FA;}


.instructions { color: #9F0000; font-weight: bolder; }
.smaller { FONT-SIZE: <?=$FONT_SIZE_PLUS_2;?>px; }

.main_wrap
{
  border-width : 2;
  border-style : solid;
  border-color : #000000;
}

.wrap
{
  border-width : 2;
  border-style : dotted;
  border-color : #888844;
}

.wrap2
{
  border-width : 2;
  border-style : solid;
  border-color : #002244;
  background-color: #FFFFFF;
}

A {font-size: <?=$FONT_SIZE_PLUS_6;?>px; color: #004080;}

pre { font-family: courier,serif }

.page_title
  {
  color            : #002244;
  background-color : #DDDDDD;
  font-size        : <?=$FONT_SIZE_PLUS_8;?>px;
  font-weight      : bold;
  text-align       : center;
  }


.page_menu
  {
  color            : #002244;
  background-color : #DDDDDD;
  font-size        : <?=$FONT_SIZE_PLUS_6;?>px;
  font-weight      : bolder;
  }
A.page_menu
  {
  color      : #008844;
  font-size  : <?=$FONT_SIZE_PLUS_6;?>px;
  font-weight: bolder;
  }
.link_box
  {
  background-color    : #FFFFFF;
  border-top-width    : 0;
  border-bottom-width : 2;
  border-left-width   : 2;
  border-right-width  : 2;
  border-style        : solid;
  border-color        : #000000;
  }
.top_links
  {
  color       : #000000;
  font-size   : <?=$FONT_SIZE_PLUS_6;?>px;
  font-weight : bolder;
  }
.admin_links {
  color       : #000;
  font-size   : <?=$FONT_SIZE_PLUS_4;?>px;
  font-weight : bolder;
  }
A.links
  {
  padding      : 2px;
  border-width : 0;
  color        : #000000;
  font-size    : <?=$FONT_SIZE_PLUS_6;?>px;
  font-weight  : bolder;
  }
A.admin_links
  {
  padding      : 2px;
  border-width : 0;
  color        : #000;
  font-size    : <?=$FONT_SIZE_PLUS_4;?>px;
  font-weight  : bolder;
  }
.message
  {
  font-weight: normal;
  font-size: <?=$FONT_SIZE_PLUS_6;?>px;
  background-color: #FFFFFF;
  border-width:1;
  border-style:solid;
  border-color:#888855;
  }
.footer
  {
  background-color: #EEFFFF;
  border-width:1;
  border-style:solid;
  border-color:#000000;
  }
.warn
  {
    background-color : #ffffff;
    color            : #AA6020;
    font-weight      : bold;
    font-size        : <?=$FONT_SIZE_PLUS_6;?>px;
    border-width     : 2px;
    border-style     : ridge;
    border-color     : #602000;
  }
.warn2
  {
    background-color : #000;
    color            : #FF0;
    font-weight      : bold;
    font-size        : <?=$FONT_SIZE_PLUS_6;?>px;
    border-width     : 2px;
    border-style     : ridge;
    border-color     : #F00;
  }
.warn_noborder
  {
    background-color:#ffffff;
    color: #888855;
    font-weight: bolder;
    font-size: <?=$FONT_SIZE_PLUS_10;?>px;
  }
.warn2_noborder
  {
    color: #000;
    font-weight: bolder;
    font-size: <?=$FONT_SIZE_PLUS_8;?>px;
  }
.forms
  {
    font-weight : bold;
    font-size   : <?=$FONT_SIZE_PLUS_6;?>px;
    color       : #402000;
    padding     : 2px 0px 0px 2px;
    border      : solid #046 1px;
  }
.forms-filter
  {
    font-weight : bold;
    font-size   : <?=$FONT_SIZE_PLUS_2;?>px;
    color       : #402000;
    padding     : 2px 0px 0px 2px;
    border      : solid #046 1px;
  }
.forms_login
  {
    font-weight : bold;
    font-size   : <?=$FONT_SIZE_PLUS_6;?>px;
    color       : #402000;
    padding     : 2px 0px 0px 2px;
  }
.form_button
  {
    font-weight      : bold;
    font-size        : <?=$FONT_SIZE_PLUS_6;?>px;
    color            : #882222;
    background-color : #FFFFFF;
    border-width     : 1px;
    border-style     : outset;
    border-color     : #440000;
  } 
.form_button2
  {
    font-weight      : normal;
    font-size        : <?=$FONT_SIZE_PLUS_6;?>px;
    color            : #CCCCCC;
    background-color : #FFFFFF;
    border-width     : 1px;
    border-style     : outset;
    border-color     : #CCCCCC;
  } 
#wrap
  {
    border-width : 1px;
    border-style : solid;
    border-color : #000000;
  }
.required {color: red; font-weight: bolder ; font-size: <?=$FONT_SIZE_PLUS_8;?>px;}
.one      {color: green; font-weight: bolder ; font-size: <?=$FONT_SIZE_PLUS_8;?>px;}
.empty    {color: blue; font-weight: bolder ; font-size: <?=$FONT_SIZE_PLUS_8;?>px;}
.cc {font-family: verdana; font-size: <?=$FONT_SIZE_PLUS_4;?>px; font-color:#000000; font-weight:normal;}
A.cc{font-family: verdana; font-size: <?=$FONT_SIZE_PLUS_4;?>px; font-color:#000000; font-weight:normal;}

.security_level
  {
    color: green;
  }
.bigtext_history
 {
   margin-left: 4em;
   padding: 2px 1px 1px 5px;
   font-size: <?=$FONT_SIZE_PLUS_2;?>px;
   border: solid 1px #02A;
   background-color: #EEC;
   font-style: italic;
   color: #004;
 }
.comment_history
 {
   margin-left: 4em;
   padding: 2px 1px 1px 5px;
   font-size: <?=$FONT_SIZE_PLUS_2;?>px;
   border: solid 1px #02A;
   background-color: #DDB;
   font-style: italic;
   color: #400;
 }
.note
 {
   color: #F00;
   font-weight: bolder;
 }
</style>
