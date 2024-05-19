<?php

/**
 * Gravity Flow Custom Step: Update Memberpress Transaction.
 *
 * Company: MemberFix
 * URL: https://memberfix.rocks
 * Author: Denys Melnychuk
 * Date: 15.05.2024
 * Version: 1.0
 */

// Wait until Gravity Flow is ready before declaring the step class.
add_action('gravityflow_loaded', function() {

    // Define a custom step class
    class Gravity_Flow_Step_Update_Mepr_Txn extends Gravity_Flow_Step {

        // Unique identifier for this step type
        public $_step_type = 'update_mepr_txn_step';

        // Label for the step
        public function get_label() {
            return 'Update Mepr Transaction';
        }

        // Icon for the step
        public function get_icon_url() {
            return '<i class="fa fa-indent"></i>';
        }

        // Define settings fields
        public function get_settings() {
            return array(
                'title'  => 'Memberpress Transaction Details',
                'fields' => array(
                    array(
                        'name'       => 'email_field',         // Unique id for the field
                        'class'      => 'merge-tag-support',   // Adds merge tag option to the field
                        'required'   => true,                  // Is required
                        'label'      => 'Email field',         // Label of the field
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
            $email_field = $this->get_setting('email_field');
            $entry_timeline_note = $this->get_setting('entry_timeline_note');

            // Replace merge tags in the settings values
            $email_field = GFCommon::replace_variables($email_field, $this->get_form(), $entry, false, false, false, 'text');
            $entry_timeline_note = GFCommon::replace_variables($entry_timeline_note, $this->get_form(), $entry, false, false, false, 'text');

            // Format the created_at date
            $date = new DateTime();
            $formatted_date = $date->format('Y-m-d H:i:s');

            // Find user by email field
            $user = get_user_by('email', $email_field);

            if ($user) {
                $mepr_user = new MeprUser($user->ID);
                $txn_mepr = MeprTransaction::get_all_by_user_id($user->ID);

                foreach ($txn_mepr as $txn) {
                    if ($txn->status === 'pending') {
                        $txn_id = $txn->id;

                        $txn = new MeprTransaction($txn_id);

                        if ($txn->id) { // Ensure the transaction exists
                            // Update the transaction properties
                            $txn->status = MeprTransaction::$complete_str; // Update the status if needed
                            $txn->created_at = $formatted_date; // Update the creation date if needed
                            // Save the updated transaction
                            $txn->store();
							
							//log txn
							error_log (print_r($txn, true));
							
							
                        } else {
                            // Handle the case where the transaction does not exist
                            echo "Transaction not found.";
                        }
                    }
                }
            } else {
                echo "User not found.";
            }

            // Add a note to the entry's timeline
            gravity_flow()->add_timeline_note($entry['id'], $entry_timeline_note);

            // Return true to indicate the step is complete
            return true;
        }
    }

    // Register the custom step class
    Gravity_Flow_Steps::register(new Gravity_Flow_Step_Update_Mepr_Txn());
});
