<div class="panel panel-default">
<div class="panel-heading">Rollen</div>
<div class="panel-body">

<table class="table tablerollerolle table-showall">
<tr>
<?php if ($gremienrollen_edit): ?>
  <th><a href="?tab=rolle.new&amp;gremium_id=<?php echo $gremium["id"]; ?>" target="_blank"><i class="fa fa-fw fa-plus"></i></a></th>
<?php endif; ?>
  <th>Rolle</th><th>Aktiv</th><th class="hidden-xs">Personen</th>
</tr>
<?php
$hasInactiveRole = false;
if (count($rollen) == 0):
?>
<tr><td colspan="<?php echo $gremienrollen_edit ? 4 : 3; ?>"><i>Keine Rollen.</td></tr>
<?php
else:
$iall = 0; $iactive=0;
foreach($rollen as $rolle):
  $cssclass = [];
  $iall++;
  if (!$rolle["active"]) {
    $hasInactiveRole = true;
    $cssclass[] = "inactiverow";
  } else {
    $cssclass[] = "activerow";
    $iactive++;
  }
  if ($iall % 2 == 0)
    $cssclass[] = "alleven";
  else
    $cssclass[] = "allodd";
  if ($iactive % 2 == 0)
    $cssclass[] = "activeeven";
  else
    $cssclass[] = "activeodd";
?>
<tr class="<?php echo implode(" ", $cssclass); ?>">
<?php if ($gremienrollen_edit): ?>
  <td><nobr>
    <a href="?tab=rolle.edit&amp;rolle_id=<?php echo $rolle["id"]; ?>" target="_blank"><i class="fa fa-fw fa-pencil"></i></a>
    <a href="?tab=rolle.delete&amp;rolle_id=<?php echo $rolle["id"]; ?>" target="_blank"><i class="fa fa-fw fa-trash"></i></a>
  </nobr></td>
<?php endif; ?>
 <td>
  <a target="_blank" href="?tab=rolle.edit&amp;rolle_id=<?php echo $rolle["id"]; ?>">
  <?php echo htmlspecialchars($rolle["name"]);?>
 </a>
</td>
 <td><?php echo ($rolle["active"] ? "ja" : "nein");?></td>
 <td class="hidden-xs">
<?php
 $personen = [];
 foreach (getRollePersonen($rolle["id"]) as $p) {
   if (!$p["active"]) continue;
   $personen[] = "<a target=\"_blank\" href=\"?tab=person.edit&amp;person_id=".$p["id"]."\">".htmlspecialchars($p["name"])."</a>";
 }
 echo join(", ", $personen);
?>
 </td>
</tr>
<?php
endforeach;
endif;
?>
</table>

<?php if ($hasInactiveRole): ?>
<label class="checkbox">
  <input data-toggle="toggle" type="checkbox" id="rollerolletoggle"> Inaktive Rollen anzeigen
</label>
<?php endif; ?>

  </div> </div> <!--panel -->


<script>
$("#rollerolletoggle").on("change.rollerolle", function() {
  if ($(this).is(":checked")) {
    $("table.tablerollerolle").addClass("table-showall");
    $("table.tablerollerolle").removeClass("table-showactive");
  } else {
    $("table.tablerollerolle").addClass("table-showactive");
    $("table.tablerollerolle").removeClass("table-showall");
  }
});
$("#rollerolletoggle").trigger("change");
</script>

<?php

// vim:set filetype=php:

