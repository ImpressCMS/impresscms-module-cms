<?php
/**
 * Classes responsible for managing cms start objects
 *
 * @copyright	Copyright Madfish (Simon Wilkinson) 2012
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Madfish (Simon Wilkinson) <simon@isengard.biz>
 * @package		cms
 * @version		$sato-san$
*/

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_cms_StartHandler extends icms_ipf_Handler
{
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database connection object
	 */
	public function __construct(&$db)
	{
		parent::__construct($db, "start", "start_id", "title", "description", "cms");
		$this->enableUpload(array("image/gif", "image/jpeg", "image/pjpeg", "image/png"), 2512000, 3800, 2600);
	}

	/**
	 * Toggles the online_status field and updates the object
	 *
	 * @return null
	 */
	public function toggleOnlineStatus($id)
	{
		$status = '';
		
		// Load the object that will be manipulated
		$startObj = $this->get($id);
		
		// Toggle the online status field and update the object
		if ($startObj->getVar('online_status', 'e') == 1) {
			$startObj->setVar('online_status', 0);
			$status = 0;
		} else {
			$startObj->setVar('online_status', 1);
			$status = 1;
		}
		$this->insert($startObj, TRUE);
		
		return $status;
	}
	
	/**
	* Toggles the fertigstellung field and updates the object
	*
	* @return null
	*/
	public function toggleFertigstellung($id)
	{
	$status = '';
	
		// Load the object that will be manipulated
		$startObj = $this->get($id);
	
		// Toggle the beendet field and update the object
		if ($startObj->getVar('beendet', 'e') == 1) {
				$startObj->setVar('beendet', 0);
		$status = 0;
		} else {
		$startObj->setVar('beendet', 1);
		$status = 1;
			}
		$this->insert($startObj, TRUE);
	
		return $status;
	}
	
	/**
	 * Converts beendet value to human readable text
	 *
	 * @return array
	 */
	public function beendet_filter()
	{
		return array(0 => _AM_CMS_START_NO, 1 => _AM_CMS_START_YES);
	}

	/**
	 * Converts status value to human readable text
	 *
	 * @return array
	 */
	public function online_status_filter()
	{
		return array(0 => _AM_CMS_START_OFFLINE, 1 => _AM_CMS_START_ONLINE);
	}
	
	/**
	 * Provides the global search functionality for the Cms module
	 *
	 * @param array $queryarray
	 * @param string $andor
	 * @param int $limit
	 * @param int $offset
	 * @param int $userid
	 * @return array 
	 */
	public function getCmsForSearch($queryarray, $andor, $limit, $offset, $userid)
	{		
		$criteria = new icms_db_criteria_Compo();
		$criteria->setStart($offset);
		$criteria->setLimit($limit);
		$criteria->setSort('title');
		$criteria->setOrder('ASC');

		if ($userid != 0) 
		{
			$criteria->add(new icms_db_criteria_Item('submitter', $userid));
		}
		
		if ($queryarray) 
		{
			$criteriaKeywords = new icms_db_criteria_Compo();
			for ($i = 0; $i < count($queryarray); $i++) {
				$criteriaKeyword = new icms_db_criteria_Compo();
				$criteriaKeyword->add(new icms_db_criteria_Item('title', '%' . $queryarray[$i] . '%', 'LIKE'), 'OR');
				$criteriaKeyword->add(new icms_db_criteria_Item('description', '%' . $queryarray[$i] . '%', 'LIKE'), 'OR');
				$criteriaKeyword->add(new icms_db_criteria_Item('extended_text', '%' . $queryarray[$i] . '%', 'LIKE'), 'OR'); //neu hinzugefügt
				$criteriaKeywords->add($criteriaKeyword, $andor);
				unset ($criteriaKeyword);
			}
			$criteria->add($criteriaKeywords);
		}
		
		$criteria->add(new icms_db_criteria_Item('online_status', TRUE));
		
		return $this->getObjects($criteria, TRUE, TRUE);
	}
		
	/**
	 * Stores tags when a start is inserted or updated
	 *
	 * @param object $obj CmsStart object
	 * @return bool
	 */
	protected function afterSave(& $obj)
	{
		$sprocketsModule = icms::handler("icms_module")->getByDirname("sprockets");

		// Only update the taglinks if the object is being updated from the add/edit form (POST).
		// The taglinks should *not* be updated during a GET request (ie. when the toggle buttons
		// are used to change the Fertigstellung status or online status). Attempting to do so will 
		// trigger an error, as the database should not be updated during a GET request.
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && icms_get_module_status("sprockets"))
		{		
			$sprockets_taglink_handler = '';
			$sprockets_taglink_handler = icms_getModuleHandler('taglink', 
					$sprocketsModule->getVar('dirname'), 'sprockets');
			$sprockets_taglink_handler->storeTagsForObject($obj);
		}
		return TRUE;
	}
	
	/**
	 * Deletes taglinks when a start is deleted
	 *
	 * @param object $obj CmsStart object
	 * @return bool
	 */
	protected function afterDelete(& $obj) 
	{	
		$sprocketsModule = $notification_handler = $module_handler = $module = $module_id
				= $category = $item_id = '';
		
		$sprocketsModule = icms::handler("icms_module")->getByDirname("sprockets");
		
		if (icms_get_module_status("sprockets"))
		{
			$sprockets_taglink_handler = icms_getModuleHandler('taglink',
					$sprocketsModule->getVar('dirname'), 'sprockets');
			$sprockets_taglink_handler->deleteAllForObject($obj);
		}

		return TRUE;
	}
	
	/**
	* Update number of comments on a start
	*
	* @param int $start_id id of the start to update
	* @param int $total_num total number of comments so far in this start
	* @return VOID
	*/
	public function updateComments($start_id, $total_num) {
		$startObj = $this->get($start_id);
		if ($startObj && !$startObj->isNew()) {
			$startObj->setVar('start_comments', $total_num);
			$this->insert($startObj, true);
		}
	}
}
