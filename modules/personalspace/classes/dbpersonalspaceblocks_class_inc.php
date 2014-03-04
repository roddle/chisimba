<?php

/**
* Context Blocks
* 
* Class to add, rearrange, move around and remove blocks from a context home page
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
* @category  Chisimba
* @package   context
* @author    Tohir Solomons <tsolomons@uwc.ac.za>
* @copyright 2008 Tohir Solomons
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
* @version   $Id: dbcontextblocks_class_inc.php 3611 2008-02-26 13:31:01Z tohir $
* @link      http://avoir.uwc.ac.za
* @see       core
*/
/* -------------------- dbTable class ----------------*/
// security check - must be included in all scripts
if (!
/**
* Description for $GLOBALS
* @global entry point $GLOBALS['kewl_entry_point_run']
* @name   $kewl_entry_point_run
*/
$GLOBALS['kewl_entry_point_run']) {
die("You cannot view this page directly");
}
// end security check


/**
* Context Blocks
* 
* Class to add, rearrange, move around and remove blocks from a context home page
* 
* @category  Chisimba
* @package   context
* @author    Tohir Solomons <tsolomons@uwc.ac.za>
* @copyright 2008 Tohir Solomons
* @license   http://www.gnu.org/licenses/gpl-2.0.txt The GNU General Public License
* @version   Release: @package_version@
* @link      http://avoir.uwc.ac.za
* @see       core
*/
class dbpersonalspaceblocks extends dbTable
{
   /**
   * @var object $objUser : The user Object
   */
   public $objUser;


    /**
    * Constructor
    */
    public function init()
    {
        parent::init('tbl_personalspace_blocks');
        $this->objUser = $this->getObject('user','security');
    }
    
    /**
     * Method to get a list of blocks used in a context, and have them rendered one time
     * @param string $contextCode Context Code
     * @param string $side Side on which the blocks are on
     * @param return array
     */
    public function getUserBlocks($userId, $side)
    {
        $results = $this->getUserBlocksList($userId, $side);
        
        if (count($results) == 0) {
           return '';
        } else {
           
            $str = '';
            
            $objBlocks = $this->getObject('blocks', 'blocks');
            $objDynamicBlocks = $this->getObject('dynamicblocks', 'blocks');
            
            foreach ($results as $result)
            {
                $block = explode('|', $result['block']);
                
                $blockId = $side.'___'.str_replace('|', '___', $result['block']);
                
                // At the moment, only blocks are catered for, not yet dynamic blocks
                if ($block[0] == 'block') {
                    
                    
                    $blockStr = $objBlocks->showBlock($block[1], $block[2], NULL, 20, TRUE, FALSE);
                    
                    //$blockStr = preg_replace('/onchange\ *=\ *".*?"/i', '', $blockStr);
                    //$blockStr = preg_replace('/type\ *=\ *"submit"/i', 'type="button"', $blockStr);
                    //$blockStr = preg_replace('/href\ *=\ *".*?"/i', 'href="javascript:alert(\'Disabled\');"', $blockStr);
                    
                    $str .= '<div id="'.$result['id'].'" class="block">'.$blockStr.'</div>';
                } else if ($block[0] == 'dynamicblock') {
                    $block = explode('|', $result['block']);
                    $blockStr = $objDynamicBlocks->showBlock($block[1]);
                    $str .= '<div id="'.$result['id'].'" class="block">'.$blockStr.'</div>';
                }
           }
           
           return $str;
        }
    }
    
    /**
     * Method to get a list of blocks used in a context
     * @param string $contextCode Context Code
     * @param string $side Side on which the blocks are on
     * @param return array
     */
    public function getUserBlocksList($userId, $side)
    {
        return $this->getAll(' WHERE side=\''.$side.'\' AND userid=\''.$userId.'\' ORDER BY position');
    }
    
    
    /**
     * Method to get a list of blocks used by a context
     * @param string $contextCode
     * @return array List of Blocks
     */
    public function getUserBlocksArray($userId)
    {
        $results = $this->getAll(' WHERE userid=\''.$userId.'\' ');
        
        $array = array();
        
        if (count($results) > 0) {
            foreach ($results as $result)
            {
                $array[] = $result['block'];
            }
        }
        
        return $array;
    }
    
    
    /**
     * Method to add a block to a context
     * @param string $block Block Id
     * @param string $side Side Block is On
     * @param string $contextCode Context Code
     * @param string $module Module Block is from
     *
     */
    public function addBlock($block, $side, $userId, $module)
    {
        return $this->insert(array(
                'userid' => $userId,
                'block' => $block,
                'side' => $side,
                'module' => $module,
                'position' => $this->getLastOrder($side, $userId)+1,
                'datelastupdated' => strftime('%Y-%m-%d %H:%M:%S', mktime()),
            ));
    }
    
    /**
     * Method to get the last order of a block on a side
     * This is used for ordering purposes
     *
     * @param string $side Side block will be added
     * @param string $contextCode Context Code
     *
     * @return int
     */
    private function getLastOrder($side, $userId)
    {
        $results = $this->getAll(' WHERE side=\''.$side.'\' AND userid=\''.$userId.'\' ORDER BY position DESC LIMIT 1');
        
        if (count($results) == 0) {
            return 0;
        } else {
            return $results[0]['position'];
        }
    }
    
    
    /**
     * Method to remove a block
     * @param string $id Block Id
     */
    public function removeBlock($id)
    {
        return $this->delete('id', $id);
    }
    
    /**
     * Method to remove a block
     * @param string $id Block Id
     */
    public function removeUserBlocks($userId)
    {
        return $this->delete('userid', $userId);
    }
    
    /**
     * Method to move a block up
     *
     * @param string $id Block Id
     * @param string $contextCode Context Code - required to prevent malicious changes
     */
    public function moveBlockUp($id, $userId)
    {
        $record = $this->getRow('id', $id);
        
        if ($record == FALSE) {
            return FALSE;
        } else {
            
            if ($record['userid'] != $userId) {
                return FALSE;
            }
            
            $prevRecord = $this->getPreviousBlock($record['userid'], $record['side'], $record['position']);
            
            if ($prevRecord == FALSE) {
                return FALSE;
            } else {
                $this->update('id', $record['id'], array('position'=>$prevRecord['position']));
                $this->update('id', $prevRecord['id'], array('position'=>$record['position']));
                
                return TRUE;
            }
        }
    }
    
    /**
     * Method to move a block down
     *
     * @param string $id Block Id
     * @param string $contextCode Context Code - required to prevent malicious changes
     */
    public function moveBlockDown($id, $userId)
    {
        $record = $this->getRow('id', $id);
        
        if ($record == FALSE) {
            return FALSE;
        } else {
            
            if ($record['userid'] != $userId) {
                return FALSE;
            }
            
            $nextRecord = $this->getNextBlock($record['userid'], $record['side'], $record['position']);
            
            if ($nextRecord == FALSE) {
                return FALSE;
            } else {
                $this->update('id', $record['id'], array('position'=>$nextRecord['position']));
                $this->update('id', $nextRecord['id'], array('position'=>$record['position']));
                
                return TRUE;
            }
        }
    }
    
    /**
     * Method to get the details of the previous block
     *
     * @param string $contextCode Context Code - required to prevent malicious changes
     * @param string $side Side Block is on
     * @param int $position Position of the Block
     *
     * @return array
     */
    private function getPreviousBlock($userId, $side, $position)
    {
        $results = $this->getAll(' WHERE side=\''.$side.'\' AND userid=\''.$userId.'\' AND position < '.$position.' ORDER BY position DESC LIMIT 1');
        
        if (count($results) == 0) {
            return FALSE;
        } else {
            return $results[0];
        }
    }
    
    /**
     * Method to get the details of the next block
     *
     * @param string $contextCode Context Code - required to prevent malicious changes
     * @param string $side Side Block is on
     * @param int $position Position of the Block
     *
     * @return array
     */
    private function getNextBlock($userId, $side, $position)
    {
        $results = $this->getAll(' WHERE side=\''.$side.'\' AND userid=\''.$userId.'\' AND position > '.$position.' ORDER BY position LIMIT 1');
        
        if (count($results) == 0) {
            return FALSE;
        } else {
            return $results[0];
        }
    }
    
}
?>