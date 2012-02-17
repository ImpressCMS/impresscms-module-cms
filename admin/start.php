<?php
/**
 * Admin page to manage cms
 *
 * List, add, edit and delete start objects
 *
 * @copyright	Copyright Madfish (Simon Wilkinson) 2012
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Madfish (Simon Wilkinson) <simon@isengard.biz>
 * @package		cms
 * @version		$sato-san$
*/

/**
 * Edit a Start
 *
 * @param int $start_id start to be edited
*/
function editstart($start_id = 0)
{
	global $cms_start_handler, $icmsModule, $icmsAdminTpl;

	$startObj = $cms_start_handler->get($start_id);
	$sprocketsModule = icms::handler("icms_module")->getByDirname("sprockets");

	if (!$startObj->isNew())
	{
		$startObj->loadTags();
		$icmsModule->displayAdminMenu(0, _AM_CMS_CMS . " > " . _CO_ICMS_EDITING);
		$sform = $startObj->getForm(_AM_CMS_START_EDIT, "addstart");
		$sform->assign($icmsAdminTpl);
	}
	else
	{
		$icmsModule->displayAdminMenu(0, _AM_CMS_CMS . " > " . _CO_ICMS_CREATINGNEW);
		$sform = $startObj->getForm(_AM_CMS_START_CREATE, "addstart");
		$sform->assign($icmsAdminTpl);

	}
	$icmsAdminTpl->display("db:cms_admin_start.html");
}

include_once "admin_header.php";

$clean_op = "";
$cms_start_handler = icms_getModuleHandler("start", basename(dirname(dirname(__FILE__))), "cms");
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ("mod", "changedField", "addstart", "del", "view", "changeWeight", "changeBeendet", "visible", "");

if (isset($_GET["op"])) $clean_op = htmlentities($_GET["op"]);
if (isset($_POST["op"])) $clean_op = htmlentities($_POST["op"]);

$clean_start_id = isset($_GET["start_id"]) ? (int)$_GET["start_id"] : 0 ;
$clean_tag_id = isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : 0 ;

if (in_array($clean_op, $valid_op, TRUE))
{
	switch ($clean_op)
	{
		case "mod":
		case "changedField":
			icms_cp_header();
			editstart($clean_start_id);
			break;

		case "addstart":
			$controller = new icms_ipf_Controller($cms_start_handler);
			$controller->storeFromDefaultForm(_AM_CMS_START_CREATED, _AM_CMS_START_MODIFIED);
			break;

		case "del":
			$controller = new icms_ipf_Controller($cms_start_handler);
			$controller->handleObjectDeletion();
			break;

		case "view":
			$startObj = $cms_start_handler->get($clean_start_id);
			icms_cp_header();
			$startObj->displaySingleObject();
			break;
		
		case "changeWeight":
			foreach ($_POST['mod_cms_Start_objects'] as $key => $value)
			{
				$changed = TRUE;
				$itemObj = $cms_start_handler->get($value);

				if ($itemObj->getVar('weight', 'e') != $_POST['weight'][$key])
				{
					$itemObj->setVar('weight', intval($_POST['weight'][$key]));
					$changed = TRUE;
				}
				if ($changed)
				{
					$cms_start_handler->insert($itemObj);
				}
			}
			$ret = '/modules/' . basename(dirname(dirname(__FILE__))) . '/admin/start.php';
			redirect_header(ICMS_URL . $ret, 2, _AM_CMS_START_WEIGHTS_UPDATED);
			break;
			
		case "visible":
			$visibility = $cms_start_handler->toggleOnlineStatus($clean_start_id, 'online_status');
			$ret = '/modules/' . basename(dirname(dirname(__FILE__))) . '/admin/start.php';
			if ($visibility == 0)
			{
				redirect_header(ICMS_URL . $ret, 2, _AM_CMS_START_INVISIBLE);
			} 
			else
			{
				redirect_header(ICMS_URL . $ret, 2, _AM_CMS_START_VISIBLE);
			}
			break;
			
		case "changeBeendet":
			$fertigstellungStatus = $cms_start_handler->toggleFertigstellung($clean_start_id, 'beendet');
			$ret = '/modules/' . basename(dirname(dirname(__FILE__))) . '/admin/start.php';
			if ($fertigstellungStatus == 0)
			{
				redirect_header(ICMS_URL . $ret, 2, _AM_CMS_START_ACTIVE);
			}
			else 
			{
				redirect_header(ICMS_URL . $ret, 2, _AM_CMS_START_ARCHIVIERT);
			}
			break;

		default:
			icms_cp_header();
			$icmsModule->displayAdminMenu(0, _AM_CMS_CMS);
			
			// Display a single project, if a project_id is set
			if ($clean_start_id)
			{
				$startObj = $cms_start_handler->get($clean_start_id);
				$startObj->displaySingleObject();
			}
			
			// Display a tag select filter (if the Sprockets module is installed)
			if (icms_get_module_status("sprockets")) {
			
				$tag_select_box = '';
				$taglink_array = $tagged_article_list = array();
				$sprockets_tag_handler = icms_getModuleHandler('tag', 'sprockets', 'sprockets');
				$sprockets_taglink_handler = icms_getModuleHandler('taglink', 'sprockets', 'sprockets');
				
				$tag_select_box = $sprockets_tag_handler->getTagSelectBox('start.php', $clean_tag_id,
					_AM_CMS_START_ALL_CMS, FALSE, icms::$module->getVar('mid'));
				
				if (!empty($tag_select_box)) {
					echo '<h3>' . _AM_CMS_START_FILTER_BY_TAG . '</h3>';
					echo $tag_select_box;
				}
				
				if ($clean_tag_id) {
				
					// get a list of start IDs belonging to this tag
					$criteria = new icms_db_criteria_Compo();
					$criteria->add(new icms_db_criteria_Item('tid', $clean_tag_id));
					$criteria->add(new icms_db_criteria_Item('mid', icms::$module->getVar('mid')));
					$criteria->add(new icms_db_criteria_Item('item', 'start'));
					$taglink_array = $sprockets_taglink_handler->getObjects($criteria);
					foreach ($taglink_array as $taglink) {
					$tagged_start_list[] = $taglink->getVar('iid');
					}
					$tagged_start_list = "('" . implode("','", $tagged_start_list) . "')";
					
					// use the list to filter the persistable table
					$criteria = new icms_db_criteria_Compo();
					$criteria->add(new icms_db_criteria_Item('start_id', $tagged_start_list, 'IN'));
				}
			}
				
				if (empty($criteria)) {
					$criteria = null;
			}
			$objectTable = new icms_ipf_view_Table($cms_start_handler, $criteria);
			$objectTable->addQuickSearch(array('title','description','extended_text'));
			
			//get Preview
			$objectTable->addColumn(new icms_ipf_view_Column("title", FALSE, FALSE, 'getPreviewItemLink'));
			
			$objectTable->addColumn(new icms_ipf_view_Column("date"));
			$objectTable->addColumn(new icms_ipf_view_Column("last_update"));
			$objectTable->addColumn(new icms_ipf_view_Column("counter"));
			//$objectTable->addColumn(new icms_ipf_view_Column('weight', 'center', TRUE, 'getWeightControl'));
			//$objectTable->addActionButton("changeWeight", FALSE, _SUBMIT);
			$objectTable->addColumn(new icms_ipf_view_Column("beendet", "center", TRUE));
			$objectTable->addColumn(new icms_ipf_view_Column("online_status", "center", TRUE));
			$objectTable->setDefaultSort('last_update'); //geändert von date zu last_update
			$objectTable->setDefaultOrder('DESC'); //geändert von ASC zu DESC
			$objectTable->addIntroButton("addstart", "start.php?op=mod", _AM_CMS_START_CREATE);
			$objectTable->addFilter('beendet', 'beendet_filter');
			$objectTable->addFilter('online_status', 'online_status_filter');
			
			//detailpage ACP
			$objectTable->addCustomAction( 'getViewItemLink' );
			
			$icmsAdminTpl->assign("cms_start_table", $objectTable->fetch());
			$icmsAdminTpl->display("db:cms_admin_start.html");
						
			break;
	}
	icms_cp_footer();
}
/**
 * If you want to have a specific action taken because the user input was invalid,
 * place it at this point. Otherwise, a blank page will be displayed
 */