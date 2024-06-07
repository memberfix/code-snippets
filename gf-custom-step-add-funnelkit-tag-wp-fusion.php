<?php
/**
 * Gravity Flow Custom Step: Add Funnelkit Tag with WP Fusion.
 *
 * Company: MemberFix
 * URL: https://memberfix.rocks
 * Author: Denys Melnychuk
 * Date: 23.05.2024
 * Version: 1.0
 */

// Wait until Gravity Flow is ready before declaring the step class.
add_action('gravityflow_loaded', function() {

    // Define a custom step class
    class Gravity_Flow_Step_Add_Funnelkit_Tag extends Gravity_Flow_Step {

        // Unique identifier for this step type
        public $_step_type = 'add_fk_tag';

        // Label for the step
        public function get_label() {
            return 'Add FunnelKit Tag';
        }

        // Icon for the step
        public function get_icon_url() {
            return '<i class="fa fa-tag"></i>';
        }

        // Define settings fields
        public function get_settings() {
            return array(
                'title'  => 'Details',
                'fields' => array(
                    array(
                        'name'       => 'user_id_field',         // Unique id for the field
                        'class'      => 'merge-tag-support',   // Adds merge tag option to the field
                        'required'   => true,                  // Is required
                        'label'      => 'User ID field',         // Label of the field
                        'type'       => 'text',
                    ),
                    array(
                        'name'       => 'fk_tag',
                        'class'      => 'merge-tag-support',
                        'required'   => true,
                        'label'      => 'FunnelKit Tag (Tags corresponding number)',///this field will require id of the FunnelKit tag
                        'type'       => 'text',
                    ),
                    array(
                      'name'       => 'entry_timeline_note',
                      'class'      => 'merge-tag-support',
                      'required'   => true,
                      'label'      => 'Entries timeline note',
                      'type'       => 'text',
                  ),
                ),
            );
        }

        // Process the step
        public function process() {
            $entry = $this->get_entry();

            // Get settings values
            $user_id_field = $this->get_setting('user_id_field');
            $entry_timeline_note = $this->get_setting('entry_timeline_note');
            $fk_tag = $this->get_setting('fk_tag');

            // Replace merge tags in the settings values

            $user_id_field = GFCommon::replace_variables($user_id_field, $this->get_form(), $entry, false, false, false, 'text');
            $fk_tag = GFCommon::replace_variables($fk_tag, $this->get_form(), $entry, false, false, false, 'text');
            $entry_timeline_note = GFCommon::replace_variables($entry_timeline_note, $this->get_form(), $entry, false, false, false, 'text');


            // Find user by email field
           /// $user = get_user_by('email', $user_id_field);



//error_log('Expiry_formatted_date: ' . $expiry_formatted_date);
            wp_fusion()->user->apply_tags(array($fk_tag), $user_id_field);
			


            // Add a note to the entry's timeline
            gravity_flow()->add_timeline_note($entry['id'], $entry_timeline_note);

            // Return true to indicate the step is complete
            return true;
        }
    }

    // Register the custom step class
    Gravity_Flow_Steps::register(new Gravity_Flow_Step_Add_Funnelkit_Tag());
});
