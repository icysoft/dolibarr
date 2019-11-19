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
use Luracast\Restler\Format\UploadFormat;


require_once DOL_DOCUMENT_ROOT.'/main.inc.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiDivers {@requires user,external}
 */
class AvoloiCustomTypes extends DolibarrApi
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
	 * Update color of a contact type
	 * 
	 * @param string    $id  					Type ID
	 * @param string    $color_code  	Type string
	 * @return  string								Updated type ID
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url PUT /contacttypecolor
	 */
	public function contacttypecolor($id, $color_code = "#737373") {
		global $conf, $langs, $user;
		
		$sql = "UPDATE `avo_custom_contact_type`";
		$sql.= " SET `color`='$color_code',";
		$sql.= " `fk_pays`='1'";
		$sql.= " WHERE rowid = $id";

		$resql=$this->db->query($sql);

		return $id;
	}

	/**
	 * Update color of a contact type
	 * 
	 * @param string    $id  					Type ID
	 * @param string    $color_code  	Type string
	 * @return  string								Updated type ID
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /contacttypes
	 */
	public function getcontacttypes() {
		global $conf, $langs, $user;
		
		$sql = "SELECT * FROM `avo_custom_contact_type`";

		$resql=$this->db->query($sql);

		$test = $this->_cleanObjectDatas($resql);

		$rtdObject = array();

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min)
			{
				$obj = $this->db->fetch_object($resql);
				$rtdObject[] = $obj;
				$i++;
			}
		}

		foreach ($rtdObject as &$r) {
			$r->datec = $this->db->jdate($r->datec);
		}

		return $rtdObject;
	}
}
