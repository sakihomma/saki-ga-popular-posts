<?php
require_once( dirname(dirname(dirname(dirname( __FILE__ )))) . '/wp-load.php' );

$gaapi = get_option( 'sakigapp_gaapi' );
$keyfile = get_option( 'sakigapp_keyfile' );
$viewid = get_option( 'sakigapp_viewid' );

$start = isset($_REQUEST["start"]) ? $_REQUEST["start"] : "7daysAgo";
$end = isset($_REQUEST["end"]) ? $_REQUEST["end"] : "today";
$num = isset($_REQUEST["num"]) ? intval($_REQUEST["num"]) : 10;// init 10

// Load the Google API PHP Client Library.
require_once $gaapi;

$analytics = initializeAnalytics( $keyfile );
$response = getReport( $viewid, $start, $end, $analytics );

//printResults($response);
printResults($response,$num);

function initializeAnalytics($keyfile)
{
  // Creates and returns the Analytics Reporting service object.

  // Use the developers console and download your service account
  // credentials in JSON format. Place them in this directory or
  // change the key file location if necessary.
  $KEY_FILE_LOCATION = $keyfile;

  // Create and configure a new client object.
  $client = new Google_Client();
  $client->setApplicationName("Hello Analytics Reporting");
  $client->setAuthConfig($KEY_FILE_LOCATION);
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_AnalyticsReporting($client);

  return $analytics;
}

function getReport( $viewid, $start, $end, $analytics ) {

  // Replace with your view ID, for example XXXX.
  $VIEW_ID = $viewid;

  // Create the DateRange object.
  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
  $dateRange->setStartDate($start);
  $dateRange->setEndDate($end);

  // Create the Metrics object.
  $sessions = new Google_Service_AnalyticsReporting_Metric();
  $sessions->setExpression("ga:pageviews");
  $sessions->setAlias("pageviews");

  // Create the Dimension object.
  $dimention = new Google_Service_AnalyticsReporting_Dimension();
  $dimention->setName("ga:pagePathLevel1");

  $dimention2 = new Google_Service_AnalyticsReporting_Dimension();
  $dimention2->setName("ga:pageTitle");

  // Filter @@@ PLEASE CONFIRM @@@
  $filter = new Google_Service_AnalyticsReporting_DimensionFilter();
  $filter->setDimensionName("ga:pagePathLevel1");
  $filter->setNot(true);
  $filter->setOperator("IN_LIST");
  $filter->setExpressions( ["/sakidesign.com", "/category/"] );

  $filters = new Google_Service_AnalyticsReporting_DimensionFilterClause();
  $filters->setFilters(array($filter));

  // OrderBy
  $orderby = new Google_Service_AnalyticsReporting_OrderBy();
  $orderby->setFieldName("ga:pageviews");
  $orderby->setOrderType("VALUE");
  $orderby->setSortOrder("DESCENDING");

  // Create the ReportRequest object.
  $request = new Google_Service_AnalyticsReporting_ReportRequest();
  $request->setViewId($VIEW_ID);
  $request->setDateRanges($dateRange);
  $request->setMetrics(array($sessions));
  $request->setDimensions(array($dimention,$dimention2));
  $request->setDimensionFilterClauses($filters);
  $request->setOrderBys($orderby);

  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
  $body->setReportRequests( array( $request) );
  return $analytics->reports->batchGet( $body );
}

function printResults($reports,$num) {
	$report = $reports[0];
	$rows = $report->getData()->getRows();

	$data = array();
	for ( $rowIndex = 0; $rowIndex < count($rows) && $rowIndex < $num; $rowIndex++) {
		$row = $rows[ $rowIndex ];

		$dimensions = $row->getDimensions();
//		print( $dimensions[0] ); //pagePathLevel1
//		print( $dimensions[1] ); //pageTitle

		$metrics = $row->getMetrics();
		$values = $metrics[0];
		$value = $values->getValues()[0]; //pageviews
//		print($value);//pageviews

		$data[] = array($dimensions[0],$dimensions[1],$value);
    }
	$data = json_encode( $data );

	Header("Content-Type: application/json; charset=utf-8");
	Header("X-Content-Type-Options: nosniff");
	echo $data;
}
?>