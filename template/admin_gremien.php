<div id="gremium">
<a name="gremium"></a>
<noscript><h3>Gremien und Rollen</h3></noscript>

<div class="table" style="min-width:100%;">
<div class="tr" id="rowGhead">
 <div class="th">
  <a href="#" onClick="$('#insertG').dialog('open'); return false;" title="Gremium anlegen">[NEU]</a>
  <div id="insertG" title="neues Gremium anlegen" class="editgremiumdialog">
    <noscript><h4>Neues Gremium anlegen</h4></noscript>
    <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
     <ul>
     <li><label for="name">Name:</label><input type="text" name="name" value=""/></li>
     <li><label for="fakultaet">Fakultät:</label><input type="text" name="fakultaet" value=""/><br/>
         (wahlweise EI/IA/MB/MN/WW oder leer lassen)</li>
     <li><label for="studiengang">Studiengang:</label><input type="text" name="studiengang" value=""/><br/>
         (gängiges Kürzel verwenden, beispw. EI)</li>
     <li><label for="studiengangabschluss">Stg-Abschluss:</label><input type="text" name="studiengangabschluss" value=""/><br/>
         (Bachelor oder Master, leer lassen falls Gremium alle Abschlüsse abdeckt)</li>
     <li><label for="wiki_members">Wiki-Seite mit Mitgliederliste:</label><input type="text" name="wiki_members" value=""/><br/>
         (beispw. :gremium:mitglieder:stura:%LEGISLATUR%)</li>
     <li><label for="active"  >Gremium existent/aktiv?:        </label>
         <select name="active" size="1" selected="selected"><option value="1" >Ja, derzeit existent</option><option value="0" >Nein, derzeit nicht existent</option></select>
     </li>
     <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
     </ul>
     <input type="hidden" name="action" value="gremium.insert"/>
     <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
     <input type="submit" name="submit" value="Speichern"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertG').dialog('close');"/>
    </form>
  </div>
  <?php $script[] = "\$('#insertG').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: $('#rowGhead') } });"; ?>
 </div>
 <div class="th">Gremium</div><div class="th">Fakultät</div><div class="th">Studiengang</div><div class="th">Aktiv</div>
</div>
<?php
$struct_gremien = Array();
$last_gremium_id = -1; $last_struct_id = -1;
$filter = Array();
$filter["name"] = Array();
$filter["fakultaet"] = Array();
$filter["studiengang"] = Array();
$filter["studiengangabschluss"] = Array();
$filter["active"] = Array(1);
$activefilter = json_decode(base64_decode($_COOKIE["filter_gremien"]), true);

foreach ($alle_gremien as $i => $gremium):
 if (count($activefilter["name"]) > 0 && !in_array($gremium["gremium_name"], $activefilter["name"])) continue;
 if (count($activefilter["fakultaet"]) > 0 && !in_array($gremium["gremium_fakultaet"], $activefilter["fakultaet"])) continue;
 if (count($activefilter["studiengang"]) > 0 && !in_array($gremium["gremium_studiengang"], $activefilter["studiengang"])) continue;
 if (count($activefilter["studiengangabschluss"]) > 0 && !in_array($gremium["gremium_studiengangabschluss"], $activefilter["studiengangabschluss"])) continue;
 if (count($activefilter["active"]) > 0 && !in_array($gremium["gremium_active"], $activefilter["active"])) continue;
  if ($last_gremium_id != $gremium["gremium_id"]) {
    $last_gremium_id = $gremium["gremium_id"];
    $last_struct_id++;
  }
  $struct_gremien[$last_struct_id]["id"] = $gremium["gremium_id"];
  $struct_gremien[$last_struct_id]["name"] = $gremium["gremium_name"];
  $struct_gremien[$last_struct_id]["fakultaet"] = $gremium["gremium_fakultaet"];
  $struct_gremien[$last_struct_id]["studiengang"] = $gremium["gremium_studiengang"];
  $struct_gremien[$last_struct_id]["studiengangabschluss"] = $gremium["gremium_studiengangabschluss"];
  $struct_gremien[$last_struct_id]["active"] = (int) $gremium["gremium_active"];
  $struct_gremien[$last_struct_id]["display_name"] = $gremium["gremium_name"]." ".$gremium["gremium_fakultaet"]." ".$gremium["gremium_studiengang"]." ".$gremium["gremium_studiengangabschluss"];
  $struct_gremien[$last_struct_id]["wiki_members"] = $gremium["gremium_wiki_members"];
  if ($gremium["rolle_id"] !== NULL)
    $struct_gremien[$last_struct_id]["rollen"][] = Array("rolle_name" => $gremium["rolle_name"], "rolle_id" => $gremium["rolle_id"], "rolle_active" => (int) $gremium["rolle_active"]);
  else
    $struct_gremien[$last_struct_id]["rollen"] = Array();
  $filter["name"][] = $gremium["gremium_name"];
  $filter["fakultaet"][] = $gremium["gremium_fakultaet"];
  $filter["studiengang"][] = $gremium["gremium_studiengang"];
  $filter["studiengangabschluss"][] = $gremium["gremium_studiengangabschluss"];
endforeach;

$filter["name"] = array_unique($filter["name"]);
sort($filter["name"]);
$filter["fakultaet"] = array_unique($filter["fakultaet"]);
sort($filter["fakultaet"]);
$filter["studiengang"] = array_unique($filter["studiengang"]);
sort($filter["studiengang"]);
$filter["studiengangabschluss"] = array_unique($filter["studiengangabschluss"]);
sort($filter["studiengangabschluss"]);
$filter["active"] = Array(0 => "Nein", 1 => "Ja");
asort($filter["active"]);

?>
<form class="tr" style="background-color: lightyellow;" action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
 <div class="td">Filter: <input type="submit" name="submit" value="filtern"/>
             <input type="submit" name="submit" value="zurücksetzen"/>
     <a href="<?=htmlspecialchars($_SERVER["PHP_SELF"].'?filter_gremien_name=&filter_gremien_fakultaet=&filter_gremien_studiengang=&filter_gremien_studiengangabschluss=&filter_gremien_active=#gremium');?>">kein Filter</a>
 </div>
 <div class="td"><select name="filter_gremien_name[]" multiple="multiple"><?php foreach ($filter["name"] as $name): ?><option <?if (in_array($name, $activefilter["name"])):?> selected="selected"<? endif;?>><?=$name;?></option><?php endforeach;?></select></div>
 <div class="td"><select name="filter_gremien_fakultaet[]" multiple="multiple"><?php foreach ($filter["fakultaet"] as $fakultaet): ?><option <?if (in_array($fakultaet, $activefilter["fakultaet"])):?> selected="selected"<? endif;?>><?=$fakultaet;?></option><?php endforeach;?></select></div>
 <div class="td"><select name="filter_gremien_studiengang[]" multiple="multiple"><?php foreach ($filter["studiengang"] as $studiengang): ?><option <?if (in_array($studiengang, $activefilter["studiengang"])):?> selected="selected"<? endif;?>><?=$studiengang;?></option><?php endforeach;?></select>
 <select name="filter_gremien_studiengangabschluss[]" multiple="multiple"><?php foreach ($filter["studiengangabschluss"] as $studiengangabschluss): ?><option <?if (in_array($studiengangabschluss, $activefilter["studiengangabschluss"])):?> selected="selected"<? endif;?>><?=$studiengangabschluss;?></option><?php endforeach;?></select></div>
 <div class="td"><select name="filter_gremien_active[]" multiple="multiple"><?php foreach ($filter["active"] as $v => $active): ?><option value="<?=htmlspecialchars($v);?>" <?if (in_array($v, $activefilter["active"])):?> selected="selected"<? endif;?>><?=$active;?></option><?php endforeach;?></select></div>
</form>
<?php
foreach ($struct_gremien as $i => $gremium):
 if (($_COOKIE["gremium_start"] >= 0) && ($i < $_COOKIE["gremium_start"]) && ($_COOKIE["gremium_start"] <= count($struct_gremien))) continue;
 if (($_COOKIE["gremium_length"] >= 0) && ($i >= $_COOKIE["gremium_length"] + $_COOKIE["gremium_start"])) break;
?>
<div class="tr" id="rowG<?=$gremium["id"];?>">
 <div class="td">
  <?=$i;?>.
  <a href="#" onClick="$('#deleteG<?=$gremium["id"];?>').dialog('open'); return false;" titel="Gremium <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?> löschen" >[X]</a>
  <div id="deleteG<?=$gremium["id"];?>" title="Gremium <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?> entfernen" class="editgremiumdialog">
   <noscript><h4>Gremium <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?> entfernen</h4></noscript>
   <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
    <ul>
     <li>ID: <?php echo $gremium["id"];?></li>
     <li><label for="name"                >Name:                      </label><input type="text" name="name" value="<?php echo htmlspecialchars($gremium["name"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="fakultaet"           >Fakultät:                  </label><input type="text" name="fakultaet" value="<?php echo htmlspecialchars($gremium["fakultaet"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="studiengang"         >Studiengang:               </label><input type="text" name="studiengang" value="<?php echo htmlspecialchars($gremium["studiengang"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="studiengangabschluss">Stg-Abschluss:             </label><input type="text" name="studiengangabschluss" value="<?php echo htmlspecialchars($gremium["studiengangabschluss"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="wiki_members"        >Wiki-Seite für Mitglieder: </label><input type="text" name="wiki_members" value="<?php echo htmlspecialchars($gremium["wiki_members"],ENT_QUOTES);?>" readonly="readonly"/></li>
     <li><label for="active"              >Gremium existent/aktiv?:   </label><? if ($gremium["active"]): ?>Ja<? else: ?>Nein<? endif; ?></li>
     <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
    </ul>
    <input type="hidden" name="id" value="<?php echo $gremium["id"];?>"/>
    <input type="hidden" name="action" value="gremium.delete"/>
    <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
    <input type="submit" name="submit" value="Löschen"/>
    <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteG<?=$gremium["id"];?>').dialog('close');"/>
   </form>
   <h4>Rollen</h4>
   <div class="table">
   <div class="tr"><div class="th">Rolle</div><div class="th">Aktiv</th></div></div>
<?php
$rollen = $gremium["rollen"];
if (count($rollen) == 0):
?>
   <div class="tr"><div class="td"><i>Keine Rollen.</i></div></div>
<?php
else:
foreach($rollen as $rolle):
?>
   <div class="tr">
    <div class="td"><?php echo htmlspecialchars($rolle["rolle_name"]);?></div>
    <div class="td"><?php if (htmlspecialchars($rolle["rolle_active"])) { echo "ja"; } else { echo "nein"; } ;?></div>
   </div>
<?php
endforeach;
endif;
?>
   </div>
  </div>
  <a href="#" onClick="$('#editG<?=$gremium["id"];?>').dialog('open'); return false;" title="Gremium <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?> bearbeiten">[E]</a>
  <div id="editG<?=$gremium["id"];?>" title="Gremium <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?> bearbeiten" class="editgremiumdialog">
   <noscript><h4>Gremium <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?> bearbeiten</h4></noscript>
   <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
    <ul>
     <li>ID: <?php echo $gremium["id"];?></li>
     <li><label for="name">Name:</label><input type="text" name="name" value="<?php echo htmlspecialchars($gremium["name"],ENT_QUOTES);?>" /></li>
     <li><label for="fakultaet">Fakultät:</label><input type="text" name="fakultaet" value="<?php echo htmlspecialchars($gremium["fakultaet"],ENT_QUOTES);?>" /></li>
     <li><label for="studiengang">Studiengang:</label><input type="text" name="studiengang" value="<?php echo htmlspecialchars($gremium["studiengang"],ENT_QUOTES);?>" /></li>
     <li><label for="studiengangabschluss">Stg-Abschluss:</label><input type="text" name="studiengangabschluss" value="<?php echo htmlspecialchars($gremium["studiengangabschluss"],ENT_QUOTES);?>" /></li>
     <li><label for="wiki_members">Wiki-Seite für Mitglieder:</label><input type="text" name="wiki_members" value="<?php echo htmlspecialchars($gremium["wiki_members"],ENT_QUOTES);?>" /></li>
     <li><label for="active"  >Gremium existent/aktiv?:        </label>
         <select name="active" size="1"><option value="1" <? if ($gremium["active"]) echo "selected=\"selected\""; ?>>Ja, derzeit existent</option><option value="0" <? if (!$gremium["active"]) echo "selected=\"selected\""; ?>>Nein, derzeit nicht existent</option></select>
     </li>
     <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
    </ul>
    <input type="hidden" name="id" value="<?php echo $gremium["id"];?>"/>
    <input type="hidden" name="action" value="gremium.update"/>
    <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
    <input type="submit" name="submit" value="Speichern"/>
    <input type="reset" name="reset" value="Abbrechen" onClick="$('#editG<?=$gremium["id"];?>').dialog('close');"/>
   </form>

   <h4>Rollen</h4>
   <div class="table">
   <div class="tr">
    <div class="th">
     <a href="#" onClick="$('#insertG<?=$gremium["id"];?>R').dialog('open'); return false;" titel="Rolle einfügen" >[NEU]</a>
     <div id="insertG<?=$gremium["id"];?>R" title="Rolle einfügen">
      <noscript><h4>Rolle einfügen</h4></noscript>
      <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
       <ul>
        <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
        <li>Rolle: <input type="text" name="name" value=""/></li>
        <li><label for="active"  >Rolle existent/aktiv?:        </label>
            <select name="active" size="1"><option value="1" selected="selected">Ja, derzeit existent</option><option value="0" >Nein, derzeit nicht existent</option></select>
        </li>
        <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
       </ul>
       <input type="hidden" name="gremium_id" value="<?php echo $gremium["id"];?>"/>
       <input type="hidden" name="action" value="rolle_gremium.insert"/>
       <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
       <input type="submit" name="submit" value="Rolle eintragen"/>
       <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertG<?=$gremium["id"];?>R').dialog('close');"/>
      </form>
     </div>
     <?php $script[] = "\$('#insertG{$gremium['id']}R').dialog({ autoOpen: false, width: 700, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}') } });"; ?>
    </div>
    <div class="th">Rolle</div>
    <div class="th">Aktiv</div>
    <div class="th">Personen</div>
   </div>
<?php
$rollen = $gremium["rollen"];
if (count($rollen) == 0):
?>
   <div class="tr"><div class="td" colspan="3"><i>Keine Rollen.</i></div></div>
<?php
else:
foreach($rollen as $rolle):
?>
   <div class="tr">
    <div class="td">
     <a href="#" onClick="$('#editG<?=$gremium["id"];?>R<?=$rolle["rolle_id"];?>').dialog('open'); return false;" titel="Rollen bearbeiten" >[E]</a>
     <div id="editG<?=$gremium["id"];?>R<?=$rolle["rolle_id"];?>" title="Rolle bearbeiten">
      <noscript><h4>Rolle bearbeiten</h4></noscript>
      <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
       <ul>
        <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
        <li>Rolle: <input type="text" name="name" value="<?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?>"/></li>
        <li><label for="active"  >Rolle existent/aktiv?:        </label>
           <select name="active" size="1"><option value="1" <? if ($rolle["rolle_active"]) echo "selected=\"selected\""; ?>>Ja, derzeit existent</option><option value="0" <? if (!$rolle["rolle_active"]) echo "selected=\"selected\""; ?>>Nein, derzeit nicht existent</option></select>
        </li>
        <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
       </ul>
       <input type="hidden" name="id" value="<?php echo $rolle["rolle_id"];?>"/>
       <input type="hidden" name="action" value="rolle_gremium.update"/>
       <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
       <input type="submit" name="submit" value="Rolle bearbeiten"/>
       <input type="reset" name="reset" value="Abbrechen" onClick="$('#editG<?=$gremium["id"];?>R<?=$rolle["rolle_id"];?>').dialog('close');"/>
      </form>

      <h4>Personen</h4>
<?php
$current_personen = getRollePersonen($rolle["rolle_id"]);
?>
      <div class="table">
      <div class="tr">
       <div class="th">
        <a href="#" onClick="$('#insertR<?=$rolle["rolle_id"];?>P').dialog('open'); return false;" titel="Personen-Rollenzuordnung einfügen" >[NEU]</a>
        <div id="insertR<?=$rolle["rolle_id"];?>P" title="Personen-Rollenzuordnung einfügen" class="editpersonrole">
         <noscript><h4>Personen-Rollenzuordnung einfügen</h4></noscript>
         <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
          <ul>
           <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
           <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
           <li><label for="person_id">Person:</label> <select name="person_id" size="1"><?php foreach ($alle_personen as $person):?><option class="forinsertR<?=$rolle["rolle_id"];?>P <?=($person["active"] ? "personactive" : "personinactive");?>" value="<?php echo $person["id"];?>"><?php echo htmlspecialchars($person["email"]);?></option><? endforeach; ?></select>
             <a href="#" onClick="$('option.personinactive.forinsertR<?=$rolle["rolle_id"];?>P').toggle(); return false;" titel="inaktive Personen anzeigen/ausblenden" >[inaktive Personen anzeigen/ausblenden]</a>
             <?php $script[] = "\$('option.personinactive.forinsertR{$rolle["rolle_id"]}P').hide();"; ?>
           </li>
           <li><label for="von">von:</label> <input type="text" name="von" value="<?=date("Y-m-d");?>" class="datepicker"/></li>
           <li><label for="bis">bis:</label> <input type="text" name="bis" value="" class="datepicker"/></li>
<?php $script[] = "\$( '.datepicker' ).datepicker({ dateFormat: 'yy-mm-dd' });"; ?>
           <li><label for="beschlussAm">beschlussen am:</label> <input type="text" name="beschlussAm" value=""/></li>
           <li><label for="beschlussDurch">beschlossen durch:</label> <input type="text" name="beschlussDurch" value=""/></li>
           <li><label for="kommentar">Kommentar:</label> <textarea name="kommentar"></textarea></li>
           <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
          </ul>
          <input type="hidden" name="rolle_id" value="<?php echo $rolle["rolle_id"];?>"/>
          <input type="hidden" name="action" value="rolle_person.insert"/>
          <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
          <input type="submit" name="submit" value="Personen-Rollenzuordnung einfügen"/>
          <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertR<?=$rolle["rolle_id"];?>P').dialog('close');"/>
         </form>
        </div>
        <?php $script[] = "\$('#insertR{$rolle['rolle_id']}P').dialog({ autoOpen: false, width: 1100, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}R{$rolle['rolle_id']}') } });"; ?>
       </div>
       <div class="th">e-Mail</div><div class="th">Name</div><div class="th">Zeitraum</div><div class="th">Beschluss</div><div class="th">Kommentar</div>
      </div>
<?php
if (count($current_personen) == 0):
?>
      <div class="tr"><div class="td" colspan="6">Keine Personen</div></div>
<?php
else:
foreach ($current_personen as $person):
?>
      <div class="tr <?=($person["active"] ? "personactive" : "personinactive");?> forrole<?=$rolle["rolle_id"];?>">
       <div class="td">
        <a href="#" onClick="$('#deleteR<?=$rolle["rolle_id"];?>P<?=$person["rel_id"];?>').dialog('open'); return false;" titel="Personen-Rollenzuordnung entfernen" >[X]</a>
        <div id="deleteR<?=$rolle["rolle_id"];?>P<?=$person["rel_id"];?>" title="Personen-Rollenzuordnung entfernen">
         <noscript><h4>Personen-Rollenzuordnung entfernen</h4></noscript>
         <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
          <ul>
           <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
           <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
           <li>Person: <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?></li>
           <li><span class="label">Zeitraum:</span> <?php
            if (empty($person["von"]) && empty($person["bis"])) {
             echo "keine Angabe";
            } elseif (empty($person["von"])) {
             echo "bis ".$person["bis"];
            } elseif (empty($person["bis"])) {
             echo "seit ".$person["von"];
            } else {
             echo htmlspecialchars($person["von"])." - ".$person["bis"];
            }
            ?></li>
           <li><span class="label">Beschluss:</span> <?php echo htmlspecialchars($person["beschlussAm"])." ".htmlspecialchars($person["beschlussDurch"]); ?></li>
           <li><span class="label">Kommentar:</span> <div class="kommentar"><?=str_replace("\n","<br/>",htmlspecialchars($person["kommentar"]));?></div></li>
           <li><label for="action">Aktion:</label><select name="action" size="1"><option value="rolle_person.disable" selected="selected">Zuordnung terminieren</option><option value="rolle_person.delete">Datensatz löschen</option></select></li>
           <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
          </ul>
          <input type="hidden" name="id" value="<?php echo $person["rel_id"];?>"/>
          <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
          <input type="submit" name="submit" value="Personen-Rollenzuordnung entfernen"/>
          <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteR<?=$rolle["rolle_id"];?>P<?=$person["rel_id"];?>').dialog('close');"/>
         </form>
     
        </div>
        <?php $script[] = "\$('#deleteR{$rolle['rolle_id']}P{$person['rel_id']}').dialog({ autoOpen: false, width: 900, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}R{$rolle['rolle_id']}') } });"; ?>
        <a href="#" onClick="$('#editR<?=$rolle["rolle_id"];?>P<?=$person["rel_id"];?>').dialog('open'); return false;" titel="Rollenzuordnung bearbeiten" >[E]</a>
        <div id="editR<?=$rolle["rolle_id"];?>P<?=$person["rel_id"];?>" title="Rollenzuordnung bearbeiten" class="editpersonrole">
         <noscript><h4>Rollenzuordnung bearbeiten</h4></noscript>
         <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
          <ul>
           <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
           <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
           <li>Person: <?php echo htmlspecialchars($person["email"],ENT_QUOTES);?></li>
           <li><label for="von">von:</label> <input type="text" name="von" value="<?=htmlspecialchars($person["von"]);?>" class="datepicker"/></li>
           <li><label for="bis">bis:</label> <input type="text" name="bis" value="<?=htmlspecialchars($person["bis"]);?>" class="datepicker"/></li>
           <?php $script[] = "\$( '.datepicker' ).datepicker({ dateFormat: 'yy-mm-dd' });"; ?>
           <li><label for="beschlussAm"   >beschlossen am:</label> <input type="text" name="beschlussAm" value="<?=htmlspecialchars($person["beschlussAm"]);?>"/></li>
           <li><label for="beschlussDurch">beschlossen durch:</label> <input type="text" name="beschlussDurch" value="<?=htmlspecialchars($person["beschlussDurch"]);?>"/></li>
           <li><label for="kommentar"     >Kommentar:</label> <textarea name="kommentar"><?=htmlspecialchars($person["kommentar"]);?></textarea></li>
           <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
          </ul>
          <input type="hidden" name="id" value="<?php echo $person["rel_id"];?>"/>
          <input type="hidden" name="person_id" value="<?php echo $person["id"];?>"/>
          <input type="hidden" name="rolle_id" value="<?php echo $rolle["rolle_id"];?>"/>
          <input type="hidden" name="action" value="rolle_person.update"/>
          <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
          <input type="submit" name="submit" value="Zuordnung bearbeiten"/>
          <input type="reset" name="reset" value="Abbrechen" onClick="$('#editR<?=$rolle["rolle_id"];?>P<?=$person["rel_id"];?>').dialog('close');"/>
         </form>
        </div>
        <?php $script[]="\$('#editR{$rolle['rolle_id']}P{$person["rel_id"]}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}R{$rolle['rolle_id']}') } });"; ?>
       </div>
       <div class="td"><?php echo htmlspecialchars($person["email"]);?></div>
       <div class="td"><?php echo htmlspecialchars($person["name"]);?></div>
       <div class="td"><?php
           if (empty($person["von"]) && empty($person["bis"])) {
            echo "keine Angabe";
           } elseif (empty($person["von"])) {
            echo "bis ".$person["bis"];
           } elseif (empty($person["bis"])) {
            echo "seit ".$person["von"];
           } else {
            echo htmlspecialchars($person["von"])." - ".$person["bis"];
           }
         ?></div>
       <div class="td"><?php echo htmlspecialchars($person["beschlussAm"])." ".htmlspecialchars($person["beschlussDurch"]); ?></div>
       <div class="td"><?php echo str_replace("\n","<br/>",htmlspecialchars($person["kommentar"]));?></div>
      </div>
<?php
endforeach;
endif;
?>
      </div>
      <a href="#" onClick="$('div.tr.personinactive.forrole<?=$rolle["rolle_id"];?>').toggle(); return false;" titel="inaktive Personenzuordnungen anzeigen/ausblenden" >[inaktive Personen anzeigen/ausblenden]</a>
      <?php $script[] = "\$('div.tr.personinactive.forrole{$rolle['rolle_id']}').hide();"; ?>
      <a href="#" onClick="$('#insertR<?=$rolle["rolle_id"];?>Pmass').dialog('open'); return false;" titel="Rollenzuordnung bearbeiten (Mehrfacheintragung)" >[Mehrfacheintragung]</a>
      <div id="insertR<?=$rolle["rolle_id"];?>Pmass" title="Rollenzuordnung bearbeiten (Mehrfacheintragung)" class="editpersonrole">
       <noscript><h4>Rollenzuordnung hinzufügen (Mehrfacheintragung)</h4></noscript>
       <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
        <ul>
         <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
         <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
         <li><label for="email">eMail-Adressen (zeilenweise):</label><textarea name="email"></textarea></li>
         <li><label for="von">von:</label> <input type="text" name="von" value="<?=date("Y-m-d");?>" class="datepicker"/></li>
         <li><label for="bis">bis:</label> <input type="text" name="bis" value="" class="datepicker"/></li>
         <?php $script[] = "\$( '.datepicker' ).datepicker({ dateFormat: 'yy-mm-dd' });"; ?>
         <li><label for="beschlussAm">beschlussen am:</label> <input type="text" name="beschlussAm" value=""/></li>
         <li><label for="beschlussDurch">beschlossen durch:</label> <input type="text" name="beschlussDurch" value=""/></li>
         <li><label for="kommentar">Kommentar:</label> <textarea name="kommentar"></textarea></li>
         <li><label for="duplicate">Bei bestehender aktiver Zuordnung:</label> <select name="duplicate" size="1"><option selected="selected" value="skip">Person nicht hinzufügen</option><option value="ignore">Person dennoch hinzufügen</option></select></li>
         <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
        </ul>
        <input type="hidden" name="rolle_id" value="<?php echo $rolle["rolle_id"];?>"/>
        <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
        <input type="hidden" name="action" value="rolle_person.bulkinsert"/>
        <input type="submit" name="submit" value="Personen-Rollenzuordnung hinzufügen"/>
        <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertR<?=$rolle["rolle_id"];?>Pmass').dialog('close');"/>
       </form>
      </div>
      <?php $script[]="\$('#insertR{$rolle['rolle_id']}Pmass').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}R{$rolle['rolle_id']}') } });"; ?>
      <a href="#" onClick="$('#deleteR<?=$rolle["rolle_id"];?>Pmass').dialog('open'); return false;" titel="Rollenzuordnung bearbeiten (Mehrfachaustragung)" >[Mehrfachaustragung]</a>
      <div id="deleteR<?=$rolle["rolle_id"];?>Pmass" title="Rollenzuordnung bearbeiten (Mehrfachaustragung)" class="editpersonrole">
       <noscript><h4>Rollenzuordnung entfernen (Mehrfachaustragung)</h4></noscript>
       <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
        <ul>
         <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
         <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
         <li><label for="email">eMail-Adressen (zeilenweise):</label><textarea name="email"></textarea></li>
         <li><label for="bis">bis:</label> <input type="text" name="bis" value="" class="datepicker"/></li>
         <?php $script[] = "\$( '.datepicker' ).datepicker({ dateFormat: 'yy-mm-dd' });"; ?>
         <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
        </ul>
        <input type="hidden" name="rolle_id" value="<?php echo $rolle["rolle_id"];?>"/>
        <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
        <input type="hidden" name="action" value="rolle_person.bulkdisable"/>
        <input type="submit" name="submit" value="Personen-Rollenzuordnung entfernen"/>
        <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteR<?=$rolle["rolle_id"];?>Pmass').dialog('close');"/>
       </form>
      </div>
      <?php $script[]="\$('#deleteR{$rolle['rolle_id']}Pmass').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}R{$rolle['rolle_id']}') } });"; ?>
      <h4>Gruppen</h4>
      <div class="table">
      <div class="tr">
       <div class="th">
        <a href="#" onClick="$('#insertR<?=$rolle["rolle_id"];?>GRP').dialog('open'); return false;" titel="Gruppen-Rollenzuordnung einfügen" >[NEU]</a>
        <div id="insertR<?=$rolle["rolle_id"];?>GRP" title="Gruppen-Rollenzuordnung einfügen">
         <noscript><h4>Gruppen-Rollenzuordnung einfügen</h4></noscript>
         <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
          <ul>
           <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
           <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
           <li>Gruppe: <select name="gruppe_id" size="1"><?php foreach ($alle_gruppen as $gruppe):?><option value="<?php echo $gruppe["id"];?>"><?php echo htmlspecialchars($gruppe["name"]);?></option><? endforeach; ?></select></li>
           <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
          </ul>
          <input type="hidden" name="rolle_id" value="<?php echo $rolle["rolle_id"];?>"/>
          <input type="hidden" name="action" value="rolle_gruppe.insert"/>
          <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
          <input type="submit" name="submit" value="Gruppen-Rollenzuordnung einfügen"/>
          <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertR<?=$rolle["rolle_id"];?>GRP').dialog('close');"/>
         </form>
        </div>
        <?php $script[] = "\$('#insertR{$rolle['rolle_id']}GRP').dialog({ autoOpen: false, width: 900, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}R{$rolle['rolle_id']}') } });"; ?>
       </div>
       <div class="th">Name</div><div class="th">Beschreibung</div>
      </div>
<?php
$current_gruppen = getRolleGruppen($rolle["rolle_id"]);
if (count($current_gruppen) == 0):
?>
      <div class="tr"><div class="td" colspan="3">Keine Gruppen</div></div>
<?php
else:
foreach ($current_gruppen as $gruppe):
?>
      <div class="tr">
       <div class="td">
        <a href="#" onClick="$('#deleteR<?=$rolle["rolle_id"];?>GRP<?=$gruppe["id"];?>').dialog('open'); return false;" titel="Gruppen-Rollenzuordnung entfernen" >[X]</a>
        <div id="deleteR<?=$rolle["rolle_id"];?>GRP<?=$gruppe["id"];?>" title="Gruppen-Rollenzuordnung entfernen">
          <noscript><h4>Gruppen-Rollenzuordnung entfernen</h4></noscript>
          <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
          <ul>
          <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
          <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
          <li>Gruppe: <?php echo htmlspecialchars($gruppe["name"],ENT_QUOTES);?></li>
          <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
          </ul>
          <input type="hidden" name="gruppe_id" value="<?php echo $gruppe["id"];?>"/>
          <input type="hidden" name="rolle_id" value="<?php echo $rolle["rolle_id"];?>"/>
          <input type="hidden" name="action" value="rolle_gruppe.delete"/>
          <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
          <input type="submit" name="submit" value="Gruppen-Rollenzuordnung entfernen"/>
          <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteR<?=$rolle["rolle_id"];?>GRP<?=$gruppe["id"];?>').dialog('close');"/>
          </form>
     
        </div>
        <?php $script[] = "\$('#deleteR{$rolle['rolle_id']}GRP{$gruppe['id']}').dialog({ autoOpen: false, width: 900, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}R{$rolle['rolle_id']}') } });"; ?>
       </div>
       <div class="td"><?php echo htmlspecialchars($gruppe["name"]);?></div>
       <div class="td"><?php echo htmlspecialchars($gruppe["beschreibung"]);?></div>
      </div>
<?php
endforeach;
endif;
?>
      </div>
     <h4>Mailinglisten</h4>
      <div class="table">
      <div class="tr"><div class="th">
        <a href="#" onClick="$('#insertR<?=$rolle["rolle_id"];?>ML').dialog('open'); return false;" titel="Mailinglisten-Rollenzuordnung einfügen" >[NEU]</a>
         <div id="insertR<?=$rolle["rolle_id"];?>ML" title="Mailinglisten-Rollenzuordnung einfügen">
          <noscript><h4>Mailinglisten-Rollenzuordnung einfügen</h4></noscript>
          <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
          <ul>
          <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
          <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
          <li>Mailingliste: <select name="mailingliste_id" size="1"><?php foreach ($alle_mailinglisten as $mailingliste):?><option value="<?php echo $mailingliste["id"];?>"><?php echo htmlspecialchars($mailingliste["address"]);?></option><? endforeach; ?></select></li>
          <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
          </ul>
          <input type="hidden" name="rolle_id" value="<?php echo $rolle["rolle_id"];?>"/>
          <input type="hidden" name="action" value="rolle_mailingliste.insert"/>
          <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
          <input type="submit" name="submit" value="Mailinglisten-Rollenzuordnung einfügen"/>
          <input type="reset" name="reset" value="Abbrechen" onClick="$('#insertR<?=$rolle["rolle_id"];?>ML').dialog('close');"/>
          </form>
         </div>
         <?php $script[] = "\$('#insertR{$rolle['rolle_id']}ML').dialog({ autoOpen: false, width: 900, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}R{$rolle['rolle_id']}') } });"; ?>
        </div><div class="th">Adresse</div><div class="th">Webseite</div></div>
<?php
$current_mailinglisten = getRolleMailinglisten($rolle["rolle_id"]);
if (count($current_mailinglisten) == 0):
?>
      <div class="tr"><div class="td" colspan="3">Keine Mailinglisten</div></div>
<?php
else:
foreach ($current_mailinglisten as $mailingliste):
?>
      <div class="tr">
       <div class="td">
        <a href="#" onClick="$('#deleteR<?=$rolle["rolle_id"];?>ML<?=$mailingliste["id"];?>').dialog('open'); return false;" titel="Mailinglisten-Rollenzuordnung entfernen" >[X]</a>
        <div id="deleteR<?=$rolle["rolle_id"];?>ML<?=$mailingliste["id"];?>" title="Mailinglisten-Rollenzuordnung entfernen">
          <noscript><h4>Mailinglisten-Rollenzuordnung entfernen</h4></noscript>
          <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
          <ul>
          <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
          <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
          <li>Mailingliste: <?php echo htmlspecialchars($mailingliste["address"],ENT_QUOTES);?></li>
          <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
          </ul>
          <input type="hidden" name="mailingliste_id" value="<?php echo $mailingliste["id"];?>"/>
          <input type="hidden" name="rolle_id" value="<?php echo $rolle["rolle_id"];?>"/>
          <input type="hidden" name="action" value="rolle_mailingliste.delete"/>
          <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
          <input type="submit" name="submit" value="Mailinglisten-Rollenzuordnung entfernen"/>
          <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteR<?=$rolle["rolle_id"];?>ML<?=$mailingliste["id"];?>').dialog('close');"/>
          </form>
     
        </div>
        <?php $script[] = "\$('#deleteR{$rolle['rolle_id']}ML{$mailingliste['id']}').dialog({ autoOpen: false, width: 900, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}R{$rolle['rolle_id']}') } });"; ?>
       </div>
       <div class="td"><a href="mailto:<?php echo htmlspecialchars($mailingliste["address"]);?>"><?php echo htmlspecialchars($mailingliste["address"]);?></a></div>
       <div class="td"><a href="<?php echo htmlspecialchars($mailingliste["url"]);?>"><?php echo htmlspecialchars($mailingliste["url"]);?></a></div>
      </div>
<?php
endforeach;
endif;
?>
      </div>

   </div>
  <?php $script[] = "\$('#editG{$gremium['id']}R{$rolle['rolle_id']}').dialog({ autoOpen: false, width: 1300, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}') } });"; ?>
   <a href="#" onClick="$('#deleteG<?=$gremium["id"];?>R<?=$rolle["rolle_id"];?>').dialog('open'); return false;" titel="Rollen entfernen" >[X]</a>
   <div id="deleteG<?=$gremium["id"];?>R<?=$rolle["rolle_id"];?>" title="Rolle löschen">
     <noscript><h4>Rolle entfernen</h4></noscript>
     <form action="<?php echo $_SERVER["PHP_SELF"];?>#gremium" method="POST">
     <ul>
     <li>Gremium: <?php echo htmlspecialchars($gremium["display_name"],ENT_QUOTES);?></li>
     <li>Rolle: <?php echo htmlspecialchars($rolle["rolle_name"],ENT_QUOTES);?></li>
     <li>Gremium existent/aktiv?: <? if ($rolle["rolle_active"]): ?>Ja<? else: ?>Nein<? endif; ?></li>
     <li><img class="captcha" src="data:image/png;base64,<?php echo base64_encode($imgBinary);?>" alt="Captcha" class="captcha"/> Bitte Captcha eingeben: <input type="text" name="captcha" value="<?=$captcha;?>"/></li>
     </ul>
     <input type="hidden" name="id" value="<?php echo $rolle["rolle_id"];?>"/>
     <input type="hidden" name="action" value="rolle_gremium.delete"/>
     <input type="hidden" name="captchaId" value="<?php echo htmlspecialchars($captchaId);?>"/>
     <input type="submit" name="submit" value="Rolle löschen"/>
     <input type="reset" name="reset" value="Abbrechen" onClick="$('#deleteG<?=$gremium["id"];?>R<?=$rolle["rolle_id"];?>').dialog('close');"/>
     </form>

   </div>
  <?php $script[] = "\$('#deleteG{$gremium['id']}R{$rolle['rolle_id']}').dialog({ autoOpen: false, width: 900, height: 'auto', position: { my: 'center', at: 'center', of: \$('#editG{$gremium['id']}') } });"; ?>
      </div>
      <div class="td"><?php echo htmlspecialchars($rolle["rolle_name"]);?></div>
      <div class="td"><?php if (htmlspecialchars($rolle["rolle_active"])) { echo "ja"; } else { echo "nein"; } ;?></div>
      <div class="td"><?php 
          $current_personen = getRollePersonen($rolle["rolle_id"]);
          $liste = Array(); foreach ($current_personen as $person) { if ($person["active"]) { $liste[] = $person["email"]; } }
          echo htmlspecialchars(implode(", ", $liste));?></div>
     </div>
<?php
endforeach;
endif;
?>
     </div>
   </div>
   <?php
     $script[] = "\$('#editG{$gremium['id']}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: $('#rowG{$gremium['id']}') } });";
     $script[] = "\$('#deleteG{$gremium['id']}').dialog({ autoOpen: false, width: 1000, height: 'auto', position: { my: 'center', at: 'center', of: $('#rowG{$gremium['id']}') } });";
   ?>
 </div>
 <div class="td"><?php echo htmlspecialchars($gremium["name"]);?></div>
 <div class="td"><?php echo htmlspecialchars($gremium["fakultaet"]);?></div>
 <div class="td"><?php echo htmlspecialchars($gremium["studiengang"]);
            if (!empty($gremium["studiengangabschluss"])) {
              echo " (".htmlspecialchars($gremium["studiengangabschluss"]).")";
            }
      ?></div>
 <div class="td"><?php if (htmlspecialchars($gremium["active"])) { echo "ja"; } else { echo "nein"; } ;?></div>
</div>
<?php
endforeach;
?>
</div>
<hr/>
<ul class="pageselect">
<?php if ($_COOKIE["gremium_start"] > 0): ?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gremium_start=0#gremium">&lt;&lt;</a></li><? endif; ?>
<?php
if ($_COOKIE["gremium_start"] > $_COOKIE["gremium_length"]) {
  $prev = $_COOKIE["gremium_start"] - $_COOKIE["gremium_length"];
} else {
  $prev = -1;
}
if ((count($struct_gremien) > $_COOKIE["gremium_start"] + 2 * $_COOKIE["gremium_length"])) {
  $next = $_COOKIE["gremium_start"] + $_COOKIE["gremium_length"];
} else {
  $next = -1;
}
if ($_COOKIE["gremium_length"] > 0):
 for($i = $_COOKIE["gremium_length"] ; $i < count($struct_gremien); $i = $i +  $_COOKIE["gremium_length"]):
  if ($i < $_COOKIE["gremium_start"] || $i > $_COOKIE["gremium_start"]): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gremium_start=<?=$i;?>#gremium" title="<?=htmlspecialchars($struct_gremien[$i]["display_name"]);?>"><?=$i;?></a></li><?
  endif;
  if ($i < $prev && $i + $_COOKIE["gremium_length"] > $prev): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gremium_start=<?=$prev;?>#gremium" title="<?=htmlspecialchars($struct_gremien[$prev]["display_name"]);?>">&lt;</a></li><?
  endif;
  if ($i <= $_COOKIE["gremium_start"] && $i + $_COOKIE["gremium_length"] > $_COOKIE["gremium_start"]): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gremium_start=<?=$_COOKIE["gremium_start"];?>#gremium">[<?=$_COOKIE["gremium_start"];?>]</a></li><?
  endif;
  if ($i < $next && $i + $_COOKIE["gremium_length"] > $next): 
?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"]);?>?gremium_start=<?=$next;?>#gremium" title="<?=htmlspecialchars($struct_gremien[$next]["display_name"]);?>">&gt;</a></li><?
  endif;
 endfor;
endif; ?>
<?php if ($_COOKIE["gremium_start"] + $_COOKIE["gremium_length"] < count($struct_gremien)): ?><li><a href="<?=htmlentities($_SERVER["PHP_SELF"])?>?gremium_start=<?=count($struct_gremien) - $_COOKIE["gremium_length"];?>#gremium">&gt;&gt;</a></li><? endif; ?>
</ul>
<form action="<?=htmlentities($_SERVER["PHP_SELF"]);?>#gremium" method="POST">
Einträge je Seite: <input type="text" name="gremium_length" value="<?=htmlentities($_COOKIE["gremium_length"]);?>"/>
<input type="submit" name="submit" value="Auswählen"/><input type="reset" name="reset" value="Zurücksetzen"/>
</form>
</div>
