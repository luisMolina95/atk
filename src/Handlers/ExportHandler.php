<?php

namespace Sintattica\Atk\Handlers;

use Exception;
use Sintattica\Atk\Core\Node;
use Sintattica\Atk\Db\Db;
use Sintattica\Atk\RecordList\CustomRecordList;
use Sintattica\Atk\Core\Tools;
use Sintattica\Atk\Core\Config;
use Sintattica\Atk\Security\SecurityManager;
use Sintattica\Atk\Session\SessionManager;
use Sintattica\Atk\Attributes\Attribute;
use Sintattica\Atk\Ui\Ui;
use SmartyException;

/**
 * Handler for the 'import' action of a node. The import action is a
 * generic tool for importing CSV files into a table.
 *
 * @author Ivo Jansch <ivo@achievo.org>
 */
class ExportHandler extends ActionHandler
{
    /**
     * The action handler.
     */
    public function action_export()
    {
        global $ATK_VARS;

        // Intercept partial call
        if (!empty($this->m_partial)) {
            $this->partial($this->m_partial);

            return;
        }

        // Intercept delete call
        if (array_key_exists('dodelete', $this->m_postvars) && ctype_digit($this->m_postvars['dodelete'])) {
            if (array_key_exists('confirmed', $this->m_postvars) && $this->m_postvars['confirmed'] == 'true') {
                $this->deleteSelection($this->m_postvars['dodelete']);
            }
        }

        //need to keep the postdata after a Attribute::AF_LARGE selection in the allfield
        if (!isset($this->m_postvars['phase']) && isset($ATK_VARS['atkformdata'])) {
            foreach ($ATK_VARS['atkformdata'] as $key => $value) {
                $this->m_postvars[$key] = $value;
            }
        }

        //need to keep the selected item after an exporterror
        $phase = Tools::atkArrayNvl($this->m_postvars, 'phase', 'init');
        if (!in_array($phase, array('init', 'process'))) {
            $phase = 'init';
        }

        switch ($phase) {
            case 'init':
                $this->doInit();
                break;
            case 'process':
                $this->doProcess();
                break;
        }

        return true;
    }

    /**
     * This function shows a form to configure the .csv.
     */
    public function doInit(): bool
    {
        $content = $this->_getInitHtml();
        $page = $this->getPage();

        $page->register_scriptcode("
        
         function onSelectAllTabClick(tabId){
           const allTabFields = getAllFieldsOfTab(tabId);
           
           for (let i=0; i<allTabFields.length; i++){
                const field = allTabFields[i];
                $(field).prop('checked', true); 
           }
          
         }
                      
         function onSelectNoneTabClick(tabId){
           const allTabFields = getAllFieldsOfTab(tabId);
           
           for (let i=0; i<allTabFields.length; i++){
                const field = allTabFields[i];
                $(field).prop('checked', false); 
           }
          
          console.log('clicked none on ', allTabFields)
         }
         
         function getAllFieldsOfTab(tabId){
            const selector = '.' + tabId +' .atkcheckbox';
            return document.querySelectorAll(selector)
         }
        
        function toggleSelectionName( fieldval )
        {
          if( fieldval == undefined )
          {
            fieldval = $( 'export_selection_options' ).value;
          }
          new Ajax.Updater('export_attributes', '" . Tools::partial_url($this->m_postvars['atknodeuri'], 'export', 'export') . "exportvalue='+fieldval+'&' );

          if( fieldval != 'none' )
          {
            if( fieldval != 'new' )
            {
              new Ajax.Updater('selection_interact', '" . Tools::partial_url($this->m_postvars['atknodeuri'], 'export', 'selection_interact') . "exportvalue='+fieldval+'&' );
              new Ajax.Updater('export_name', '" . Tools::partial_url($this->m_postvars['atknodeuri'], 'export', 'selection_name') . "exportvalue='+fieldval+'&' );
              $( 'selection_interact' ).style.display='';
              $( 'export_name' ).style.display='';
              $( 'export_save_button' ).style.display='';
            }
            else
            {
              $( 'selection_interact' ).style.display='none';
              $( 'export_name' ).style.display='';
              $( 'export_selection_name' ).value='';
              $( 'export_selection_options' ).selectedIndex=0;
              $( 'export_save_button' ).style.display='none';
            }
          }
          else
          {
            $( 'export_name' ).style.display='none';
            $( 'selection_interact' ).style.display='none';
            $( 'export_save_button' ).style.display='none';
            $( 'export_selection_name' ).value='';
          }
        }");

        $page->register_scriptcode("
        function confirm_delete()
        {
         const where_to = confirm('" . Tools::atktext('confirm_delete') . "');
         const dodelete = $( 'export_selection_options' ).value;

         if (where_to == true)
         {
           window.location= \"" . Tools::dispatch_url($this->m_postvars['atknodeuri'], 'export', array('confirmed' => 'true')) . '&dodelete="+dodelete;
         }
        }');

        $params = [];
        $params['title'] = $this->m_node->actionTitle('export');
        $params['content'] = $content;
        $content = $this->getUi()->renderBox($params);
        $output = $this->m_node->renderActionPage('export', $content);
        $page->addContent($output);

        return true;
    }

    /**
     * Handle partial request.
     *
     * @return string
     * @throws Exception
     */
    public function partial_export(): string
    {
        $value = array_key_exists('exportvalue', $this->m_postvars) ? $this->m_postvars['exportvalue'] : null;

        return $this->getAttributeSelect($value);
    }

    /**
     * Partial fetches and displays the name of the selected value.
     *
     * @return string
     */
    public function partial_selection_name(): string
    {
        $selected = array_key_exists('exportvalue', $this->m_postvars) ? $this->m_postvars['exportvalue'] : null;
        $value = '';

        if ($selected) {
            $db = Db::getInstance();
            $rows = $db->getRows('SELECT name FROM atk_exportcriteria WHERE id = ' . (int)$selected);
            if (Tools::count($rows) == 1) {
                $value = htmlentities($rows[0]['name']);
            }
        }

        return '<td>' . Tools::atktext('export_selections_name',
                'atk') . ': </td><td align="left"><input type="text" size="40" name="export_selection_name" id="export_selection_name" value="' . $value . '"></td>
              <input type="hidden" name="exportvalue" value="' . $this->m_postvars['exportvalue'] . '" />';
    }

    /**
     * Partial displays a interaction possibilities with an export selection.
     *
     * @return string
     */
    public function partial_selection_interact(): string
    {
        $selected = array_key_exists('exportvalue', $this->m_postvars) ? $this->m_postvars['exportvalue'] : null;

        $url_delete = Tools::dispatch_url($this->m_node->m_module . '.' . $this->m_node->m_type, 'export', array('dodelete' => $selected));

        if ($selected) {
            return '<a href="' . $url_delete . '" title="' . Tools::atktext('delete_selection') . '" onclick="confirm_delete();">' . Tools::atktext('delete_selection') . '</a>';
        }

        return "";
    }

    /**
     * Gets the HTML for the initial mode of the exporthandler.
     *
     * @return string The HTML for the screen
     * @throws SmartyException
     */
    public function _getInitHtml(): string
    {
        $action = Tools::dispatch_url($this->m_node->m_module . '.' . $this->m_node->m_type, 'export');
        $sm = SessionManager::getInstance();

        $params = [];
        $params['formstart'] = '<form id="entryform" name="entryform" enctype="multipart/form-data" action="' . $action . '" method="post" class="form-horizontal">';
        $params['formstart'] .= $sm->formState();
        $params['formstart'] .= '<input type="hidden" name="phase" value="process"/>';
        if ($sm->atkLevel() > 0) {
            $params['buttons'][] = Tools::atkButton(Tools::atktext('cancel', 'atk'), '', SessionManager::SESSION_BACK);
        }

        $exportText = Tools::atktext('export', 'atk');
        $params['buttons'][] = '<button class="btn btn-primary" type="submit" value="' . $exportText . '">' . $exportText . '</button>';

        $saveExportText = Tools::atktext('save_export_selection', 'atk');
        $params['buttons'][] = '<button id="export_save_button" style="display:none;" value="' . $saveExportText . '" name="save_export" class="btn" type="submit" >' . $saveExportText . '</button>';

        $params['content'] = '<b>' . Tools::atktext('export_config_explanation', 'atk', $this->m_node->m_type) . '</b><br/><br/>';
        $params['content'] .= $this->_getOptions();
        $params['formend'] = '</form>';

        return Ui::getInstance()->renderAction('export', $params, $this->m_node->m_module);
    }

    /**
     * This function checks if there is enough information to export the date
     * else it wil shows a form to set how the file wil be exported.
     */
    public function doProcess()
    {
        // Update selection
        if (array_key_exists('exportvalue', $this->m_postvars) && array_key_exists('save_export',
                $this->m_postvars) && '' != $this->m_postvars['export_selection_name']
        ) {
            $this->updateSelection();
            $this->getNode()->redirect(Tools::dispatch_url($this->getNode(), 'export'));
        }

        // Save selection
        if (array_key_exists('export_selection_options', $this->m_postvars) && array_key_exists('export_selection_name',
                $this->m_postvars) && 'none' == $this->m_postvars['export_selection_options'] && '' != $this->m_postvars['export_selection_name']
        ) {
            $this->saveSelection();
        }

        // Export CVS
        if (!array_key_exists('save_export', $this->m_postvars)) {
            return $this->doExport();
        }
    }

    private function _getOptionsFormRow($rowAttributes, $label, $field): string
    {
        $content = '';

        $content .= '<div class="row form-group"';
        if ($rowAttributes) {
            foreach ($rowAttributes as $k => $v) {
                $content .= ' ' . $k . '="' . $v . '"';
            }
        }
        $content .= '>';

        $content .= '  <label class="col-sm-2 control-label">' . $label . '</label>';
        $content .= '  <div class="col-sm-10">' . $field . '</div>';
        $content .= '</div>';
        return $content;
    }

    /**
     * Get the options for the export.
     *
     * @return string html
     * @throws Exception
     */
    public function _getOptions(): string
    {

        $content = '';

        // enable extended export options
        if (true === Config::getGlobal('enable_export_save_selection')) {
            $content .= $this->_getOptionsFormRow(
                null,
                Tools::atktext('export_selections', 'atk'),
                $this->getExportSelectionDropdown() . '&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" onclick="toggleSelectionName(\'new\');return false;">' . Tools::atktext('new', 'atk'));

            $content .= $this->_getOptionsFormRow(null, '', '<div id="selection_interact"></div>');

            $content .= $this->_getOptionsFormRow(
                ['id' => 'export_name', 'style' => "display:none;"],
                Tools::atktext('export_selections_name', 'atk'),
                '<input type="text" size="40" id="export_selection_name" name="export_selection_name" value="" class="form-control form-control-sm">'
            );
        }

        $content .= $this->_getOptionsFormRow(
            null,
            Tools::atktext('delimiter', 'atk'),
            '<input type="text" class="form-control form-control-sm" size="2" name="delimiter" value=' . Config::getGlobal('export_delimiter', ';') . '>'
        );

        $content .= $this->_getOptionsFormRow(
            null,
            Tools::atktext('enclosure', 'atk'),
            '<input type="text" size="2" class="form-control form-control-sm" name="enclosure" value=' . Config::getGlobal('export_enclosure', '&quot;') . '>'
        );

        $content .= $this->_getOptionsFormRow(
            null,
            Tools::atktext('export_selectcolumns', 'atk'),
            '<div id="export_attributes">' . $this->getAttributeSelect() . '</div>'
        );

        $content .= $this->_getOptionsFormRow(
            null,
            Tools::atktext('export_generatetitlerow'),
            '<input type="checkbox" name="generatetitlerow" class="atkcheckbox" value=1 ' . (Config::getGlobal('export_titlerow_checked', true) ? 'checked' : '') . '>'
        );

        return $content;
    }

    /**
     * Build the dropdown field to add the exportselections.
     *
     * @return string
     */
    private function getExportSelectionDropdown(): string
    {
        $html = '
        <select name="export_selection_options" id="export_selection_options" onchange="toggleSelectionName();return false;" class="form-control select-standard">
          <option value="none">' . Tools::atktext('none', 'atk');

        $options = $this->getExportSelections();
        if (Tools::count($options)) {
            foreach ($options as $option) {
                $html .= '
           <option value="' . $option['id'] . '">' . htmlentities($option['name']) . '</option>';
            }
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Store selectiondetails in the database.
     */
    private function saveSelection()
    {
        $db = Db::getInstance();
        $id = $db->nextid('exportcriteria');

        $user_id = 0;
        if ('none' !== strtolower(Config::getGlobal('authentication'))) {
            $user = SecurityManager::atkGetUser();
            $user_id = array_key_exists(Config::getGlobal('auth_userpk'), $user) ? $user[Config::getGlobal('auth_userpk')] : 0;
        }

        // first check if the combination of node, name and user_id doesn't already exist
        $rows = $db->getRows("SELECT id FROM atk_exportcriteria
                            WHERE nodetype = '" . $this->m_postvars['atknodeuri'] . "'
                            AND name = '" . $this->m_postvars['export_selection_name'] . "'
                            AND user_id = " . $user_id);
        if (Tools::count($rows)) {
            return;
        }

        $query = 'INSERT INTO atk_exportcriteria ( id, nodetype, name, criteria, user_id )
                VALUES ( ' . $id . ', "' . $this->m_postvars['atknodeuri'] . '", "' . $db->escapeSQL($this->m_postvars['export_selection_name']) . '",
                         "' . addslashes(serialize($this->m_postvars)) . '", ' . $user_id . ' )';

        $db->query($query);
    }

    /**
     * Update selectiondetails in the database.
     */
    private function updateSelection()
    {
        $db = Db::getInstance();

        $user_id = 0;
        if ('none' !== strtolower(Config::getGlobal('authentication'))) {
            $user = SecurityManager::atkGetUser();
            $user_id = array_key_exists(Config::getGlobal('auth_userpk'), $user) ? $user[Config::getGlobal('auth_userpk')] : 0;
        }

        // first check if the combination of node, name and user_id doesn't already exist
        $rows = $db->getRows("SELECT id FROM atk_exportcriteria
                            WHERE nodetype = '" . $this->m_postvars['atknodeuri'] . "'
                            AND name = '" . $this->m_postvars['export_selection_name'] . "'
                            AND user_id = " . $user_id . '
                            AND id <> ' . (int)$this->m_postvars['exportvalue']);
        if (Tools::count($rows)) {
            return;
        }

        $query = 'UPDATE
                  atk_exportcriteria
                SET
                  name = "' . $db->escapeSQL($this->m_postvars['export_selection_name']) . '",
                  criteria = "' . addslashes(serialize($this->m_postvars)) . '"
                WHERE
                  id = ' . (int)$this->m_postvars['exportvalue'];

        $db->query($query);
    }

    /**
     * Delete record.
     *
     * @param int $id
     */
    private function deleteSelection(int $id)
    {
        $db = Db::getInstance();
        $db->query('DELETE FROM atk_exportcriteria WHERE id = ' . (int)$id);
    }

    /**
     * Determine the export selections that should be displayed.
     *
     * @return array
     */
    protected function getExportSelections(): array
    {
        $where = ' nodetype = "' . $this->m_postvars['atknodeuri'] . '"';
        if ('none' !== strtolower(Config::getGlobal('authentication'))) {
            $user = SecurityManager::atkGetUser();
            if (!SecurityManager::isUserAdmin($user)) {
                $where .= ' AND user_id IN( 0, ' . (int)$user[Config::getGlobal('auth_userpk')] . ' )';
            }
        }

        $db = Db::getInstance();

        return $db->getRows($query = 'SELECT id, name FROM atk_exportcriteria WHERE ' . $where . ' ORDER BY name');
    }

    /**
     * Get all attributes to select for the export.
     * @param string|null $value
     * @return string HTML code with checkboxes for each attribute to select
     * @throws Exception
     */
    public function getAttributeSelect(string $value = ''): string
    {
        $atts = $this->getUsableAttributes($value);
        $content = '<div class="container-fluid ExportHandler d-flex  flex-wrap m-0 p-0 justify-content-center">';

        $content .= '<div class="row no-gutters"></div>';


        foreach ($atts as $tab => $group) {


            $tabId = 'tab-' . str_replace(' ', '_', $tab);
            $content .= '<div id=' . $tabId . ' class="card card-outline card-secondary m-1 attributes-group flex-grow-1 ' . $tabId . '" style="min-width: 300px">';


            $tabTitle = "<div class='d-flex flex-grow-1 my-auto px-1 text-bold'>" . ($tab != 'default' ? Tools::atktext(["tab_$tab", $tab], $this->m_node->m_module, $this->m_node->m_type) : Tools::atktext("menu_main", $this->m_node->m_module, $this->m_node->m_type)) . "</div>";


            $navButtons = '<div class="d-flex" >
                               <div class="my-auto px-1">' . Tools::atktext("select", $this->m_node->m_module, $this->m_node->m_type) . '</div>        
                               <div class="btn-group" role="group">                   
                                <button type="button" class="btn btn-xs btn-default px-2" onclick="onSelectAllTabClick(\'' . $tabId . '\')">' . Tools::atktext("pf_check_all", $this->m_node->m_module, $this->m_node->m_type) . '</button>
                                <button type="button" class="btn btn-xs btn-default px-2" onclick="onSelectNoneTabClick(\'' . $tabId . '\')">' . Tools::atktext("pf_check_none", $this->m_node->m_module, $this->m_node->m_type) . '</button>
                            </div>
                            </div>';


            $content .= '<div class="card-header text-sm d-flex justify-content-between">' . $tabTitle . $navButtons . '</div>';


            $content .= '<div class="card-body d-flex justify-content-start flex-wrap">';

            foreach ($group as $item) {
                $checked = $item['checked'] ? 'CHECKED' : '';
                $content .= '<div class="attributes-checkbox-container mx-1">';
                $content .= '<label class="text-nowrap"><input type="checkbox" name="export_' . $item['name'] . '" class="atkcheckbox" value="export_' . $item['name'] . '" ' . $checked . '> ' . $item['text'] . '</label>';
                $content .= '</div>';
            }

            $content .= "</div>";


            $content .= '</div>';

        }

        $content .= '</div></div>';

        return $content;
    }

    /**
     * Gives all the attributes that can be used for the import.
     * @param string $value
     * @return array the attributes
     * @throws Exception
     */
    public function getUsableAttributes(string $value = ''): array
    {
        $selected = $value != 'new';

        $criteria = [];
        if (!in_array($value, array('new', 'none', ''))) {
            $db = Db::getInstance();
            $rows = $db->getRows('SELECT * FROM atk_exportcriteria WHERE id = ' . (int)$value);
            $criteria = unserialize($rows[0]['criteria']);
        }

        $attributes = [];
        $attributesList = $this->invoke('getExportAttributes');
        foreach ($attributesList as $key => $attr) {
            $flags = $attr->m_flags;
            $class = strtolower(get_class($attr));
            if ($attr->hasFlag(Attribute::AF_AUTOKEY) || $attr->hasFlag(Attribute::AF_HIDE_VIEW) || !(strpos($class, 'dummy') === false) || !(strpos($class,
                        'image') === false) || !(strpos($class, 'tabbedpane') === false)
            ) {
                continue;
            }
            if (method_exists($this->m_node, 'getExportAttributeGroup')) {
                $group = $this->m_node->getExportAttributeGroup($attr->m_name);
            } else {
                $group = $attr->m_tabs[0];
            }
            if (in_array($group, $attributes)) {
                $attributes[$group] = [];
            }
            // selected options based on a new selection, or no selection
            if (empty($criteria)) {
                $attributes[$group][] = array(
                    'name' => $key,
                    'text' => $attr->label(),
                    'checked' => $selected == true ? !$attr->hasFlag(Attribute::AF_HIDE_LIST) : false,
                );
            } // selected options based on a selection from DB
            else {
                $attributes[$group][] = array(
                    'name' => $key,
                    'text' => $attr->label(),
                    'checked' => in_array('export_' . $key, $criteria) ? true : false,
                );
            }
        }

        return $attributes;
    }

    /**
     * Return all attributes that can be exported.
     *
     * @return array Array with attribs that needs to be exported
     */
    public function getExportAttributes(): array
    {
        $attribs = $this->m_node->getAttributes();
        return is_null($attribs) ? [] : $attribs;
    }

    /**
     * the real import function
     * import the uploaded csv file for real.
     */
    public function doExport(): bool
    {
        $enclosure = $this->m_postvars['enclosure'];
        $delimiter = $this->m_postvars['delimiter'];
        $source = $this->m_postvars;
        $listIncludes = [];
        foreach ($source as $name => $value) {
            $pos = strpos($name, 'export_');
            if (is_integer($pos) and $pos == 0) {
                $listIncludes[] = substr($name, strlen('export_'));
            }
        }
        $sm = SessionManager::getInstance();
        $sessionData = &SessionManager::getSession();
        $sessionBack = $sessionData['default']['stack'][$sm->atkStackID()][$sm->atkLevel() - 1];
        $atkOrderBy = $sessionBack['atkorderby'] ?? null;

        $node = $this->m_node;
        $nodeBk = $node;
        $nodeBkAttributes = &$nodeBk->m_attribList;

        foreach ($nodeBkAttributes as $name => $object) {
            $att = $nodeBk->getAttribute($name);
            if (in_array($name, $listIncludes) && $att->hasFlag(Attribute::AF_HIDE_LIST)) {
                $att->removeFlag(Attribute::AF_HIDE_LIST);
            } elseif (!in_array($name, $listIncludes)) {
                $att->addFlag(Attribute::AF_HIDE_LIST);
            }
        }

        $customRecordList = new CustomRecordList();
        $nodeBk->m_postvars = $sessionBack;

        if (isset($sessionBack['atkdg']['admin']['atksearch'])) {
            $nodeBk->m_postvars['atksearch'] = $sessionBack['atkdg']['admin']['atksearch'];
        }
        if (isset($sessionBack['atkdg']['admin']['atksearchmode'])) {
            $nodeBk->m_postvars['atksearchmode'] = $sessionBack['atkdg']['admin']['atksearchmode'];
        }

        $atkFilter = Tools::atkArrayNvl($source, 'atkfilter', '');

        $atkSelector = $sessionBack[Node::PARAM_ATKSELECTOR] ?? '';
        $condition = $atkSelector . ($atkSelector != '' && $atkFilter != '' ? ' AND ' : '') . $atkFilter;
        $recordSet = $nodeBk->select($condition)->orderBy($atkOrderBy)->includes($listIncludes)->mode('export')->getAllRows();

        if ($nodeBk->hasNestedAttributes()) {
            $nestedAttributeField = $nodeBk->getNestedAttributeField();
            $nestedAttributesList = $nodeBk->getNestedAttributesList();
            foreach ($recordSet as &$record) {
                foreach ($nestedAttributesList as $nestedAttribute) {
                    if (in_array($nestedAttribute, $listIncludes) && $record[$nestedAttributeField] != null) {
                        $record[$nestedAttribute] = $record[$nestedAttributeField][$nestedAttribute];
                    } else {
                        $record[$nestedAttribute] = null;
                    }
                }
            }
        }

        if (method_exists($this->m_node, 'assignExportData')) {
            $this->m_node->assignExportData($listIncludes, $recordSet);
        }

        $recordSetNew = [];

        foreach ($recordSet as $row) {
            foreach ($row as $name => $value) {
                if (in_array($name, $listIncludes)) {
                    $value = str_replace("\r\n", '\\n', $value);
                    $value = str_replace("\n", '\\n', $value);
                    $value = str_replace("\t", '\\t', $value);
                    $row[$name] = $value;
                }
            }
            $recordSetNew[] = $row;
        }

        $customRecordList->render(
            $nodeBk,
            $recordSetNew,
            '',
            $enclosure,
            $enclosure,
            "\r\n",
            1,
            '',
            '',
            ['filename' => $this->getNode()->exportFileName()],
            'csv',
            $source['generatetitlerow'],
            true,
            $delimiter
        );

        return true;
    }
}
