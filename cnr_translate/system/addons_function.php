<?php
/* Translate add-on for Codychat 10.1 | Author: M Kashif | MIT License */
if(!defined('BOOM')){ die(); }
/* Translate helpers */
function ctrLangs(){
	return array(
		'en' => 'English', 'ur' => 'اردو Urdu', 'hi' => 'हिन्दी Hindi', 'pa' => 'ਪੰਜਾਬੀ Punjabi', 'ps' => 'پښتو Pashto', 'sd' => 'سنڌي Sindhi',
		'bn' => 'বাংলা Bengali', 'ar' => 'العربية Arabic', 'fa' => 'فارسی Persian', 'tr' => 'Türkçe Turkish', 'sv' => 'Svenska Swedish',
		'de' => 'Deutsch German', 'fr' => 'Français French', 'es' => 'Español Spanish', 'it' => 'Italiano Italian', 'pt' => 'Português Portuguese',
		'nl' => 'Nederlands Dutch', 'ru' => 'Русский Russian', 'ro' => 'Română Romanian', 'bg' => 'Български Bulgarian', 'hr' => 'Hrvatski Croatian',
		'el' => 'Ελληνικά Greek', 'he' => 'עברית Hebrew', 'id' => 'Bahasa Indonesia', 'ms' => 'Bahasa Melayu', 'zh-CN' => '中文 Chinese',
		'ja' => '日本語 Japanese', 'ko' => '한국어 Korean'
	);
}
function ctrNames(){
	return array('en' => 'English', 'ur' => 'Urdu', 'hi' => 'Hindi', 'pa' => 'Punjabi', 'ps' => 'Pashto', 'sd' => 'Sindhi', 'bn' => 'Bengali', 'ar' => 'Arabic',
		'fa' => 'Persian', 'tr' => 'Turkish', 'sv' => 'Swedish', 'de' => 'German', 'fr' => 'French', 'es' => 'Spanish', 'it' => 'Italian', 'pt' => 'Portuguese',
		'nl' => 'Dutch', 'ru' => 'Russian', 'ro' => 'Romanian', 'bg' => 'Bulgarian', 'hr' => 'Croatian', 'el' => 'Greek', 'he' => 'Hebrew', 'iw' => 'Hebrew',
		'id' => 'Indonesian', 'ms' => 'Malay', 'zh-CN' => 'Chinese', 'zh' => 'Chinese', 'zh-Hans' => 'Chinese', 'ja' => 'Japanese', 'ko' => 'Korean',
		'no' => 'Norwegian', 'da' => 'Danish', 'fi' => 'Finnish', 'pl' => 'Polish', 'so' => 'Somali', 'ne' => 'Nepali', 'gu' => 'Gujarati', 'mr' => 'Marathi');
}
function ctrCfg($a){
	$s = function($k, $d) use ($a){ return (isset($a[$k]) && $a[$k] !== '') ? (string)$a[$k] : $d; };
	$eng = $s('custom2', 'google');
	return array('site' => (int)$s('custom1', '1'), 'engine' => in_array($eng, array('google', 'azure')) ? $eng : 'google',
		'key' => $s('custom3', ''), 'region' => $s('custom4', ''), 'min' => (int)$s('custom5', '0'), 'rate' => max(5, (int)$s('custom6', '40')));
}
function ctrSiteLang($name){
	$m = array('English' => 'en', 'Arabic' => 'ar', 'Bulgarian' => 'bg', 'Croatia' => 'hr', 'Francais' => 'fr', 'German' => 'de', 'Greek' => 'el',
		'Hebrew' => 'he', 'Netherlands' => 'nl', 'Portuguese' => 'pt', 'Romana' => 'ro', 'Russian' => 'ru', 'Spanish' => 'es', 'Turkish' => 'tr');
	return isset($m[$name]) ? $m[$name] : 'en';
}
function ctrPref($user){
	global $mysqli;
	$uid = (int)$user['user_id'];
	$q = $mysqli->query("SELECT lang, auto FROM cnr_tr_pref WHERE user_id = '$uid' LIMIT 1");
	if($q && $q->num_rows){ $r = $q->fetch_assoc(); $l = isset(ctrLangs()[$r['lang']]) ? $r['lang'] : ctrSiteLang($user['user_language']); return array('lang' => $l, 'auto' => (int)$r['auto'], 'set' => 1); }
	return array('lang' => ctrSiteLang($user['user_language']), 'auto' => 0, 'set' => 0);
}
function ctrBase($l){ $l = strtolower((string)$l); if($l === 'iw'){ return 'he'; } if(strpos($l, 'zh') === 0){ return 'zh'; } $p = explode('-', $l); return $p[0]; }
function ctrClean($t){
	$t = html_entity_decode(strip_tags((string)$t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$t = preg_replace('/\s+/u', ' ', $t);
	return trim($t);
}
function ctrWorth($t){
	$x = preg_replace('~https?://\S+|www\.\S+~iu', '', $t);
	$x = preg_replace('/@\S+|:[a-z0-9_]+:/iu', '', $x);
	return preg_match_all('/\p{L}/u', $x) >= 2;
}
function ctrHttp($url, $post = null, $head = array()){
	$c = curl_init($url);
	curl_setopt_array($c, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_USERAGENT => 'Mozilla/5.0 (Codychat Translate add-on)'));
	if($post !== null){ curl_setopt($c, CURLOPT_POST, true); curl_setopt($c, CURLOPT_POSTFIELDS, $post); }
	if($head){ curl_setopt($c, CURLOPT_HTTPHEADER, $head); }
	$r = curl_exec($c); $code = (int)curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
	return ($code >= 200 && $code < 300) ? $r : false;
}
/* returns array(text, src) or false */
function ctrGoogle($t, $to){
	$r = ctrHttp('https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&dt=t&tl=' . rawurlencode($to) . '&q=' . rawurlencode($t));
	$j = ($r === false) ? null : json_decode($r, true);
	if(is_array($j) && isset($j[0]) && is_array($j[0])){
		$out = '';
		foreach($j[0] as $seg){ if(isset($seg[0]) && is_string($seg[0])){ $out .= $seg[0]; } }
		if($out !== ''){ return array($out, isset($j[2]) && is_string($j[2]) ? $j[2] : ''); }
	}
	/* second free route */
	$r = ctrHttp('https://clients5.google.com/translate_a/t?client=dict-chrome-ex&sl=auto&tl=' . rawurlencode($to) . '&q=' . rawurlencode($t));
	$j = ($r === false) ? null : json_decode($r, true);
	if(is_array($j) && isset($j[0])){
		if(is_array($j[0]) && isset($j[0][0]) && is_string($j[0][0])){ return array($j[0][0], isset($j[0][1]) ? (string)$j[0][1] : ''); }
		if(is_string($j[0])){ return array($j[0], ''); }
	}
	return false;
}
function ctrAzure($t, $to, $c){
	if($c['key'] === ''){ return false; }
	$h = array('Content-Type: application/json', 'Ocp-Apim-Subscription-Key: ' . $c['key']);
	if($c['region'] !== ''){ $h[] = 'Ocp-Apim-Subscription-Region: ' . $c['region']; }
	$az = ($to === 'zh-CN') ? 'zh-Hans' : (($to === 'he') ? 'he' : $to);
	$r = ctrHttp('https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&to=' . rawurlencode($az), json_encode(array(array('Text' => $t))), $h);
	if($r === false){ return false; }
	$j = json_decode($r, true);
	if(!isset($j[0]['translations'][0]['text'])){ return false; }
	return array($j[0]['translations'][0]['text'], isset($j[0]['detectedLanguage']['language']) ? $j[0]['detectedLanguage']['language'] : '');
}
function ctrRun($t, $to, $c){
	$r = ($c['engine'] === 'azure') ? ctrAzure($t, $to, $c) : ctrGoogle($t, $to);
	if($r === false){ $r = ($c['engine'] === 'azure') ? ctrGoogle($t, $to) : ctrAzure($t, $to, $c); }
	return $r;
}
function ctrRateOk($uid, $max){
	if(!defined('BOOM_REDIS') || !BOOM_REDIS){ return true; }
	global $redis;
	try {
		$k = BOOM_PREFIX . 'ctr:' . (int)$uid . ':' . floor(time() / 60);
		$n = $redis->incr($k); if($n == 1){ $redis->expire($k, 70); }
		return $n <= $max;
	} catch(Exception $e){ return true; }
}
?>
