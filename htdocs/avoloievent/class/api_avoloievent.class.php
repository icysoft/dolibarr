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
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiEvent {@requires user,external}
 */
class AvoloiEvent extends DolibarrApi
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
	 * Create an Avoloi event.
	 * 
	 * @param   array   $avoloi_event
	 * @return  array                   List of documents
	 *
	 * @throws 500
	 * @throws 501
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 200
	 *
	 * @url POST /createavoloievent
	 */
	public function createavoloievent($avoloi_event)
	{
		global $conf, $langs, $user;

		$avoloi_event = (object) $avoloi_event;

		$agendaevent = $avoloi_event->agendaevent;
		$tiers = $avoloi_event->tiers;
		$affair = $avoloi_event->affair;

		// Vérification d'un eventagenda envoyé en paramètre
		if (is_null($agendaevent)) {
			throw new RestException(400, 'Event agenda is missing');
		}

		// Vérification de l'existence d'un tiers
		if (is_null($tiers)) {
			throw new RestException(400, 'Tiers is missing');
		}

		$societe = new Societe($this->db);

		$tiers['email'] = trim($tiers['email']);
		$tiers['phone'] = trim($tiers['phone']);

		if (empty($tiers['email']) && empty($tiers['phone'])) {
			throw new RestException(400, 'Email and/or phone is needed for tiers');
		}

		$sql = "SELECT *";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as p";
		if (!empty($tiers['email']) && !empty($tiers['phone'])) {
			// Search on email and phone
			$sql .= " WHERE p.email = \"".$tiers['email']."\" OR p.phone = \"".$tiers['phone']."\"";
		} else if (!empty($tiers['email'])) {
			// Search only on email
			$sql .= " WHERE p.email = \"".$tiers['email']."\"";
		} else {
			// Search only on phone
			$sql .= " WHERE p.phone = \"".$tiers['phone']."\"";
		}

		$result = $this->db->query($sql);
		$checktiers = $this->db->fetch_object($result);

		if (is_null($checktiers)) {
			// Si le tiers n'existe pas, on le créé et on récupère son id
			$societe->name = $tiers['name'];
			$societe->name_alias = $tiers['name_alias'];
			$societe->client = $tiers['client'];
			$societe->default_lang = $tiers['default_lang'];
			$societe->tva_assuj = $tiers['tva_assuj'];
			$societe->tva_intra = $tiers['tva_intra'];
			$societe->typent_id = $tiers['typent_id'];
			$societe->capital = $tiers['capital'];
			$societe->idprof1 = $tiers['id_prof_1'];
			$societe->idprof2 = $tiers['id_prof_2'];
			$societe->idprof3 = $tiers['id_prof_3'];
			$societe->idprof4 = $tiers['id_prof_4'];
			$societe->idprof5 = $tiers['id_prof_5'];
			$societe->idprof6 = $tiers['id_prof_6'];
			$societe->address = $tiers['address'];
			$societe->zip = $tiers['zip'];
			$societe->town = $tiers['town'];
			$societe->email = $tiers['email'];
			$societe->country_id = $tiers['country_id'] ? $tiers['country_id'] : 1;
			$societe->array_options = $tiers['array_options'];
			
			$socid = $societe->create($user);
		} else {
			// Si le tiers existe on récupère son id
			$socid = $checktiers->rowid;
		}

		if (!is_null($affair) && $socid) {
			$projet = new Project($this->db);

			$sql = "SELECT *";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
			$sql .= " WHERE p.ref = \"".$affair['ref']."\"";

			$result = $this->db->query($sql);
			$checkaffair = $this->db->fetch_object($result);

			if (is_null($checkaffair)) {
				// Si l'affaire n'existe pas, on la créée et on récupère son id
				$projet->title = $affair['title'];
				$projet->description = $affair['description'];
				$projet->socid = $socid;
				$projet->ref = $affair['ref'];
				$projet->statut = $affair['statut'];
				$projet->date_start = $affair['date_start'];
				$projet->date_end = $affair['date_end'];
				$projet->budget_amount = $affair['budget_amount'];
				$projet->opp_percent = $affair['opp_percent'];
				$projet->opp_status = $affair['opp_status'];

				$affairid = $projet->create($user);
			} else {
				// Si l'affaire existe on récupère son id
				$affairid = $checkaffair->rowid;
			}
		}

		if (!is_null($affair) && $affairid <= 0) {
			throw new RestException(418, 'Problem occurs while creating an affair, event agenda has not been created Check ref does not already exist');
		}

		// Création de l'eventagenda
		$event = new ActionComm($this->db);

		$event->socid = $affairid;
		$event->userownerid = $agendaevent['userownerid'];
		$event->type_id = $agendaevent['type_id'];
		$event->datep = $agendaevent['datep'];
		$event->datef = $agendaevent['datef'];
		$event->note = $agendaevent['note'];
		$event->userdoneid = $agendaevent['userdoneid'];
		$event->contactid = $socid;

		$eventcreated = $event->create($user);

		// TODO Envoi de la notification push

		// Constitution de la réponse
		$rtd = array();

		$rtd['tiers_id'] = $socid;
		if (!is_null($affair)) {
			$rtd['affair_id'] = $affairid;
		}
		$rtd['event_id'] = $eventcreated;

		return $rtd;
	}
}
