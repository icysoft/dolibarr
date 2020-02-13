<?php
use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/avoloimultiuser/avoloimultiuser.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

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
   * @throws  RestException   500 if request_data is empty or if an error occurs while creating user
   * @throws  RestException   401 if user is not admin
   * @return  string          User ID
   * @url GET /
   */
  public function getusergroup($request_data = null) {
    global $user;

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to create a user");

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
      $usergroups[] = $tmp;
    }

    return $usergroups;
  }

  /**
   * Get group using their ID
   * @param string $id
   * 
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
   * Check if current user have admin rights
   * @param   int     $user   Current user
   * @param   string  $msg    Message shown on error
   * 
   * @throws  RestException   401 if user is not admin
   * @return  void
   */
  private function checkAdminRights($user, $msg) {
    global $user;

    $amu = new AvoloiMultiUserClass($this->db);
    if (!$amu->isUserAdmin($user)) {
			throw new RestException(401, $msg);
    }
  }
}