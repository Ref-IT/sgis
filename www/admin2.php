<?php

global $attributes, $logoutUrl, $ADMINGROUP, $nonce;
ob_start('ob_gzhandler');

require_once "../lib/inc.all.php";
requireGroup($ADMINGROUP);

if (isset($_POST["action"])) {
 $msgs = Array();
 $ret = false;
 if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
  $msgs[] = "Formular veraltet - CSRF Schutz aktiviert.";
 } else {
  $logId = logThisAction();
  switch ($_POST["action"]):
  case "person.table":
   header("Content-Type: text/json; charset=UTF-8");
   $columns = array(
     array( 'db' => 'id',                 'dt' => 'id' ),
     array( 'db' => 'email',              'dt' => 'email' ),
     array( 'db' => 'name',               'dt' => 'name' ),
     array( 'db' => 'username',           'dt' => 'username' ),
//     array( 'db' => 'password', 'dt' => 3 ),
     array( 'db' => 'unirzlogin',         'dt' => 'unirzlogin',
       'formatter' => function( $d, $row ) {
         return str_replace("@tu-ilmenau.de","",$d);
       }
     ),
     array( 'db' => 'lastLogin',          'dt' => 'lastLogin',
       'formatter' => function( $d, $row ) {
         return $d ? date( 'Y-m-d', strtotime($d)) : "";
       }
     ),
     array( 'db'    => 'canLoginCurrent', 'dt'    => 'canLogin',
       'formatter' => function( $d, $row ) {
         return (!$d) ? "ja" : "nein";
       }
     ),
     array( 'db'    => 'active',          'dt'    => 'active',
       'formatter' => function( $d, $row ) {
         return $d ? "ja" : "nein";
       }
     ),
   );
   echo json_encode(
     SSP::simple( $_POST, ["dsn" => $DB_DSN, "user" => $DB_USERNAME, "pass" => $DB_PASSWORD], "{$DB_PREFIX}person_current", /* primary key */ "id", $columns )
   );
  exit;
  case "gremium.table":
   header("Content-Type: text/json; charset=UTF-8");
   $columns = array(
     array( 'db' => 'id',                    'dt' => 'id' ),
     array( 'db' => 'name',                  'dt' => 'name' ),
     array( 'db' => 'fakultaet',             'dt' => 'fakultaet' ),
     array( 'db' => 'studiengang',           'dt' => 'studiengang' ),
     array( 'db' => 'studiengangabschluss',  'dt' => 'studiengangabschluss' ),
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
  case "mailingliste.insert":
   $ret = dbMailinglisteInsert($_POST["address"], $_POST["url"], $_POST["password"]);
   $msgs[] = "Mailingliste wurde erstellt.";
  break;
  case "mailingliste.update":
   $ret = dbMailinglisteUpdate($_POST["id"], $_POST["address"], $_POST["url"], $_POST["password"]);
   $msgs[] = "Mailingliste wurde aktualisiert.";
  break;
  case "mailingliste.delete":
   $ret = dbMailinglisteDelete($_POST["id"]);
   $msgs[] = "Mailingliste wurde entfernt.";
  break;
  case "rolle_mailingliste.delete":
   $ret = dbMailinglisteDropRolle($_POST["mailingliste_id"], $_POST["rolle_id"]);
   $msgs[] = "Mailinglisten-Rollenzuordnung wurde entfernt.";
  break;
  case "rolle_mailingliste.insert":
   $ret = dbMailinglisteInsertRolle($_POST["mailingliste_id"], $_POST["rolle_id"]);
   $msgs[] = "Mailinglisten-Rollenzuordnung wurde eingetragen.";
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
   $ret = dbPersonUpdate($_POST["id"],trim($_POST["name"]),trim($_POST["email"]),trim($_POST["unirzlogin"]),trim($_POST["username"]),$_POST["password"],$_POST["canlogin"]);
   $msgs[] = "Person wurde aktualisiert.";
  break;
  case "person.insert":
   $quiet = isset($_FILES["csv"]) && !empty($_FILES["csv"]["tmp_name"]);
   $ret = true;
   if (!empty($_POST["email"])) {
     $ret = dbPersonInsert(trim($_POST["name"]),trim($_POST["email"]),trim($_POST["unirzlogin"]),trim($_POST["username"]),$_POST["password"],$_POST["canlogin"], $quiet);
     $msgs[] = "Person {$_POST["name"]} wurde ".($ret ? "": "nicht ")."angelegt.";
   }
   if ($quiet) {
     if (($handle = fopen($_FILES["csv"]["tmp_name"], "r")) !== FALSE) {
       fgetcsv($handle, 1000, ",");
       while (($data = fgetcsv($handle, 0, ",", '"')) !== FALSE) {
         $ret2 = dbPersonInsert(trim($data[0]),trim($data[1]),trim((string)$data[2]),"","",$_POST["canlogin"], $quiet);
         $msgs[] = "Person {$data[0]} <{$data[1]}> wurde ".($ret2 ? "": "nicht ")."angelegt.";
         $ret = $ret && $ret2;
       }
       fclose($handle);
     }
   }
  break;
  case "rolle_person.insert":
   $ret = dbPersonInsertRolle($_POST["person_id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["kommentar"]);
   $msgs[] = "Person-Rollen-Zuordnung wurde angelegt.";
  break;
  case "rolle_person.update":
   $ret = dbPersonUpdateRolle($_POST["id"], $_POST["person_id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["kommentar"]);
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
   $ret = dbGremiumInsert($_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengangabschluss"], $_POST["wiki_members"], $_POST["active"]);
   $msgs[] = "Gremium wurde angelegt.";
  break;
  case "gremium.update":
   $ret = dbGremiumUpdate($_POST["id"], $_POST["name"], $_POST["fakultaet"], $_POST["studiengang"], $_POST["studiengangabschluss"], $_POST["wiki_members"], $_POST["active"]);
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
   $ret = dbGremiumInsertRolle($_POST["gremium_id"],$_POST["name"],$_POST["active"],$spiGroupId);
   $msgs[] = "Rolle wurde angelegt.";
  break;
  case "rolle_gremium.update":
   $spiGroupId = $_POST["spiGroupId"];
   if ($spiGroupId === "") $spiGroupId = NULL;
   $ret = dbGremiumUpdateRolle($_POST["id"], $_POST["name"],$_POST["active"],$spiGroupId);
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
   $emails = explode("\n", $_REQUEST["email"]);
   foreach ($emails as $email) {
     $email = trim($email);
     if (empty($email)) continue;
     $person = getPersonDetailsByMail($email);
     if ($person === false) {
       $msgs[] = "Personen-Rollenzuordnung: $email wurde nicht gefunden.";
       continue;
     }
     $rel_mems = getActiveMitgliedschaftByMail(trim($email), $_POST["rolle_id"]);
     if ($rel_mems === false || count($rel_mems) == 0 || $_POST["duplicate"] == "ignore") {
       $ret2 = dbPersonInsertRolle($person["id"],$_POST["rolle_id"],$_POST["von"],$_POST["bis"],$_POST["beschlussAm"],$_POST["beschlussDurch"],$_POST["kommentar"]);
       $ret = $ret && $ret2;
       $msgs[] = "Person-Rollen-Zuordnung für $email wurde erstellt.";
     } else {
       $msgs[] = "Person-Rollen-Zuordnung für $email wurde übersprungen.";
     }
   }
   $ret = true;
  break;
  case "rolle_person.bulkdisable":
   $emails = explode("\n", $_REQUEST["email"]);
   $ret = true;
   foreach ($emails as $email) {
     $email = trim($email);
     if (empty($email)) continue;
     $rel_mems = getActiveMitgliedschaftByMail($email, $_POST["rolle_id"]);
     if ($rel_mems === false) {
       $msgs[] = "Personen-Rollenzuordnung: $email wurde nicht gefunden.";
     } else {
       foreach ($rel_mems as $rel_mem) {
         $ret2 = dbPersonDisableRolle($rel_mem["id"], $_POST["bis"]);
         $ret = $ret && $ret2;
       }
       $msgs[] = "Person-Rollen-Zuordnung für $email wurde beendet.";
     }
   }
  break;
  default:
   logAppend($logId, "__result", "invalid action");
   die("Aktion nicht bekannt.");
  endswitch;
 } /* switch */

 logAppend($logId, "__result", $ret ? "ok" : "failed");
 logAppend($logId, "__result_msg", $msgs);

 $result = Array();
 $result["msgs"] = $msgs;
 $result["ret"] = $ret;

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
  case "person.edit":
  require "../template/admin_personen_edit.tpl";
  break;
  case "person.delete":
  require "../template/admin_personen_delete.tpl";
  break;
  case "gremium":
  require "../template/admin_gremien.tpl";
  break;
  case "gremium.edit":
  require "../template/admin_gremium_edit.tpl";
  break;
  case "gremium.delete":
  require "../template/admin_gremium_delete.tpl";
  break;
  case "rolle.edit":
  require "../template/admin_rolle_edit.tpl";
  break;
  case "rolle.delete":
  require "../template/admin_rolle_delete.tpl";
  break;
  case "rel_mitgliedschaft.edit":
  require "../template/admin_rel_mitgliedschaft_edit.tpl";
  break;
  case "rel_mitgliedschaft.delete":
  require "../template/admin_rel_mitgliedschaft_delete.tpl";
  break;
  case "gruppe":
  require "../template/admin_gruppen.php";
  break;
  case "mailingliste":
  require "../template/admin_mailinglisten.php";
  break;
  case "export":
  require "../template/admin_export.php";
  break;
  case "help":
  require "../template/admin_help.php";
  break;
  default:
  die("invalid tab name");
}

require "../template/admin_footer.tpl";
require "../template/footer.tpl";

exit;
