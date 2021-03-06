<?php
global $ADMINGROUP, $wikiperson, $skippedPages;
require_once "../lib/inc.all.php";

prof_flag("Start");

if (isset($_REQUEST["autoExportPW"])) {
  requireExportAutoPW();
} else {
  requireGroup($ADMINGROUP);
}

function progress($msg) {
  if (!isset($_REQUEST["autoExportPW"])) return;
  echo "$msg<br>\n";
  flush();
}

$validnonce = false;
if (isset($_REQUEST["nonce"]) && $_REQUEST["nonce"] === $nonce) {
 $validnonce = true;
}

prof_flag("Init");

$rollen = getAlleRolle();
$mapping = Array();
$mapping_table = Array();
$mapping_fulltable = Array();
$mapping_coltable = Array();
$mapping_colextable = Array();
$mapping_mastertable = Array();
$mapping_nachbesetzung = Array();
$mapping_ptable = Array();
$name_gremien = Array();
$name_rollen = Array();

$wikiprefix = "sgis:mitglieder:";
$wikiperson = "person:";
$skippedPages = [];

function escapeContactForWiki($str) {
  return preg_replace('#[^a-zA-Z0-9 -_()]/#','',$str);
}

function startsWith($prefix, $str) {
  return substr($str, 0, strlen($prefix)) == $prefix;
}

function skipWiki($wiki) {
  global $skippedPages;
  if (empty($wiki)) return true;
  if (!isset($_REQUEST["wiki"])) return false;
  if (!is_array($_REQUEST["wiki"]) && substr($_REQUEST["wiki"],-1) != "*" && cleanID($_REQUEST["wiki"]) != cleanID($wiki)) {
    $skippedPages[] = $wiki;
    return true;
  }
  if (!is_array($_REQUEST["wiki"]) && substr($_REQUEST["wiki"],-1) == "*" && !startsWith(cleanID(substr($_REQUEST["wiki"],0,-1)), cleanID($wiki))) {
    $skippedPages[] = $wiki;
    return true;
  }
  if (is_array($_REQUEST["wiki"]) && !in_array(cleanID($wiki), array_map("cleanID",$_REQUEST["wiki"]))) {
    $skippedPages[] = $wiki;
    return true;
  }
  return false;
}

$pp = getAllePersonCurrent();
$contactPersonen = [];
foreach ($pp as $p) {
  if (!$p["canLoginCurrent"]) continue;
  $wiki = ($p["wikiPage"] !== NULL) ? $p["wikiPage"] : $wikiperson.$p["name"];
  $wiki = cleanID($wiki);
  if (substr($wiki, 0, strlen($wikiperson)) != $wikiperson) continue;
  if (skipWiki($wiki)) continue;
  $contactPersonen[] = [ "person_id" => $p["id"], "wiki" => $wiki, "old" => getPersonContactDetails($p["id"]) ];
}

prof_flag("DB Fetch Done");

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach ($rollen as $rolle) {
  $wiki = cleanID($rolle["gremium_wiki_members"]);
  if (skipWiki($wiki)) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Gremium: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
  $gremium_id = $rolle["gremium_id"];
  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    $active = ($person["active"] == 1) ? "active" : "inactive";
    $mapping[$wiki][$gremium_id][$rolle_id][$active][$rel_id] = $person;
  }
}

// group roles by wiki page, skip empty wiki pages, and list all active persons
foreach ($rollen as $rolle) {
  $key = "rolle_wiki_members";
  if (strpos($rolle[$key],"#") !== false) {
    list ($wiki1, $wiki2) = explode("#",$rolle[$key],2);
    $wiki = cleanID($wiki1);
    if (strpos($wiki2, " ") !== false) {
      $section_name = $wiki2;
    } elseif (preg_match('/^[0-9]+$/', $wiki2)) { # nur Zahlen -> Reihenfolge only
      $section_name = $wiki2." ".$rolle["gremium_name"];
    } elseif (preg_match('/^([0-9]+)\?(.*)$/', $wiki2, $treffer)) { # nur Zahlen -> Reihenfolge only
      $section_name = $treffer[1]." ".$rolle["gremium_name"]."?".$treffer[2];
    } else { # kein Leerzeichen und nicht nur Zahlen -> d.h. keine Reihenfolge
      $section_name = " ".$wiki2;
    }
  } elseif (strpos($rolle[$key],"?") !== false) { # keine # aber ?
    list ($wiki1, $wiki2) = explode("?",$rolle[$key],2);
    $wiki = cleanID($wiki1);
    $section_name = " ".$rolle["gremium_name"]."?".$wiki2;
  } else {
    $wiki = cleanID($rolle[$key]);
    $section_name = " ".$rolle["gremium_name"];
  }
  if (strpos($section_name,"?") !== false) {
    list ($wiki1, $wiki2) = explode("?",$section_name,2);
    $section_name = $wiki1;
    $flags = $wiki2;
  } else {
    $flags = "";
  }
  if (skipWiki($wiki)) continue;
  if ($rolle["gremium_active"] == 0) continue;
  if ($rolle["rolle_active"] == 0) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Gremium: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    $person_id = $person["id"];
    if ($person["active"] != 1) continue;
    if (!isset($mapping_ptable[$wiki][$section_name][$person_id]["flags"]))
      $mapping_ptable[$wiki][$section_name][$person_id]["flags"] = "";
    $mapping_ptable[$wiki][$section_name][$person_id]["data"][$rel_id] = $person;
    $mapping_ptable[$wiki][$section_name][$person_id]["flags"] .= $flags;
  }
}

prof_flag("gremium_wiki_members");

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach ($rollen as $rolle) {
  $wiki = cleanID($rolle["gremium_wiki_members_table"]);
  if (skipWiki($wiki)) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Gremium: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  $mapping_table[$wiki][$gremium_name][$gremium_id][$rolle_id]["active"] = [];
  $mapping_table[$wiki][$gremium_name][$gremium_id][$rolle_id]["inactive"] = [];
  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    $active = ($person["active"] == 1) ? "active" : "inactive";
    $mapping_table[$wiki][$gremium_name][$gremium_id][$rolle_id][$active][$rel_id] = $person;
  }
}

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach (["gremium_wiki_members_fulltable","gremium_wiki_members_fulltable2"] as $key) {
  prof_flag($key);

 foreach ($rollen as $rolle) {
  $wiki = cleanID($rolle[$key]);
  if (skipWiki($wiki)) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Gremium: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $gremium_fak = $rolle["gremium_fakultaet"];
  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  $mapping_fulltable[$wiki][$gremium_fak][$gremium_id][$rolle_id]["active"] = [];
  $mapping_fulltable[$wiki][$gremium_fak][$gremium_id][$rolle_id]["inactive"] = [];
  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    $active = ($person["active"] == 1) ? "active" : "inactive";
    $mapping_fulltable[$wiki][$gremium_fak][$gremium_id][$rolle_id][$active][$rel_id] = $person;
  }
 }
}

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach (["rolle_wiki_members_roleAsColumnTable"] as $key) {
 foreach ($rollen as $rolle) {
  if (strpos($rolle[$key],"#") === false) {
    $wiki = cleanID($rolle[$key]);
    $rolle_name = $rolle["rolle_name"];
  } else {
    list ($wiki1, $wiki2) = explode("#",$rolle[$key],2);
    $wiki = cleanID($wiki1);
    $rolle_name = $wiki2;
  }
  if (skipWiki($wiki)) continue;
  if ($rolle["gremium_active"] == 0) continue;
  if ($rolle["rolle_active"] == 0) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["rolle_name"]} {$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Rolle: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $gremium_fak = $rolle["gremium_fakultaet"];
  $gremium_tmp = preg_replace("/\s+/"," ",trim("{$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
  $gremium_tmp = str_replace(["Ä", "Ö", "Ü", "ä", "ö", "ü", "ß"], ["Ae", "Oe", "Ue", "ae", "oe", "ue", "ss"], $gremium_tmp);

  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  $mapping_coltable[$wiki][$rolle_name][$gremium_tmp][$gremium_id][$rolle_id]["active"] = [];
  foreach ($personen as $person) {
    if ($person["active"] != 1) continue;
    $rel_id = $person["rel_id"];
    $mapping_coltable[$wiki][$rolle_name][$gremium_tmp][$gremium_id][$rolle_id]["active"][$rel_id] = $person;
  }
 }
}

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach (["rolle_wiki_members_roleAsColumnTableExtended"] as $key) {
  prof_flag($key);
 foreach ($rollen as $rolle) {
  if (strpos($rolle[$key],"#") === false) {
    $wiki = cleanID($rolle[$key]);
    $rolle_name = $rolle["rolle_name"];
  } else {
    list ($wiki1, $wiki2) = explode("#",$rolle[$key],2);
    $wiki = cleanID($wiki1);
    $rolle_name = $wiki2;
  }
  if (skipWiki($wiki)) continue;
  if ($rolle["gremium_active"] == 0) continue;
  if ($rolle["rolle_active"] == 0) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["rolle_name"]} {$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Rolle: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $gremium_fak = $rolle["gremium_fakultaet"];
  $gremium_tmp = preg_replace("/\s+/"," ",trim("{$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
  $gremium_tmp = str_replace(["Ä", "Ö", "Ü", "ä", "ö", "ü", "ß"], ["Ae", "Oe", "Ue", "ae", "oe", "ue", "ss"], $gremium_tmp);

  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  $mapping_colextable[$wiki][$rolle_name][$gremium_tmp][$gremium_id][$rolle_id]["active"] = [];
  foreach ($personen as $person) {
    if ($person["active"] != 1) continue;
    $rel_id = $person["rel_id"];
    $mapping_colextable[$wiki][$rolle_name][$gremium_tmp][$gremium_id][$rolle_id]["active"][$rel_id] = $person;
  }
 }
}

// group roles by wiki page, skip empty wiki pages, and list all persons
foreach (["rolle_wiki_members_roleAsMasterTable","rolle_wiki_members_roleAsMasterTableExtended"] as $key) {
  prof_flag($key);
 $withContactDetails = ($key == "rolle_wiki_members_roleAsMasterTableExtended");
 foreach ($rollen as $rolle) {
  if (strpos($rolle[$key],"#") !== false) {
    list ($wiki1, $wiki2) = explode("#",$rolle[$key],2);
    $wiki = cleanID($wiki1);
    if (strpos($wiki2, " ") !== false) {
      $section_name = $wiki2;
    } elseif (preg_match('/^[0-9]+$/', $wiki2)) { # nur Zahlen -> Reihenfolge only
      $section_name = $wiki2." ".$rolle["gremium_name"];
    } elseif (preg_match('/^([0-9]+)\?(.*)$/', $wiki2, $treffer)) { # nur Zahlen -> Reihenfolge only
      $section_name = $treffer[1]." ".$rolle["gremium_name"]."?".$treffer[2];
    } else { # kein Leerzeichen und nicht nur Zahlen -> d.h. keine Reihenfolge
      $section_name = " ".$wiki2;
    }
  } elseif (strpos($rolle[$key],"?") !== false) { # keine # aber ?
    list ($wiki1, $wiki2) = explode("?",$rolle[$key],2);
    $wiki = cleanID($wiki1);
    $section_name = " ".$rolle["gremium_name"]."?".$wiki2;
  } else {
    $wiki = cleanID($rolle[$key]);
    $section_name = " ".$rolle["gremium_name"];
  }
  if (strpos($section_name,"?") !== false) {
    list ($wiki1, $wiki2) = explode("?",$section_name,2);
    $section_name = $wiki1;
    $flags = $wiki2;
  } else {
    $flags = "";
  }
  if (skipWiki($wiki)) continue;
  if ($rolle["gremium_active"] == 0) continue;
  if ($rolle["rolle_active"] == 0) continue;
  if (substr($wiki,0,strlen($wikiprefix)) != $wikiprefix) {
    $gname = preg_replace("/\s+/"," ",trim("{$rolle["rolle_name"]} {$rolle["gremium_name"]} {$rolle["gremium_fakultaet"]} {$rolle["gremium_studiengang"]} {$rolle["gremium_studiengangabschluss"]}"));
    echo "Rolle: ".htmlentities($gname)." hat ungültigen Wiki-Eintrag, der nicht mit :$wikiprefix beginnt.<br/>\n";
  }
  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $gremium_fak = $rolle["gremium_fakultaet"]; if (empty($gremium_fak)) $gremium_fak="";
  $gremium_sg = $rolle["gremium_studiengang"]; if (empty($gremium_sg)) $gremium_sg="";
  $gremium_sga = $rolle["gremium_studiengangabschluss"];
  if (!empty($gremium_sga)) $gremium_sg = "$gremium_sga $gremium_sg";

  $rolle_id = $rolle["rolle_id"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);

  if (!isset($mapping_mastertable[$wiki][$section_name]["data"][$gremium_fak][$gremium_sg])) {
    $mapping_mastertable[$wiki][$section_name]["data"][$gremium_fak][$gremium_sg] = [];
  }
  if (!isset($mapping_mastertable[$wiki][$section_name]["meta"]["withContactDetails"])) {
    $mapping_mastertable[$wiki][$section_name]["meta"]["withContactDetails"] = false;
  }
  $mapping_mastertable[$wiki][$section_name]["meta"]["withContactDetails"] |= $withContactDetails;
  foreach ($personen as $person) {
    if ($person["active"] != 1) continue;
    $rel_id = $person["rel_id"];
    $person_id = $person["id"];
    if ($withContactDetails) {
      $person["_contactDetails"] = getPersonContactDetails($person_id);
    }
    $mapping_mastertable[$wiki][$section_name]["data"][$gremium_fak][$gremium_sg][$person_id]["data"][$rel_id] = $person;
    if (!isset($mapping_mastertable[$wiki][$section_name]["data"][$gremium_fak][$gremium_sg][$person_id]["flags"])) {
      $mapping_mastertable[$wiki][$section_name]["data"][$gremium_fak][$gremium_sg][$person_id]["flags"] = "";
    }
    $mapping_mastertable[$wiki][$section_name]["data"][$gremium_fak][$gremium_sg][$person_id]["flags"] .= $flags;
  }
 }
}

prof_flag("gremium_isempty");

// group roles by wiki page, skip empty wiki pages, and list all persons
$gremium_isempty = [];
foreach ($rollen as $rolle) {
  if (!$rolle["rolle_active"]) continue;
  if (!$rolle["gremium_active"]) continue;

  $rolle_id = $rolle["rolle_id"];
  $gremium_id = $rolle["gremium_id"];
  $personen = getRollePersonen($rolle_id);

  if (!isset($gremium_isempty[$gremium_id]))
    $gremium_isempty[$gremium_id] = true;

  foreach ($personen as $person) {
    if ($person["active"] != 1)
      continue;
    $gremium_isempty[$gremium_id] = false;
  }
}

prof_flag("nachbesetzung");

foreach ($rollen as $rolle) {
  if (!$rolle["rolle_active"]) continue;
  if (!$rolle["gremium_active"]) continue;

  $wiki = cleanID(":sgis:nachbesetzung:".$rolle["rolle_wahlDurchWikiSuffix"]);
  $wiki2 = cleanID(":sgis:nachbesetzung");

  if (skipWiki($wiki) && skipWiki($wiki2)) continue;

  $gremium_id = $rolle["gremium_id"];
  $gremium_name = $rolle["gremium_name"];
  $gremium_fak = $rolle["gremium_fakultaet"];
  $rolle_id = $rolle["rolle_id"];
  $wahlPeriodeDays = $rolle["rolle_wahlPeriodeDays"];
  $name_gremien[$gremium_id] = $rolle;
  $name_rollen[$gremium_id][$rolle_id] = $rolle;
  $personen = getRollePersonen($rolle_id);
  $gremium_sort = $gremium_fak . "|" . $gremium_name;

  $isempty2 = $rolle["rolle_numPlatz"];
  $lastUpdate = NULL;
  foreach ($personen as $person) {
    if ($person["active"] != 1)
      continue;
    $isempty2--;
    $lastCheck = $person["lastCheck"];
    if ($lastCheck === NULL) $lastCheck = $person["von"];
    if ($lastUpdate === NULL || $lastUpdate < $lastCheck)
    $lastUpdate = $lastCheck;
  }

  /* wenn Plätze leer sind ODER das Gremium länger nicht angefasst wurde, soll es gelistet werden */
  if (($isempty2 <= 0) && (!$gremium_isempty[$gremium_id]) && ($lastUpdate !== NULL) && ($lastUpdate > date("Y-m-d", time() - $wahlPeriodeDays * 24 * 3600)))
    continue;
  if (($rolle["rolle_numPlatz"] == 0) && (!$gremium_isempty[$gremium_id]))
    continue;

  if ($wiki !== ":sgis:nachbesetzung:")
    $mapping_nachbesetzung[$wiki][$gremium_sort][$gremium_id][$rolle_id]["active"] = [];
  $mapping_nachbesetzung[$wiki2][$gremium_sort][$gremium_id][$rolle_id]["active"] = [];

  foreach ($personen as $person) {
    $rel_id = $person["rel_id"];
    if ($person["active"] != 1)
      continue;
    if ($wiki !== ":sgis:nachbesetzung:")
      $mapping_nachbesetzung[$wiki][$gremium_sort][$gremium_id][$rolle_id]["active"][$rel_id] = $person;
    $mapping_nachbesetzung[$wiki2][$gremium_sort][$gremium_id][$rolle_id]["active"][$rel_id] = $person;
  }
}

prof_flag("mapping done");

function person2link($person) {
  global $wikiperson;
  $wikiPage = $person["wikiPage"];
  if (empty($wikiPage) || (substr(trim($wikiPage,":"),0,strlen(trim($wikiperson,":"))) != trim($wikiperson,":"))) {
    $wikiPage = "{$wikiperson}{$person["name"]}";
    $line = "[[:{$wikiPage}]]";
  } else {
    $line = "[[:{$wikiPage}|{$person["name"]}]]";
  }
  return $line;
}

// generate wiki pages
function person2string($person) {
  $line = person2link($person)." ";
  if (!empty($person["von"]) && !empty($person["bis"])) {
    $line .= "{$person["von"]} - {$person["bis"]}";
  } else if (!empty($person["von"])) {
    $line .= "seit {$person["von"]}";
  } else if (!empty($person["bis"])) {
    $line .= "bis {$person["bis"]}";
  }
  return "$line";
}

function cmpPerson($a, $b) {
  if ($a["bis"] < $b["bis"]) return -1;
  if ($a["bis"] > $b["bis"]) return 1;
  if ($a["von"] < $b["von"]) return -1;
  if ($a["von"] > $b["von"]) return 1;
  if ($a["email"] < $b["email"]) return -1;
  if ($a["email"] > $b["email"]) return 1;
  return 0;
}

prof_flag("render pages");

$pages = Array();

prof_flag("render pages: mapping");

foreach ($mapping as $wiki => $data) {
  if (skipWiki($wiki)) continue;
  $text = Array();
  foreach ($data as $gremium_id => $data2) {
    $g = $name_gremien[$gremium_id];
    $gname = preg_replace("/\s+/"," ",trim("{$g["gremium_name"]} {$g["gremium_fakultaet"]} {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]}"));
    $text[] = "====== $gname (studentische Mitglieder) (Ilmenau) ======";
    foreach ($data2 as $rolle_id => $personen) {
      $text[] = "";
      $r = $name_rollen[$gremium_id][$rolle_id];
      $text[] = "===== {$r["rolle_name"]} =====";
      if (!empty($personen["active"])) {
        $text[] = "==== aktuelle in {$r["rolle_name"]} in $gname ====";
        uasort($personen["active"], 'cmpPerson');
        foreach($personen["active"] as $person) {
          $text[] = "  * ".person2string($person);
        }
        $text[] = "";
      }
      if (!empty($personen["inactive"])) {
        uasort($personen["inactive"], 'cmpPerson');
        $text[] = "==== ehemalige/zukünftige in {$r["rolle_name"]} in $gname ====";
        foreach($personen["inactive"] as $person) {
          $text[] = "  * ".person2string($person);
        }
        $text[] = "";
      }
    }
  }
  $pages[$wiki]["new"] = $text;
}

prof_flag("render pages: mapping_table");

foreach ($mapping_table as $wiki => $data) {
  if (skipWiki($wiki)) continue;
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];

  ksort($data);
  foreach ($data as $gremium_name => $data1) {

    $gname = trim($gremium_name);
    $text[] = "====== $gname (studentische Mitglieder) (Ilmenau) ======";
    $text[] = "";
    $text[] = "^ Fak ^ Studiengang ^ letzte Aktualisierung ^ Mitglieder ^ Bemerkungen ^";

    foreach ($data1 as $gremium_id => $data2) {
      $g = $name_gremien[$gremium_id];

      if (!$g["gremium_active"]) continue;

      $lastUpdate = NULL;
      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        if (!$r["rolle_active"]) continue;
        if (empty($personen["active"])) continue;
        foreach($personen["active"] as $person) {
          $lastCheck = $person["lastCheck"];
          if ($lastCheck === NULL) $lastCheck = $person["von"];
          if ($lastUpdate === NULL || $lastUpdate < $lastCheck)
            $lastUpdate = $lastCheck;
        }
      }
      if ($lastUpdate === NULL)
        $lastUpdate = "n/a";

      $prefix = preg_replace("/\s+/"," ","| {$g["gremium_fakultaet"]} | {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]} | {$lastUpdate} | ");
      $isempty = true;

      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        $isempty2 = $r["rolle_numPlatz"];

        if (!$r["rolle_active"]) continue;

        if (!empty($personen["active"])) {
          $isempty = false;
          foreach($personen["active"] as $person) {
            $isempty2--;
            $text[] = $prefix.person2string($person)." | {$r["rolle_name"]} |";
            $prefix = "| ::: | ::: | ::: | ";
          }
        }

        for($i = 0; $i < $isempty2; $i++) {
          $isempty = false;
          $text[] = "{$prefix}//unbesetzt// | {$r["rolle_name"]} |";
          $prefix = "| ::: | ::: | ::: | ";
        }
      }
      if($isempty) {
        $text[] = "{$prefix}//unbesetzt// | |";
      }
    }

    $text[] = "";

  } /* $gremium_name */
  $pages[$wiki]["new"] = $text;
}

prof_flag("render pages: mapping_fulltable");

foreach ($mapping_fulltable as $wiki => $data) {
  if (skipWiki($wiki)) continue;
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];

#  $text[] = "====== studentische Mitglieder (Ilmenau) ======";
#  $text[] = "";
  $text[] = "^ Gremium ^ Fak ^ letzte Aktualisierung ^ Mitglieder ^ Bemerkungen ^";

  ksort($data);
  foreach ($data as $gremium_fak => $data1) {
    foreach ($data1 as $gremium_id => $data2) {
      $g = $name_gremien[$gremium_id];
      $gname = trim($g["gremium_name"]);

      if (!$g["gremium_active"]) continue;

      $lastUpdate = NULL;
      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        if (!$r["rolle_active"]) continue;
        if (empty($personen["active"])) continue;
        foreach($personen["active"] as $person) {
          $lastCheck = $person["lastCheck"];
          if ($lastCheck === NULL) $lastCheck = $person["von"];
          if ($lastUpdate === NULL || $lastUpdate < $lastCheck)
            $lastUpdate = $lastCheck;
        }
      }
      if ($lastUpdate === NULL)
        $lastUpdate = "n/a";

      $prefix = preg_replace("/\s+/"," ","| $gname {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]} | {$g["gremium_fakultaet"]} | {$lastUpdate} | ");
      $isempty = true;

      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        $isempty2 = $r["rolle_numPlatz"];

        if (!$r["rolle_active"]) continue;

        if (!empty($personen["active"])) {
          $isempty = false;
          foreach($personen["active"] as $person) {
            $isempty2--;
            $text[] = $prefix.person2string($person)." | {$r["rolle_name"]} |";
            $prefix = "| ::: | ::: | ::: | ";
          }
        }
        for($i = 0; $i < $isempty2; $i++) {
          $isempty = false;
          $text[] = "{$prefix}//unbesetzt// | {$r["rolle_name"]} |";
          $prefix = "| ::: | ::: | ::: | ";
        }
      }
      if ($isempty) {
        $text[] = "{$prefix}//unbesetzt// | |";
      }
    }

  } /* $gremium_name */

  $text[] = "";

  $pages[$wiki]["new"] = $text;
}

prof_flag("render pages: mapping_coltable");

foreach ($mapping_coltable as $wiki => $data) {
  if (skipWiki($wiki)) continue;
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];
  ksort($data);

#  $text[] = "====== studentische Mitglieder (Ilmenau) ======";
#  $text[] = "";
  $headline = [ "Gremium" ];
  foreach (array_keys($data) as $rolle_name) {
    $headline[$rolle_name] = $rolle_name;
  }
  $table = ["0head" => [$headline] ];

  foreach ($data as $col_name => $data0 ) {
    foreach ($data0 as $gremium_tmp => $data1) {
      $rowIdx = 0;
      foreach ($data1 as $gremium_id => $data2) {
        $g = $name_gremien[$gremium_id];

        if (!$g["gremium_active"]) continue;

        $prefix = preg_replace("/\s+/"," ",trim("{$g["gremium_name"]} {$g["gremium_fakultaet"]} {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]}"));

#echo $prefix."<br>\n";

        $table["1".$gremium_tmp][$rowIdx][0] = $prefix;
        $firstRowIdx = $rowIdx;

        foreach ($data2 as $rolle_id => $personen) {
          $r = $name_rollen[$gremium_id][$rolle_id];

          if (!$r["rolle_active"]) continue;
          if (empty($personen["active"])) continue;

          if ($firstRowIdx != $rowIdx)
            $table["1".$gremium_tmp][$rowIdx][0] = " ::: ";

          $ptext = [];
          foreach($personen["active"] as $person) {
            #$item = person2string($person);
            $item = person2link($person);
            $ptext[] = $item;
          }
          $table["1".$gremium_tmp][$rowIdx][$col_name] = implode(", ", $ptext);
          if ($r["rolle_name"] != $col_name) $table["1".$gremium_tmp][$rowIdx][$col_name] .= " ({$r["rolle_name"]})";
          $rowIdx++;
        }
      }
    }
  } /* $col_name */
#echo "<pre>"; print_r($table); echo "</pre>";
  ksort($table);
  foreach ($table as $i => $rows) {
    $sep = ($i == "0head") ? "^" : "|";

    foreach ($rows as $k=>$row) {
      if (!isset($row[0])) {
        echo "ERROR: \$i=$i \$k=$k ";print_r($row);echo"<br>\n";
      }

      $line = $sep." ".$row[0]." ".$sep;

      foreach (array_keys($data) as $col_name) {
        $line .= " ".@$row[$col_name]." ".$sep;
      }
      $text[] = $line;
    }
  }

  $text[] = "";

  $pages[$wiki]["new"] = $text;
}

prof_flag("render pages: mapping_colextable");

foreach ($mapping_colextable as $wiki => $data) {
  if (skipWiki($wiki)) continue;
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];
  ksort($data);

#  $text[] = "====== studentische Mitglieder (Ilmenau) ======";
#  $text[] = "";
  $headline = [ "Gremium", "letzte Aktualisierung" ];
  foreach (array_keys($data) as $rolle_name) {
    $headline[$rolle_name] = $rolle_name;
  }
  $table = ["0head" => [$headline] ];

  foreach ($data as $col_name => $data0 ) {
    foreach ($data0 as $gremium_tmp => $data1) {
      $rowIdx = 0;
      foreach ($data1 as $gremium_id => $data2) {
        $g = $name_gremien[$gremium_id];

        if (!$g["gremium_active"]) continue;

        $prefix = preg_replace("/\s+/"," ",trim("{$g["gremium_name"]} {$g["gremium_fakultaet"]} {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]}"));

        $lastUpdate = NULL;
        foreach ($data2 as $rolle_id => $personen) {
          foreach ($personen["active"] as $person) {
            $lastCheck = $person["lastCheck"];
            if ($lastCheck === NULL) $lastCheck = $person["von"];
            if ($lastUpdate === NULL || $lastUpdate < $lastCheck)
            $lastUpdate = $lastCheck;
          }
        }
        if ($lastUpdate === NULL) $lastUpdate = "n/a";

        $table["1".$gremium_tmp][$rowIdx][0] = $prefix;
        $table["1".$gremium_tmp][$rowIdx][1] = $lastUpdate;
        $firstRowIdx = $rowIdx;

        foreach ($data2 as $rolle_id => $personen) {
          $r = $name_rollen[$gremium_id][$rolle_id];
          $isempty2 = $r["rolle_numPlatz"];

          if (!$r["rolle_active"]) continue;
          if (empty($personen["active"]) && ($isempty2 == 0)) continue;

          if ($firstRowIdx != $rowIdx) {
            $table["1".$gremium_tmp][$rowIdx][0] = " ::: ";
            $table["1".$gremium_tmp][$rowIdx][1] = " ::: ";
          }

          $ptext = [];
          foreach($personen["active"] as $person) {
            $isempty2--;
            $item = person2string($person);
            $ptext[] = $item;
          }
          for ($j = 0; $j < $isempty2; $j++) {
            $ptext[] = "unbesetzt";
          }
          $table["1".$gremium_tmp][$rowIdx][$col_name] = implode(", ", $ptext);
          if ($r["rolle_name"] != $col_name) $table["1".$gremium_tmp][$rowIdx][$col_name] .= " ({$r["rolle_name"]})";
          $rowIdx++;
        }
      }
    }
  } /* $col_name */
#echo "<pre>"; print_r($table); echo "</pre>";
  ksort($table);
  foreach ($table as $i => $rows) {
    $sep = ($i == "0head") ? "^" : "|";

    foreach ($rows as $k=>$row) {
      if (!isset($row[0])) {
        echo "ERROR: \$i=$i \$k=$k ";print_r($row);echo"<br>\n";
      }

      $line = $sep." ".$row[0]." ".$sep." ".$row[1]." ".$sep;

      foreach (array_keys($data) as $col_name) {
        $line .= " ".@$row[$col_name]." ".$sep;
      }
      $text[] = $line;
    }
  }

  $text[] = "";

  $pages[$wiki]["new"] = $text;
}

prof_flag("render pages: mapping_mastertable");

function cmpPersonMaster($a, $b) {
  $ad = array_values($a["data"]);
  $bd = array_values($b["data"]);
  $a0 = $ad[0];
  $b0 = $bd[0];

  if ($a0["name"] < $b0["name"]) return -1;
  if ($a0["name"] > $b0["name"]) return 1;
  if ($a0["email"] < $b0["email"]) return -1;
  if ($a0["email"] > $b0["email"]) return 1;
  if ($a0["id"] < $b0["id"]) return -1;
  if ($a0["id"] > $b0["id"]) return 1;
  return 0;
}

foreach ($mapping_mastertable as $wiki => $data) {
  if (skipWiki($wiki)) continue;
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];
  ksort($data);
  progress("read dokuwiki $wiki");

  $template = explode("\n",fetchWikiPage(":vorlagen:tree:{$wiki}"));
  foreach ($template as $line)
    $text[] = $line;

  foreach ($data as $section_name => $data00 ) {
    $data0 = $data00["data"];
    $withContactDetails = $data00["meta"]["withContactDetails"];

    if (strpos($section_name, " ") !== false) {
      list ($tmp1, $tmp2) = explode(" ", $section_name, 2);
      $section_name = $tmp2;
    }

    if (count($data0) == 1 && array_keys($data0)[0] != "") {
      # exactly one faculty
      $suffixA = " (".array_keys($data0)[0]. ")";
      $suffixB = " ".array_keys($data0)[0];
      if ((substr($section_name, -strlen($suffixA)) != $suffixA) &&
          (substr($section_name, -strlen($suffixB)) != $suffixB)
         )
        $section_name .= $suffixA;
    }
    $needFakCol = (count($data0) > 1);

    $needSGCol = false;
    foreach ($data0 as $gremium_fak => $data1 ) {
      $needSGCol |= (count($data1) > 1);
      $needSGCol |= (count($data0) > 1 && count($data1) == 1 && array_keys($data1)[0] != ""); # mehrere Fakultäten mit wenigstens einem nicht-leeren Studiengang angegeben
    }

    $text[] = "===== {$section_name} =====";
      
    $line = "^ Name ^ ";
    if ($withContactDetails) $line .= "Kontakt ^^^"; else $line .= "eMail ^";
#    if ($withContactDetails) $line .= "Kontakt ^^"; else $line .= "eMail ^";
    if ($needSGCol) $line = "^ Studiengang $line";
    if ($needFakCol) $line = "^ Fakultät $line";
    $text[] = $line;

    ksort($data0);
    foreach ($data0 as $gremium_fak => $data1 ) {
     ksort($data1);
     foreach ($data1 as $gremium_sg => $data ) {
       uasort($data, "cmpPersonMaster");

       foreach ($data as $person_id => $data1) {

         $person = array_values($data1["data"])[0];
         $flags = $data1["flags"];
         $sep = "";
         if (strpos($flags, "i") !== false) $sep .= "//";
         if (strpos($flags, "b") !== false) $sep .= "**";
         if (strpos($flags, "u") !== false) $sep .= "__";

         $sepr = strrev($sep);

         $email = explode(",", $person["email"])[0];
         $contact = [ ];
         if ($withContactDetails && isset($person["_contactDetails"])) {
           
           foreach ( $person["_contactDetails"] as $c) {
             if ($c["fromWiki"] && !$c["active"]) continue;
             $contact[] = contactType2Str($c["type"]).": ".escapeContactForWiki($c["details"]);
             
           }
         }
         $contact = array_unique($contact);
         sort($contact);
         $contact = implode('\\\\ ', $contact);

         if ($withContactDetails)
           $line = "| $sep ".person2link($person)." $sepr | $sep {$email} $sepr | $sep {$contact} $sepr | ";
#           $line = "| $sep ".person2link($person)." $sepr | $sep {$email} \\\\ {$contact} $sepr | $qrcode |";
         else
           $line = "| $sep ".person2link($person)." $sepr | $sep {$email} $sepr |";
         if ($needSGCol) $line = "| $sep $gremium_sg $sepr $line";
         if ($needFakCol) $line = "| $sep $gremium_fak $sepr $line";
         $line = preg_replace("/\s+/"," ",$line);

         $text[] = $line;

       } /* person_id */
     }
    }
    $text[] = "";
  } /* $section_name */

  $text[] = "";
  $text[] = "";
  $text[] = "//Stand: ".date("d.m.Y")."//";
  $text[] = "";

  $pages[$wiki]["new"] = $text;
}

prof_flag("render pages: mapping_mastertable");

function cmpPersonPTable($a, $b) {
  $ad = array_values($a["data"]);
  $bd = array_values($b["data"]);
  $a0 = $ad[0];
  $b0 = $bd[0];

  if ($a0["name"] < $b0["name"]) return -1;
  if ($a0["name"] > $b0["name"]) return 1;
  if ($a0["email"] < $b0["email"]) return -1;
  if ($a0["email"] > $b0["email"]) return 1;
  if ($a0["id"] < $b0["id"]) return -1;
  if ($a0["id"] > $b0["id"]) return 1;
  return 0;
}

foreach ($mapping_ptable as $wiki => $data) {
  if (skipWiki($wiki)) continue;
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];
  ksort($data);
  $alreadyPersons = [];
  progress("read dokuwiki $wiki");

  $template = explode("\n",fetchWikiPage(":vorlagen:tree:{$wiki}"));
  foreach ($template as $line)
    $text[] = $line;

  foreach ($data as $section_name => $data0 ) {

    if (strpos($section_name, " ") !== false) {
      list ($tmp1, $tmp2) = explode(" ", $section_name, 2);
      $section_name = $tmp2;
    }

    $text[] = "===== {$section_name} =====";
    $text[] = "^ Name ^ eMail ^";

    uasort($data0, "cmpPersonMaster");
    foreach ($data0 as $person_id => $data1 ) {
      if (in_array($person_id, $alreadyPersons)) continue;
      $alreadyPersons[] = $person_id;

      $person = array_values($data1["data"])[0];
      $flags = $data1["flags"];
      $sep = "";
      if (strpos($flags, "i") !== false) $sep .= "//";
      if (strpos($flags, "b") !== false) $sep .= "**";
      if (strpos($flags, "u") !== false) $sep .= "__";

      $sepr = strrev($sep);

      $email = explode(",", $person["email"])[0];
      $line = "| $sep ".person2link($person)." $sepr | $sep {$email} $sepr |";
      $line = preg_replace("/\s+/"," ",$line);

      $text[] = $line;

    } /* person_id */
    $text[] = "";
  } /* $section_name */

  $text[] = "";
  $text[] = "";
  $text[] = "//Stand: ".date("d.m.Y")."//";
  $text[] = "";

  $pages[$wiki]["new"] = $text;
}

prof_flag("render pages: mapping_nachbesetzung");

foreach ($mapping_nachbesetzung as $wiki => $data) {
  if (skipWiki($wiki)) continue;
  $text = Array();
  if (isset($pages[$wiki]["new"]))
    $text = $pages[$wiki]["new"];

#  $text[] = "====== studentische Mitglieder (Ilmenau) ======";
#  $text[] = "";
  $text[] = "^ Gremium ^ Fak ^ letzte Aktualisierung ^ Mitglieder ^ Bemerkungen ^";

  ksort($data);
  foreach ($data as $gremium_sort => $data1) {
    foreach ($data1 as $gremium_id => $data2) {
      $g = $name_gremien[$gremium_id];
      $gname = trim($g["gremium_name"]);

      if (!$g["gremium_active"]) continue;

      $lastUpdate = NULL;
      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        if (!$r["rolle_active"]) continue;
        if (empty($personen["active"])) continue;
        foreach($personen["active"] as $person) {
          $lastCheck = $person["lastCheck"];
          if ($lastCheck === NULL) $lastCheck = $person["von"];
          if ($lastUpdate === NULL || $lastUpdate < $lastCheck)
            $lastUpdate = $lastCheck;
        }
      }
      if ($lastUpdate === NULL)
        $lastUpdate = "n/a";

      $prefix = preg_replace("/\s+/"," ","| $gname {$g["gremium_studiengang"]} {$g["gremium_studiengangabschluss"]} | {$g["gremium_fakultaet"]} | {$lastUpdate} | ");
      $isempty = true;

      foreach ($data2 as $rolle_id => $personen) {
        $r = $name_rollen[$gremium_id][$rolle_id];
        $isempty2 = $r["rolle_numPlatz"];

        if (!$r["rolle_active"]) continue;

        if (!empty($personen["active"])) {
          $isempty = false;
          foreach($personen["active"] as $person) {
            $isempty2--;
            $text[] = $prefix.person2string($person)." | {$r["rolle_name"]} |";
            $prefix = "| ::: | ::: | ::: | ";
          }
        }
        for($i = 0; $i < $isempty2; $i++) {
          $isempty = false;
          $text[] = "{$prefix}//unbesetzt// | {$r["rolle_name"]} |";
          $prefix = "| ::: | ::: | ::: | ";
        }
      }
      if ($isempty) {
        $text[] = "{$prefix}//unbesetzt// | |";
      }
    }

  } /* $gremium_name */

  $text[] = "";

  $pages[$wiki]["new"] = $text;
}

prof_flag("render pages: import contact from wiki");

foreach($contactPersonen as $i => $p) {
  $wiki = $p["wiki"];
  if (skipWiki($wiki)) continue;
  progress("read dokuwiki $wiki");
  $text = fetchWikiPage($wiki);
  // get Telefon= Mobil= Jabber=
  $matches = ["tel" => [], "xmpp" => []];
  #$r1 = preg_match_all('#\|\s*\(Telefon|Mobil\)\*s=\s*([^|\n]+)#i', $text, $matches["tel"],  PREG_PATTERN_ORDER);
  $r1 = preg_match_all('#\|\s*(Telefon|Mobil)\s*=\s*([^|\n]+)#i', $text, $matches["tel"],  PREG_PATTERN_ORDER);
  $r2 = preg_match_all('#\|\s*(Jabber)\s*=\s*([^|\n]+)#i', $text, $matches["xmpp"],  PREG_PATTERN_ORDER);
  if ($r1 === false) continue;
  if ($r2 === false) continue;

  $old = ["tel" => [], "xmpp" => []];
  foreach ($p["old"] as $c) {
    if (!$c["fromWiki"]) continue;
    switch (strtolower($c["type"])) {
      case "tel":
      case "xmpp":
        $old[strtolower($c["type"])][] = trim($c["details"]);
        break;
    }
  }
  foreach ($old as $k => $m) {
    $old[$k] = array_unique($m);
    sort($old[$k]);
  }

  $new = [];
  foreach ($matches as $k => $m) {
    $details = [];
    foreach($m[2] as $dd) {
      $dd = explode("\\\\ ", $dd);
      foreach ($dd as $d) {
        $d = trim($d);
        $d = preg_replace('#\s*/\s*#',' / ',$d);
        $d = trim($d, "-/");
        if (empty($d)) continue;
        $details[] = $d;
      }
    }
    $new[$k] = array_unique($details);
    sort($new[$k]);
  }

  $remove = []; $add = [];
  foreach(array_keys($matches) as $k) {
    $remove[$k] = array_diff($old[$k], $new[$k]);
    $add[$k] = array_diff($new[$k], $old[$k]);
  }

  $contactPersonen[$i]["add"] = $add;
  $contactPersonen[$i]["remove"] = [];

  foreach($p["old"] as $c) {
    if (!$c["fromWiki"]) continue;
    if (!isset($remove[strtolower($c["type"])])) continue;
    if (!in_array(trim($c["details"]), $remove[strtolower($c["type"])])) continue;
    $contactPersonen[$i]["remove"][] = $c["id"];
  }
}

prof_flag("render pages: diff or post");

foreach (array_keys($pages) as $wiki) {
  if (skipWiki($wiki)) continue;
  if (isset($_POST["commit"]) && is_array($_POST["commit"]) && in_array($wiki, $_POST["commit"]) && $validnonce) {
    progress("write dokuwiki $wiki");
    writeWikiPage($wiki, base64_decode($_POST["text"][$wiki]));
  } elseif (isset($_POST["commit"]) && is_array($_POST["commit"]) && isset($_POST["commit"][$wiki])) {
    echo "<b class=\"msg\">CSRF Schutz.</b>";
  } elseif (!isset($_POST["commit"])) {
    progress("read dokuwiki $wiki");
    $pages[$wiki]["old"] = explode("\n",fetchWikiPage($wiki));
    $x = new Text_Diff('auto',Array($pages[$wiki]["old"],$pages[$wiki]["new"]));
    $y = new Text_Diff_Renderer_unified();
    $pages[$wiki]["diff"] = $y->render($x);
  }
}

foreach ($contactPersonen as $p) {
  $wiki = $p["wiki"];
  if (skipWiki($wiki)) continue;
  if (isset($_POST["commit"]) && is_array($_POST["commit"]) && in_array($wiki, $_POST["commit"]) && $validnonce) {
    foreach ($p["remove"] as $id) {
      progress("delete contact $id");
      dbPersonDeleteContact($id);
    }
    foreach ($p["add"] as $type => $list) {
      foreach ($list as $details) {
        progress("add contact");
        dbPersonInsertContact($p["person_id"], $type, $details, 1, 1);
      }
    }
  } elseif (isset($_POST["commit"]) && is_array($_POST["commit"]) && isset($_POST["commit"][$wiki])) {
    echo "<b class=\"msg\">CSRF Schutz.</b>";
  }
}

if (isset($_POST["commit"])) {
  $wikiStr = "";
  if (isset($_POST["wiki"]) && !is_array($_POST["wiki"])) {
    $wikiStr = "wiki=".urlencode($_POST["wiki"])."&";
  }
  if (isset($_POST["wiki"]) && is_array($_POST["wiki"])) {
    foreach ($_POST["wiki"] as $wiki) {
      $wikiStr = "wiki[]=".urlencode($wiki)."&";
    }
  }

  if (!isset($_REQUEST["autoExportPW"])) {
    header("Location: ${_SERVER["PHP_SELF"]}?$wikiStr");
  }
  die();
}

prof_flag("render html");

require "../template/header.tpl";
require "../template/admin.tpl";


?>
<h2>Gremienmitgliedschaften im Wiki aktualisieren</h2>

<style type="text/css">
 td {vertical-align: top; }
 textarea {display: none; }
</style>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST">

<table class="table table-striped">
<tr><th></th><th>Seite</th><th>Änderung</th></tr>
<?php

global $wikiUrl;
$url = parse_url($wikiUrl);
#$openUrl = http_build_url($url, Array(), HTTP_URL_STRIP_AUTH);
$openUrl = $url["scheme"]."://".$url["host"];
if (isset($url["port"]))
  $openUrl .= ":".$url["port"];
if (isset($url["path"]))
  $openUrl .= ":".$url["path"];
foreach ($pages as $wiki => $data):
  if (skipWiki($wiki)) continue;
  echo "<tr>";
  echo " <td><input ".(($data["diff"] != "") ? "class=\"mls\"" : "")." type=\"checkbox\" name=\"commit[]\" value=\"".htmlspecialchars($wiki)."\"></td>";
  echo " <td><a href=\"".htmlspecialchars($openUrl.str_replace(":","/",$wiki))."\">".htmlspecialchars($wiki)."</a></td>\n";
  echo " <td><pre class=\"diffcode\">".htmlspecialchars($data["diff"])."</pre><input type=\"hidden\" readonly=readonly name=\"text[".htmlspecialchars($wiki)."]\" value=\"".base64_encode(implode("\n",$data["new"]))."\"></td>\n";
  echo "</tr>";
endforeach;

?></table>

<h2>Kontaktdaten aus Wiki aktualisieren</h2>
<table class="table table-striped">
<tr><th></th><th>Seite</th><th>Änderung</th></tr>
<?php

global $wikiUrl;
$url = parse_url($wikiUrl);
#$openUrl = http_build_url($url, Array(), HTTP_URL_STRIP_AUTH);
$openUrl = $url["scheme"]."://".$url["host"];
if (isset($url["port"]))
  $openUrl .= ":".$url["port"];
if (isset($url["path"]))
  $openUrl .= ":".$url["path"];
foreach ($contactPersonen as $p):
  $wiki = $p["wiki"];
  if (skipWiki($wiki)) continue;

  echo "<tr>";
  echo " <td><input ".((count($p["add"]) + count($p["remove"]) > 0) ? "class=\"mls\"" : "")." type=\"checkbox\" name=\"commit[]\" value=\"".htmlspecialchars($wiki)."\"></td>";
  echo " <td><a href=\"".htmlspecialchars($openUrl.str_replace(":","/",$wiki))."\">".htmlspecialchars($wiki)."</a></td>\n";
  echo " <td>\n";
  foreach ($p["add"] as $key => $list) {
    if (count($list) > 0) {
      echo "Add $key:<br/>\n<ul>";
      foreach ($list as $l) {
        echo "<li>".htmlspecialchars($l)."</li>\n";
      }
      echo "</ul><br/>\n";
    }
  }
  if (count($p["remove"]) > 0) {
   echo "Remove:<br/>\n<ul>";
   foreach ($p["remove"] as $l) {
     echo "<li>".htmlspecialchars($l)."</li>\n";
   }
   echo "</ul><br/>\n";
  }
  echo "</td>\n";
  echo "</tr>";
endforeach;

?></table>

<input type="hidden" name="nonce" value="<?php echo htmlspecialchars($nonce);?>"/>

<a href="#" onClick="$('.mls').attr('checked',true); return false;">alle Änderungen auswählen</a>
<a href="#" onClick="$('.mls').attr('checked',false); return false;">keine Änderungen auswählen</a>
<input type="submit" value="Anwenden" name="submit"/>
<input type="reset" value="Zurücksetzen" name="reset"/>
<?php
if (isset($_REQUEST["autoExportPW"]))
  echo "<input type=\"hidden\" name=\"autoExportPW\" value=\"".htmlspecialchars($_REQUEST["autoExportPW"])."\">";

if (isset($_REQUEST["wiki"])) {
  if (!is_array($_REQUEST["wiki"])) {
    echo "<input type=\"hidden\" name=\"wiki\" value=\"".htmlspecialchars($_REQUEST["wiki"])."\">\n";
  }
  if (is_array($_REQUEST["wiki"])) {
    foreach ($_REQUEST["wiki"] as $wiki) {
      echo "<input type=\"hidden\" name=\"wiki[]\" value=\"".htmlspecialchars($wiki)."\">\n";
    }
  }
}

?>

</form>
<hr/>
<a href="<?php echo $logoutUrl; ?>">Logout</a> &bull;
<a href="index.php">Selbstauskunft</a> &bull;
<a href="admin.php">Verwaltung</a>
<?php

prof_flag("End");

if (false) {
  echo "<h2>Profiling</h2>";
  prof_print();
}

if (false) {
  echo "<h2>Ignored Pages</h2>";
  echo "<ul>";
  $skippedPages = array_unique($skippedPages);
  sort($skippedPages);
  foreach ($skippedPages as $wiki)
    echo "<li>".htmlspecialchars($wiki)."</li>\n";
  echo "</ul>";
}

require_once "../template/footer.tpl";

# vim: set expandtab tabstop=8 shiftwidth=8 :
