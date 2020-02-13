<?php
use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT . '/main.inc.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiMinimumAccess {@requires user}
 */

class AvoloiMinimumAccess extends DolibarrApi
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
  }

  // TIERS

  /**
   * Get all tiers
   * @url GET /tiers
   */
  public function getalltiers() {}

  /**
   * Get tiers by ID
   * @url GET /tiers/{id}
   */
  public function gettiers($id) {}

  // AFFAIRS

  /**
   * Get all affairs
   * @url GET /affairs
   */
  public function getallaffairs() {}

  /**
   * Get affair by ID
   * @url GET /affairs/{id}
   */
  public function getaffair($id) {}

  // PROPOSALS

  /**
   * Get all proposals
   * @url GET /proposals
   */
  public function getallproposals() {}

  /**
   * Get proposal by ID
   * @url GET /proposals/{id}
   */
  public function getproposal($id) {}
}