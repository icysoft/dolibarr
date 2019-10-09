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
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiDivers {@requires user,external}
 */
class AvoloiDivers extends DolibarrApi
{

	/**
	 * @var array   $DOCUMENT_FIELDS     Mandatory fields, checked when create and update object
	 */
	static $DOCUMENT_FIELDS = array(
		'modulepart'
	);

	public $clientFilter = "-1";
	public $prospectFilter = "-1";
	public $tiersFilter = "-1";

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}


	/**
	 * Search a tiers by it's name
	 * 
	 * @param   string   $searched
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /searchtiers
	 */
	public function searchtiers($searched = '', $page = "-1", $limit = "-1", $clientFilter = "-1", $prospectFilter = "-1", $tiersFilter = "-1") {
		global $conf, $langs, $user;
		$this->clientFilter = $clientFilter;
		$this->prospectFilter = $prospectFilter;
		$this->tiersFilter = $tiersFilter;

		// Find contacts
		if ($searched) {
			$contacts = $this->getContacts($searched);
		}

		// Find societies
		$societies = $this->getSocieties($searched);

		$scArr = [];
		$conArr = [];

		foreach ($societies as $s) {
			$is_individual = $this->isIndividual($s->id);

			if (!$searched) {
				if ($s->array_options["options_primary_contact"] !== "")
				$tmpContact = $this->getContact($s->array_options["options_primary_contact"]);
				$society = array();
				$society["is_individual"] = $is_individual;
				$society["is_contact"] = false;
				$society["primary_contact"] = $s->array_options["options_primary_contact"];
				$society["contact_firstname"] = $tmpContact && $tmpContact->firstname ? $tmpContact->firstname : null;
				$society["contact_lastname"] = $tmpContact && $tmpContact->lastname ? $tmpContact->lastname : null;
				$society["contact_id"] = $tmpContact && $tmpContact->id ? $tmpContact->id : null;
				$society["society_id"] = $s->id;
				$society["society_name"] = $s->name;
				$society['contact_object'] = $tmpContact;
				$society['society_object'] = $this->getSociety($s->id);
	
				$scArr[] = $society;
				$tmpContact = null;
			} else {
				$tmpContacts = $this->getContactsOfSociety($s->id);

				foreach ($tmpContacts as $tmpContact) {
					$society = array();
					$society["is_individual"] = $is_individual;
					$society["is_contact"] = false;
					$society["primary_contact"] = $s->array_options["options_primary_contact"];
					$society["contact_firstname"] = $tmpContact && $tmpContact->firstname ? $tmpContact->firstname : null;
					$society["contact_lastname"] = $tmpContact && $tmpContact->lastname ? $tmpContact->lastname : null;
					$society["contact_id"] = $tmpContact && $tmpContact->id ? $tmpContact->id : null;
					$society["society_id"] = $s->id;
					$society["society_name"] = $s->name;
					$society['contact_object'] = $tmpContact;
					$society['society_object'] = $this->getSociety($s->id);
		
					$scArr[] = $society;
					$tmpContact = null;
				}
			}
		}

		// Filtrer les doublons sur $contacts
		if ($searched) {
			foreach ($contacts as $c) {
				$tmpSoc = $this->getSociety($c->socid);

				$contact = array();
				$contact["is_individual"] = $tmpSoc->array_options["options_is_society"] === '1' ? false : true;
				$contact["is_contact"] = true;
				$contact["primary_contact"] = $tmpSoc->array_options["options_primary_contact"];
				$contact["contact_firstname"] = $c->firstname;
				$contact["contact_lastname"] = $c->lastname;
				$contact["contact_id"] = $c->id;
				$contact["society_id"] = $c->socid;
				$contact["society_name"] = $c->socname;
				$contact['contact_object'] = $c;
				$contact['society_object'] = $tmpSoc;
				
				$foundDuplicateIndividual = false;
				foreach ($scArr as $sc) {
					if ($contact["society_id"] == $sc["society_id"]) {
						$foundDuplicateIndividual = true;
						break;
					}
				}

				// Si le contact n'est pas le contact d'une societé particulier on peut l'ajouter
				if (!$foundDuplicateIndividual) {
					$conArr[] = $contact;
				}
			}
		}

		$rtdArr = array_merge($scArr, $conArr);

		// Filtrer sur le(s) type(s) de tiers (client, propect, tiers)
		$rtdArr = array_filter($rtdArr, function ($ra) {
			return $this->typeTiersFilter($ra, $this->clientFilter, $this->prospectFilter, $this->tiersFilter);
		});

		// Retirer les doublons
		// if ($searched) {
		// 	$tmp = array_filter($rtdArr, function ($t) {
		// 		if (($t["is_individual"] && !$t["is_contact"]) || (!$t["is_individual"] && !$t["is_contact"])) {
		// 			return false;
		// 		}
		// 		return true;
		// 	});
		// 	$rtdArr = [];
		// 	foreach ($tmp as $t) {
		// 		$rtdArr[] = $t;
		// 	}
		// }

		// Pagination
		if ($page !== -1 && $limit !== -1) {
			$tmppage = (int) $page;
			$tmplimit = (int) $limit;
			$tmp = array_slice($rtdArr, $tmppage * $tmplimit, $tmplimit);

			$rtdArr = [];
			foreach ($tmp as $t) {
				$rtdArr[] = $t;
			}
		}

		return $rtdArr;
	}

	/**
	 * Search a propal by it's name
	 * 
	 * @param   string   $searched
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url GET /searchpropalsbyname
	 */
	public function searchpropalsbyname($searched) {
		global $conf, $langs, $user;

		$obj_ret = array();

		// TODO Récupérer IDs des propals dans llx_propal_extrafields sur title
		$sql = "SELECT fk_object";
		$sql.= " FROM ".MAIN_DB_PREFIX."propal_extrafields as p";
		$sql.= " WHERE p.titre LIKE '%".$searched."%'";

		$resql=$this->db->query($sql);

		// TODO Récupérer les propals avec les IDs récupérés précédement
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min)
			{
					$obj = $this->db->fetch_object($resql);
					$propals = new Propal($this->db);
					if($propals->fetch($obj->fk_object)) {
							$obj_ret[] = $this->_cleanObjectDatas($propals);
					}
					$i++;
			}
		}

		// TODO Retourner le résultat
		return $obj_ret;
	}

	private function isIndividual($socid) {
		$society = new Societe($this->db);
		$society->fetch($socid);
		$society = $this->_cleanObjectDatas($society);

		return $society->array_options["options_is_society"] === '1' ? false : true;
	}

	public function getSociety($id) {
		$society = new Societe($this->db);
		$society->fetch($id);
		return $this->_cleanObjectDatas($society);
	}

	private function getContact($id) {
		$contact = new Contact($this->db);
		$contact->fetch($id);
		return $this->_cleanObjectDatas($contact);
	}

	private function typeTiersFilter($soc, $clientFilter, $prospectFilter, $tiersFilter) {
		$society = new Societe($this->db);
		$society->fetch($soc["society_id"]);
		$society = $this->_cleanObjectDatas($society);

		if (($clientFilter === -1 && $prospectFilter === -1 && $tiersFilter === -1)
				|| ($clientFilter !== -1 && $society->client === "1")
				|| ($prospectFilter !== -1 && $society->client === "2")
				|| ($tiersFilter !== -1 && $society->client === "0")) {
			return true;
		} else {
			return false;;
		}
	}

	private function getContacts($value) {
		global $conf, $langs, $user;

		$obj_ret = array();

		$sql = "SELECT *";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql.= " WHERE (c.lastname LIKE '%".$value."%')";
		$sql.= " OR (c.firstname LIKE '%".$value."%')";

		$resql=$this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min)
			{
					$obj = $this->db->fetch_object($resql);
					$contacts = new Contact($this->db);
					if($obj->fk_soc && $contacts->fetch($obj->rowid)) {
							$obj_ret[] = $this->_cleanObjectDatas($contacts);
					}
					$i++;
			}
		}

		return $obj_ret;
	}

	public function getContactsOfSociety($socid) {
		global $conf, $langs, $user;

		$obj_ret = array();

		$sql = "SELECT *";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql.= " WHERE (c.fk_soc = $socid)";

		$resql=$this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min)
			{
					$obj = $this->db->fetch_object($resql);
					$contacts = new Contact($this->db);
					if($obj->fk_soc && $contacts->fetch($obj->rowid)) {
							$obj_ret[] = $this->_cleanObjectDatas($contacts);
					}
					$i++;
			}
		}

		return $obj_ret;
	}

	private function getSocieties($value) {
		global $conf, $langs, $user;

		$obj_ret = array();

		$sql = "SELECT *";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE (s.nom LIKE '%".$value."%')";

		$resql=$this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min)
			{
					$obj = $this->db->fetch_object($resql);
					$societies = new Societe($this->db);
					if($societies->fetch($obj->rowid)) {
							$obj_ret[] = $this->_cleanObjectDatas($societies);
					}
					$i++;
			}
		}

		return $obj_ret;
	}
}
