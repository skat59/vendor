<?php
$PS = array();

$PS['NewsDate'] = '$format = isset($format) ? $format : "d.m.Y"; return (IntVal($pub_date) ? date($format, IntVal($pub_date)) : date($format, IntVal($createdon)));';
$PS['GetDocAlias'] = '$doc = $modx->getDocument($id, "alias"); return $doc["alias"];';
class PSPlugin {
	
	public static function getYearCompany(){
		return (date("Y") - 1999);
	}
	
	public static function getCompanyCopyright(){
		return "ООО «СКАТ» © 1999 — ".date("Y");
	}
	
	public static function addPsSnippets() {
		global $_lang;
		global $modx;
		global $PS;
		$_lang['theme_skat'] = 'ТЕМА СКАТ';
		$modx->addSnippet('GetDocAlias', $PS['GetDocAlias']);
		$modx->addSnippet('NewsDate', $PS['NewsDate']);
		$latLong = explode(",", $modx->config["latlong_map"]);
		$modx->config['latitude'] = $latLong[0];
		$modx->config['longitude'] = $latLong[1];
	}
	
	public static function renderMapSettings(){
		global $modx;
		$map = $modx->config['latlong_map'];
		$keyGoogle = $modx->config['googleKey'];
		$output = '
		<style>
			tr > td[colspan="2"] > table {
				width: 100%;
			}
			.setting-map {
				position: relative;
				margin-top: 10px;
				margin-bottom: 10px;
				height: 0px;
				padding-bottom: 56.25%;
			}
			.setting-map > #companyMap {
				position: absolute;
				top: 0px;
				bottom: 0px;
				right: 0px;
				left: 0px;
				background-color: #ccc;
				width: 100%;
				height: 100%;
			}
		</style>
		<script>
			jQuery(function($){
				var mapInput = $("input[name=latlong_map]"),
					mapZoom = $("input[name=zoom_map]"),
					zoomChange = function(e){
						if(parseInt($(this).val()) != NaN){
							var latlong = {lat: val[0], lng: val[1]}
							zoom = parseInt($(this).val());
							map.setZoom(zoom);
							map.setCenter(latlong);
						}
					};
				if(mapInput.length){
					var val = mapInput.val().split(","),
						zoom = parseInt(mapZoom.val()),
						parentInput = mapInput.parent(),
						mapBlock = "<div class=\"setting-map\"><div id=\"companyMap\"></div></div>";
					parentInput.append(mapBlock);
					if(isNaN(zoom)){
						zoom=17;
						mapZoom.val(zoom);
					}
					if(val.length != 2){
						val = [58.011829,56.204462]
						mapInput.val(val.join(","));
					}else{
						val[0] = parseFloat(val[0]);
						val[1] = parseFloat(val[1]);
					}
					window.initMapContact = function(){
						var latlong = {lat: val[0], lng: val[1]},
							map = new google.maps.Map(document.getElementById("companyMap"), {
								zoom: zoom,
								center: latlong
							}),
							marker = new google.maps.Marker(
								{
									position: latlong,
									map: map,
									draggable:true
								}
							);
						google.maps.event.addListener(marker, "dragend", function() {
							var position = this.getPosition();
							val[0] = position.lat();
							val[1] = position.lng();
							mapInput.val(val.join(","));
						});
						
						google.maps.event.addListener(map, "bounds_changed", function() {
							mapZoom.unbind("input change", zoomChange);
							zoom = this.getZoom();
							mapZoom.val(zoom);
							mapZoom.on("input change", zoomChange);
						});
						
						mapInput.on("input change", function(e){
							var v = $(this).val().split(",");
							if(v.length == 2){
								var la = parseFloat(v[0]),
									ln = parseFloat(v[1]);
								if(la != NaN && ln != NaN){
									val[0] = la;
									val[1] = ln;
									mapInput.val(val.join(","));
									var p = new google.maps.LatLng(la, ln);
									marker.setPosition(p);
									//map.setCenter(p);
								}
							}
						});
						mapZoom.on("input change", zoomChange);
					};
					var scr = document.createElement("script");
					$(scr).attr({
						async: "",
						defer: "",
						src: "https://maps.googleapis.com/maps/api/js?key='.$keyGoogle.'&callback=initMapContact"
					});
					$("body").append(scr);
				}
			});
		</script>';
		return $output;
	}
	
	public static function prepareProizvoditel(array $data = array(), DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister)
	{
		$data["prepare.link"] = "";
		$pr = $_DL->getCFGDef("link");
		$pr = (IntVal($pr)==0) ? 0 : 1;
		$data["tv.img_plitca"] = $modx->runSnippet("#thumb", array(
			"input"=>$data["tv.img_plitca"],
			"options"=>'w=648,h=307,far=C,bg=ffffff,f=jpg,q=60'
		));
		$proizvoditel = IntVal($data["tv.proizvoditel"]);
		if($proizvoditel){
			$ob = $modx->getDocument($proizvoditel);
			if($ob){
				$url = $modx->makeUrl($ob["id"]);
				$title = $ob["pagetitle"];
				$data["prepare.link"] = "<span class=\"news-item-description-date\"><em>Производитель: ".($pr ? "<a href=\"{$url}\">" : "")."{$title}".($pr ? "</a>" : "")."</em></span>";
			}
		}
		return $data;
	}
	
	public static function prepareBuPrice(array $data = array(), DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister)
	{
		//$data["price"] = "Цена";
		$pr = $_DL->getCFGDef("link");
		$pr = (IntVal($pr)==0) ? 0 : 1;
		$data["ogimage"] = $modx->runSnippet("#thumb", array(
			"input"=>$data["ogimage"],
			"options"=>'w=648,h=307,zc=C,bg=ffffff,f=jpg,q=60'
		));
		$price = strip_tags(trim($data["price"]." "));
		if(mb_strlen($price)>1):
			$data["price"] = "<p><strong><span class=\"specprice-val\">Цена: <span class=\"red\">{$price}</span></span></strong></p>";
		endif;
		return $data;
	}
	
	public static function prepareCpecPred(array $data = array(), DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister)
	{
		$data["specprice"] = "";
		$data["price"] = "Цена";
		$pr = $_DL->getCFGDef("link");
		$pr = (IntVal($pr)==0) ? 0 : 1;
		$data["tv.ogimage"] = $modx->runSnippet("#thumb", array(
			"input"=>$data["tv.ogimage"],
			"options"=>'w=648,h=307,zc=C,bg=ffffff,f=jpg,q=60'
		));
		$specCena = trim($data["tv.specprice"]." ");
		if(mb_strlen($specCena)>1):
			$data["specprice"] = "<p><strong><span class=\"specprice-val\">Цена по спецпредложению: <span class=\"red\">{$specCena}</span></span></strong></p>";
		endif;
		$price = strip_tags(trim($data["tv.price"]." "));
		if(mb_strlen($price)>1):
				$data["price"] = "<p><strong><span class=\"specprice-val\">Цена: <span class=\"red\">{$price}</span></span></strong></p>";
		endif;
		return $data;
	}
	
	public static function prepareVideo(array $data = array(), DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister)
	{
		$data["prepare.link"] = "";
		$data["tv.ogimage"] = $modx->runSnippet("#thumb", array(
			"input"=>$data["tv.ogimage"],
			"options"=>'w=648,h=307,far=C,bg=000000,f=jpg,q=60'
		));
		return $data;
	}
	
	public static function preparePartner(array $data = array(), DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister)
	{
		$data["imgpart"] = $data["tv.imgpart"];
		return $data;
	}
	
	public static function prepareNews(array $data = array(), DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister)
	{
		$format = $_DL->getCFGDef("dateformat");
		$date = strlen($format) ? $format : "d.m.Y";
		$data["newsdate"] = (IntVal($data["pub_date"]) ? date($format, IntVal($data["pub_date"])) : date($format, IntVal($data["createdon"])));
		$data["newsimage"] = $modx->runSnippet("#thumb", array(
			"input"=>$data["tv.ogimage"],
			"options"=>"w=648,h=307,zc=C,bg=ffffff,f=jpg,q=60"
		));
		return $data;
	}
	
	public static function getModelNavesnoe(array $data = array(), DocumentParser $modx, $_DL, prepare_DL_Extender $_extDocLister)
	{
		global $GetModelsNaves;
		//$GetModelsNaves = $modx->GetModelsNaves;
		$models = explode(",", $data["tv.navesnoe"]);
		$arOut = array();
		foreach($models as $key=>$value):
			$arOut[] = $GetModelsNaves[$value];
		endforeach;
		$img = $data["tv.img_plitca"] == 'assets/images/default_reset.jpg' ? $data["tv.ogimage"] : $data["tv.img_plitca"];
		$data["newsimage"] = $modx->runSnippet("#thumb", array(
			"input"=>$img,
			"options"=>"w=648,h=307,zc=C,bg=ffffff,f=jpg,q=60"
		));
		$data["classes"] = implode(" ", $arOut);
		return $data;
	}
	
	public static function prepareZapchasti($modx, $data, $fl, $name)
	{
		$id = $modx->documentIdentifier;
		$fl->setField("url", "https://www.skat59.ru/" . $id . "/");
	}
	
	public static function prepareJcbSpare($modx, $data, $fl, $name)
	{
		$id = $modx->documentIdentifier;
		$fl->setField("url", "https://www.skat59.ru/" . $id . "/");
		$h1 = $modx->documentObject["h1"];
		include_once(MODX_MANAGER_PATH . 'includes/tmplvars.format.inc.php');
		include_once(MODX_MANAGER_PATH . 'includes/tmplvars.commands.inc.php');
		$h1 = getTVDisplayFormat($h1[0], $h1[1], $h1[2], $h1[3], $h1[4]);
		$arr = array(
			"pagetitle" => $modx->documentObject["pagetitle"],
			"longtitle" => $modx->documentObject["longtitle"],
			"menutitle" => $modx->documentObject["menutitle"]
		);
		$h1 = $modx->parseText($h1, $arr, "[*", "*]");
		$h1 = preg_replace('/&amp;(#[0-9]+|[a-z]+);/i', '&$1;', htmlspecialchars($h1, ENT_QUOTES, $modx->config['modx_charset']));
		$fl->setPlaceholder("themetitle", $h1);
	}
	
	public static function prepareZayavka($modx, $data, $fl, $name)
	{
		$id = $modx->documentIdentifier;
		$fl->setField("url", "https://www.skat59.ru/" . $id . "/");
		$razdel = $fl->getField("razdel");
		$childs = $modx->getDocumentChildren(3);
		$parent = ($modx->documentObject['parent'] == 498);
		$fieldRazdel = "<select id=\"razdel\" class=\"form-control\" name=\"razdel\">";
		$fieldRazdel .= "<option value=\"Задать вопрос специалисту\"".(($razdel == "Задать вопрос специалисту" && $parent==false) ? " selected=\"selected\"" : "").">Задать вопрос специалисту</option>";
		if($parent):
			$fieldRazdel .= "<option value=\"Купить " . $modx->documentObject['longtitle'] . "\" selected=\"selected\">Купить " . $modx->documentObject['longtitle'] . "</option>";
		endif;
		$fieldRazdel .= "<optgroup label=\"По разделам каталога\">";
		foreach($childs as $child):
			$title = $child["pagetitle"];
				$selected = (($razdel == $title && $parent==false) ? " selected=\"selected\"" : "");
			$fieldRazdel .= "<option value=\"".$modx->htmlspecialchars($title)."\"".$selected.">".$title."</option>";
			// selected="selected"
		endforeach;
		$fieldRazdel .= "</optgroup></select>";
		$fl->setPlaceholder("razdelplh", $fieldRazdel);
		$h1 = $modx->documentObject["h1"];
		include_once(MODX_MANAGER_PATH . 'includes/tmplvars.format.inc.php');
		include_once(MODX_MANAGER_PATH . 'includes/tmplvars.commands.inc.php');
		$h1 = getTVDisplayFormat($h1[0], $h1[1], $h1[2], $h1[3], $h1[4]);
		$arr = array(
			"pagetitle" => $modx->documentObject["pagetitle"],
			"longtitle" => $modx->documentObject["longtitle"],
			"menutitle" => $modx->documentObject["menutitle"]
		);
		$h1 = $modx->parseText($h1, $arr, "[*", "*]");
		$h1 = preg_replace('/&amp;(#[0-9]+|[a-z]+);/i', '&$1;', htmlspecialchars($h1, ENT_QUOTES, $modx->config['modx_charset']));
		$fl->setPlaceholder("themetitle", $h1);
		//file_put_contents(dirname(__FILE__) . "/test.txt", print_r($h1, true));
	}
	
	public static function setResultDevelopForm($modx, $data, $fl, $name)
	{
		$id = $modx->documentIdentifier;
		$site = html_entity_decode($modx->config['site_name']);
		$ip = \APIhelpers::getUserIP();
		$page = $modx->getPageInfo($id);
		$title = $page["pagetitle"];
		$idform = $fl->getField("formid");
		$comment = $fl->getField("comment");
		$fname = $fl->getField("first_name");
		$coment = strip_tags($comment);
		$fl->setPlaceholder("messagecomment", $coment);
		$fl->setField("pagetitle", $title);
		$cfg = $fl->config->getConfig();
		$theme = "Тестовое письмо для ProjectSoft";
		$fl->mailConfig['subject']  = $cfg["subject"] = "${theme} с сайта ${site}";
		$fl->setField("subjectval", $cfg["subject"]);
		$fl->mailConfig['replyTo']  = $cfg["replyTo"] = 'noreply@skat59.ru';
		$fl->mailConfig['fromName']  = $cfg["fromName"] = 'Робот сайта компании ООО «СКАТ»';
		$fl->mailConfig['from']  = $cfg["from"] = 'noreply@skat59.ru';
		$fl->mailConfig['to']  = $cfg["to"] = 'projectsoft2009@yandex.ru';
		
		$fl->config->setConfig($cfg);
	}
	
	public static function insertFormResult($modx, $fields) {
		$createdon = time();
		$date = intval($_SERVER['REQUEST_TIME']) + intval($modx->config['server_offset_time']);
		$print_date = $modx->toDateFormat($date);
		$cols = array(
			'ip'		=>	$modx->db->escape($fields['ip']),
			'form'		=>	$modx->db->escape($fields['form']),
			'name'		=>	$modx->db->escape($fields['name']),
			'email'		=>	$modx->db->escape($fields['email']),
			'phone'		=>	$modx->db->escape($fields['phone']),
			'theme'		=>	$modx->db->escape($fields['theme']),
			'comment'	=>	$modx->db->escape($fields['comment']),
			'pageid'	=>	$modx->db->escape($fields['pageid']),
			'policy'	=>	$modx->db->escape($fields['policy']),
			'date'		=>	$modx->db->escape($createdon)
		);
		$modx->db->insert($cols, $modx->getFullTableName('site_forms_result'));
		
		$message = "*Дата*: \t\t" . $print_date . "\n\n";
		
		switch($fields['form']){
			case "callme":
				// Заказ звонка
				$message .= "*Тема*: \t\tЗаказ звонка\n\n";
				$message .= "*Телефон*: \t\t" . $fields['phone'] . "\n";
				break;
			case "zayavka":
				// Форма обратной связи
				$message .= "*ФОРМА*: \t\tФорма обратной связи\n\n";
				$message .= "*Тема*: \t\t" . $fields['razdel'] . "\n";
				$message .= "*Имя*: \t\t" . $fields['name'] . "\n";
				$message .= "*Email:* \t\t" . $fields['email'] . "\n";
				$message .= "*Телефон:* \t\t" . $fields['phone'] . "\n";
				$message .= "*Сообщение:* \t\t" . $fields['comment'] . "\n\n";
				break;
			case "technic":
				// Заявка на покупку техники
				$message .= "*ФОРМА*: \t\tЗаявка на покупку техники\n";
				$message .= "*Техника*: \t\t" . $fields['razdel'] . "\n\n";
				$message .= "*Имя*: \t\t" . $fields['name'] . "\n";
				$message .= "*Email:* \t\t" . $fields['email'] . "\n";
				$message .= "*Телефон:* \t\t" . $fields['phone'] . "\n";
				$message .= "*Сообщение:* \t\t" . $fields['comment'] . "\n\n";
				break;
			case "zapchast":
				// Заявка на запчасти
				$message .= "*ФОРМА*: \t\tЗаявка на запчасти\n\n";
				$message .= "*Имя*: \t\t" . $fields['name'] . "\n";
				$message .= "*Email:* \t\t" . $fields['email'] . "\n";
				$message .= "*Телефон:* \t\t" . $fields['phone'] . "\n";
				$message .= "*Сообщение:* \t\t" . $fields['comment'] . "\n\n";
				break;
		}
		$message .= "*Страница отправки*:\n" . $fields['pagetitle'] . "\n";
		$message .= "https://www.skat59.ru/" . $fields['pageid'] . "/" . "\n";
		
		$modx->invokeEvent('OnSendFormSite', array(
			'message' => $message
		));
		
	}

	public static function setResultZayavkaForm($modx, $data, $fl, $name)
	{
		$id = $modx->documentIdentifier;
		$site = html_entity_decode($modx->config['site_name']);
		$ip = \APIhelpers::getUserIP();
		$page = $modx->getPageInfo($id);
		$title = $page["pagetitle"];
		$idform = $fl->getField("formid");
		$comment = $fl->getField("comment");
		$razdel = $fl->getField("razdel");
		$email = $fl->getField("email");
		$fname = $fl->getField("first_name");
		$phone = $fl->getField("phone");
		$coment = strip_tags($comment);
		$policy = strtolower($fl->getField("policy"));
		$policy = ($policy == 'on' ? 1 : 0);
		$fl->setField("pagetitle", $title);
		
		$cfg = $fl->config->getConfig();
		
		switch($idform){
			case "zayavka":
				$theme = "Заявка на технику ${razdel}";
				$fl->mailConfig['subject']  = $cfg["subject"] = "Заявка на технику с сайта: ${site}: ${razdel}";
				$fl->setPlaceholder("messagecomment", $coment);
				break;
			case "zapchast":
				$theme = "Заявка на запчасти";
				$fl->mailConfig['subject']  = $cfg["subject"] = "Заявка на запчасти с сайта: ${site}";
				$fl->setPlaceholder("messagecomment", $coment);
				break;
			case "technic":
				$theme = "Заявка на покупку техники: ${razdel}";
				$fl->mailConfig['subject']  = $cfg["subject"] = "Заявка на покупку техники с сайта: ${site}";
				$fl->setPlaceholder("messagecomment", $coment);
				break;
			case "sparejcb":
				$theme = "Заявка на ${razdel}";
				$fl->mailConfig['subject']  = $cfg["subject"] = "Заявка на ${razdel} с сайта: ${site}";
				$jcb_txt = "";
				$jcb_html = "";
				$jcb = json_decode($fl->getField("sparejcb_all"));
				if(is_array($jcb)):
					if(count($jcb)):
						$jcb_txt = "Заказанные запчасти:" . PHP_EOL;
						$jcb_html = "<h3>Заказанные запчасти</h3><ol>";
						foreach ($jcb as $key => $value) {
							$jcb_txt .= ($key + 1) . $value->full . PHP_EOL;
							$jcb_html .= "<li><b>Артикул:</b> " . $value->article . "<br>";
							$jcb_html .= "<b>Наименование:</b> " . $value->name . "<br>";
							$jcb_html .= "<b>Производство:</b> " . $value->proizvod . "</li>";
						}
						$jcb_html .= "</ol>";
						$html = $coment . "<br>" . $jcb_html;
						$coment = $coment . PHP_EOL . $jcb_txt;
						$fl->setPlaceholder("messagecomment", $html);
					endif;
				endif;
				break;
		}
		$fl->setField("subjectval", $cfg["subject"]);
		$fl->mailConfig['replyTo']  = $cfg["replyTo"] = $email;
		
		$fl->mailConfig['fromName']  = $cfg["fromName"] = 'Робот сайта компании ООО «СКАТ»';
		$fl->mailConfig['from']  = $cfg["from"] = 'noreply@skat59.ru';
		
		/* test */
		//$fl->mailConfig['to']  = $cfg["to"] = 'projectsoft2009@yandex.ru';
		//$fl->mailConfig['bcc']  = $cfg["bcc"] = 'direkt.skat@yandex.ru';
		/* end test */
		
		$fl->config->setConfig($cfg);
		// write db result
		$fields = array(
			'ip'		=>	$ip,
			'form'		=>	$idform,
			'name'		=>	$fname,
			'email'		=>	$email,
			'phone'		=>	$phone,
			'theme'		=>	$theme,
			'comment'	=>	$coment,
			'pageid'	=>	$id,
			'policy'	=>	$policy,
			'date'		=>	time(),
			'pagetitle'	=>	$title,
			'url'		=>	$modx->makeUrl($id, '', '', 'full'),
			'razdel'	=>	$razdel
		);
		
		self::insertFormResult($modx, $fields);
	}
	
	public static function setResultCallmeForm($modx, $data, $fl, $name)
	{
		
		$id = $modx->documentIdentifier;
		$site = html_entity_decode($modx->config['site_name']);
		$ip = \APIhelpers::getUserIP();
		$page = $modx->getPageInfo($id);
		$title = $page["pagetitle"];
		$idform = $fl->getField("formid");
		$phone = $fl->getField("phone");
		$comment = "Заказ звонка с сайта на номер " . $phone;
		$razdel = "Заказ звонка с сайта";
		$email = "noreply@skat59.ru";
		$fname = "Аноним";
		$policy = 1;
		$fl->setPlaceholder("messagecomment", $comment);
		$fl->setField("pagetitle", $title);
		$cfg = $fl->config->getConfig();
		$theme = "Заказ звонка на номер";
		
		$fl->mailConfig['subject']  = $cfg["subject"] = "Заказ звонка с сайта ${site}";
		
		$fl->setField("subjectval", $cfg["subject"]);
		$fl->mailConfig['replyTo']  = $cfg["replyTo"] = $email;
		
		$fl->mailConfig['fromName']  = $cfg["fromName"] = 'Робот сайта компании ООО «СКАТ»';
		$fl->mailConfig['from']  = $cfg["from"] = 'noreply@skat59.ru';
		
		/* test */
		//$fl->mailConfig['to']  = $cfg["to"] = 'projectsoft2009@yandex.ru';
		//$fl->mailConfig['bcc']  = $cfg["bcc"] = 'direkt.skat@yandex.ru';
		/* end test */
		
		$fl->config->setConfig($cfg);
		// write db result
		$fields = array(
			'ip'		=> $ip,
			'form'		=> $idform,
			'name'		=> $fname,
			'email'		=> $email,
			'phone'		=> $phone,
			'theme'		=> $theme,
			'comment'	=> $comment,
			'pageid'	=> $id,
			'policy'	=> $policy,
			'date'		=> time(),
			'pagetitle'	=>	$title,
			'url'		=>	$modx->makeUrl($id, '', '', 'full'),
			'razdel'	=>	$razdel
		);
		self::insertFormResult($modx, $fields);
	}
}