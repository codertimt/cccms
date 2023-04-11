<?php



function html_formatGroup($title, $htmlBody)
{
	$html = "<div class=\"group\">\n";
	$html .= "<div class=\"groupTitle\">$title</div>\n";
	$html .= $htmlBody;
	$html .= "</div>\n";

	return $html;
}



?>
