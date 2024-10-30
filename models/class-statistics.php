<?php
namespace mx_analytics;

if ( ! defined( 'ABSPATH' ) )
	exit();

interface Statistics {

	function validate();

	function get_counters();

	function get_sessions_count( \DateTime $startDate, \DateTime $endDate );

	function get_users_count( \DateTime $startDate, \DateTime $endDate );

	function get_show_pages_count( \DateTime $startDate, \DateTime $endDate );

	function get_bounce_rate( \DateTime $startDate, \DateTime $endDate );

	function get_page_per_session_count( \DateTime $startDate, \DateTime $endDate );

	function get_organic_search_count( \DateTime $startDate, \DateTime $endDate );

}