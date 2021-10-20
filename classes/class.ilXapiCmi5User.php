<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once __DIR__.'/class.ilXapiCmi5DateTime.php';

/**
 * Class ilXapiCmi5User
 *
 * @author      Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @author      Björn Heyser <info@bjoernheyser.de>
 * @author      Stefan Schneider <info@eqsoft.de>
 *
 */
class ilXapiCmi5User
{
    /**
     * @var int
     */
    protected $objId;
    
    /**
     * @var int
     */
    protected $usrId;
    
    /**
     * @var bool
     */
    protected $proxySuccess;
    
    /**
     * @var ilXapiCmi5DateTime
     */
    protected $fetchUntil;
    
    /**
     * @var string
     */
    protected $usrIdent;
	
	
	protected $privacyIdent;
    
    public function __construct($objId = null, $usrId = null, $privacyIdent = null)
    {
        $this->objId = $objId;
        $this->usrId = $usrId;
		$this->privacyIdent = $privacyIdent;
        // $this->proxySuccess = false;
        // $this->fetchUntil = new ilXapiCmi5DateTime(0, IL_CAL_UNIX);
        $this->usrIdent = '';
        
        if ($objId !== null && $usrId !== null && $privacyIdent !== null) {
           $this->load();
        }
    }
    
    /**
     * @return int
     */
    public function getObjId()
    {
        return $this->objId;
    }
    
    /**
     * @param int $objId
     */
    public function setObjId($objId)
    {
        $this->objId = $objId;
    }
    
    /**
     * @return int
     */
    public function getPrivacyIdent()
    {
        return $this->privacyIdent;
    }
    
    /**
     * @param int $privacyIdent
     */
    public function setPrivacyIdent($privacyIdent)
    {
        $this->privacyIdent = $privacyIdent;
    }

    /**
     * @return int
     */
    public function getUsrId()
    {
        return $this->usrId;
    }
    
    /**
     * @param int $usrId
     */
    public function setUsrId($usrId)
    {
        $this->usrId = $usrId;
    }
    
    /**
     * @return string
     */
    public function getUsrIdent() : string
    {
        return $this->usrIdent;
    }

    /**
     * @return string
     */
    // public static function getUsrIdentPlugin($usr_id, $obj_id) : string
    // {
		// //muss überarbeitet werden, da array zurückkommt
        // global $ilDB; //DIC
		// $query = 'SELECT usr_ident FROM xxcf_users WHERE obj_id = '
				// . $ilDB->quote($obj_id, 'integer') .' AND usr_id=' .$ilDB->quote($usr_id, 'integer');

		// $res = $ilDB->query($query);
        // $row = $ilDB->fetchObject($res);
        // if ($row) {
            // return $row->usr_ident;
        // }
        // else {
            // return '';
        // }
		
        // // global $ilDB;
		// // $query = 'SELECT * FROM xxcf_data_settings WHERE obj_id = '
				// // . $ilDB->quote($obj_id, 'integer');

		// // $res = $ilDB->query($query);
        // // $row = $ilDB->fetchObject($res);
        // // if ($row) {
            // // return self::getIdentPlugin($row->privacy_ident,$usr_id,$obj_id);
        // // }
        // // else {
            // // return '';
        // // }
    // }
    
    /**
     * @param string $usrIdent
     */
    public function setUsrIdent(string $usrIdent)
    {
        $this->usrIdent = $usrIdent;
    }
    
    /**
     * @return string
     */
    public static function getIliasUuid() : string
    {
        $setting = new ilSetting('cmix');
		if (!$setting->get('ilias_uuid', false)) {
			// $uuid = (new \Ramsey\Uuid\UuidFactory())->uuid4()->toString();
			$uuid = self::getUUID(32);
			$setting->set('ilias_uuid', $uuid);
		}
        $ilUuid = $setting->get('ilias_uuid');
        return $ilUuid;
    }
    
    /**
     * @return bool
     */
    public function hasProxySuccess()
    {
        return $this->proxySuccess;
    }
    
    /**
     * @param bool $proxySuccess
     */
    public function setProxySuccess($proxySuccess)
    {
        $this->proxySuccess = $proxySuccess;
    }
    
    /**
     * @return ilXapiCmi5DateTime
     */
    public function getFetchUntil() : ilCmiXapiDateTime
    {
        return $this->fetchUntil;
    }
    
    /**
     * @param ilXapiCmi5DateTime $fetchUntil
     */
    public function setFetchUntil(ilXapiCmi5DateTime $fetchUntil)
    {
        $this->fetchUntil = $fetchUntil;
    }

	public function load()
	{
		global $DIC; /* @var \ILIAS\DI\Container $DIC */
		
		$res = $DIC->database()->queryF(
			"SELECT * FROM xxcf_users WHERE obj_id = %s AND usr_id = %s AND privacy_ident = %s",
			array('integer', 'integer', 'integer'),
			array($this->getObjId(), $this->getUsrId(), $this->getPrivacyIdent())
		);
		
		while ($row = $DIC->database()->fetchAssoc($res)) {
			$this->assignFromDbRow($row);
		}
	}
    
    public function assignFromDbRow($dbRow)
    {
        $this->setObjId((int) $dbRow['obj_id']);
        $this->setUsrId((int) $dbRow['usr_id']);
        // $this->setProxySuccess((bool) $dbRow['proxy_success']);
        // $this->setFetchUntil(new ilXapiCmi5DateTime($dbRow['fetched_until'], IL_CAL_DATETIME));
        $this->setUsrIdent((string) $dbRow['usr_ident']);
    }
    
    public function save()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
		$DIC->database()->insert('xxcf_users', array(
			'obj_id' => array('integer', (int) $this->getObjId()),
			'usr_id' => array('integer', (int) $this->getUsrId()),
			'privacy_ident' => array('integer', (int) $this->getPrivacyIdent()),
			'usr_ident' => array('text', $this->getUsrIdent())
			)
		);
        
        // $DIC->database()->replace(
            // 'cmix_users',
            // array(
                // 'obj_id' => array('integer', (int) $this->getObjId()),
                // 'usr_id' => array('integer', (int) $this->getUsrId())
            // ),
            // array(
                // 'proxy_success' => array('integer', (int) $this->hasProxySuccess()),
                // 'fetched_until' => array('timestamp', $this->getFetchUntil()->get(IL_CAL_DATETIME)),
                // 'usr_ident' => array('text', $this->getUsrIdent())
            // )
        // );
    }
    
    public static function getInstancesByObjectIdAndUsrId($objId, $usrId)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $res = $DIC->database()->queryF(
            "SELECT * FROM xxcf_users WHERE obj_id = %s AND usr_id = %s",
            array('integer', 'integer'),
            array($objId, $usrId)
        );
        $cmixUsers = array();
        while ($row = $DIC->database()->fetchAssoc($res)) {
            $cmixUser = new self();
            $cmixUser->assignFromDbRow($row);
            $cmixUsers[] = $cmixUser;
        }
        return $cmixUsers;
    }

    public static function getInstanceByObjectIdAndUsrIdent($objId, $usrIdent)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */        
        $res = $DIC->database()->queryF(
            "SELECT * FROM xxcf_users WHERE obj_id = %s AND usr_ident = %s",
            array('integer', 'integer'),
            array($objId, $usrIdent)
        );
        $cmixUser = new self();
        while ($row = $DIC->database()->fetchAssoc($res)) {
            $cmixUser->assignFromDbRow($row);
        }
        return $cmixUser;
    }
    
    /**
     * @param int $objId
     * @param int $usrId
     */
    public static function saveProxySuccess($objId, $usrId)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $DIC->database()->update(
            'xxcf_users',
            array(
                'proxy_success' => array('integer', (int) true)
            ),
            array(
                'obj_id' => array('integer', (int) $objId),
                'usr_id' => array('integer', (int) $usrId)
            )
        );
    }
    
    /**
     * @param string $userIdentMode
     * @param ilObjUser $user
     * @return string
     */
    public static function getIdent($userIdentMode, ilObjUser $user)
    {
		require_once __DIR__.'/class.ilXapiCmi5Type.php';
        switch ($userIdentMode) {
            case ilXapiCmi5Type::PRIVACY_IDENT_CODE : //ilObjXapiCmi5::USER_IDENT_IL_UUID_USER_ID:
                
                return self::buildPseudoEmail($user->getId(), self::getIliasUuid());
                
            // case ilObjXapiCmi5::USER_IDENT_IL_UUID_LOGIN:
                
                // return self::buildPseudoEmail($user->getLogin(), self::getIliasUuid());
                
            // case ilObjXapiCmi5::USER_IDENT_IL_UUID_EXT_ACCOUNT:
                
                // return self::buildPseudoEmail($user->getExternalAccount(), self::getIliasUuid());
                
            case ilXapiCmi5Type::PRIVACY_IDENT_RANDOM : //ilObjXapiCmi5::USER_IDENT_IL_UUID_RANDOM:

                return self::buildPseudoEmail(self::getUserObjectUniqueId(), self::getIliasUuid());

            case ilXapiCmi5Type::PRIVACY_IDENT_EMAIL : //ilObjXapiCmi5::USER_IDENT_REAL_EMAIL:
                
                return $user->getEmail();
        }
        
        return '';
    }
    
    public static function getIdentPlugin($userIdentMode, ilObjUser $user) {
        require_once __DIR__.'/class.ilXapiCmi5Type.php';
		switch ($userIdentMode) {
			case ilXapiCmi5Type::PRIVACY_IDENT_CODE :
				$iliasDomain = substr(ILIAS_HTTP_PATH,7);
				if (substr($iliasDomain,0,1) == "\/") $iliasDomain = substr($iliasDomain,1);
				if (substr($iliasDomain,0,4) == "www.") $iliasDomain = substr($iliasDomain,4);
				$usr_ident = ''.$user->getId().'_'.str_replace('/','_',$iliasDomain).'_'.CLIENT_ID.'@iliassecretuser.de';
				break;
			case ilXapiCmi5Type::PRIVACY_IDENT_NUMERIC :
				$usr_ident = $user->getId().'@iliassecretuser.de';
				break;
			case ilXapiCmi5Type::PRIVACY_IDENT_LOGIN :
				$usr_ident = $user->getLogin();
				break;
			case ilXapiCmi5Type::PRIVACY_IDENT_EMAIL :
				$usr_ident = $user->getEmail();
				break;
			case ilXapiCmi5Type::PRIVACY_IDENT_RANDOM :
				$iliasDomain = "";
				if (IL_INST_ID == 0) {
					$iliasDomain = substr(ILIAS_HTTP_PATH,7);
					if (substr($iliasDomain,0,1) == "\/") $iliasDomain = substr($iliasDomain,1);
					if (substr($iliasDomain,0,4) == "www.") $iliasDomain = substr($iliasDomain,4);
					$iliasDomain = '_' . str_replace('/','_',$iliasDomain).'_'.CLIENT_ID;
				}
				// $query = "SELECT uuid FROM xxcf_usrobjuuid_map".
				// " WHERE usr_id = " . $user->getId() .
				// " AND obj_id = " . $DIC->database()->quote($obj_id, 'integer');
				// $result = $DIC->database()->query($query);
				// $uuid = is_array($row = $DIC->database()->fetchAssoc($result)) ? $row['uuid'] : '';
				// $privacy_ident = IL_INST_ID . '_' . $uuid . $iliasDomain . '@iliassecretuser.de';
				$usr_ident = IL_INST_ID . '_' . self::getUserObjectUniqueId() . $iliasDomain . '@iliassecretuser.de';
				break;
			default :
				$usr_ident = $user->getEmail();
		}
		return $usr_ident;
    }
    
    /**
     * @param string $userIdentMode
     * @param ilObjUser $user
     * @return integer/string
     */
    public static function getIdentAsId($userIdentMode, ilObjUser $user)
    {
        switch ($userIdentMode) {
            case ilObjXapiCmi5::USER_IDENT_IL_UUID_USER_ID:
                
                return $user->getId();
                
            case ilObjXapiCmi5::USER_IDENT_IL_UUID_LOGIN:
                
                return $user->getLogin();
                
            case ilObjXapiCmi5::USER_IDENT_IL_UUID_EXT_ACCOUNT:
                
                return $user->getExternalAccount();
                
            case ilObjXapiCmi5::USER_IDENT_IL_UUID_RANDOM:

                return self::getUserObjectUniqueId();

            case ilObjXapiCmi5::USER_IDENT_REAL_EMAIL:
                
                return 'realemail' . $user->getId();
        }
        
        return '';
    }

    /**
     * @param string $mbox
     * @param string $domain
     * @return string
     */
    protected static function buildPseudoEmail($mbox, $domain)
    {
        return "{$mbox}@{$domain}.ilias";
    }
    
    /**
     * @param string $userNameMode
     * @param ilObjUser $user
     * @return string|null
     */
    public static function getName($userNameMode, ilObjUser $user)
    {
        switch ($userNameMode) {
            case ilObjXapiCmi5::USER_NAME_FIRSTNAME:
                
                $usrName = $user->getFirstname();
                break;
            
            case ilObjXapiCmi5::USER_NAME_LASTNAME:
                
                $usrName = $user->getUTitle() ? $user->getUTitle() . ' ' : '';
                $usrName .= $user->getLastname();
                break;
            
            case ilObjXapiCmi5::USER_NAME_FULLNAME:
                
                $usrName = $user->getFullname();
                break;
            
            case ilObjXapiCmi5::USER_NAME_NONE:
            default:
                
                $usrName = '';
                break;
        }
        
        return $usrName;
    }
    

    public static function getNamePlugin($userNameMode, ilObjUser $user) {
        global $lng;
        $privacy_name = "";
		switch ($userNameMode) {
			case 0 :
				$privacy_name = "";
				break;
			case 1 :
				$privacy_name = $user->getFirstname();
				break;
			case 2 :
				$privacy_name = $lng->txt("salutation_".$user->getGender()) .' '. $user->getLastname();
				break;
			default :
				$privacy_name = $user->getFullname();;
        }
        return $privacy_name;
    }
    /**
     * @param int $object
     * @return ilXapiCmi5User[]
     */
    public static function getUsersForObject($objId) : array
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $res = $DIC->database()->queryF(
            "SELECT * FROM xxcf_users WHERE obj_id = %s",
            array('integer'),
            array($objId)
        );
        
        $users = [];
        
        while ($row = $DIC->database()->fetchAssoc($res)) {
            $cmixUser = new self();
            $cmixUser->assignFromDbRow($row);
            
            $users[] = $cmixUser;
        }
        
        return $users;
    }
    
    /**
     * @param int $objId
     * @param int[] $users
     */
    public static function deleteUsersForObject(int $objId, ?array $users = [])
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        $query = "DELETE FROM xxcf_users WHERE obj_id = ".$DIC->database()->quote($objId, 'integer');
        if (count($users) == 0) {
            $DIC->database()->manipulate($query);
        }
        else {
            $DIC->database()->manipulateF($query." AND usr_id = %s",
                array('integer'),
                $users
            );
        }
    }
    /**
     * @param int $object
     * @return int[]
     */
    // public static function getUsersForObjectPlugin($objId) : array
    // {
        // global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        // $res = $DIC->database()->queryF(
            // "SELECT * FROM xxcf_users WHERE obj_id = %s",
            // array('integer'),
            // array($objId)
        // );
        
        // $users = [];
        
        // while ($row = $DIC->database()->fetchAssoc($res)) {
            // //$cmixUser = new self($row['usr_id'],$row['obj_id'],$this->object); // ToDo not nice :-|
            // //$cmixUser->assignFromDbRow($row);
            // $users[] = $row['usr_id'];
        // }
        // return $users;
    // }

    public static function exists($objId, $usrId)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $query = "SELECT count(*) cnt FROM xxcf_users WHERE obj_id = %s AND usr_id = %s";

        $res = $DIC->database()->queryF(
            $query,
            array('integer', 'integer'),
            array($objId, $usrId)
        );
        
        while ($row = $DIC->database()->fetchAssoc($res)) {
            return (bool) $row['cnt'];
        }
        
        return false;
    }

    public static function userExists($objId, $usrId=null)
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        if (is_null($usrId)) {
            $query = "SELECT count(*) cnt FROM xxcf_users WHERE obj_id = %s";

            $res = $DIC->database()->queryF(
                $query,
                array('integer'),
                array($objId)
            );
        }
        else {
            $query = "SELECT count(*) cnt FROM xxcf_users WHERE obj_id = %s AND usr_id = %s";

            $res = $DIC->database()->queryF(
                $query,
                array('integer','integer'),
                array($objId,$usrId)
            );
        }
        while ($row = $DIC->database()->fetchAssoc($res)) {
            return (bool) $row['cnt'];
        }
        
        return false;
    }

    public static function getCmixObjectsHavingUsersMissingProxySuccess()
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $query = "
			SELECT DISTINCT cu.obj_id
			FROM xxcf_users cu
			INNER JOIN object_data od
			ON od.obj_id = cu.obj_id
			AND od.type = 'cmix'
			WHERE cu.proxy_success != %s
		";
        
        $res = $DIC->database()->queryF($query, array('integer'), array(1));
        
        $objects = array();
				   
        while ($row = $DIC->database()->fetchAssoc($res)) {
            $objects[] = $row['obj_id'];
        }
        
        return $objects;
    }

    /*
    public static function updateFetchedUntilForObjects(ilCmiXapiDateTime $fetchedUntil, $objectIds)
    {
        global $DIC;
        
        $IN_objIds = $DIC->database()->in('obj_id', $objectIds, false, 'integer');
        
        $query = "UPDATE cmix_users SET fetched_until = %s WHERE $IN_objIds";
        $DIC->database()->manipulateF($query, array('timestamp'), array($fetchedUntil->get(IL_CAL_DATETIME)));
    }
    */
    public static function lookupObjectIds($usrId, $type = '')
    {
        global $DIC; /* @var \ILIAS\DI\Container $DIC */
        
        $TYPE_JOIN = '';
        
        if (strlen($type)) {
            $TYPE_JOIN = "
				INNER JOIN object_data od
				ON od.obj_id = cu.obj_id
				AND od.type = {$DIC->database()->quote($type, 'text')}
			";
        }
        
        $query = "
			SELECT cu.obj_id
			FROM xxcf_users cu
			{$TYPE_JOIN}
			WHERE cu.usr_id = {$DIC->database()->quote($usrId, 'integer')}
		";
        
        $res = $DIC->database()->query($query);
        
        $objIds = [];
        
        while ($row = $DIC->database()->fetchAssoc($res)) {
            $objIds[] = $row['obj_id'];
        }
        
        return $objIds;
    }
    /**
     * @param int $length
     * @return string
     */
    public static function getUserObjectUniqueId( $length = 32 )
    {
		//entfällt weil vorher abgefragt
        // $storedId = self::readUserObjectUniqueId();
        // if( (bool)strlen($storedId) ) {
            // return strstr($storedId,'@', true);
        // }

        // $getId = function( $length ) {
            // $multiplier = floor($length/8) * 2;
            // $uid = str_shuffle(str_repeat(uniqid(), $multiplier));

            // try {
                // $ident = bin2hex(random_bytes($length));
            // } catch (Exception $e) {
                // $ident = $uid;
            // }

            // $start = rand(0, strlen($ident) - $length - 1);
            // return substr($ident, $start, $length);
        // };

        $id = self::getUUID($length);//$getId($length);
        $exists = self::userObjectUniqueIdExists($id);
        while( $exists ) {
            $id = self::getUUID($length);//$getId($length);
            $exists = self::userObjectUniqueIdExists($id);
        }

        return $id;

    }
	
	public static function getUUID($length = 32 )
	{
		$multiplier = floor($length/8) * 2;
		$uid = str_shuffle(str_repeat(uniqid(), $multiplier));

		try {
			$ident = bin2hex(random_bytes($length));
		} catch (Exception $e) {
			$ident = $uid;
		}

		$start = rand(0, strlen($ident) - $length - 1);
		return substr($ident, $start, $length);
	}

    // private static function readUserObjectUniqueId()
    // {
        // global $DIC; /** @var Container */
        // $obj_id = ilObject::_lookupObjId($_GET["ref_id"]);

        // $query = "SELECT usr_ident FROM xxcf_users".
            // " WHERE usr_id = " . $DIC->database()->quote($DIC->user()->getId(), 'integer') .
            // " AND obj_id = " . $DIC->database()->quote($obj_id, 'integer');
        // $result = $DIC->database()->query($query);
        // return is_array($row = $DIC->database()->fetchAssoc($result)) ? $row['usr_ident'] : '';
    // }

    private static function userObjectUniqueIdExists($id)
    {
        global $DIC; /** @var Container */

        $query = "SELECT usr_ident FROM xxcf_users WHERE " . $DIC->database()->like('usr_ident', 'text', $id . '@%');
        $result = $DIC->database()->query($query);
        return (bool)$num = $DIC->database()->numRows($result);
    }

    public static function getRegistration(ilObjXapiCmi5 $obj, ilObjUser $user)
    {
        // return (new \Ramsey\Uuid\UuidFactory())->uuid3(self::getIliasUuid(),$obj->getRefId() . '-' . $user->getId());
		return self::getUUID(32);
    }


}
