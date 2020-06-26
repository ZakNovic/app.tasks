<?php
namespace AppTask;


class Pager
{
	/**
	 * How many records must be displayed per page.
	 */
	const ITEMS_IN_PAGE = 3;
	
	function __construct()
	{
	}
	
	public static function getQueries($input, $entriesCount)
	{
		$queries = array();
		//number of pages on which all results can be displayed
		$pageCount = ceil($entriesCount / self::ITEMS_IN_PAGE);
		//for each of pages we create an original link
		for ($i = 1; $i <= $pageCount; $i++) {
			$queries[$i] = self::getPaginationQuery($input, $i);
		}
		
		return $queries;
	}
	
	/**
	 * Fetches current get parameter and produces query for them. Just append it to url with '?'.
	 * @param array $input Array, containing GET keys=>values
	 * @param int $page Current page
	 * @return string query to append to URL
	 */
	private static function getPaginationQuery($input, $page = 0)
	{
		if (is_int($page)) {
			$input['page'] = $page;
		} else throw new \InvalidArgumentException('Parameter is not int.');
		
		//sort other get parameters
		ksort($input);
		//build a line of parameters for future urls
		$query = http_build_query($input);
		
		return $query;
	}
	
}