<?php

/**
 * Gravity Flow Custom Step: Create Memberpress Transaction.
 *
 * Description: This script establishes a custom step within Gravity Flow, regestering a new Transaction in Memberpress. 
 * Originally designed to create transactions in Memberpress as a step in Gravity Flow.
 *
 * Company: MemberFix 
 * URL: https://memberfix.rocks
 * Author: Denys Melnychuk
 * Date: 09.05.2024
 * Version: 1.0
 */


// Wait until Gravity Flow is ready before declaring the step class.
add_action('gravityflow_loaded', function () {

    // Define a custom step class
    class Gravity_Flow_Step_Add_Mepr_Txn extends Gravity_Flow_Step {

        // Unique identifier for this step type
        public $_step_type = 'add_mepr_txn_step';

        //Label for the step
        public function get_label()
        {
            return 'Add Mepr Transaction';
        }

        public function get_icon_url()
        {
            return '<i class="fa fa-indent"></i>';
        }

        // Define settings fields

        public function get_settings()
        {
            return array (
                'title' => 'Memberpress Transaction Details',
                'fields' => array (
                        array (
                            'name' => 'member_id',                 ///unique id for the field
                            'class' => 'merge-tag-support',         ///adds merge tag option to the field
                            'required' => true,                         //is required
                            'label' => 'Member ID',                //label of the field
                            'type' => 'text',
                        ),
                        array (
                            'name' => 'membership_id',
                            'class' => 'merge-tag-support',
                            'required' => true,
                            'label' => 'Membership ID',
                            'type' => 'text',
                        ),
                        array (
                            'name' => 'membership_term',
                            'class' => 'merge-tag-support',
                            'required' => true,
                            'label' => 'Membership Term',
                            'type' => 'text',
                        ),
                        array (
                            'name' => 'entry_timeline_note',
                            'class' => 'merge-tag-support',
                            'required' => true,
                            'label' => 'Entries timeline note',
                            'type' => 'text',
                        ),
                        array (
                            'name' => 'start_date',
                            'class' => 'merge-tag-support',
                            'required' => true,
                            'label' => 'Renewal Start Date',
                            'type' => 'text',
                        ),
                    ),
            );
        }

        //Process the step

        public function process()
        {
            $entry = $this->get_entry();

            // Get settings values
            $member_id = $this->get_setting('member_id');
            $membership_id = $this->get_setting('membership_id');
            $membership_term = $this->get_setting('membership_term');
            $entry_timeline_note = $this->get_setting('entry_timeline_note');
            $start_date = $this->get_setting('start_date');

            // Replace merge tags in the settings values. Needed to replace merge tags with values picked from the form
            $member_id = GFCommon::replace_variables($member_id, $this->get_form(), $entry, false, false, false, 'text');
            $membership_id = GFCommon::replace_variables($membership_id, $this->get_form(), $entry, true, false, false, 'text');
            $entry_timeline_note = GFCommon::replace_variables($entry_timeline_note, $this->get_form(), $entry, false, false, false, 'text');
            $start_date = GFCommon::replace_variables($start_date, $this->get_form(), $entry, false, false, false, 'text');
            $membership_term = GFCommon::replace_variables($membership_term, $this->get_form(), $entry, false, false, false, 'text');


            $user = reset(
                get_users(
                    array (
                        'meta_key' => 'mepr_member_number',
                        'meta_value' => $member_id
                    )
                )
            );

            $member_id = $user->ID;

            ///created_at and expires_at dates manipulation
            $date = DateTime::createFromFormat('m/d/Y', $start_date);
            $formatted_date = $date->format('Y-m-d H:i:s');
            $date->modify("+" . $membership_term . " years");
            $expiry_formatted_date = $date->format('Y-m-d 23:59:59');



            //create new transaction
            $txn = new MeprTransaction();
            $txn->amount = 0.00;
            $txn->total = 0.00;
            $txn->user_id = $member_id;
            $txn->product_id = $membership_id;
            $txn->status = MeprTransaction::$complete_str; /// Status set to  complete
            $txn->txn_type = MeprTransaction::$payment_str;
            $txn->gateway = 'manual';
            $txn->created_at = $formatted_date;
            $txn->expires_at = $expiry_formatted_date;
            $txn->store();
            error_log(print_r($txn, true));


            // Add a note to the entry's timeline
            gravity_flow()->add_timeline_note($entry['id'], $entry_timeline_note);

            // Return true to indicate the step is complete
            return true;
        }
    }
    // Register the custom step class
    Gravity_Flow_Steps::register(new Gravity_Flow_Step_Add_Mepr_Txn());
});
