<?php 

/**
 * Gravity Flow Custom Step: Send Webhook to Funnelkit with details.
 *
 * Description: This script establishes a custom step within Gravity Flow, enabling the sending of a webhook to Funnelkit. 
 * Originally designed to facilitate the addition of notes to Funnelkit contact records.
 *
 * Company: MemberFix 
 * URL: https://memberfix.rocks
 * Author: Denys Melnychuk
 * Date: 25.04.2024
 * Version: 1.0
 */


// Wait until Gravity Flow is ready before declaring the step class.
add_action( 'gravityflow_loaded', function() {

    // Define a custom step class
    class Gravity_Flow_Step_Add_Note_FK extends Gravity_Flow_Step {
        
        // Unique identifier for this step type
        public $_step_type = 'add_note_step';
        
        //Label for the step
        public function get_label() {
            return 'Add FK CRM Note';
        }

        public function get_icon_url() {
            return '<i class="fa fa-commenting-o"></i>';
        }

        // Define settings fields

        public function get_settings() {
            return array(
                'title'  => 'FunnelKit Contact Profile Note Settings',
                'fields' => array(
                    array(
                        'name'       => 'email_field', 				///unique id for the field
                        'class'      => 'merge-tag-support', 		///adds merge tag option to the field
                        'required'   => true, 						//is required
                        'label'      => 'Email field',				//label of the field
                        'type'       => 'text',
                    ),
                    array(
                        'name'       => 'fk_webhook_url',
                        'required'   => true,
                        'label'      => 'Webhook URL',
                        'type'       => 'text',
                    ),
                    array(
                        'name'       => 'note_title',
						'class'      => 'merge-tag-support',
                        'required'   => true,
                        'label'      => 'Note Title',
                        'type'       => 'text',
                    ),
                    array(
                        'name'       => 'note_body',
						'class'      => 'merge-tag-support',
                        'required'   => true,
                        'label'      => 'Note Body',
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

        //Process the step
  
public function process() {
    $entry = $this->get_entry();
 

    // Get settings values
    $fk_webhook_url = $this->get_setting('fk_webhook_url');
    $note_title = $this->get_setting('note_title');
    $note_body = $this->get_setting('note_body');
    $email_field = $this->get_setting('email_field');
    $entry_timeline_note = $this->get_setting('entry_timeline_note');

    // Replace merge tags in the settings values. Needed to replace merge tags with values picked from the form
    $email_field = GFCommon::replace_variables($email_field, $this->get_form(), $entry, false, false, false, 'text');
    $note_title = GFCommon::replace_variables($note_title, $this->get_form(), $entry, false, false, false, 'text');
    $note_body = GFCommon::replace_variables($note_body, $this->get_form(), $entry, false, false, false, 'text');
    $entry_timeline_note = GFCommon::replace_variables($entry_timeline_note, $this->get_form(), $entry, false, false, false, 'text');

    // Log replaced values for debugging
    error_log( print_r( $email_field , true ) );
    error_log( print_r( $fk_webhook_url, true ) );
    error_log( print_r( $note_title, true ) );
    error_log( print_r( $note_body, true ) );
    error_log( print_r( $entry_timeline_note, true ) );

    // Prepare webhook data
    $webhook_data = array(
        'Title' => $note_title,
        'Note' => $note_body,
        'email' => $email_field,
    );

    // Send webhook request
    $response = wp_remote_post($fk_webhook_url, array(
        'body' => $webhook_data,
    ));

    // Check if request was successful
    if (is_wp_error($response)) {
        // Log error if request failed
        error_log('Webhook request failed: ' . $response->get_error_message());
    } else {
        // Log success if request was successful
        error_log('Webhook request successful: ' . wp_remote_retrieve_body($response));
    }

    // Add a note to the entry's timeline
    gravity_flow()->add_timeline_note($entry['id'], $entry_timeline_note);

    // Return true to indicate the step is complete
    return true;
}


}
    // Register the custom step class
    Gravity_Flow_Steps::register( new Gravity_Flow_Step_Add_Note_FK() );
});
