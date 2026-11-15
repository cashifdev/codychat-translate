<?php
/* Translate add-on for Codychat 10.1 | Author: M Kashif | MIT License */
$load_addons = 'cnr_translate';
require(__DIR__ . '/../../../system/config_addons.php');
if(!canManageAddons()){ die(); }

$c = ctrCfg($addons);
$st = array('rows' => 0, 'day' => 0, 'auto' => 0, 'users' => 0);
$day = time() - 86400;
$q = $mysqli->query("SELECT (SELECT COUNT(*) FROM cnr_tr_cache WHERE txt <> '') a, (SELECT COUNT(*) FROM cnr_tr_cache WHERE made > '$day') b, (SELECT COUNT(*) FROM cnr_tr_pref WHERE auto = 1) c, (SELECT COUNT(*) FROM cnr_tr_pref) d");
if($q && $q->num_rows){ $r = $q->fetch_assoc(); $st = array('rows' => (int)$r['a'], 'day' => (int)$r['b'], 'auto' => (int)$r['c'], 'users' => (int)$r['d']); }
function ctrRanks($cur){ $o = ''; foreach(rankList() as $v){ if($v != botRank()){ $o .= rankOption($cur, $v); } } return $o; }
echo elementTitle('Translate', "loadLob('admin/setting_addons.php');");
?>
<div class="page_full"><div class="page_element"><div class="tpad15">
	<p class="sub_text">A translate icon on every message, and optional auto-translate into each member's own language. Each message is translated once per language and then served from the cache.</p>
	<div class="bback pad15" style="border-radius:12px;margin:12px 0;display:flex;gap:22px;flex-wrap:wrap;">
		<div><b><?php echo $st['rows']; ?></b><div class="sub_text text_xsmall">translations cached</div></div>
		<div><b><?php echo $st['day']; ?></b><div class="sub_text text_xsmall">new in the last 24 hours</div></div>
		<div><b><?php echo $st['users']; ?></b><div class="sub_text text_xsmall">members set a language</div></div>
		<div><b><?php echo $st['auto']; ?></b><div class="sub_text text_xsmall">use auto-translate</div></div>
	</div>
	<div class="setting_element"><p class="label">Translate on this site</p>
		<select id="ctr_c1" class="full_input"><option value="1" <?php echo $c['site'] === 1 ? 'selected' : ''; ?>>On</option><option value="0" <?php echo $c['site'] !== 1 ? 'selected' : ''; ?>>Off</option></select></div>
	<div class="setting_element"><p class="label">Translation engine</p>
		<p class="sub_text text_xsmall">Google free works with no key. Azure uses your own key (2 million characters a month free) and falls back to Google if it fails.</p>
		<select id="ctr_c2" class="full_input"><option value="google" <?php echo $c['engine'] === 'google' ? 'selected' : ''; ?>>Google (free, no key)</option><option value="azure" <?php echo $c['engine'] === 'azure' ? 'selected' : ''; ?>>Microsoft Azure Translator</option></select></div>
	<div class="setting_element"><p class="label">Azure key</p>
		<input id="ctr_c3" class="full_input" type="password" autocomplete="off" placeholder="<?php echo $c['key'] !== '' ? 'Saved. Leave empty to keep it' : 'Paste your Azure Translator key'; ?>"/></div>
	<div class="setting_element"><p class="label">Azure region</p>
		<input id="ctr_c4" class="full_input" type="text" value="<?php echo htmlspecialchars($c['region']); ?>" placeholder="e.g. westeurope (leave empty for global)"/></div>
	<div class="setting_element"><p class="label">Lowest rank that can translate</p>
		<select id="ctr_c5" class="full_input"><?php echo ctrRanks($c['min']); ?></select></div>
	<div class="setting_element"><p class="label">New translations per member per minute</p>
		<input id="ctr_c6" class="full_input" type="number" min="5" max="500" value="<?php echo (int)$c['rate']; ?>"/></div>
	<button class="reg_button theme_btn tmargin10" onclick="ctrAdminSave()">Save</button>
	<p class="sub_text text_xsmall tmargin10">Translate add-on by M Kashif</p>
</div></div></div>
<script>
function ctrAdminSave(){
	var k = $('#ctr_c3').val();
	$.post('addons/cnr_translate/system/tr.php', { act: 'admin', custom1: $('#ctr_c1').val(), custom2: $('#ctr_c2').val(), custom3: k, keep: k === '' ? '1' : '0', custom4: $('#ctr_c4').val(), custom5: $('#ctr_c5').val(), custom6: $('#ctr_c6').val() }, function(r){
		if(r && r.ok){ callSaved('Saved', 1); } else { callSaved('Not saved', 3); }
	}, 'json');
}
</script>
