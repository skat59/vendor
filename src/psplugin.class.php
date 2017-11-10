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
		global $modx;
		global $PS;
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
		$data["tv.img_plitca"] = $modx->runSnippet("phpthumb", array(
			"input"=>$data["tv.img_plitca"],
			"options"=>'w=648,h=307,far=C,bg=ffffff,f=jpeg,q=60'
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
		$data["newsimage"] = $modx->runSnippet("phpthumb", array(
			"input"=>$data["tv.ogimage"],
			"options"=>"w=648,h=307,far=C,bg=ffffff,f=jpeg,q=60"
		));
		return $data;
	}
}