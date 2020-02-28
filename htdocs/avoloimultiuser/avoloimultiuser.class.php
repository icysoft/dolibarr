<?php
class AvoloiMultiUserClass
{
  /**
   *  Constructor
   *
   *  @param      DoliDB		$db      Database handler
   */
  public function __construct($db)
  {
      $this->db = $db;
  }

  /**
   * Can a user can access to an object
   * @param   int     $objectid   ID of the object to access
   * @param   string  $objecttype Type of the object to access
   * 
   * @return  bool  Return true if user can access object, false otherwise
   */
  public function canUserAccessObject($objectid, $objecttype) {
    global $user;
    return $this->isUserAdmin($user) || $this->canAccessObject($user, $objectid, $objecttype) ? true : false;
  }

  /**
   * Does user is an admin
   * If user is an admin, he/she can access any data
   * @param array $user Current user
   * 
   * @return  bool  Return true if user is an admin, false otherwise
   */
  public function isUserAdmin($user) {
    return DolibarrApiAccess::$user->admin ? true : false;
  }

  /**
   * Does user have access to shared object
   * @param array $user       Current user
   * @param array $objectid   ID of the object to access
   * @param array $objecttype Type of the object to access
   * 
   * @return bool Return true if user can access object, false otherwise
   */
  private function canAccessObject($user, $objectid, $objecttype) {

    $sql = "SELECT * ";
    $sql.= "FROM ".MAIN_DB_PREFIX."element_contact as ec ";
    $sql.= "WHERE element_id = $objectid ";
    $sql.= "AND fk_socpeople = ".DolibarrApiAccess::$user->id." ";
    $sql.= "AND fk_c_type_contact = $objecttype";

    $result = $this->db->query($sql);

    return $this->db->num_rows($result) ? true : false;
  }

  public function createUserMegabase($user) {
    // TODO Créer un user auprès de la Megabase
    $email = $user["email"];
    $lastname = $user["lastname"];
    $firstname = $user["firstname"];
    $phone = $user["user_mobile"];
    $password = $user["password"];
    $dolapikey = $user["api_key"];
    $lawyerId = $user["lawyerId"];

    return "123";
  }

  public function generateKey() {
		// Caractères pouvant aparaîtres dans la clef générée
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$key = '';

		// Un caractère de $charaters est sélectionné au hasard
		$max = strlen($characters) - 1;
		for ($i = 0; $i < 32; $i++) {
				 $key .= $characters[mt_rand(0, $max)];
		}

		// Vérification de l'existence de cette clef dans la table
		$sql = "SELECT * FROM `llx_user` WHERE api_key = '$key';";
		$resql = $this->db->query($sql);
		$objTmp = $this->db->fetch_object($resql);

		// Si la clef existe dans la table, on rappel la fonction de génération pour proposer une nouvelle clef
		if ($objTmp) {
			$key = $this->generateKey();
		}

		return $key;
  }

  public function getUserRights($id) {
    require_once DOL_DOCUMENT_ROOT.'/avoloi-manage-rights/avoloi-manage-rights.php';
    $amr = new AvoloiManageRights($this->db);
    return $amr->getRights($id, 'user');
  }

  public function setUserRights($id, $rights) {
    require_once DOL_DOCUMENT_ROOT.'/avoloi-manage-rights/avoloi-manage-rights.php';
    $amr = new AvoloiManageRights($this->db);
    return $amr->setRights($id, $rights, 'user');
  }

}
