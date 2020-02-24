<?php
use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/avoloimultiuser/avoloimultiuser.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * API class for receive files
 *
 * @access protected
 * @class AvoloiMultiUser {@requires user}
 */

class AvoloiMultiUser extends DolibarrApi
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->useraccount = new User($this->db);
  }

  /**
   * @url GET /test/{id}
   */
  public function test($id) {
    // $mu = new AvoloiMultiUserClass($this->db);
    // return $mu->getUserRights($id);
    return $this->setGroup($id);
  }

  /**
	 * @param   string           $id User id
	 * @param   array           $user_rights User rights to set
   * @url POST /testbis/{id}
   */
  public function testbis($id, $user_rights) {
    $mu = new AvoloiMultiUserClass($this->db);
    return $mu->setUserRights($id, $user_rights);
  }
  
  /**
   * Create user
	 * @param   array           $request_data New user data
   * 
   * @throws  RestException   500 if request_data is empty or if an error occurs while creating user
   * @throws  RestException   401 if user is not admin
   * @return  string          User ID
   * @url POST /
   */
  public function postuser($request_data = null) {
    global $user;

    if (!$request_data) {
      throw new RestException(500, 'No data provided');
    }

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to create a user");

    // Instanciation de la class AvoloiMultiUserClass
    $mu = new AvoloiMultiUserClass($this->db);

    // Génération d'une clef d'API pour le nouvel utilisateur
    $request_data["api_key"] = $mu->generateKey();

    foreach ($request_data as $field => $value) {
      $this->useraccount->$field = $value;

      // Si on souahite créer un user inactif, avec un statut à 0 donc.
      // Permet de passer les vérifications Dolibarr empêchant l'update du statut par la suite.
			if ($field === 'statut') {
        $this->useraccount->statut = "1";
			}
    }

    // Créer un user auprès de la Megabase
    $megabaseuserid = $mu->createUserMegabase($request_data);

    if (!$megabaseuserid || $megabaseuserid === "") {
      throw new RestException(500, 'Error creating user in Megabase');
    }

    // Le password sera utilisé uniquement côté Megabase et pas côté Dolibarr (la DOLAPIKEY sera utilisée à la place).
    // On le supprime donc afin de ne pas l'enregistrer dans Dolibarr.
    $request_data["password"] = null;

    if ($this->useraccount->create(DolibarrApiAccess::$user) < 0) {
      throw new RestException(500, 'Error creating', array_merge(array($this->useraccount->error), $this->useraccount->errors));
    }

    // Nous voulons créer un utilisateur avec le même ID qu'en Megabase
    // Dolibarr ne nous permettant pas de choisir explicitement un ID à la création de l'utilisateur
    // nous modifions l'ID après sa création ET AVANT TOUTES AUTRES MANIPULATIONS !
    $this->updateUserId($megabaseuserid);

    // On set le statut de l'utilisateur
    $this->useraccount->setstatus($request_data["statut"]);

    // On set les droits individuel
    $mu->setUserRights($this->useraccount->id, $request_data["user_rights"]);

    // On set le groupe
    $this->setGroup($request_data["group"]["id"]);

    return $this->useraccount->id;
  }
  
  /**
   * Update user
	 * @param   int   $id             Id of account to update
	 * @param   array $request_data   Datas
   * 
   * @throws  RestException         500 if request_data is empty or if an error occurs while updating user
   * @throws  RestException         401 if user is not admin
   * @throws  RestException         404 if user to update is not found
   * @return  string                User ID
   * @url PUT /{id}
   */
  public function updateuser($id, $request_data) {
    global $user;

    if (!$request_data) {
      throw new RestException(500, 'No data provided');
    }

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to update a user");

    $result = $this->useraccount->fetch($id);

		if (!$result) {
			throw new RestException(404, 'Account not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') continue;
			if ($field == 'admin') continue;
			if ($field == 'statut') {
				$result = $this->useraccount->setstatus($value);
				if ($result < 0) {
				   throw new RestException(500, 'Error when updating status of user: '.$this->useraccount->error);
				}
			} else {
			   $this->useraccount->$field = $value;
			}
    }

		if ($this->useraccount->update(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, $this->useraccount->error);
    }
    
    // On set les droits utilisateur
    $mu = new AvoloiMultiUserClass($this->db);
    $mu->setUserRights($id, $request_data["user_rights"]);

    // On set le groupe
    $this->setGroup($request_data["group"]["id"]);

    return $this->getuserbyid($id);
  }
  
  // /**
  //  * Delete user
  //  * @param   int $id       User ID
  //  * 
  //  * @throws  RestException 401 if user is not admin
  //  * @throws  RestException 404 if user to delete is not found
  //  * @return  int           Return 1 if user is deleted
  //  * @url DELETE /{id}
  //  */
  // public function deleteuser($id) {
  //   global $user;

  //   // We check if the user trying to create another user is an admin
  //   $this->checkAdminRights($user, "Admin rights are needed to delete a user");

	// 	$result = $this->useraccount->fetch($id);
	// 	if ($result <= 0) {
	// 		throw new RestException(404, 'User not found');
	// 	}

	// 	return $this->useraccount->delete(DolibarrApiAccess::$user);
  // }
  
  /**
   * Get user
   * @param   int $id       User ID
   * 
   * @throws  RestException 401 if user is not admin
   * @throws  RestException 404 if user to delete is not found
   * @return  array          Return the user
   * @url GET /{id}
   */
  public function getuserbyid($id) {
    global $user;

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to get a user");

    $tmp = new User($this->db);

		$result = $tmp->fetch($id);
		if ($result <= 0) {
			throw new RestException(404, 'User not found');
    }

    // On ajoute le groupe auquel appartient l'utilisateur
    $tmp->group = $this->findUserGroups($id);

    // On ajoute les droits de l'utilisateur
    $mu = new AvoloiMultiUserClass($this->db);
    $tmp->user_rights = $mu->getUserRights($id);

    return $this->_cleanObjectDatas($tmp);
  }
  
  /**
   * Get all user
   * 
   * @throws  RestException 401 if user is not admin
   * @return  Array<User>   Return an array of users
   * @url GET /
   */
  public function getalluser() {
    global $user;
    $obj_ret = array();

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to get all users");

    $sql = "SELECT * ";
    $sql.= "FROM ".MAIN_DB_PREFIX."user as t ";
    $sql.= "WHERE t.entity IN (".getEntity('user').")";

    $result = $this->db->query($sql);

    if ($result) {
      $num = $this->db->num_rows($result);
      $min = min($num, ($limit <= 0 ? $num : $limit));
      while ($i < $min)
      {
          $obj = $this->db->fetch_object($result);
          $user_static = new User($this->db);
          if($user_static->fetch($obj->rowid)) {
            // On ajoute le groupe auquel appartient l'utilisateur
            $user_static->group = $this->findUserGroups($user_static->id);

            // On ajoute les droits de l'utilisateur
            $mu = new AvoloiMultiUserClass($this->db);
            $user_static->user_rights = $mu->getUserRights($user_static->id);
            $obj_ret[] = $this->_cleanObjectDatas($user_static);
          }
          $i++;
      }
    }
    return $obj_ret;
  }
  
  // /**
  //  * Change author of objects
  //  * @param  int  $olduserid  Old user ID
  //  * @param  int  $newuserid  Old user ID
  //  * 
  //  * @throws RestException    401 if user is not admin
  //  * @throws RestException    404 if one of the two user is not found
  //  * @throws RestException    500 if two ID are the same
  //  * @return string           A string explaining changes
  //  * @url POST /changeauthor/{olduserid}/{newuserid}
  //  */
  // public function changeauthor($olduserid, $newuserid) {
  //   global $user;

  //   if ($olduserid == $newuserid) {
  //     throw new RestException(500, 'The two users have to be different');
  //   }

  //   // We check if the user trying to create another user is an admin
  //   $this->checkAdminRights($user, "Admin rights are needed to change author of objects");

  //   // On vérifie l'existence de l'ancien user
  //   $resultolduser = $this->useraccount->fetch($olduserid);
  //   if ($resultolduser <= 0) {
  //     throw new RestException(404, 'Cannot find user with id '.$olduserid);
  //   }
  //   $oldusernlogin = $this->useraccount->login;

  //   // On vérifie l'existance du nouveau user
  //   $resultnewuser = $this->useraccount->fetch($newuserid);
  //   if ($resultnewuser <= 0) {
  //     throw new RestException(404, 'Cannot find user with id '.$newuserid);
  //   }
  //   $newusernlogin = $this->useraccount->login;

  //   $this->changeObjectAuthor($olduserid, $newuserid);

  //   return "Objects's author changed from $oldusernlogin to $newusernlogin.";
  // }
  
  /**
   * Is user have admin rights
   * 
   * @return int Return 1 if user have admin rights, 0 if not
   * @url GET /isadmin
   */
  public function isadmin() {
    global $user;

    $amu = new AvoloiMultiUserClass($this->db);
    return $amu->isUserAdmin($user) ? 1 : 0;
  }
  
  /**
   * Set user as admin
   * @param   int $id       User ID
   * 
   * @throws  RestException 401 if user is not admin
   * @throws  RestException 404 if user is not found
   * @throws  RestException 500 if error occurs while setting user as admin
   * @return  array          User setted as admin
   * @url PUT /admin/{id}
   */
  public function setadmin($id) {
    global $user;

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to set admin rights to a user");

    $result = $this->updateAdmin($id, 1);

    if (!$result) {
      $usertmp = $this->getuserbyid($id);
			throw new RestException(500, "Error occurs while setting user ".$usertmp->firstname." ".$usertmp->lastname." as admin");
    }

    return $this->getuserbyid($id);
  }
  
  /**
   * Remove user as admin
   * @param   int $id       User ID
   * 
   * @throws  RestException 401 if user is not admin
   * @throws  RestException 404 if user is not found
   * @throws  RestException 500 if error occurs while removing user as admin
   * @return  array          User removed as admin
   * @url DELETE /admin/{id}
   */
  public function removeadmin($id) {
    global $user;

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to remove admin rights to a user");

    $result = $this->updateAdmin($id, 0);

    if (!$result) {
      $usertmp = $this->getuserbyid($id);
			throw new RestException(500, "Error occurs while removing user ".$usertmp->firstname." ".$usertmp->lastname." admin rights");
    }

    return $this->getuserbyid($id);
  }
  
  /**
   * Enable user
   * @param   int $id       User ID
   * 
   * @throws  RestException 401 if user is not admin
   * @throws  RestException 404 if user is not found
   * @throws  RestException 500 if error occurs while enabling user
   * @return  array          Enabled user
   * @url PUT /enable/{id}
   */
  public function enableuser($id) {
    global $user;

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to enable user");

    $result = $this->enableOrDisableUser($id, 1);

    if (!$result) {
      $usertmp = $this->getuserbyid($id);
			throw new RestException(500, "Error occurs while enable user ".$usertmp->firstname." ".$usertmp->firstname);
    }

    return $this->getuserbyid($id);
  }
  
  /**
   * Disable user
   * @param   int $id       User ID
   * 
   * @throws  RestException 401 if user is not admin
   * @throws  RestException 404 if user is not found
   * @throws  RestException 500 if error occurs while disabling user
   * @return  array          Disabled user
   * @url PUT /disable/{id}
   */
  public function disableuser($id) {
    global $user;

    // We check if the user trying to create another user is an admin
    $this->checkAdminRights($user, "Admin rights are needed to disable user");

    $result = $this->enableOrDisableUser($id, 0);

    if (!$result) {
      $usertmp = $this->getuserbyid($id);
			throw new RestException(500, "Error occurs while disable user ".$usertmp->firstname." ".$usertmp->firstname);
    }

    return $this->getuserbyid($id);
  }

  /**
   * Get current user ID
   * 
   * @return string
   * @url GET /userid
   */
  public function currentUserId() {
    global $user;
    return DolibarrApiAccess::$user->id;
  }

  /**
   * Get current user rights
   * 
   * @return array Return user rights and his group's rights
   * @url GET /userrights
   */
  public function getUserRights() {
    global $user;

    $mu = new AvoloiMultiUserClass($this->db);
    $currentuserrights = $mu->getUserRights(DolibarrApiAccess::$user->id);

    $usergroupinfos = $this->findUserGroups($this->currentUserId());
    
    require_once DOL_DOCUMENT_ROOT . '/avoloigroup/avoloigroup.class.php';
    $amr = new AvoloiGroupClass($this->db);
    $usergroup =  $amr->getGroupRights($usergroupinfos["id"]);

    $rightsmatrix = [
      "affairs" => [
        "right_code" => 'n',
        "right_label" => 'RIGHT_NONE'
      ],
      "agenda" => [
        "right_code" => 'n',
        "right_label" => 'RIGHT_NONE',
      ],
      "invoices" => [
        "right_code" => 'n',
        "right_label" => 'RIGHT_NONE',
      ],
      "proposals" => [
        "right_code" => 'n',
        "right_label" => 'RIGHT_NONE',
      ],
      "tasks" => [
        "right_code" => 'n',
        "right_label" => 'RIGHT_NONE',
      ],
      "tiers" => [
        "right_code" => 'n',
        "right_label" => 'RIGHT_NONE',
      ]
    ];

    foreach ($rightsmatrix as $key => $ug) {
      if ($currentuserrights[$key]["right_code"] === 'g'
        || $currentuserrights[$key]["right_code"] === $usergroup[$key]["right_code"]) {
        $rightsmatrix[$key]["right_code"] = $usergroup[$key]["right_code"];
        $rightsmatrix[$key]["right_label"] = $usergroup[$key]["right_label"];
      } else  {
        $rightsmatrix[$key]["right_code"] = $currentuserrights[$key]["right_code"];
        $rightsmatrix[$key]["right_label"] = $currentuserrights[$key]["right_label"];
      }
    }

    return $rightsmatrix;
  }

  /**
   * Update user to set or remove their as admin
   * @param int $id         user ID
   * @param int $isadmin    1 if admin, 0 if not
   * 
   * @throws RestException  404 if user is not found
   * @return bool           Return true if user is updated
   */
  private function updateAdmin($id, $isadmin = 0) {
		$userexist = $this->useraccount->fetch($id);
		if ($userexist <= 0) {
			throw new RestException(404, 'User not found');
    }

    $sql = "UPDATE ".MAIN_DB_PREFIX."user ";
    $sql.= "SET admin =  $isadmin ";
    $sql.= "WHERE rowid = $id";

    return $this->db->query($sql);
  }

  /**
   * Enable or disable an user
   * @param int $id         user ID
   * @param int $enable     1 if admin, 0 if not
   * 
   * @throws RestException  404 if user is not found
   * @return bool           Return true if user is updated
   */
  private function enableOrDisableUser($id, $enable = 0) {
		$userexist = $this->useraccount->fetch($id);
		if ($userexist <= 0) {
			throw new RestException(404, 'User not found');
    }

    $sql = "UPDATE ".MAIN_DB_PREFIX."user ";
    $sql.= "SET statut =  $enable ";
    $sql.= "WHERE rowid = $id";

    return $this->db->query($sql);
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

  // /**
  //  * Change owner of all objects with another user
  //  * @param   int $olduserid  ID of the old owner
  //  * @param   int $newuserid  ID of the now owner
  //  * 
  //  * @throws  RestException   500 if an error occurs while updating an object
  //  */
  // private function changeObjectAuthor($olduserid, $newuserid) {
  //   $objectsarrcret = ["societe", "socpeople", "projet", "projet_task"];
  //   $objectsarrauthor = ["facture", "propal", "actioncomm"];

  //   foreach ($objectsarrcret as $object) {
  //     try  {
  //       $sql = "UPDATE ".MAIN_DB_PREFIX."$object ";
  //       $sql.= "SET fk_user_creat =  $newuserid ";
  //       $sql.= "WHERE fk_user_creat = $olduserid";
  
  //       $result = $this->db->query($sql);
  //       if (!$result) {
  //         throw new Exception("Error occurs while changing $object author");
  //       }
  //     } catch (Exception $e) {
  //       throw new RestException(500, $e);
  //     }
  //   }

  //   foreach ($objectsarrauthor as $object) {
  //     try  {
  //       $sql = "UPDATE ".MAIN_DB_PREFIX."$object ";
  //       $sql.= "SET fk_user_author =  $newuserid ";
  //       $sql.= "WHERE fk_user_author = $olduserid";
  
  //       $result = $this->db->query($sql);
  //       if (!$result) {
  //         throw new Exception("Error occurs while changing $object author");
  //       }
  //     } catch (Exception $e) {
  //       throw new RestException(500, $e);
  //     }
  //   }
  // }


  /**
   * Return an array of user's groups
   * @param int $id         user ID
   * 
   * @throws RestException  404 if user is not found
   * @return bool           Return true if user is updated
   */
  private function findUserGroups($id) {
		$userexist = $this->useraccount->fetch($id);
		if ($userexist <= 0) {
			throw new RestException(404, 'User not found');
    }

    $sql = "SELECT ug.rowid, ug.nom FROM `".MAIN_DB_PREFIX."usergroup_user` as ugu ";
    $sql.= "LEFT JOIN `llx_usergroup` as ug ";
    $sql.= "ON ug.rowid = ugu.fk_usergroup ";
    $sql.= "WHERE ugu.fk_user = $id";

    $result = $this->db->query($sql);

    $rtd = array();
    foreach ($result as $group) {
      $tmp = array();
      $tmp["nom"] = $group["nom"];
      $tmp["id"] = $group["rowid"];
      $rtd = $tmp;
    }

    return $rtd;
  }

	/**
	 * Clean sensible object datas
	 *
	 * @param   object  $object    Object to clean
	 * @return    array    Array of cleaned object properties
	 */
	protected function _cleanObjectDatas($object)
	{
		global $conf;

	    $object = parent::_cleanObjectDatas($object);

	    unset($object->default_values);
	    unset($object->lastsearch_values);
	    unset($object->lastsearch_values_tmp);

	    unset($object->total_ht);
	    unset($object->total_tva);
	    unset($object->total_localtax1);
	    unset($object->total_localtax2);
	    unset($object->total_ttc);

	    unset($object->libelle_incoterms);
	    unset($object->location_incoterms);

	    unset($object->fk_delivery_address);
	    unset($object->fk_incoterms);
	    unset($object->all_permissions_are_loaded);
	    unset($object->shipping_method_id);
	    unset($object->nb_rights);
	    unset($object->search_sid);
	    unset($object->ldap_sid);
	    unset($object->clicktodial_loaded);

	    // List of properties never returned by API, whatever are permissions
	    unset($object->pass);
	    unset($object->pass_indatabase);
	    unset($object->pass_indatabase_crypted);
	    unset($object->pass_temp);
	    unset($object->api_key);
	    unset($object->clicktodial_password);
	    unset($object->openid);


	    $canreadsalary = ((! empty($conf->salaries->enabled) && ! empty(DolibarrApiAccess::$user->rights->salaries->read))
	    	|| (! empty($conf->hrm->enabled) && ! empty(DolibarrApiAccess::$user->rights->hrm->employee->read)));

		if (! $canreadsalary)
		{
			unset($object->salary);
			unset($object->salaryextra);
			unset($object->thm);
			unset($object->tjm);
		}

	    return $object;
	}

  private function setGroup($group) {
    global $user;

    // On cherche à n'avoir qu'un groupe par utilisateur
    // On supprime donc le group auquel appartient déjà l'utilisateur
    $sql = "DELETE FROM `llx_usergroup_user` WHERE `fk_user`=".$this->useraccount->id;
    $result = $this->db->query($sql);

    $entity = (DolibarrApiAccess::$user->entity > 0 ? DolibarrApiAccess::$user->entity : $conf->entity);
    $result = $this->useraccount->SetInGroup($group, 1);
		if (! ($result > 0))
		{
			throw new RestException(500, $this->useraccount->error);
    }
    
    // Si le groupe est "GROUP_ADMIN", alors on set les droits admin pour le user, sinon on les retire.
    $sql = "SELECT nom FROM ".MAIN_DB_PREFIX."usergroup ";
    $sql.= "WHERE rowid = $group";
    $result = $this->db->query($sql);

    $isadmin = false;
    foreach ($result as $r) {
      if ($r["nom"] === "GROUP_ADMIN") {
        $isadmin = true;
      }
    }

    if ($isadmin) {
      $this->setadmin($this->useraccount->id);
    } else {
      $this->removeadmin($this->useraccount->id);
    }
  }

  private function updateUserId($newid) {
    $sql = "UPDATE ".MAIN_DB_PREFIX."user ";
    $sql.= "SET rowid = $newid ";
    $sql.= "WHERE rowid = ".$this->useraccount->id;
    $this->useraccount->id = $newid;
    return $this->db->query($sql);
  }
}