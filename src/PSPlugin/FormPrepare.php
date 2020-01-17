<?php

class FormPrepare {
	
	public static function prepareZapchasti($modx, $data, $fl, $name)
	{
		$id = $modx->documentIdentifier;
		$fl->setField("url", $modx->makeUrl($id,"", "", "full"));
	}
	
	public static function prepareZayavka($modx, $data, $fl, $name)
	{
		$id = $modx->documentIdentifier;
		$fl->setField("url", $modx->makeUrl($id,"", "", "full"));
		$razdel = $fl->getField("razdel");
		$childs = $modx->getDocumentChildren(3);
		$fieldRazdel = "<select id=\"razdel\" class=\"form-control\" name=\"razdel\">";
		$fieldRazdel .= "<option value=\"Задать вопрос специалисту\"".($razdel == "Задать вопрос специалисту" ? " selected=\"selected\"" : "").">Задать вопрос специалисту</option>";
		$fieldRazdel .= "<optgroup label=\"По разделам каталога\">";
		foreach($childs as $child):
			$title = $child["pagetitle"];
			$selected = ($razdel == $title ? " selected=\"selected\"" : "");
			$fieldRazdel .= "<option value=\"".$modx->htmlspecialchars($title)."\"".$selected.">".$title."</option>";
			// selected="selected"
		endforeach;
		$fieldRazdel .= "</optgroup></select>";
		$fl->setPlaceholder("razdelplh", $fieldRazdel);
	}
	
	public static function setResultZayavkaForm($modx, $data, $fl, $name)
	{
		$id = $modx->documentIdentifier;
		$site = $modx->config['site_name'];
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
		$fl->setPlaceholder("messagecomment", $coment);
		$fl->setField("pagetitle", $title);
		
		$cfg = $fl->config->getConfig();
		
		switch($idform){
			case "zayavka":
				$theme = "Заявка на технику " . $razdel;
				$fl->mailConfig['subject']  = $cfg["subject"] = "Заявка на технику с сайта ".$site.": ".$razdel;
				break;
			case "zapchast":
				$theme = "Заявка на запчасти";
				$fl->mailConfig['subject']  = $cfg["subject"] = "Заявка на запчасти с сайта ".$site;
				break;
		}
		$fl->setField("subjectval", $cfg["subject"]);
		$fl->mailConfig['replyTo']  = $cfg["replyTo"] = $email;
		
		$fl->mailConfig['fromName']  = $cfg["fromName"] = 'Робот сайта компании ООО «СКАТ»';
		$fl->mailConfig['from']  = $cfg["from"] = 'noreply@skat59.ru';
		
		/* test */
		$fl->mailConfig['to']  = $cfg["to"] = 'projectsoft2009@yandex.ru';
		$fl->mailConfig['bcc']  = $cfg["bcc"] = 'direkt.skat@yandex.ru';
		/* end test */
		
		$fl->config->setConfig($cfg);
		// write db result
		$fields = array(
			'ip'		=>	$modx->db->escape($ip),
			'form'		=>	$modx->db->escape($idform),
			'name'		=>	$modx->db->escape($fname),
			'email'		=>	$modx->db->escape($email),
			'phone'		=>	$modx->db->escape($phone),
			'theme'		=>	$modx->db->escape($theme),
			'comment'	=>	$modx->db->escape($coment),
			'pageid'	=>	$modx->db->escape($id),
			'policy'	=>	$modx->db->escape($policy),
			'date'		=>	$modx->db->escape(time())
		);
		$modx->db->insert($fields, $modx->getFullTableName('site_forms_result'));
	}
}