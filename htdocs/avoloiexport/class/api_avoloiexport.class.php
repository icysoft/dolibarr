<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2016   Jean-François Ferry     <jfefe@aternatik.fr>
 *
 * This program is free software you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

include_once DOL_DOCUMENT_ROOT . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php";

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiExport {@requires user,external}
 */
class AvoloiExport extends DolibarrApi
{

	/**
	 * @var array   $DOCUMENT_FIELDS     Mandatory fields, checked when create and update object
	 */
	static $DOCUMENT_FIELDS = array(
		'modulepart'
	);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	/**
	 * Export des facturations en fichier excel
	 * 
	 * @return array List of documents
	 *
	 * @throws 400
	 * @throws 200
	 *
	 * @url PUT /facturations
	 */
	public function facturations($datestart, $dateend)
	{

		if (!$datestart || !$dateend) {
			throw new RestException(400, "Missing field");
		}

		if (!$this->isValidTimestamp($datestart) || !$this->isValidTimestamp($dateend)) {
			throw new RestException(400, "Wrong field format");
		}

		$sql = "SELECT *";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture as c";
		$sql .= " WHERE (c.date_valid >= FROM_UNIXTIME($datestart, '%Y-%m-%d'))";
		$sql .= " AND (c.date_valid <= FROM_UNIXTIME($dateend, '%Y-%m-%d'))";

		$resql = $this->db->query($sql);
		// $sql.= " WHERE (c.date_valid = FROM_UNIXTIME(?, '%Y-%m-%d'))";

		// $stmt= $db->prepare($sql);
		// $resql= $stmt->execute([$datestart]);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$factures = new Facture($this->db);
				if ($factures->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($factures);
				}
				$i++;
			}
		}
		require_once DOL_DOCUMENT_ROOT . '/avoloiexport/class/create_spreadsheet.class.php';
		$create_spreadsheet = new CreateSpreadsheet();
		return $create_spreadsheet->write('facturations', '/export/facturations/Export_de_la_facturation.xlsx', $obj_ret);
		// return $obj_ret;
	}

	/**
	 * Export des encaissements en fichier excel
	 * 
	 * @return array List of documents
	 *
	 * @throws 400
	 * @throws 200
	 *
	 * @url PUT /encaissements
	 */
	public function encaissements($datestart, $dateend)
	{

		if (!$datestart || !$dateend) {
			throw new RestException(400, "Missing field");
		}

		if (!$this->isValidTimestamp($datestart) || !$this->isValidTimestamp($dateend)) {
			throw new RestException(400, "Wrong field format");
		}

		$sql = "SELECT *";
		$sql .= " FROM " . MAIN_DB_PREFIX . "paiement as c";
		$sql .= " WHERE (c.datec >= FROM_UNIXTIME($datestart))";
		$sql .= " AND (c.datec <= FROM_UNIXTIME($dateend))";

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$factures = new Facture($this->db);
				if ($factures->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($factures);
				}
				$i++;
			}
		}
		require_once DOL_DOCUMENT_ROOT . '/avoloiexport/class/create_spreadsheet.class.php';
		$create_spreadsheet = new CreateSpreadsheet();
		return $create_spreadsheet->write('encaissements', '/export/encaissements/Export_des_encaissements.xlsx', $obj_ret);
		// return $obj_ret;
	}

	function isValidTimeStamp($timestamp)
	{
		return ((string) (int) $timestamp === $timestamp)
			&& ($timestamp >= 0) && strlen($timestamp) === 10;
	}
}
