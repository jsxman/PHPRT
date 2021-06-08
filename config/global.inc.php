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

  /* start the timer on the page loading */
  if(!isset($PAGE_TIME_START))
    $PAGE_TIME_START=microtime();

  $DEBUG=0; // debug output on or off (1 is on , 0 is off)
  $SHOW_TODO=0; // set to 1 to show the meessage. 0 to not show them

  /* just for testing on my local server */
  if($_SERVER['SERVER_NAME']=="moe") $_SERVER['SERVER_NAME']="phprt";
  
  // where rare administrative emails will go
  //$adminEmail  = "you@yoursite.com";
  //-- use the above line or include your info from another file
  include("/home/userjsx/site_access/phprt_adminemail.php");

  //$SESSION_TIMEOUT=60*15; // 15 minutes - This keeps the session alive for 15 minutes longer
  $SESSION_TIMEOUT=60*60; // 60 minutes - This keeps the session alive for 15 minutes longer

  /* used for meta refresh */
  $REFRESH_TIME_SEC=2; // seconds to show a page before sending the user on

  /* Max number of times a person can log in with bad password before we turn off account */
  $MAX_BAD_PASS=3;

  # -------------------------------------------------------------------- #
  $PAGE_INDEX        ="index.php";
  $PAGE_CREATE_TICKET="create_ticket.php";
  $PAGE_MODIFY_TICKET="modify_ticket.php";
  $PAGE_VIEW_TICKET  ="view_ticket.php";
  $PAGE_RELATES      ="ticket_relates.php";
  $PAGE_REPLICATE    ="replicate_ticket.php";
  $PAGE_MERGE        ="merge_ticket.php";
  $PAGE_ADMIN_ACCESS ="admin_access.php";
  $PAGE_EDIT_USER    ="edit_user.php";
  $PAGE_VIEW_USER    ="view_user.php";
  $PAGE_PROJ_DIST    ="project_distribution.php";

  # -------------------------------------------------------------------- #

  $MYSQL_PREFIX="PHP_RT_";
  $TABLE_ACCESSTIMES       =$MYSQL_PREFIX."accesstimes";
  $TABLE_COMMENTS          =$MYSQL_PREFIX."comments";
  $TABLE_DB_INFO           =$MYSQL_PREFIX."db_info";
  $TABLE_EACH_TICKET       =$MYSQL_PREFIX."each_ticket";
  $TABLE_EVENTS            =$MYSQL_PREFIX."events";
  $TABLE_ITEM_DEPENDENCY   =$MYSQL_PREFIX."item_dependancy";
  $TABLE_ITEM_TO_PROJECT   =$MYSQL_PREFIX."item_to_project";
  $TABLE_ITEM_TYPE         =$MYSQL_PREFIX."item_type";  #admin:set
  $TABLE_ITEM_ENUMS        =$MYSQL_PREFIX."item_enums"; #admin:set
  $TABLE_PROJECT_ACCESS    =$MYSQL_PREFIX."project_access"; #admin:set
  $TABLE_PROJECT_DIST      =$MYSQL_PREFIX."project_dist";
  $TABLE_PROJECT_TEMPLATES =$MYSQL_PREFIX."project_templates";
  $TABLE_PROJECTS          =$MYSQL_PREFIX."projects";
  $TABLE_REMINDERS         =$MYSQL_PREFIX."reminders";
  $TABLE_SESSIONS          =$MYSQL_PREFIX."sessions"; /* NOT USED */
  $TABLE_STATE_RULES       =$MYSQL_PREFIX."state_rules";
  $TABLE_STATE_TRANSITIONS =$MYSQL_PREFIX."state_transitions";
  $TABLE_STATES            =$MYSQL_PREFIX."states";
  $TABLE_TICKET_DIST       =$MYSQL_PREFIX."ticket_dist";
  $TABLE_TICKET_ITEMS      =$MYSQL_PREFIX."ticket_items";
  $TABLE_TICKET_RELATIONS  =$MYSQL_PREFIX."ticket_relations";
  $TABLE_TRANSACTIONS      =$MYSQL_PREFIX."transactions";
  $TABLE_USERS             =$MYSQL_PREFIX."users";

  ## This defines your connection to your MySQL database
  $DB_HOST     ="localhost";
  $DB_TABLE    ="phprt";
  $DB_USER     ="phprt";
  //$DB_PASSWORD ='-your-password-here';
  // To keep this out of the HTTP path you can put this in a file that
  // you include here:
  include("/home/userjsx/site_access/phprt_tool.php");

  $MAIL_HEADER="";
  $MAIL_HEADER.="MIME-Versio: 1.0\n";
  $MAIL_HEADER.="Content-type: text/html; charset=iso-8859-1\n";
  $MAIL_HEADER.="From: PHP RT <$adminEmail>\n";

 
  $PROJECT_ACCESS=array(
'admin'     =>10,
'leader'    =>15,
'manipulate'=>20,
'display'   =>30,
'none'      =>40,
);
  /* must manually keep this up to date */
  /* must have '' be none in addition to everything else*/
  $REVERSE_PROJECT_ACCESS=array(
10=>'admin',
15=>'leader',
20=>'manipulate',
30=>'display',
40=>'none',
''=>'none',
);

  session_register("userID");
  session_register("time");
  session_register("dbAdmin");
  session_register("security");
  session_register("colorby"); // added for filtering storing
  session_register("filters"); // added for filtering storing
  session_register("sorting"); // added for sorting storing
  session_register("nocolumn"); // added for sorting storing
  session_register("LastProject"); // added for remembering the last project asked for
  session_register("FORM"); // added to remember what was requested when user session times out and then relogs in.
  session_register("FONT_SIZE"); // used to set the font size
  // session_register("TEST");

  $strIncludePrefix = "config";
  $sequence1="seq1";
  Include($strIncludePrefix."/license.php");
  Include($strIncludePrefix."/db.inc.php");
  Include($strIncludePrefix."/securityFunctions.inc.php");
  Include($strIncludePrefix."/generalFunctions.inc.php");
  Include($strIncludePrefix."/headerFunctions.inc.php");
  Include($strIncludePrefix."/userFunctions.inc.php");
  Include($strIncludePrefix."/showfull.inc.php");

  $DEBUG_POSTED=getPostedData(); // This builds a global variable called $_FORM[] - keys are posted varaible name, values are values of posted data

  header("Cache-control: private"); // enables forms to retain values when user hits 'back' button

  $PHPRT_VERSION="3.2.4"; /* this is the version of this tool */

  /* to contain any email distribution that needs to occur */
  $EMAILS_DATA=array();

?>
