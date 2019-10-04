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
		$contacts = $this->getContacts($searched);

		// Find societies
		$societies = $this->getSocieties($searched);

		$rtdArr = [];
		
		foreach ($societies as $s) {
			$society = array();
			$society["is_individual"] = $this->isIndividual($s->id);
			$society["is_contact"] = false;
			$contact["is_primary_contact"] = false;
			$society["contact_firstname"] = null;
			$society["contact_lastname"] = null;
			$society["contact_firstname"] = null;
			$society["contact_id"] = null;
			$society["society_id"] = $s->id;
			$society["society_name"] = $s->name;
			$society['contact_object'] = null;
			$society['society_object'] = $this->getSociety($s->id);

			$rtdArr[] = $society;

			// Récupération des contacts liés à chaque sociétés
			$societyContacts = $this->getContactsOfSociety($s->id);
			foreach ($societyContacts as $c) {
				$contacts[] = $c;
			}
		}

		// Filtrer les doublons sur $contacts
		$contacts = array_unique($contacts, $c->id);

		foreach ($contacts as $c) {
			$contact = array();
			$contact["is_individual"] = $this->isIndividual($c->socid);
			$contact["is_contact"] = true;
			$contact["is_primary_contact"] = $this->isPrimaryContact($c);
			$contact["contact_firstname"] = $c->firstname;
			$contact["contact_lastname"] = $c->lastname;
			$contact["contact_firstname"] = $c->firstname;
			$contact["contact_id"] = $c->id;
			$contact["society_id"] = $c->socid;
			$contact["society_name"] = $c->socname;
			$contact['contact_object'] = $c;
			$contact['society_object'] = $this->getSociety($c->socid);

			$rtdArr[] = $contact;
		}

		// Filtrer sur le(s) type(s) de tiers (client, propect, tiers)
		$tmp = array_filter($rtdArr, function ($ra) {
			return $this->typeTiersFilter($ra, $this->clientFilter, $this->prospectFilter, $this->tiersFilter);
		});
		$rtdArr = [];
		foreach ($tmp as $t) {
			$rtdArr[] = $t;
		}

		// Retirer les doublons
		$tmp = array_filter($rtdArr, function ($t) {
			if (($t["is_individual"] && !$t["is_contact"]) || (!$t["is_individual"] && !$t["is_contact"])) {
				return false;
			}
			return true;
		});
		$rtdArr = [];
		foreach ($tmp as $t) {
			$rtdArr[] = $t;
		}

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

	private function isIndividual($socid) {
		$society = new Societe($this->db);
		$society->fetch($socid);
		$society = $this->_cleanObjectDatas($society);

		return $society->array_options["options_is_society"] === '1' ? false : true;
	}

	private function getSociety($id) {
		$society = new Societe($this->db);
		$society->fetch($id);
		return $this->_cleanObjectDatas($society);
	}

	private function isPrimaryContact($contact) {
		if ($contact->socname === $contact->firstname." ".$contact->lastname) {
			return true;
		} else {
			return false;
		}
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

	private function getContactsOfSociety($socid) {
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
