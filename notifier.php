<?
require 'vendor/phpmailer/phpmailer/PHPMailerAutoload.php';

mt_srand();
libxml_use_internal_errors(true);

function get_inner_html( $node ) {
	if (!$node) return "";
	$innerHTML= '';
	$children = $node->childNodes;
	foreach ($children as $child) {
		$innerHTML .= $child->ownerDocument->saveXML( $child );
	}

	return $innerHTML;
}

function str_to_dom($str) {
	$html = new DOMDocument();
	$html->loadHTML('<?xml encoding="UTF-8">' . $str, LIBXML_NOWARNING);
	libxml_clear_errors();
	return $html;
}

function elt_to_dom($elt) {
	$str = get_inner_html($elt);
	return str_to_dom($str);
}

if (!file_exists("seen.json")) {
	file_put_contents("seen.json", "[]");
}

$config = json_decode(file_get_contents("config.json"), true);
$seen = json_decode(file_get_contents("seen.json"), true);

foreach($config["queries"] as $entry) {
	$dom = str_to_dom(file_get_contents($entry["url"]));

	$finder = new DomXPath($dom);
	$classname="ResultsAd";
	$nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");

	foreach($nodes as $node) {
		$hash = sha1(get_inner_html($node));
		if (!in_array($hash, $seen)) {
			$seen[] = $hash;

			$ad = array(
				"img" => false,
				"title" => false,
				"url" => false,
				"properties" => "",
				"price" => false
			);

			$ad_dom = elt_to_dom($node);

			foreach($ad_dom->getElementsByTagName("img") as $image) {
				if (!$ad["img"]) {
					$ad["img"] = $image->getAttribute("src");
				}
			}

			$finder = new DomXPath($ad_dom);
			$classname="ResultsAdPrice";
			$ad_nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");

			foreach($ad_nodes as $ad_node) {
				if (!$ad["price"]) {
					$ad["price"] = trim(html_entity_decode(strip_tags(get_inner_html($ad_node))));
				}
			}


			$finder = new DomXPath($ad_dom);
			$classname="Adlink";
			$ad_nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");

			foreach($ad_nodes as $ad_node) {
				if (!$ad["title"]) {
					$ad["title"] = trim(html_entity_decode(strip_tags(get_inner_html($ad_node))));
					$ad["url"] = "http://www.avto.net/Ads/" . $ad_node->getAttribute("href");
				}
			}

			foreach($ad_dom->getElementsByTagName("li") as $li) {
				$ad["properties"] .= trim(html_entity_decode(strip_tags(get_inner_html($li)))) ."\n<br>";
			}

			$ad["properties"] = trim($ad["properties"]);

			$mail = new PHPMailer;

			//$mail->SMTPDebug = 3;

			$mail->isSMTP();
			$mail->CharSet = 'UTF-8';
			$mail->Host = $config["smtp"]["host"];
			$mail->SMTPAuth = true;
			$mail->Username = $config["smtp"]["username"];
			$mail->Password = $config["smtp"]["password"];
			$mail->SMTPSecure = $config["smtp"]["secure"];
			$mail->Port = $config["smtp"]["port"];

			$mail->setFrom($config["smtp"]["from"], 'avto.net notifier');
			$mail->addAddress($config["smtp"]["to"]);

			$mail->isHTML(true);

			$mail->Subject = 'Avto.net notifier - ' . $ad["title"] . " - " . $ad["price"];
			$mail->Body = "<b><a href='" . $ad["url"] . "'>" . $ad["title"] . " - " . $ad["price"] . "</a></b><br><br>";
			$mail->Body .= "<img src='" . $ad["img"] . "' style='float:left'><br>";
			$mail->Body .= $ad["properties"];

			$mail->send();
		}
	}
}

file_put_contents("seen.json", json_encode($seen));
?>