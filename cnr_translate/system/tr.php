<?php
/* Translate add-on for Codychat 10.1 | Author: M Kashif | MIT License */
/* Translate endpoint: act=tr (kind 1 room | 2 private, ids) | act=me | act=set (lang, auto) | act=admin (staff) */
$load_addons = 'cnr_translate';
require(__DIR__ . '/../../../system/config_addons.php');

header('Content-Type: application/json');
function ctrOut($a){ echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$c   = ctrCfg($addons);
$act = isset($_POST['act']) ? (string)$_POST['act'] : '';
$me  = (int)$data['user_id'];

if($act === 'admin'){
	if(!canManageAddons()){ ctrOut(array('ok' => 0)); }
	$s1 = ((int)($_POST['custom1'] ?? 1) === 1) ? '1' : '0';
	$s2 = (($_POST['custom2'] ?? '') === 'azure') ? 'azure' : 'google';
	$s3 = $mysqli->real_escape_string(trim((string)($_POST['custom3'] ?? '')));
	$s4 = $mysqli->real_escape_string(preg_replace('/[^a-z0-9]/', '', strtolower((string)($_POST['custom4'] ?? ''))));
	$s5 = (int)($_POST['custom5'] ?? 0);
	$s6 = max(5, min(500, (int)($_POST['custom6'] ?? 40)));
	$key = ($s3 === '' && isset($_POST['keep']) && $_POST['keep'] === '1') ? $mysqli->real_escape_string($c['key']) : $s3;
	$mysqli->query("UPDATE boom_addons SET custom1 = '$s1', custom2 = '$s2', custom3 = '$key', custom4 = '$s4', custom5 = '$s5', custom6 = '$s6' WHERE addons = 'cnr_translate'");
	if(function_exists('redisUpdateAddons')){ redisUpdateAddons('cnr_translate'); }
	ctrOut(array('ok' => 1));
}
if($me <= 0 || $c['site'] !== 1 || (int)$data['user_rank'] < $c['min']){ ctrOut(array('ok' => 0, 'off' => 1)); }

if($act === 'me'){
	$p = ctrPref($data);
	ctrOut(array('ok' => 1, 'lang' => $p['lang'], 'auto' => $p['auto'], 'langs' => ctrLangs()));
}
if($act === 'set'){
	$l = (string)($_POST['lang'] ?? '');
	if(!isset(ctrLangs()[$l])){ ctrOut(array('ok' => 0)); }
	$a = ((int)($_POST['auto'] ?? 0) === 1) ? 1 : 0; $t = time();
	$le = $mysqli->real_escape_string($l);
	$mysqli->query("INSERT INTO cnr_tr_pref (user_id, lang, auto, changed) VALUES ('$me', '$le', '$a', '$t') ON DUPLICATE KEY UPDATE lang = VALUES(lang), auto = VALUES(auto), changed = VALUES(changed)");
	ctrOut(array('ok' => 1, 'lang' => $l, 'auto' => $a));
}
if($act === 'tr'){
	$kind = ((int)($_POST['kind'] ?? 1) === 2) ? 2 : 1;
	$to = (string)($_POST['to'] ?? '');
	if(!isset(ctrLangs()[$to])){ $to = ctrPref($data)['lang']; }
	$toe = $mysqli->real_escape_string($to);
	$ids = array_slice(array_unique(array_filter(array_map('intval', explode(',', (string)($_POST['ids'] ?? ''))))), 0, 8);
	$names = ctrNames(); $res = array(); $limited = 0;
	foreach($ids as $id){
		/* cached for this language? */
		$q = $mysqli->query("SELECT src, txt FROM cnr_tr_cache WHERE kind = '$kind' AND mid = '$id' AND lang = '$toe' LIMIT 1");
		if($q && $q->num_rows){ $r = $q->fetch_assoc(); $res[$id] = ($r['txt'] === '') ? array('same' => 1) : array('t' => $r['txt'], 'src' => $r['src'], 'from' => $names[$r['src']] ?? strtoupper($r['src'])); continue; }
		/* already known to be in this language from another reader? */
		$q = $mysqli->query("SELECT src FROM cnr_tr_cache WHERE kind = '$kind' AND mid = '$id' AND src <> '' LIMIT 1");
		if($q && $q->num_rows && ctrBase($q->fetch_assoc()['src']) === ctrBase($to)){ $res[$id] = array('same' => 1); continue; }
		/* load the message itself: only real messages the reader can see */
		if($kind === 1){
			$q = $mysqli->query("SELECT post_message AS m, syslog FROM boom_chat WHERE post_id = '$id' LIMIT 1");
			$row = ($q && $q->num_rows) ? $q->fetch_assoc() : null;
			if(!$row || (int)$row['syslog'] === 1){ $res[$id] = array('none' => 1); continue; }
		}
		else {
			$q = $mysqli->query("SELECT message AS m FROM boom_private WHERE id = '$id' AND (hunter = '$me' OR target = '$me') LIMIT 1");
			$row = ($q && $q->num_rows) ? $q->fetch_assoc() : null;
			if(!$row){ $res[$id] = array('none' => 1); continue; }
		}
		$txt = ctrClean($row['m']);
		if($txt === '' || !ctrWorth($txt)){ $res[$id] = array('none' => 1); continue; }
		if(!ctrRateOk($me, $c['rate'])){ $limited = 1; $res[$id] = array('wait' => 1); continue; }
		$out = ctrRun(mb_substr($txt, 0, 1500), $to, $c);
		if($out === false){ $res[$id] = array('fail' => 1); continue; }
		list($tt, $src) = $out; $t = time();
		$same = (ctrBase($src) === ctrBase($to)) || (mb_strtolower(trim($tt)) === mb_strtolower($txt));
		$tte = $same ? '' : $mysqli->real_escape_string($tt); $srce = $mysqli->real_escape_string(substr($src, 0, 12));
		$mysqli->query("INSERT IGNORE INTO cnr_tr_cache (kind, mid, lang, src, txt, made) VALUES ('$kind', '$id', '$toe', '$srce', '$tte', '$t')");
		$res[$id] = $same ? array('same' => 1) : array('t' => $tt, 'src' => $src, 'from' => $names[$src] ?? strtoupper($src));
	}
	ctrOut(array('ok' => 1, 'to' => $to, 'r' => $res, 'limited' => $limited));
}
ctrOut(array('ok' => 0));
?>
