<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_my
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_my\module;

defined('MOODLE_INTERNAL') or die();

use \StdClass;
use \core_text;
use \moodle_url;
use \context_system;
use \html_writer;

class statictext_module extends module {

    public function __construct($calledmodulename) {
        $this->area = 'static';
        $this->index = str_replace('static_', '', $calledmodulename);
        $this->modulename = get_string('static', 'local_my');
    }

    public function render($required = 'plain') {
        global $CFG, $DB, $USER, $OUTPUT;

        if (!file_exists($CFG->dirroot.'/local/staticguitexts/lib.php')) {
            return $OUTPUT->notification(get_string('nostaticguitexts', 'local_my', 'static'));
        }

        $context = context_system::instance();
        $template = new StdClass();

        if (!file_exists($CFG->dirroot.'/local/staticguitexts/lib.php')) {
            return $OUTPUT->notification(get_string('staticguitextsnotinstalled', 'local_my'));
        }

        include_once($CFG->dirroot.'/local/staticguitexts/lib.php');

        if (preg_match('/profile_field_(.*?)_(.*)/', $this->index, $matches)) {

            // Provide content for an only modality of a profile selector.

            $profileexpectedvalue = core_text::strtolower($matches[2]);
            if (is_numeric($matches[1])) {
                $fieldid = $matches[1];
                $field = $DB->get_record('user_info_field', array('id' => $fieldid));
            } else {
                $fieldname = $matches[1];
                $field = $DB->get_record('user_info_field', array('shortname' => $fieldname));
                $fieldid = $field->id;
            }

            $params = array('userid' => $USER->id, 'fieldid' => $fieldid);
            $profilevalue = core_text::strtolower($DB->get_field('user_info_data', 'data', $params));

            if ($field->datatype == 'menu') {
                $modalities = explode("\n", $field->param1);
            }

            $class = '';
            if (($profilevalue != $profileexpectedvalue)) {
                if (!has_capability('moodle/site:config', $context)) {
                    return '';
                } else {
                    $class = 'adminview';
                }
            }

            // Normal user, one sees his own.
            $template->staticindex = $this->index;
            $template->staticclass = $class;

            if ($class == 'adminview') {
                $e = new StdClass();
                $e->field = $field->name;
                $e->value = $profileexpectedvalue;
                $template->adminviewstr = get_string('adminview', 'local_my', $e);
                $template->isadminview = true;
            }
            $template->statictext = local_print_static_text('custommystaticarea_'.$this->index, $CFG->wwwroot.'/my/index.php', '', true);

        } else if (preg_match('/profile_field_(.*)$/', $this->index, $matches)) {

            // Provide values for all modalities of a profile selector.

            if (is_numeric($matches[1])) {
                $fieldid = $matches[1];
                $field = $DB->get_record('user_info_field', array('id' => $fieldid));
            } else {
                $fieldname = $matches[1];
                $field = $DB->get_record('user_info_field', array('shortname' => $fieldname));
                if ($field) {
                    $fieldid = $field->id;
                } else {
                    return $OUTPUT->notification(get_string('fieldnotfound', 'local_my', $fieldname));
                }
            }

            if (!$field) {
                return;
            }

            if ($field->datatype == 'menu') {
                $modalities = explode("\n", $field->param1);
            }

            $params = array('userid' => $USER->id, 'fieldid' => $fieldid);
            $profilevalue = core_text::strtolower($DB->get_field('user_info_data', 'data', $params));
            $profilevalue = trim($profilevalue);
            $profilevalue = str_replace(' ', '-', $profilevalue);
            $profilevalue = str_replace('_', '-', $profilevalue);
            $profilevalue = preg_replace("/[^0-9a-zA-Z-]/", '', $profilevalue);

            // This is a global match catching all values.
            if (has_capability('moodle/site:config', $context)) {

                $template->isadminview = true;

                // I'm administrator, so i can see all modalities and edit them.
                if (!isset($modalities)) {
                    $sql = "
                        SELECT
                            DISTINCT(data) as data
                        FROM
                            {user_info_data}
                        WHERE
                            fieldid = ?
                    ";

                    $modalities = $DB->get_records_sql($sql, array($fieldid));
                }

                if ($modalities) {

                    $modoptions = array();

                    $visibilityclass = '';

                    foreach ($modalities as $modality) {

                        $modaltpl = new StdClass();
                        // Reformat key for token integrity.
                        if (is_object($modality)) {
                            $modality = core_text::strtolower($modality->data);
                        } else {
                            $modality = core_text::strtolower($modality);
                        }
                        $unfilteredmodality = trim($modality);
                        $modality = str_replace(' ', '-', $unfilteredmodality);
                        $modality = str_replace('_', '-', $modality);
                        $modality = preg_replace("/[^0-9a-zA-Z-]/", '', $modality);

                        $modaltpl->modalindex = $this->index.'-'.$modality;
                        $a = new StdClass;
                        $a->profile = $field->shortname;
                        $a->data = $modality;
                        $modaltpl->contentforstr = '<span class="shadow">('.get_string('contentfor', 'local_my', $a).')</span>';
                        $return = new moodle_url('/my/index.php');
                        $modaltpl->statictext = local_print_static_text('custommystaticarea-'.$modaltpl->modalindex, $return, '', true);
                        $modaltpl->visibilityclass = $visibilityclass;
                        $template->modalities[] = $modaltpl;
                        $visibilityclass = 'local-my-hide';

                        $modoptions[$modality] = $unfilteredmodality;
                    }
                    $template->hasmodalities = count($template->modalities);

                    // Choose first as active.
                    $attrs = array('id' => 'local-my-static-select-'.$this->index, 'class' => 'local-my-modality-chooser');
                    $template->modalitiesselect = html_writer::select($modoptions, 'modalities', array_keys($modoptions)[0], null, $attrs);

                }
            }

            // Normal user, one sees his own.
            if (!empty($profilevalue)) {
                $modaltpl = new StdClass();
                $modaltpl->modalindex = $this->index.'-'.$profilevalue;

                $return = new moodle_url('/my/index.php');
                $modaltpl->statictext = local_print_static_text('custommystaticarea-'.$modaltpl->modalindex, $return, '', true);
                $template->modalities[] = $modaltpl;
            }
        } else if (is_numeric($this->index)) {
            // Simple indexed.

            $template = new StdClass();
            $template->index = $this->index;

            $return = new moodle_url('/my/index.php');
            $template->statictext = local_print_static_text('custommystaticarea-'.$template->index, $return, '', true);
        }

        return $OUTPUT->render_from_template('local_my/static_module', $template);
    }

    public function get_courses() {
        // no course related.
        assert(1);
    }
}