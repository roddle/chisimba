<?php

/**
 * Loggedinusers class
 *
 * This class keeps track of which users are logged into the system at any given time.
 *
 * PHP version 5
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *
 * @category  Chisimba
 * @package   security
 * @author AVOIR
 * @copyright 2004-2007, University of the Western Cape & AVOIR Project
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
 * @version   $Id$
 * @link      http://avoir.uwc.ac.za
 */
// security check - must be included in all scripts
if (!$GLOBALS['kewl_entry_point_run']) {
    die("You cannot view this page directly");
}
// end security check

/**
 * Loggedinusers class
 *
 * This class keeps track of which users are logged into the system at any given time.
 *
 */
class loggedInUsers extends dbTable {

    public $objConfig;
    private $systemTimeOut;
    private $logoutdestroy = true;

    /**
     *
     * Constructor for the class, which clears inactive users, and
     * updates login for anyone logged into the system.
     *
     */
    public function init() {
        parent::init('tbl_loggedinusers');
        $this->objConfig = $this->getObject('altconfig', 'config');
        $this->systemTimeOut = $this->objConfig->getsystemTimeout();
        //==$this->objSysConfig = $this->getObject ('dbsysconfig','sysconfig');
        // Because of a circular reference via dbsysconfig_class_inc.php and user_class_inc.php, the above class cannot be instantiated. So, $xlogoutdestroy is therefore hardcoded to true.
        $xlogoutdestroy = 'true'; //==$this->objSysConfig->getValue('auth_logoutdestroy', 'security', 'true');
        //$this->objConfig->getValue('auth_logoutdestroy', 'security', true);
        //trigger_error('$xlogoutdestroy::'.($xlogoutdestroy?'T':'F'));
        if ($xlogoutdestroy == 'true' || $xlogoutdestroy == 'TRUE' || $xlogoutdestroy == 'True') {
            $this->logoutdestroy = true;
        } else {
            $this->logoutdestroy = false;
        }
        // Clear inactive users.
        $this->clearInactive();
        //trigger_error('$this->logoutdestroy::'."$this->logoutdestroy");
    }

    /**
     * Insert a record at login time
     *
     * @param string $userId The userId of the user logging in
     * @access public
     * @return VOID
     *
     */
    public function insertLogin($userId) {
        // Delete old logins
        $sql = "DELETE FROM tbl_loggedinusers
            WHERE
                (userid = '$userId')
                AND ((CURRENT_TIMESTAMP-whenlastactive)>'{$this->systemTimeOut}')";
        if (!$this->logoutdestroy) {
            $sql = "DELETE FROM tbl_loggedinusers WHERE userid='$userId'";
        }
        $this->query($sql);
        // Update the tbl_loggedinusers table
        $this->activateLogin($userId);
    }

    /**
     *
     * Because the login/session management uses liveuser, this means that
     * a user may be cleared from tbl_loggedinusers but still be logged into
     * the site due to persistence. This method restores an active user to
     * the tbl_loggedinusers table.
     *
     * @param type $userId
     */
    public function activateLogin($userId)
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $sessionId = session_id();
        $contextObject = $this->getObject('dbcontext', 'context');
        $contextCode = $contextObject->getContextCode();
        if ($contextCode == "" || $contextCode == NULL) {
            $contextCode = 'lobby';
        }
        $theme = "default";
        $myDate = date('Y-m-d H:i:s');
        $isInvisible = '0';
        $sql = "
            INSERT INTO tbl_loggedinusers (
                                id,
                userid,
                ipaddress,
                sessionid,
                whenloggedin,
                whenlastactive,
                isinvisible,
                coursecode,
                themeused
            )
            VALUES (
                                '" . date('YmdHis') . "',
                '$userId',
                '$ipAddress',
                '$sessionId',
                 CURRENT_TIMESTAMP,
                 CURRENT_TIMESTAMP,
                '$isInvisible',
                '$contextCode',
                '$theme'
            )";
        $this->query($sql);
    }

    /**
     * Logout user from the site. The method deletes
     * the user from the database table tbl_loggedinusers, destroys
     * the session, and redirects the user to the index page,
     * index.php.
     * @param string $userId The userId of the logged in user
     */
    public function doLogout($userId) {
        $sql = "DELETE FROM tbl_loggedinusers
        WHERE
            userid='$userId'
            AND sessionid ='" . session_id() . "'
        ";
        if (!$this->logoutdestroy) {
            $sql = "DELETE FROM tbl_loggedinusers
        WHERE userid='$userId'";
        }
        $this->query($sql);
    }

    /**
     * Update the current user's active timestamp.
     */
    public function doUpdateLogin($userId, $contextCode='lobby') {
        $objUser = $this->getObject('user', 'security');
        if (!$this->isUserOnline($userId)
          && $objUser->isLoggedIn($userId)
        ) {
            $this->activateLogin($userId);
        } else {
            $sql = "UPDATE tbl_loggedinusers
            SET
                whenlastactive = CURRENT_TIMESTAMP,
                coursecode='$contextCode'
            WHERE
                userid='$userId'
                AND sessionid ='" . session_id() . "'
            ";

            if (!$this->logoutdestroy) {
                $sql = "UPDATE tbl_loggedinusers
                SET whenlastactive = CURRENT_TIMESTAMP, coursecode='$contextCode'
                WHERE userid='$userId'";
            }
            $this->query($sql);
        }
    }

    /**
     * Return the time logged in.
     */
    public function getMyTimeOn($userId) {
        $sql = "SELECT (whenlastactive - whenloggedin)/100 AS activetime FROM tbl_loggedinusers
        WHERE
            userid='$userId'
            AND sessionid='" . session_id() . "'
        ";
        if (!$this->logoutdestroy) {
            $sql = "SELECT TIMEDIFF(now(),whenloggedin) AS activetime FROM tbl_loggedinusers
        WHERE userid='$userId'";
            $results = $this->getArray($sql);
            return $results[0]['activetime'];
        }
        $results = $this->getArray($sql);
        if (!empty($results)) {
            $timeActive = intval($results[0]['activetime']);
        } else {
            $timeActive = 0;
        }
        return $timeActive;
    }

    /**
     * Return the time logged in.
     */
    public function getLogonTime($userId) {
        $sql = "SELECT whenloggedin FROM tbl_loggedinusers
        WHERE
            userid='$userId'
            AND sessionid='" . session_id() . "'
        ";
        if (!$this->logoutdestroy) {
            $sql = "SELECT whenloggedin FROM tbl_loggedinusers
        WHERE userid='$userId'";
        }
        $results = $this->getArray($sql);
        if (!empty($results)) {
            return $results[0]['whenloggedin'];
        }
        return "0";
    }

    /**
     * Returns the time when user was last active.
     */
    public function getLastActiveTime($userId) {
        $sql = "SELECT whenlastactive FROM tbl_loggedinusers
        WHERE
            userid='$userId'
            AND sessionid='" . session_id() . "'
        ";
        if (!$this->logoutdestroy) {
            $sql = "SELECT whenlastactive FROM tbl_loggedinusers
        WHERE userid='$userId'";
        }
        $results = $this->getArray($sql);
        if (!empty($results)) {
            return $results[0]['whenlastactive'];
        }
        return "0";
    }

    /**
     * Count active users.
     */
    public function getActiveUserCount() {
        $sql = "SELECT COUNT(id) AS usercount FROM tbl_loggedinusers";
        $results = $this->getArray($sql);
        if (!empty($results)) {
            $activeUserCount = intval($results[0]['usercount']);
        } else {
            $activeUserCount = 0;
        }
        return $activeUserCount;
    }

    /**
     * Return how long since the user was last active.
     * @param string $userId
     */
    public function getInactiveTime($userId) {
        $sql = "SELECT
            ((CURRENT_TIMESTAMP-whenlastactive)/100) AS inactivetime
        FROM
            tbl_loggedinusers
        WHERE
            userid='$userId'
            AND sessionid='" . session_id() . "'
        ";
        if (!$this->logoutdestroy) {
            $sql = "SELECT
            ((CURRENT_TIMESTAMP-whenlastactive)/100) AS inactivetime
        FROM
            tbl_loggedinusers
        WHERE
            userid='$userId'
        ";
        }
        $results = $this->getArray($sql);
        if (!empty($results)) {
            $inactiveTime = intval($results[0]['inactivetime']);
        } else {
            $inactiveTime = 10 + $this->systemTimeOut;
        }
        return $inactiveTime;
    }

    /**
     * Method to clear inactive users
     */
    public function clearInactive() {
        $sql = "DELETE FROM tbl_loggedinusers
        WHERE
            (CURRENT_TIMESTAMP-whenlastactive) > '{$this->systemTimeOut}'
        ";

        $this->query($sql);
    }

    /**
     * Method to check if a specified userId is online
     * @param string $userId
     * returns TRUE|FALSE
     */
    public function isUserOnline($userid) {
        $sql = "SELECT COUNT(userid) AS count FROM tbl_loggedinusers WHERE userid='$userid'";
        $results = $this->getArray($sql);
        if (!empty($results)) {
            if ($results[0]['count'] > 0) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Method to get a list of logged in users
     * @param string $order Order Clause
     * @return array List of Users Online
     */
    function getListOnlineUsers($order = 'WhenLastActive DESC') {
        $sql = 'SELECT DISTINCT tbl_users.userId, username, firstName, surname FROM tbl_loggedinusers INNER JOIN tbl_users ON (tbl_loggedinusers.userId = tbl_users.userId) ORDER BY ' . $order;
        return $this->getArray($sql);
    }

    /**
     * Method to get a list of the latest five logged in users
     * @param string $order Order Clause
     * @return array List of Users Online
     */
    function getLastFiveOnlineUsers($order = 'WhenLastActive DESC') {
        $sql = 'SELECT DISTINCT tbl_users.userId, username, firstName, surname FROM tbl_loggedinusers INNER JOIN tbl_users ON (tbl_loggedinusers.userId = tbl_users.userId) ORDER BY ' . $order . ' LIMIT 5';
        return $this->getArray($sql);
    }

    /**
     * returns a list of users who currently logged in the course
     * @param <type> $contextCode
     * @return <type>
     */
    function getListOnlineUsersInCurrentContext($contextCode) {
        $sql = 'SELECT DISTINCT tbl_users.userId, username, firstName, surname FROM tbl_loggedinusers INNER JOIN tbl_users ON (tbl_loggedinusers.userId = tbl_users.userId) where coursecode= "' . $contextCode . '" ORDER BY WhenLastActive DESC';

        return $this->getArray($sql);
    }

}

?>