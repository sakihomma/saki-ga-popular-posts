<?php
/**
 * shortcode
 */

add_shortcode( 'sakigapp', 'saki_ga_popular_posts' );
function saki_ga_popular_posts( $atts ) {
	extract( shortcode_atts( array(
		'num' => '10',
		'start' => '7daysAgo',
		'end' => 'today',
	), $atts ) );

	$getdata = array(
		"num" => $num,
		"start" => $start,
		"end" => $end,
	);
	$getdata = http_build_query($getdata, "", "&");

	$url = plugin_dir_url( __FILE__ ) . "getGAPopularPage.php?" . $getdata;

	if(false === ( $value = get_transient('saki_gapp') ) || $value["url"] != $url):

		$json = file_get_contents( $url );
		$json = mb_convert_encoding( $json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN' );
		$jsondata = json_decode( $json, true );

		$value = array(
			"url"=>$url,
			"data"=>$jsondata
			);
		set_transient( "saki_gapp", $value, 60*60*24 );// 1day
	endif;

	$html = "";
	if ($value["data"] === NULL) {
		$html = "no posts";
	}else{
		$html = "<ol>";
		foreach($value["data"] as $row ):
			$html .= "<li><a href='$row[0]'>$row[1]</a> ($row[2])</li>";
		endforeach;
		$html .= "</ol>";
	}
	return $html;
}

?>