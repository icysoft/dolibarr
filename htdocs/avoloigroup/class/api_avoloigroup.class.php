<?php
use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/avoloimultiuser/class/api_avoloimultiuser.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/avoloigroup/avoloigroup.class.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiGroup {@requires user}
 */

class AvoloiGroup extends DolibarrApi
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
    $this->db = $db;
    $group = new UserGroup($db);
  }
  
  /**
   * Get groups list
   * 
   * @throws  RestException   401 if user is not admin
   * @throws RestException 404 si aucun groupe n'est trouvé
   * @return  string          User ID
   * @url GET /
   */
  public function getusergroup($request_data = null) {
    global $user;

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to get groups");

    $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."usergroup ORDER BY rowid ASC";
    $result = $this->db->query($sql);

    if ($result->num_rows <= 0) {
			throw new RestException(404, 'No group found');
    }

    $usergroups = array();
    foreach ($result as $group) {
      $tmp = array();
      $tmp["nom"] = $group["nom"];
      $tmp["id"] = $group["rowid"];

      // On récupère les utilisateurs liés à ce groupe
      $tmp["users"] = $this->getGroupUsers($group["rowid"]);

      // On récupère les droits du groupe dans les différents domaines
      $tmp["group_rights"] = $this->getGroupRights($group["rowid"]);

      $usergroups[] = $tmp;
    }

    return $usergroups;
  }

  /**
   * Get group using their ID
   * @param string $id
   * 
   * @throws RestException 404 si le groupe n'est pas trouvé
   * @return array Return the group
   * @url GET /{id}
   */
  public function getGroup($id) {
    global $user;

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to get groups");

    $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."usergroup ";
    $sql.="WHERE rowid = $id ORDER BY rowid ASC";
    $result = $this->db->query($sql);

    if ($result->num_rows <= 0) {
			throw new RestException(404, 'Group not found');
    }

    $group = array();
    foreach ($result as $g) {
      $group["nom"] = $g["nom"];
      $group["id"] = $g["rowid"];

      // On récupère les utilisateurs liés à ce groupe
      $group["users"] = $this->getGroupUsers($g["rowid"]);

      // On récupère les droits du groupe dans les différents domaines
      $group["group_rights"] = $this->getGroupRights($g["rowid"]);
    }

    return $group;
  }

  /**
   * Update a group
   * @param array $group New values for the group
   * 
   * @return array Return updated group
   * @url PUT /
   */
  public function updategroup($group) {
    global $user;

    if (!$group) {
      throw new RestException(500, 'No data provided');
    }

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to update a user");

    $checkgroup = $this->getGroup($group["id"]);

    if (!$checkgroup) {
      throw new RestException(404, 'Group not found');
    }

    // On set les droits du groupe
    $ag = new AvoloiGroupClass($this->db);
    $ag->setGroupRights($group["id"], $group["group_rights"]);

    return $this->getGroup($group["id"]);
  }

  /**
   * Check if current user have admin rights
   * @param   int     $user   Current user
   * @param   string  $msg    Message shown on error
   * 
   * @throws  RestException   401 if user is not admin
   * @return  void
   */
  private function checkAdminRights($user, $msg) {
    global $user;

    $ag = new AvoloiGroupClass($this->db);
    if (!$ag->isUserAdmin($user)) {
			throw new RestException(401, $msg);
    }
  }

  /**
   * Récupère tout les utilisateurs inclus dans ce groupe
   * @param string $group ID du groupe
   * 
   * @return array Liste des users
   */
  private function getGroupUsers($group) {
    $sql = "SELECT rowid, fk_user FROM ".MAIN_DB_PREFIX."usergroup_user ";
    $sql.= "WHERE fk_usergroup = $group";
    $result = $this->db->query($sql);

    $users = array();

    if ($result->num_rows > 0) {
      foreach ($result as $r) {
        $amu = new AvoloiMultiUser();
        $users[] = $amu->getuserbyid($r["fk_user"]);
      }
    }

    return $users;
  }

  /**
   * Récupère les droits des différents domaines
   * @param string $group ID du groupe
   * 
   * @return array Liste des droits dans les différents domaines
   */
  private function getGroupRights($group) {
    require_once DOL_DOCUMENT_ROOT . '/avoloigroup/avoloigroup.class.php';
    $amr = new AvoloiGroupClass($this->db);
    return $amr->getGroupRights($group);
  }
}