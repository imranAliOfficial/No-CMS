<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Description of Grocerycrud_entity_model
 *
 * @author No-CMS Module Generator
 */
class Cck_model  extends CMS_Model{

    /**
     * Template:
     * {{ code }}
     * {{ name }}
     * {{ value }}
     * {{ foreach_option }}
     * {{ end_foreach }}
     * {{ option.value }}
     * {{ option.caption }}
     * {{ selected.caption }}
     * {{ selected.value }}
     * {{ field_name.view }}
     */

    public function __construct(){
        parent::__construct();
    }

    public function adjust_physical_table($id_entity){
        $fields = array('id' => array('type' => 'INT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true));
        foreach($this->cms_get_record_list($this->t('field'), 'id_entity', $id_entity) as $field){
            $fields['field_'.$field->id] = array('type'=>'TEXT', 'null' => TRUE);
        }
        // call adjust_tables
        $tables = array(
            $id_entity => array(
                'fields' => $fields,
                'key' => 'id',
            ),
        );
        $this->cms_adjust_tables($tables, $this->t('data').'_');
    }

    private function adjust_navigation_group($navigation_name, $lookup_table, $id_entity){
        $navigation = $this->cms_get_record(cms_table_name('main_navigation'), 'navigation_name', $navigation_name);
        // current group
        $current_group_list = $this->cms_get_record_list(cms_table_name('main_group_navigation'), 'navigation_id', $navigation->navigation_id);
        $current_group_id_list = array();
        foreach($current_group_list as $record){
            $current_group_id_list[] = $record->group_id;
        }
        // target group
        $target_group_list = $this->cms_get_record_list($lookup_table, 'id_entity', $id_entity);
        $target_group_id_list = array();
        foreach($target_group_list as $record){
            $target_group_id_list[] = $record->id_group;
        }
        // insert
        foreach($target_group_id_list as $group_id){
            if(!in_array($group_id, $current_group_list)){
                $this->db->insert(cms_table_name('main_group_navigation'),
                    array('navigation_id' => $navigation->id, 'group_id' => $group_id));
            }
        }
        // delete
        foreach($current_group_id_list as $group_id){
            if(!in_array($group_id, $target_group_list)){
                $this->db->delete(cms_table_name('main_group_navigation'),
                    array('navigation_id' => $navigation->id, 'group_id' => $group_id));
            }
        }
    }

    private function adjust_privilege_group($privilege_name, $lookup_table, $id_entity){
        $privilege = $this->cms_get_record(cms_table_name('main_privilege'), 'privilege_name', $privilege_name);
        // current group
        $current_group_list = $this->cms_get_record_list(cms_table_name('main_group_privilege'), 'privilege_id', $privilege->privilege_id);
        $current_group_id_list = array();
        foreach($current_group_list as $record){
            $current_group_id_list[] = $record->group_id;
        }
        // target group
        $target_group_list = $this->cms_get_record_list($lookup_table, 'id_entity', $id_entity);
        $target_group_id_list = array();
        foreach($target_group_list as $record){
            $target_group_id_list[] = $record->id_group;
        }
        // insert
        foreach($target_group_id_list as $group_id){
            if(!in_array($group_id, $current_group_list)){
                $this->db->insert(cms_table_name('main_group_privilege'),
                    array('privilege_id' => $privilege->id, 'group_id' => $group_id));
            }
        }
        // delete
        foreach($current_group_id_list as $group_id){
            if(!in_array($group_id, $target_group_list)){
                $this->db->delete(cms_table_name('main_group_privilege'),
                    array('privilege_id' => $privilege->id, 'group_id' => $group_id));
            }
        }
    }

    public function adjust_navigation_and_privilege($id_entity){
        // get record
        $entity = $this->cms_get_record($this->t('entity'), 'id', $id_entity);
        if($entity == NULL){
            return FALSE;
        }
        // navigation: browse
        $browse_navigation_name = $this->n('entity_'.$id_entity.'_browse');
        $this->cms_add_navigation($browse_navigation_name, $entity->name, $this->cms_module_path().'/browse/'.$id_entity, $entity->id_authorization_browse, $entity->id_authorization_browse);
        // adjust group navigation
        $this->adjust_navigation_group($browse_navigation_name, $this->t('group_entity_browse'), $id_entity);
        // navigation: manage
        $manage_navigation_name = $this->n('entity_'.$id_entity.'_manage');
        $this->cms_add_navigation($manage_navigation_name, $entity->name, $this->cms_module_path().'/manage/index/'.$id_entity, $entity->id_authorization_view, $browse_navigation_name);
        // adjust group navigation
        $this->adjust_navigation_group($manage_navigation_name, $this->t('group_entity_view'), $id_entity);
        // navigation: add
        $add_navigation_name = $this->n('entity_'.$id_entity.'_add');
        $this->cms_add_navigation($add_navigation_name, $entity->name, $this->cms_module_path().'/manage/index/'.$id_entity.'/add', $entity->id_authorization_add, $browse_navigation_name);
        // adjust group navigation
        $this->adjust_navigation_group($add_navigation_name, $this->t('group_entity_add'), $id_entity);
        // adjust privileges for operations
        $verb_list = array('read', 'add', 'edit', 'delete', 'list', 'back_to_list', 'export', 'print');
        foreach($verb_list as $verb){
            // determine authorization for each verb privilege
            $authorization_id = 4;
            if(in_array($verb, array('list', 'back_to_list'))){
                $authorization_id = $entity->id_authorization_view;
            }else if($verb == 'add'){
                $authorization_id = $entity->id_authorization_add;
            }else if($verb == 'edit'){
                $authorization_id = $entity->id_authorization_edit;
            }else if($verb == 'delete'){
                $authorization_id = $entity->id_authorization_delete;
            }
            // adjust privilege
            $privilege_name = $this->n($verb.'_data_'.$id_entity);
            $this->cms_add_privilege($privilege_name, $verb.' '.$entity->name, $authorization_id);
            // adjust group privilege
            $this->adjust_privilege_group($privilege_name, $this->t('group_entity_view'), $id_entity);
        }
    }

    public function delete_navigation_and_privilege($id_entity){
        // get record
        $entity = $this->cms_get_record($this->t('entity'), 'id', $id_entity);
        if($entity == NULL){
            return FALSE;
        }
        // navigation: browse
        $browse_navigation_name = $this->n('entity_'.$id_entity.'_browse');
        $this->cms_remove_navigation($browse_navigation_name);
        // navigation: manage
        $manage_navigation_name = $this->n('entity_'.$id_entity.'_manage');
        $this->cms_remove_navigation($manage_navigation_name);
        // navigation: add
        $add_navigation_name = $this->n('entity_'.$id_entity.'_add');
        $this->cms_remove_navigation($add_navigation_name);
        // adjust privileges for operations
        $verb_list = array('read', 'add', 'edit', 'delete', 'list', 'back_to_list', 'export', 'print');
        foreach($verb_list as $verb){
            // adjust privilege
            $privilage_name = $this->n($verb.'_data_'.$id_entity);
            $this->cms_remove_privilege($privilage_name);
        }
    }

    public function remove_white_spaces($string){
        //return  preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $string));
        // turn all kind of EOL into PHP_EOL, turn multiple tabs and multiple spaces into single space
        $search = array( PHP_EOL, '\n', '\r\n');
        return preg_replace('!\s+!', ' ', trim(str_replace($search, PHP_EOL, $string)) );
    }

    public function get_default_per_record_html_pattern($id_entity){
        $entity = $this->cms_get_record($this->t('entity'), 'id', $id_entity);
        if($entity == NULL){
            return '';
        }else{
            $field_list = $this->cms_get_record_list($this->t('field'), 'id_entity', $entity->id);
            $html = '<div class="row">'.PHP_EOL;
            foreach($field_list as $field){
                $html .= '    <div class="col-md-4"><strong>'.$field->name.'</strong></div>'.PHP_EOL;
                $html .= '    <div class="col-md-8">{{ '.$field->name.'.view }}</div>'.PHP_EOL;
            }
            $html .= '</div>';
            return $html;
        }

    }

    public function get_per_record_html_pattern($id_entity){
        $entity = $this->cms_get_record($this->t('entity'), 'id', $id_entity);
        if($entity != NULL && $entity->per_record_html != NULL){
            return $entity->per_record_html;
        }else{
            return $this->get_default_per_record_html_pattern($id_entity);
        }

    }


    public function get_actual_per_record_view($id_entity, $record){
        $html = $this->get_per_record_html_pattern($id_entity);
        foreach($this->cms_get_record_list($this->t('field'), 'id_entity', $id_entity) as $field){
            $value = $record->{'field_'.$field->id};
            $html = str_ireplace('{{ '.$field->name.'.view }}', $this->get_actual_field_view($field->id, $value), $html);
        }
        return $html;
    }

    public function get_input_pattern_by_template($id_template){
        $template = $this->cms_get_record($this->t('template'), 'id', $id_template);
        return $template == NULL || $template->input == NULL? '<input id="field-{name}" name="{name}" value="{value}" />' : $template->input;
    }

    public function get_view_pattern_by_template($id_template){
        $template = $this->cms_get_record($this->t('template'), 'id', $id_template);
        return $template == NULL || $template->view == NULL? '{value}' : $template->view;
    }

    public function create_string($field, $value, $pattern){
        // get option list
        $option_list = $this->cms_get_record_list($this->t('option'), 'id_field', $field->id);
        $selected_caption_array = array();
        $selected_value_array = array();
        $value_array = explode(PHP_EOL, $value); // change value to array
        foreach($option_list as $option){
            if(in_array($option->name, $value_array)){
                $selected_caption_array[] = $option->shown;
                $selected_value_array[] = $option->name;
            }
        }
        // change value to comma delimited
        if(count($value_array) <2){
            $value_to_be_shown = implode(', ', $value_array);
            $selected_caption = implode(', ', $selected_caption_array);
            $selected_value = implode(', ', $selected_value_array);
        }else{
            // value to be shown
            $value_to_be_shown = '<ul>';
            foreach($value_array as $item){
                $value_to_be_shown .= '<li>'.$item.'</li>';
            }
            $value_to_be_shown .= '</ul>';
            // selected caption
            $selected_caption = '<ul>';
            foreach($selected_caption_array as $item){
                $selected_caption .= '<li>'.$item.'</li>';
            }
            $selected_caption .= '</ul>';
            // selected value
            $selected_value = '<ul>';
            foreach($selected_value_array as $item){
                $selected_value .= '<li>'.$item.'</li>';
            }
            $selected_value .= '</ul>';
        }
        // firstly, string is equal to pattern, then we change it gradually
        $string = $pattern;
        // parse simple patterns
        $search = array(
            '{{ name }}',
            '{{ code }}',
            '{{ value }}',
            '{{ selected.caption }}',
            '{{ selected.value }}',
        );
        $replace = array(
            $field->name,
            'field_'.$field->id,
            $value_to_be_shown,
            $selected_caption,
            $selected_value,
        );
        $string = str_ireplace($search, $replace, $string);
        // parse {{ foreach_option }} ... {{ end_foreach }} pattern
        $foreach_option_pattern = '/\{\{ foreach_option \}\}(.*?)\{\{ end_foreach \}\}/si';
        $matches = array();
        if(preg_match_all($foreach_option_pattern, $string, $matches)){
            for($i=0; $i<count($matches[0]); $i++){
                $current_pattern = $matches[0][$i]; // This is the pattern that should be replaced
                $current_subpattern = $matches[1][$i]; // This is the sub pattern that should be rendered differently for each option
                $option_string = '';
                foreach($option_list as $option){
                    // render {{ if_selected:some_string }}
                    if(in_array($option->name, $value_array)){
                        $current_option_string = preg_replace('/\{\{ if_selected:(.*?) \}\}/i', '$1', $current_subpattern);
                    }else{
                        $current_option_string = preg_replace('/\{\{ if_selected:(.*?) \}\}/i', '', $current_subpattern);
                    }
                    // render {{ option.value }} and {{ option.caption }}
                    $search = array('{{ option.value }}', '{{ option.caption }}');
                    $replace = array($option->name, $option->shown);
                    $current_option_string = str_replace($search, $replace, $current_option_string);
                    // add to option string
                    $option_string .= $current_option_string;
                }
                $string = str_replace($current_pattern, $option_string, $string);
            }
        }
        return $string;
    }

    public function get_actual_field_view($id_field, $value){
        $field = $this->cms_get_record($this->t('field'), 'id', $id_field);
        // no field found? then nothing
        if($field == NULL){
            return '';
        }
        // determine pattern
        if(trim($field->view) == ''){
            $pattern = $this->get_view_pattern_by_template($field->id_template);
        }else{
            $pattern = $field->view;
        }
        // return
        return $this->create_string($field, $value, $pattern);
    }

    public function get_actual_field_input($id_field, $value=NULL){
        $field = $this->cms_get_record($this->t('field'), 'id', $id_field);
        // no field found? then nothing
        if($field == NULL){
            return '';
        }
        // determine pattern
        if(trim($field->input) == ''){
            $pattern = $this->get_input_pattern_by_template($field->id_template);
        }else{
            $pattern = $field->input;
        }
        // get option list
        $option_list = $this->cms_get_record_list($this->t('option'), 'id_field', $id_field);
        // return
        return $this->create_string($field, $value, $pattern);
    }
}
