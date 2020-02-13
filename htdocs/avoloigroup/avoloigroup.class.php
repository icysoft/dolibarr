<?php
class AvoloiGroupClass
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
}
