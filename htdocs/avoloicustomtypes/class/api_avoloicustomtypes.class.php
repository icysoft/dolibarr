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

	/////////////// PARTIE TYPE DE CONTACT ///////////////

	/**
	 * Update color of a contact type
	 *
	 * @param 	string    $id  					Type ID
	 * @param 	string    $color_code  	Type string
	 * @return  string									Updated type ID
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
	public function contacttypecolor($id, $color_code = "737373") {
		global $conf, $langs, $user;

		if ($color_code === "") {
			$color_code = "737373";
		}

		if (!ctype_xdigit($color_code)) {
			throw new RestException(400, "$color_code is not a hexadecimal digit");
		}

		$sql = "UPDATE `avo_custom_contact_type`";
		$sql.= " SET `color`='$color_code',";
		$sql.= " `fk_pays`='1'";
		$sql.= " WHERE rowid = $id";

		$resql=$this->db->query($sql);

		return $id;
	}

	/**
	 * Get list of contact types
	 *
	 * @return  string								Updated type ID
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /getallcontacttype
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
				$obj->datec = $this->db->jdate($obj->datec);
				$rtdObject[] = $obj;
				$i++;
			}
		}

		return $rtdObject;
	}

	/////////////// PARTIE TYPE DE TIERS ///////////////

	/**
	 * Get list of tiers types
	 *
	 * @param string    $id  					Type ID
	 * @return  string								Updated type ID
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /tierstypes
	 */
	public function gettierstypes() {
		global $conf, $langs, $user;

		$sql = "SELECT * FROM `avo_custom_tiers_type`";

		$resql=$this->db->query($sql);

		$test = $this->_cleanObjectDatas($resql);

		$rtdObject = array();

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min)
			{
				$obj = $this->db->fetch_object($resql);
				$obj->datec = $this->db->jdate($obj->datec);
				$obj->datem = $this->db->jdate($obj->datem);
				$rtdObject[] = $obj;
				$i++;
			}
		}

		return $rtdObject;
	}

	/**
	 * Create a tiers type
	 *
	 * @param 	string    		$tiersType			Tiers type
	 * @return  string											Updated type ID
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url POST /createtierstype
	 */
	public function createtierstype($tiersType) {
		global $conf, $langs, $user;

		$sql = "INSERT INTO `avo_custom_tiers_type` (`datec`, `datem`, `type`, `fk_pays`)";
		$sql.= " VALUES (";
		$sql.= "'".date('Y/m/d h:i:s')."'";
		$sql.= ", '".date('Y/m/d h:i:s')."'";
		$sql.= ", \"".$tiersType."\"";
		$sql.= ", '1')";

		$resql = $this->db->query($sql);

		if ($resql) {
			$sql = "SELECT rowid FROM avo_custom_tiers_type ORDER BY rowid DESC LIMIT 1";
			$resql = $this->db->query($sql);
			$rtd = $this->db->fetch_object($resql);
			return $rtd->rowid;
		} else {
			throw new RestException(405, "Problème lors de l\'enregistrement des données");
		}
	}

	/**
	 * Get a tiers type
	 *
	 * @param 	string    		$id						Tiers type ID
	 * @return  string											Updated type ID
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /tierstype
	 */
	public function tierstype($id) {
		global $conf, $langs, $user;

		$sql = "SELECT * FROM avo_custom_tiers_type WHERE rowid=$id";
		$resql = $this->db->query($sql);

		if ($resql) {
			$rtd = $this->db->fetch_object($resql);
			$rtd->datec = $this->db->jdate($rtd->datec);
			$rtd->datem = $this->db->jdate($rtd->datem);

			return $rtd;
		} else {
			throw new RestException(405, "Problème lors de la récupération du type de tiers");
		}
	}

	/////////////// PARTIE TYPE DE RENDEZ-VOUS ///////////////

	/**
	 * Create an event type
	 *
	 * @param 	string    $eventType		Event type
	 * @param	string	  $color_code		Color of the event type
	 * @return  string	  Created type ID
	 *
	 * @throws 500
	 * @throws 200
	 *
	 * @url POST /createeventtype
	 */
	public function createeventtype($eventType, $color_code = "FFC266") {
		global $conf, $langs, $user;

		$libelle = $eventType;

		// Création du code
		$code = $this->generateRandomCode();

		// Incrémentation de l'id
		$sqlId = "SELECT MAX(id) as id FROM `llx_c_actioncomm`";
		$resqId = $this->db->query($sqlId);
		$idTmp = $this->db->fetch_object($resqId);
		$id = $idTmp->id + 1;

		// Incrémentation de la position
		$sqlPos = "SELECT MAX(position) as position FROM `llx_c_actioncomm`";
		$resqlPos = $this->db->query($sqlPos);
		$posTmp = $this->db->fetch_object($resql);
		$position = $posTmp->position + 1;

		$sql = "INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `active`, `color`, `position`)";
		$sql.= " VALUES (";
		$sql.= "'". $id ."', ";
		$sql.= "'". $code ."', ";
		$sql.= "'user', ";
		$sql.= "'". $libelle ."', ";
		$sql.= "1, ";
		$sql.= "'". $color_code ."', ";
		$sql.= "'". $position ."');";

		$resql = $this->db->query($sql);

		if ($resql) {
			$sqlId = "SELECT MAX(id) as id FROM `llx_c_actioncomm`";
			$rtdId = $this->db->query($sqlId);
			$rtd = $this->db->fetch_object($rtdId);

			return $rtd->id;
		} else {
			throw new RestException(500, "Problème lors de la création du type de rendez-vous");
		}
	}

	/**
	 * Update an event type
	 *
	 * @param	string	  $id				Event ID
	 * @param 	string    $eventType		Event type
	 * @param	string	  $color_code		Color of the event type
	 * @return  string	  Created type ID
	 *
	 * @throws 500
	 * @throws 200
	 *
	 * @url PUT /updateeventtype
	 */
	public function updateeventtype($id, $eventType, $color_code = "FFC266") {
		global $conf, $langs, $user;

		$libelle = $eventType;

		$sql = "UPDATE `llx_c_actioncomm` SET ";
		$sql.= "`color`='$color_code', ";
		$sql.= "`libelle`='$libelle' ";
		$sql.= "WHERE id = $id";

		$resql = $this->db->query($sql);

		// Vérification de la bonne modification de la ligne
		$sqlTmp = "SELECT * FROM `llx_c_actioncomm` WHERE id = $id";
		$resqTmp = $this->db->query($sqlTmp);
		$objTmp = $this->db->fetch_object($resqTmp);

		if ($objTmp->libelle !== $libelle) {
			throw new RestException(500, "Problème lors de la modification du type de rendez-vous");
		}

		if ($objTmp->color !== $color_code) {
			throw new RestException(500, "Problème lors de la modification du type de rendez-vous");
		}

		return $id;
	}

	/**
	 * Get event types
	 *
	 * @return  string			Event types
	 *
	 * @throws 200
	 *
	 * @url GET /eventtype
	 */
	public function geteventtypes() {
		global $conf, $langs, $user;

		$sql = "SELECT id, code, libelle, color ";
		$sql.= "FROM `llx_c_actioncomm` ";
		$sql.= "WHERE active = 1 AND code != 'AC_OTH_AUTO' AND code != 'AC_OTH';";

		$resql = $this->db->query($sql);

		return $resql;
	}

	/**
	 * Delete an event type
	 *
	 * @param		string		$id						Event ID
	 * @return  string									Created type ID
	 *
	 * @throws 500
	 * @throws 200
	 *
	 * @url DELETE /deleteeventtype
	 */
	public function deleteeventtype($id) {
		global $conf, $langs, $user;

		$sql = "DELETE FROM `llx_c_actioncomm` WHERE `id` = $id;";
		$resql = $this->db->query($sql);

		// Vérification de la bonne suppression de la ligne
		$sqlTmp = "SELECT * FROM `llx_c_actioncomm` WHERE id = $id";
		$resqTmp = $this->db->query($sqlTmp);
		$objTmp = $this->db->fetch_object($resqTmp);

		// Si le type de rendez-vous avec id = $id existe, la suppression ne s'est pas faite
		// On renvoit donc une erreur
		if ($objTmp) {
			throw new RestException(500, "Problème lors de la suppression du type de rendez-vous");
		}

		return "Type de rendez-vous supprimé";
	}

	private function generateRandomCode() {
		// Caractères pouvant aparaîtres dans le code généré
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		$code = '';

		// Un caractère de $charaters est sélectionné au hasard
		$max = strlen($characters) - 1;
		for ($i = 0; $i < 11; $i++) {
				 $code .= $characters[mt_rand(0, $max)];
		}

		// Vérification de l'existence de ce code dans la table
		$sql = "SELECT * FROM `llx_c_actioncomm` WHERE code = '$code';";
		$resql = $this->db->query($sql);
		$objTmp = $this->db->fetch_object($resql);

		// Si le code existe dans la table, on rappel la fonction de génération pour proposer un nouveau code aléatoirfe
		if ($objTmp) {
			$code = $this->generateRandomCode();
		}

		return $code;
	}
}
