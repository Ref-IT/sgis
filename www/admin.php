<?php
$time_start = microtime(true);
global $attributes, $logoutUrl, $ADMINGROUP, $nonce, $dbDeferRefresh;

#ob_start('ob_gzhandler'); # disabled, slows down too much

require_once "../lib/inc.all.php";
#header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
requireGroup($ADMINGROUP);
#header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");

# 2016-11-15 somehow this is now escaped automatically
function escapeMeNot($d, $row) {
  return $d;
  return htmlspecialchars($d);
}

#debug
#foreach ($_GET as $k => $v)
#  $_POST[$k]=$v;

if (isset($_POST["action"])) {
 $msgs = Array();
 $ret = false;
 $target = false;
 $needReload = false;

 if (substr($_POST["action"],0,13) == "person.merge.") {
   $_POST["merge_person_id"] = substr($_POST["action"], 13);
   $_POST["action"] = "person.merge";
 }

 if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
  $msgs[] = "Formular veraltet - CSRF Schutz aktiviert.";
  $logId = false;
 } else {
  header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
  $logId = false;
  if (substr($_POST["action"], -6) != ".table") {
    $logId = logThisAction();
  }
  header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
  if (strpos($_POST["action"],"insert") !== false ||
      strpos($_POST["action"],"update") !== false ||
      strpos($_POST["action"],"delete") !== false) {
    foreach ($_REQUEST as $k => $v) {
      $_REQUEST[$k] = trimMe($v);
    }
  }
  header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");

  switch ($_POST["action"]):
  case "person.table":
   dbRefreshPersonCurrentIfNeeded(); # View contains reference to CURRENT_TIMESTAMP (von-bis Tagesangabe), muss daher tgl. aktualisiert werden
   header("Content-Type: text/json; charset=UTF-8");
  #header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
   $columns = [
     [ 'db' => 'id',                 'dt' => 'id' ],
     [ 'db' => 'email',              'dt' => 'email',    'formatter' => 'escapeMeNot' ],
     [ 'db' => 'name',               'dt' => 'name',     'formatter' => 'escapeMeNot' ],
     [ 'db' => 'username',           'dt' => 'username', 'formatter' => 'escapeMeNot' ],
//     [ 'db' => 'password', 'dt' => 3 ],
     [ 'db' => 'unirzlogin',         'dt' => 'unirzlogin',
       'formatter' => function( $d, $row ) {
         return str_replace("@tu-ilmenau.de","",$d);
       }
     ],
     [ 'db' => 'wikiPage',         'dt' => 'wikiPage', 'formatter' => 'escapeMeNot' ],
     [ 'db' => 'lastLogin',          'dt' => 'lastLogin',
       'formatter' => function( $d, $row ) {
         return $d ? date( 'Y-m-d', strtotime($d)) : "";
       }
     ],
     [ 'db'    => 'canLoginCurrent', 'dt'    => 'canLogin',
       'formatter' => function( $d, $row ) {
         return (!$d) ? "ja" : "nein";
       }
     ],
     [ 'db'    => 'active',          'dt'    => 'active',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ],
     [ 'db'    => 'hasUniMail',          'dt'    => 'hasUniMail',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ],
   ];
#header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
    $ret = SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}person_current_mat", /* primary key */ "id", $columns );
    echo json_encode($ret);
#header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
  exit;
  case "mailingliste.table":
   header("Content-Type: text/json; charset=UTF-8");
   $columns = array(
     array( 'db' => 'id',                    'dt' => 'id' ),
     array( 'db' => 'address',               'dt' => 'address', 'formatter' => 'escapeMeNot' ),
     array( 'db' => 'url',                   'dt' => 'url', 'formatter' => 'escapeMeNot' ),
   );
   echo json_encode(
     SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}mailingliste", /* primary key */ "id", $columns )
   );
  exit;
  case "gruppe.table":
   header("Content-Type: text/json; charset=UTF-8");
   $columns = array(
     array( 'db' => 'id',                    'dt' => 'id' ),
     array( 'db' => 'name',                  'dt' => 'name', 'formatter' => 'escapeMeNot' ),
     array( 'db' => 'beschreibung',          'dt' => 'beschreibung', 'formatter' => 'escapeMeNot' ),
   );
   echo json_encode(
     SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}gruppe", /* primary key */ "id", $columns )
   );
  exit;
  case "gremium.table":
   header("Content-Type: text/json; charset=UTF-8");
   $columns = array(
     array( 'db' => 'id',                    'dt' => 'id' ),
     array( 'db' => 'name',                  'dt' => 'name', 'formatter' => 'escapeMeNot' ),
     array( 'db' => 'fullname',              'dt' => 'fullname', 'formatter' => 'escapeMeNot' ),
     array( 'db' => 'fakultaet',             'dt' => 'fakultaet', 'formatter' => 'escapeMeNot' ),
     array( 'db' => 'studiengang',           'dt' => 'studiengang', 'formatter' => 'escapeMeNot' ),
     array( 'db' => 'studiengangabschluss',  'dt' => 'studiengangabschluss', 'formatter' => 'escapeMeNot' ),
     array( 'db' => 'has_members',           'dt' => 'has_members',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ),
     array( 'db' => 'has_members_in_inactive_roles', 'dt' => 'has_members_in_inactive_roles',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ),
     array( 'db'    => 'active',          'dt'    => 'active',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ),
   );
   echo json_encode(
     SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}gremium_current", /* primary key */ "id", $columns )
   );
  exit;
#  case "rolle.table.debug":
#    global $pdo, $DB_PREFIX;
#    $query = $pdo->prepare("SELECT * FROM {$DB_PREFIX}rolle_searchable_mailingliste");
#    $query->execute(Array()) or httperror(print_r($query->errorInfo(),true));
#    header("Content-type: text/plain");
#    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
#      print_r($row);
#    }
#  exit;
  case "rolle.table":
   header("Content-Type: text/json; charset=UTF-8");

   $table = "rolle_searchable";
   $columns = [
     [ 'db' => 'id',                            'dt' => 'id' ],
     [ 'db' => 'fullname',                      'dt' => 'fullname',                     'formatter' => 'escapeMeNot' ],
     [ 'db' => 'rolle_name',                    'dt' => 'rolle_name',                   'formatter' => 'escapeMeNot' ],
     [ 'db' => 'gremium_name',                  'dt' => 'gremium_name',                 'formatter' => 'escapeMeNot' ],
     [ 'db' => 'gremium_fakultaet',             'dt' => 'gremium_fakultaet',            'formatter' => 'escapeMeNot' ],
     [ 'db' => 'gremium_studiengang',           'dt' => 'gremium_studiengang',          'formatter' => 'escapeMeNot' ],
     [ 'db' => 'gremium_studiengangabschluss',  'dt' => 'gremium_studiengangabschluss', 'formatter' => 'escapeMeNot' ],
     [ 'db'    => 'active',                     'dt' => 'active',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ],
   ];

   $whereAll = NULL;
   if ( isset($_REQUEST["rel"]) && in_array( $_REQUEST["rel"], ["rolle_mailingliste"]) ) {
     $whereAll = "mailingliste_id = ".dbQuote((string)$_REQUEST["rel_id"],PDO::PARAM_INT);
     $table = "rolle_searchable_mailingliste";

     $columns[] =
       [ 'db'    => 'in_rel',          'dt'    => 'in_rel',
         'formatter' => function( $d, $row ) {
           return $d ? "ja" : "nein";
         }
       ];
   };

   echo json_encode(
     SSP::complex( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}{$table}", /* primary key */ "id", $columns, NULL, $whereAll )
   );
  exit;
  case "verify.email":
    $r = verify_tui_mail(trim($_POST["email"]));
    if (is_array($r) && isset($r["sn"])) $r["sn"] = ucfirst($r["sn"]);
    if (is_array($r) && isset($r["givenName"])) $r["givenName"] = ucfirst($r["givenName"]);
    header("Content-Type: application/json");
    echo json_encode($r);
  exit;
  case "mailingliste.insert":
   $ret = dbMailinglisteInsert($_POST["address"], $_POST["url"], $_POST["password"]);
   $msgs[] = "Mailingliste wurde erstellt.";
   if ($ret !== false)
     $target = $_SERVER["PHP_SELF"]."?tab=mailingliste.edit&mailingliste_id=".$ret;
  break;
  case "mailingliste.update":
   $ret = dbMailinglisteUpdate($_POST["id"], $_POST["address"], $_POST["url"], $_POST["password"]);
   $msgs[] = "Mailingliste wurde aktualisiert.";
  break;
  case "mailingliste.delete":
   $ret = dbMailinglisteDelete($_POST["id"]);
   $msgs[] = "Mailingliste wurde entfernt.";
  break;
  case "mailingliste_mailman.insert":
   $ret = dbMailinglisteMailmanInsert($_POST["mailingliste_id"], $_POST["url"], $_POST["field"], $_POST["mode"], $_POST["priority"], $_POST["value"]);
   $msgs[] = "Mailinglisteneinstellung wurde erstellt.";
  break;
  case "mailingliste_mailman.update":
   $ret = dbMailinglisteMailmanUpdate($_POST["id"], $_POST["mailingliste_id"], $_POST["url"], $_POST["field"], $_POST["mode"], $_POST["priority"], $_POST["value"]);
   $msgs[] = "Mailinglisteneinstellung wurde aktualisiert.";
  break;
  case "mailingliste_mailman.delete":
   $ret = dbMailinglisteMailmanDelete($_POST["id"]);
   $msgs[] = "Mailinglisteneinstellung wurde entfernt.";
  break;
  case "rolle_mailingliste.delete":
   $ret = dbMailinglisteDropRolle($_POST["mailingliste_id"], $_POST["rolle_id"]);
   $msgs[] = "Mailinglisten-Rollenzuordnung wurde entfernt.";
  break;
  case "rolle_mailingliste.insert":
   $ret = dbMailinglisteInsertRolle($_POST["mailingliste_id"], $_POST["rolle_id"]);
   $msgs[] = "Mailinglisten-Rollenzuordnung wurde eingetragen.";
  break;
  case "person.duplicate":
   $tmp = getAllePerson();
   $personen = [];
   foreach ($tmp as $p) {
     $emails = explode(",",trim(strtolower($p["email"])));
     foreach ($emails as $email) {
       $p["email"] = $email;
       $personen[$email] = $p;
     }
   }
   $r = verify_tui_mail_many(array_keys($personen));

   $unipersonen = [];
   foreach ($r as $p) {
     foreach ($p["mail"] as $email) {
       $unipersonen[trim(strtolower($email))] = $p;
     }
   }

   header("Content-Type: text/plain");

   echo "Uni-eMail in sGIS aber nicht im LDAP:\n";
   global $unimail;
   foreach($personen as $p) {
     $email = trim(strtolower($p["email"]));
     if (!$p["canLogin"]) continue;
     if (isset($unipersonen[$email])) continue;
     $found = false;
     foreach ($unimail as $domain) {
       $found |= substr(strtolower($email),-strlen($domain)-1) == strtolower("@$domain");
     }
     if (!$found) continue;

     echo "  $email\n";
     #dbPersonDisable($p["id"]);
   }

   $unipersonen = [];
   foreach ($r as $p) {
     if (count($p["mail"]) <= 1) continue; # cannot link any two persons
     foreach ($p["mail"] as $email) {
       $unipersonen[trim(strtolower($email))] = $p;
     }
   }

   echo "\nUnterschiedliche Personen im sGIS aber gleiche Person laut Uni:\n";
   foreach($personen as $p) {
     $email = trim(strtolower($p["email"]));
     if (!isset($unipersonen[$email])) continue;

     foreach ($unipersonen[$email]["mail"] as $otheremail) {
       $otheremail = trim(strtolower($otheremail));
       if (!isset($personen[$otheremail])) continue;
       if ($p["id"] == $personen[$otheremail]["id"]) continue;

       echo "  Person ".$p["id"]." and ".$personen[$otheremail]["id"]." (".$p["name"].")\n";
     }
   }

   echo "\n-- ENDE --";
   exit;
  break;
  case "person.merge":
   $ret = dbPersonMerge($_POST["id"], $_POST["merge_person_id"]);
   if ($ret !== false)
     $target = $_SERVER["PHP_SELF"]."?tab=person.edit&person_id=".((int) $_POST["merge_person_id"]);
   $msgs[] = "Person wurde verschoben.";
  break;
  case "person.delete":
   $ret = dbPersonDelete($_POST["id"]);
   $msgs[] = "Person wurde entfernt.";
  break;
  case "person.disable":
   $ret = dbPersonDisable($_POST["id"]);
   $msgs[] = "Person wurde deaktiviert.";
  break;
  case "person.update":
   $dbDeferRefresh = true;
header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
   $ret = dbPersonUpdate($_POST["id"],trim($_POST["name"]),$_POST["email"],trim($_POST["unirzlogin"]),trim($_POST["username"]),$_POST["password"],$_POST["canlogin"],$_POST["wikiPage"]);
   $msgs[] = "Person wurde aktualisiert.";

header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
   foreach ($_POST["_contactDetails_id"] as $i => $id) {
     if ($id < 0 && (trim($_POST["_contactDetails_details"][$i]) != "")) {
       $ret1 = dbPersonInsertContact($_POST["id"],trim($_POST["_contactDetails_type"][$i]),trim($_POST["_contactDetails_details"][$i]),$_POST["_contactDetails_fromWiki"][$i],$_POST["_contactDetails_active"][$i]);
       $msgs[] = "Kontaktdaten wurden ergänzt.";
       $needReload = true;
     } elseif ($id < 0) {
       /* leere neue Kontaktdaten */
       $ret1 = true;
     } elseif (trim($_POST["_contactDetails_details"][$i]) != "") {
       $ret1 = dbPersonUpdateContact($id, $_POST["id"],trim($_POST["_contactDetails_type"][$i]),trim($_POST["_contactDetails_details"][$i]),$_POST["_contactDetails_fromWiki"][$i],$_POST["_contactDetails_active"][$i]);
       $msgs[] = "Kontaktdaten wurden aktualisiert.";
       $needReload = true;
     } else {
       $ret1 = dbPersonDeleteContact($id);
       $msgs[] = "Kontaktdaten wurden entfernt.";
       $needReload = true;
     }
     $ret = $ret && $ret1;
header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
   }
header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");
   $dbDeferRefresh = false;
   dbRefreshPersonCurrent($_POST["id"]);
header("X-Trace-".basename(__FILE__)."-".__LINE__.": ".round((microtime(true) - $time_start)*1000,2)."ms");

  break;
  case "person.insert":
   $dbDeferRefresh = true;
   $quiet = isset($_FILES["csv"]) && !empty($_FILES["csv"]["tmp_name"]);
   $ret = true;
   if (!empty($_POST["email"]) || !$quiet) {
     $ret = dbPersonInsert(trim($_POST["name"]),$_POST["email"],trim($_POST["unirzlogin"]),trim($_POST["username"]),$_POST["password"],$_POST["canlogin"], $_POST["wikiPage"], $quiet);
     $personId = $ret;
     $msgs[] = "Person {$_POST["name"]} wurde ".(($ret !== false) ? "": "nicht ")."angelegt.";
     if ($ret !== false) {
       $target = $_SERVER["PHP_SELF"]."?tab=person.edit&person_id=".$ret;
       foreach (array_keys($_POST["_contactDetails_details"]) as $i) {
         if (trim($_POST["_contactDetails_details"][$i]) != "") {
           $ret1 = dbPersonInsertContact($personId,trim($_POST["_contactDetails_type"][$i]),trim($_POST["_contactDetails_details"][$i]),$_POST["_contactDetails_fromWiki"][$i],$_POST["_contactDetails_active"][$i]);
           $msgs[] = "Kontaktdaten wurden ergänzt.";
           $ret = $ret && $ret1;
         }
       }
     }
   }
   if ($quiet) {
     if (($handle = fopen($_FILES["csv"]["tmp_name"], "r")) !== FALSE) {
       fgetcsv($handle, 1000, ",");
       while (($data = fgetcsv($handle, 0, ",", '"')) !== FALSE) {
         $email = strtolower(trim($data[1]));
         $r = verify_tui_mail($email);
         if (is_array($r) && isset($r["mail"])) {
           $emails = $r["mail"];
         } else {
           $emails = [$email];
         }
         $ret2 = dbPersonInsert(trim($data[0]),$emails,trim((string)$data[2]),"","",$_POST["canlogin"], "", $quiet);
         $msgs[] = "Person {$data[0]} <{$data[1]}> wurde ".(($ret2 !== false) ? "": "nicht ")."angelegt.";
         $ret = $ret && $ret2;
       }
       fclose($handle);
     }
   }
   $dbDeferRefresh = false;
   dbRefreshPersonCurrent($quiet ? NULL : $personId);
  break;
  case "rolle_person.insert":
   if ($_POST["person_id"] < 0) {
     $ret = false;
     $msgs[] = "Keine Person ausgewählt.";
   } else if ($_POST["rolle_id"] < 0) {
     $ret = false;
     $msgs[] = "Keine Rolle ausgewählt.";
   } else {
     $ret = dbPersonInsertRolle($_POST["person_id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["lastCheck"], $_POST["kommentar"]);
     $msgs[] = "Person-Rollen-Zuordnung wurde angelegt.";
   }
  break;
  case "rolle_person.update":
   $ret = dbPersonUpdateRolle($_POST["id"], $_POST["person_id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["lastCheck"],$_POST["kommentar"]);
   $msgs[] = "Person-Rollen-Zuordnung wurde aktualisiert.";
  break;
  case "rolle_person.delete":
   $ret = dbPersonDeleteRolle($_POST["id"]);
   $msgs[] = "Person-Rollen-Zuordnung wurde gelöscht.";
  break;
  case "rolle_person.disable":
   $ret = dbPersonDisableRolle($_POST["id"]);
   $msgs[] = "Person-Rollen-Zuordnung wurde beendet.";
  break;
  case "gruppe.insert":
   $ret = dbGruppeInsert($_POST["name"], $_POST["beschreibung"]);
   $msgs[] = "Gruppe wurde erstellt.";
   if ($ret !== false)
     $target = $_SERVER["PHP_SELF"]."?tab=gruppe.edit&gruppe_id=".$ret;
  break;
  case "gruppe.update":
   $ret = dbGruppeUpdate($_POST["id"], $_POST["name"], $_POST["beschreibung"]);
   $msgs[] = "Gruppe wurde aktualisiert.";
  break;
  case "gruppe.delete":
   $ret = dbGruppeDelete($_POST["id"]);
   $msgs[] = "Gruppe wurde entfernt.";
  break;
  case "rolle_gruppe.delete":
   $ret = dbGruppeDropRolle($_POST["gruppe_id"], $_POST["rolle_id"]);
   $msgs[] = "Gruppen-Rollenzuordnung wurde entfernt.";
  break;
  case "rolle_gruppe.insert":
   $ret = dbGruppeInsertRolle($_POST["gruppe_id"], $_POST["rolle_id"]);
   $msgs[] = "Gruppen-Rollenzuordnung wurde eingetragen.";
  break;
  case "gremium.insert":
   $ret = dbGremiumInsert($_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengang_short"], $_POST["studiengang_english"], $_POST["studiengangabschluss"], $_POST["wiki_members"], $_POST["wiki_members_table"], $_POST["wiki_members_fulltable"], $_POST["active"], $_POST["wiki_members_fulltable2"]);
   $msgs[] = "Gremium wurde angelegt.";
   if ($ret !== false)
     $target = $_SERVER["PHP_SELF"]."?tab=gremium.edit&gremium_id=".$ret;
  break;
  case "gremium.update":
   $ret = dbGremiumUpdate($_POST["id"], $_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengang_short"], $_POST["studiengang_english"], $_POST["studiengangabschluss"], $_POST["wiki_members"], $_POST["wiki_members_table"], $_POST["wiki_members_fulltable"], $_POST["active"], $_POST["wiki_members_fulltable2"]);
   $msgs[] = "Gremium wurde geändert.";
  break;
  case "gremium.delete":
   $ret = dbGremiumDelete($_POST["id"]);
   $msgs[] = "Gremium wurde entfernt.";
  break;
  case "gremium.disable":
   $ret = dbGremiumDisable($_POST["id"]);
   $msgs[] = "Gremium wurde deaktiviert.";
  break;
  case "rolle_gremium.insert":
   $spiGroupId = $_POST["spiGroupId"];
   if ($spiGroupId === "") $spiGroupId = NULL;
   $ret = dbGremiumInsertRolle($_POST["gremium_id"],$_POST["name"],$_POST["active"],$spiGroupId,$_POST["numPlatz"],$_POST["wahlDurchWikiSuffix"],$_POST["wahlPeriodeDays"],$_POST["wiki_members_roleAsColumnTable"],$_POST["wiki_members_roleAsColumnTableExtended"],$_POST["wiki_members_roleAsMasterTable"],$_POST["wiki_members_roleAsMasterTableExtended"],$_POST["wiki_members"]);
   $msgs[] = "Rolle wurde angelegt.";
   if ($ret !== false)
     $target = $_SERVER["PHP_SELF"]."?tab=rolle.edit&rolle_id=".$ret;
  break;
  case "rolle_gremium.update":
   $spiGroupId = $_POST["spiGroupId"];
   if ($spiGroupId === "") $spiGroupId = NULL;
   $ret = dbGremiumUpdateRolle($_POST["id"], $_POST["name"],$_POST["active"],$spiGroupId,$_POST["numPlatz"],$_POST["wahlDurchWikiSuffix"],$_POST["wahlPeriodeDays"],$_POST["wiki_members_roleAsColumnTable"],$_POST["wiki_members_roleAsColumnTableExtended"],$_POST["wiki_members_roleAsMasterTable"],$_POST["wiki_members_roleAsMasterTableExtended"],$_POST["wiki_members"]);
   $msgs[] = "Rolle wurde umbenannt.";
  break;
  case "rolle_gremium.delete":
   $ret = dbGremiumDeleteRolle($_POST["id"]);
   $msgs[] = "Rolle wurde entfernt.";
  break;
  case "rolle_gremium.disable":
   $ret = dbGremiumDisableRolle($_POST["id"]);
   $msgs[] = "Rolle wurde deaktiviert.";
  break;
  case "rolle_person.bulkinsert":
   if ($_POST["rolle_id"] < 0) {
     $ret = false;
     $msgs[] = "Keine Rolle ausgewählt.";
   } else {
     $dbDeferRefresh = true;
     $emails = explode("\n", $_REQUEST["email"]);
     foreach ($emails as $email) {
       $email = strtolower(trim($email));
       if (empty($email)) continue;

       # fetch using primary email
       $person = getPersonDetailsByMail($email);

       # fallback: fetch using alternative uni email
       if ($person === false) {
         $r = verify_tui_mail($email);
       }
       if ($person === false && $r !== false && isset($r["mail"])) {
         foreach ($r["mail"] as $tmp) {
           $person = getPersonDetailsByMail($tmp);
           if ($person !== false) break;
         }
       }

       # fallback: create person
       if (($person === false) && $_POST["personfromuni"] && ($r !== false) && isset($r["givenName"]) && isset($r["sn"])) {
         $name = ucfirst(trim($r["givenName"]))." ".ucfirst(trim($r["sn"]));
         if (isset($r["mail"])) {
           $newemails = $r["mail"];
         } else {
           $newemails = [$email];
         }
         $ret = dbPersonInsert($name,$newemails,"" /* rz */,"" /* usr */,"" /* pwd */,true /* canLogin */, "" /* wiki */, false);
         if ($ret !== false) {
           $person = ["id" => $ret];
           $msgs[] = "OK  Person $name <$email> wurde angelegt.";
         } else {
           $msgs[] = "ERR Person $name <$email> wurde nicht angelegt.";
         }
       }

       # check person found
       if ($person === false) {
         $msgs[] = "ERR Personen $email wurde nicht gefunden.";
         continue;
       }

       # avoid duplicate membership / create membership
       $rel_mems = getActiveMitgliedschaftByMail(trim($email), $_POST["rolle_id"]);
       if ($rel_mems === false || count($rel_mems) == 0 || $_POST["duplicate"] == "ignore") {
         $ret2 = dbPersonInsertRolle($person["id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["lastCheck"],$_POST["kommentar"]);
         $ret = $ret && $ret2;
         $msgs[] = "OK  Person-Rollen-Zuordnung für $email wurde erstellt.";
       } else {
         $msgs[] = "IGN Person-Rollen-Zuordnung für $email wurde übersprungen.";
       }
     } // foreach
     $dbDeferRefresh = false;
     dbRefreshPersonCurrent(NULL);
     $ret = true;
   }
  break;
  case "rolle_person.bulkdisable":
   $dbDeferRefresh = true;
   if (is_array($_REQUEST["email"])) {
     $_REQUEST["email"] = implode("\n", $_REQUEST["email"]);
   }
   $emails = explode("\n", $_REQUEST["email"]);
   array_walk($emails, create_function('&$val', '$val = trim($val);'));
   $emails = array_unique($emails);

   $ret = true;
   foreach ($emails as $email) {
     $email = trim($email);
     if (empty($email)) continue;
     $rel_mems = getActiveMitgliedschaftByMail($email, $_POST["rolle_id"]);
     if ($rel_mems === false) {
       $msgs[] = "Personen-Rollenzuordnung: $email wurde nicht gefunden.";
     } else {
       foreach ($rel_mems as $rel_mem) {
         $ret2 = dbPersonDisableRolle($rel_mem["id"], $_POST["bis"], $_POST["grund"]);
         $ret = $ret && $ret2;
       }
       $msgs[] = "Person-Rollen-Zuordnung für $email wurde beendet.";
     }
   }
   $dbDeferRefresh = false;
   dbRefreshPersonCurrent(NULL);
  break;
  default:
   if ($logId !== false) {
     logAppend($logId, "__result", "invalid action");
   }
   die("Aktion nicht bekannt.");
  endswitch;
 } /* switch */

 if ($logId !== false) {
   logAppend($logId, "__result", ($ret !== false) ? "ok" : "failed");
   logAppend($logId, "__result_msg", $msgs);
 }

 $result = Array();
 $result["msgs"] = $msgs;
 $result["ret"] = ($ret !== false);
 $result["needReload"] = ($needReload !== false);
 if ($target !== false)
   $result["target"] = $target;

 header("Content-Type: text/json; charset=UTF-8");
 echo json_encode($result);
 exit;
}

require "../template/header.tpl";
require "../template/admin.tpl";

if (!isset($_REQUEST["tab"])) {
  $_REQUEST["tab"] = "person";
}

switch($_REQUEST["tab"]) {
  case "person":
  require "../template/admin_personen.tpl";
  break;
  case "person.new":
  require "../template/admin_personen_new.tpl";
  break;
  case "person.edit":
  require "../template/admin_personen_edit.tpl";
  break;
  case "person.delete":
  require "../template/admin_personen_delete.tpl";
  break;
  case "gremium":
  require "../template/admin_gremien.tpl";
  break;
  case "gremium.new":
  require "../template/admin_gremium_new.tpl";
  break;
  case "gremium.edit":
  require "../template/admin_gremium_edit.tpl";
  break;
  case "gremium.delete":
  require "../template/admin_gremium_delete.tpl";
  break;
  case "rolle.new":
  require "../template/admin_rolle_new.tpl";
  break;
  case "rolle.edit":
  require "../template/admin_rolle_edit.tpl";
  break;
  case "rolle.delete":
  require "../template/admin_rolle_delete.tpl";
  break;
  case "rel_mitgliedschaft.new":
  require "../template/admin_rel_mitgliedschaft_new.tpl";
  break;
  case "rel_mitgliedschaft.edit":
  require "../template/admin_rel_mitgliedschaft_edit.tpl";
  break;
  case "rel_mitgliedschaft.delete":
  require "../template/admin_rel_mitgliedschaft_delete.tpl";
  break;
  case "rel_mitgliedschaft_multiple.new":
  require "../template/admin_rel_mitgliedschaft_multiple_new.tpl";
  break;
  case "rel_mitgliedschaft_multiple.delete":
  require "../template/admin_rel_mitgliedschaft_multiple_delete.tpl";
  break;
  case "rel_mitgliedschaft_multiple_tutor.new":
  require "../template/admin_rel_mitgliedschaft_multiple_tutor_new.tpl";
  break;
  case "rel_rolle_gruppe.new":
  require "../template/admin_rel_rolle_gruppe_new.tpl";
  break;
  case "rel_rolle_gruppe.delete":
  require "../template/admin_rel_rolle_gruppe_delete.tpl";
  break;
  case "rel_rolle_mailingliste.new":
  require "../template/admin_rel_rolle_mailingliste_new.tpl";
  break;
  case "rel_rolle_mailingliste.delete":
  require "../template/admin_rel_rolle_mailingliste_delete.tpl";
  break;
  case "gruppe":
  require "../template/admin_gruppen.tpl";
  break;
  case "gruppe.new":
  require "../template/admin_gruppen_new.tpl";
  break;
  case "gruppe.edit":
  require "../template/admin_gruppen_edit.tpl";
  break;
  case "gruppe.delete":
  require "../template/admin_gruppen_delete.tpl";
  break;
  case "mailingliste":
  require "../template/admin_mailinglisten.tpl";
  break;
  case "mailingliste.new":
  require "../template/admin_mailinglisten_new.tpl";
  break;
  case "mailingliste.edit":
  require "../template/admin_mailinglisten_edit.tpl";
  break;
  case "mailingliste.delete":
  require "../template/admin_mailinglisten_delete.tpl";
  break;
  case "mailingliste_mailman.new":
  require "../template/admin_mailingliste_mailman_new.tpl";
  break;
  case "mailingliste_mailman.edit":
  require "../template/admin_mailingliste_mailman_edit.tpl";
  break;
  case "mailingliste_mailman.delete":
  require "../template/admin_mailingliste_mailman_delete.tpl";
  break;
  case "export":
  require "../template/admin_export.tpl";
  break;
  case "help":
  require "../template/admin_help.tpl";
  break;
  default:
  die("invalid tab name");
}

require "../template/admin_footer.tpl";
#require "../template/footer.tpl";

exit;

