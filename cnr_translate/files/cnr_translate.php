<?php
/* Translate add-on for Codychat 10.1 | Author: M Kashif | MIT License */
 if(boomAllow($addons['addons_access'])){
	require_once(BOOM_PATH . '/addons/cnr_translate/system/addons_function.php');
	$ctr_c = ctrCfg($addons);
	if($ctr_c['site'] === 1 && (int)$data['user_rank'] >= $ctr_c['min']){
		$ctr_p = ctrPref($data);
		$ctr_v = @filemtime(BOOM_PATH . '/addons/cnr_translate/files/translate.js') . '-' . @filemtime(BOOM_PATH . '/addons/cnr_translate/files/translate.css');
?>
<script data-cfasync="false">
window.CTR_CFG = <?php echo json_encode(array('me' => (int)$data['user_id'], 'lang' => $ctr_p['lang'], 'auto' => $ctr_p['auto'], 'set' => $ctr_p['set'], 'langs' => ctrLangs()), JSON_UNESCAPED_UNICODE); ?>;
boomAddCss('addons/cnr_translate/files/translate.css?v=<?php echo $ctr_v; ?>');
</script>
<script data-cfasync="false" src="addons/cnr_translate/files/translate.js?v=<?php echo $ctr_v; ?>"></script>
<?php } } ?>
