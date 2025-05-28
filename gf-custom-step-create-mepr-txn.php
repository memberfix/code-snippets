<?php 

/**
 * Gravity Flow Custom Step: Create Memberpress Transaction.
 *
 * Description: This script establishes a custom step within Gravity Flow, regestering a new Transaction in Memberpress. 
 * Originally designed to create transactions in Memberpress on Form Submit.
 *
 * Company: MemberFix 
 * URL: https://memberfix.rocks
 * Author: Denys Melnychuk
 * Date: 28.05.2025
 * Version: 1.3
 */


//forms
//17 - Renewal
//23 - Upgrade / Downgrade
//4 - Associate, single breeder dual breeder registration form
//20 - Smart Dog Owner registartion form

//Membership ID's
//25842 - Associate
//25794 - Breeder Dual
//25786 - Breeder Single
//25790 - Smart Dog Owner


// Wait until Gravity Flow is ready before declaring the step class.
add_action( 'gravityflow_loaded', function() {

    class Gravity_Flow_Step_Add_Mepr_Txn extends Gravity_Flow_Step {

        public $_step_type = 'add_mepr_txn_step';

        public function get_label() {
            return 'Create Mepr Transaction (Renewals)';
        }

        public function get_icon_url() {
            return '<i class="fa fa-indent"></i>';
        }

        public function get_settings() {
            return array(
                'title'  => 'Memberpress Transaction Details',
                'fields' => array(
                    array(
                        'name'       => 'user_id',
                        'class'      => 'merge-tag-support',
                        'required'   => true,
                        'label'      => 'User ID',
                        'type'       => 'text',
                    ),
                    array(
                        'name'       => 'membership_id',
                        'class'      => 'merge-tag-support',
                        'required'   => true,
                        'label'      => 'Membership ID',
                        'type'       => 'text',
                    ),
                    array(
                        'name'       => 'membership_term',
                        'class'      => 'merge-tag-support',
                        'required'   => false,
                        'label'      => 'Membership Term (not applicable for upgrade / downgrade)',
                        'type'       => 'text',
                    ),
                    array(
                        'name'       => 'expiration_date',
                        'class'      => 'merge-tag-support',
                        'required'   => false,
                        'label'      => 'Expiration Date (applicable for upgrade / downgrade only)',
                        'type'       => 'text',
                    ),
                    array(
                        'name'       => 'entry_timeline_note',
                        'class'      => 'merge-tag-support',
                        'required'   => true,
                        'label'      => 'Entries timeline note',
                        'type'       => 'text',
                    ),
                    array(
                        'name'       => 'start_date',
                        'class'      => 'merge-tag-support',
                        'required'   => true,
                        'label'      => 'Renewal Start Date',
                        'type'       => 'text',
                    ),
                ),
            );
        }

        public function process() {
            $entry = $this->get_entry();
            $user_id = GFCommon::replace_variables($this->get_setting('user_id'), $this->get_form(), $entry, false, false, false, 'text');
            $membership_id = GFCommon::replace_variables($this->get_setting('membership_id'), $this->get_form(), $entry, false, false, false, 'text');
            $membership_term = GFCommon::replace_variables($this->get_setting('membership_term'), $this->get_form(), $entry, false, false, false, 'text');
            $entry_timeline_note = GFCommon::replace_variables($this->get_setting('entry_timeline_note'), $this->get_form(), $entry, false, false, false, 'text');
            $start_date = GFCommon::replace_variables($this->get_setting('start_date'), $this->get_form(), $entry, false, false, false, 'text');
            $expiration_date = GFCommon::replace_variables($this->get_setting('expiration_date'), $this->get_form(), $entry, false, false, false, 'text');

            error_log (' == TXN Creation step started ==');
            error_log( 'User ID: ' . $user_id);
            error_log( 'Start Date: ' . $start_date);
            error_log( 'Membership ID: ' . $membership_id);
            error_log( 'Membership Term: ' . $membership_term);
            error_log( 'Expiration Date (from form): ' . $expiration_date);

            $form_id = $this->get_form_id();    
            error_log( 'Form ID: ' . $form_id);

            $rules = array(
                // Structure: form_id => [membership_id => [date_format, membership_term_required]]
                
                // Renewal Form
                17 => array(
                    25842 => array('m/d/Y', false),  // Associate
                    25794 => array('m/d/Y', false),  // Breeder Dual
                    25786 => array('m/d/Y', false),  // Breeder Single
                    25790 => array('m/d/Y', false),  // Smart Dog Owner
                ),
                
                // Upgrade/Downgrade Form
                23 => array(
                    25842 => array('m-d-Y', true),   // Associate
                    25794 => array('m-d-Y', true),   // Breeder Dual
                    25786 => array('m-d-Y', true),   // Breeder Single
                ),
                
                // Associate + Breeders Registration Form
                4 => array(
                    25842 => array('m-d-Y', false),  // Associate
                    25794 => array('m-d-Y', false),  // Breeder Dual
                    25786 => array('m-d-Y', false),  // Breeder Single
                ),
                
                // Smart Dog Owner Registration Form
                20 => array(
                    25790 => array('m-d-Y', false),  // Smart Dog Owner
                ),
            );

            if ($form_id == 23) { // Exclude Smart Dog Owner
                $now = new DateTime();
                $six_years_future = (clone $now)->modify('+6 years');
                $expiration_date_object = DateTime::createFromFormat('Y-m-d H:i:s', $expiration_date);
                if ($expiration_date_object < $now || $expiration_date_object > $six_years_future) {
                    // Update $rules for form_id 23
                    $rules[23][$membership_id] = array('m-d-Y', false);
                }
            }

            // Validate form and membership combination
            if (!isset($rules[$form_id]) || !isset($rules[$form_id][$membership_id])) {
                error_log('Invalid form_id (' . $form_id . ') and membership_id (' . $membership_id . ') combination');
                return false;
            }

            $date_format = $rules[$form_id][$membership_id][0];
            $add_membership_term = $rules[$form_id][$membership_id][1];

            // Parse start date
            $start_date_object = DateTime::createFromFormat($date_format, $start_date);
            if (!$start_date_object) {
                error_log('Invalid start date format: ' . $start_date);
                return false;
            }

            $start_date = $start_date_object->format('Y-m-d H:i:s');
            error_log('Start Date: ' . $start_date);

            // Handle expiration date
            if ($add_membership_term) {
                if (empty($expiration_date)) {
                    error_log('Expiration date required but not provided');
                    return false;
                }
                $expiration_date_object = DateTime::createFromFormat('Y-m-d H:i:s', $expiration_date);
                if (!$expiration_date_object) {
                    error_log('Invalid expiration date format: ' . $expiration_date);
                    return false;
                }
            } else {
                if (empty($membership_term)) {
                    error_log('Membership term required but not provided');
                    return false;
                }
                $expiration_date_object = clone $start_date_object;
                $expiration_date_object->modify("+{$membership_term} years");
            }

            $expiry_formatted_date = $expiration_date_object->format('Y-m-d H:i:s');
            error_log('Expiration Date: ' . $expiry_formatted_date);



            // Create new transaction
            $txn = new MeprTransaction();
            $txn->amount = 0.00;
            $txn->total = 0.00;
            $txn->user_id = $user_id;
            $txn->product_id = $membership_id;
            $txn->status = MeprTransaction::$complete_str;
            $txn->txn_type = MeprTransaction::$payment_str;
            $txn->gateway = 'manual';
            $txn->created_at = $start_date;
            $txn->expires_at = $expiry_formatted_date;
            $txn->store();

            $event = MeprEvent::record('transaction-completed', $txn);
            do_action('mepr-event-transaction-completed', $event);
            error_log('Triggered mepr-event-transaction-completed with event');

                // Add a note to the entry's timeline
            gravity_flow()->add_timeline_note($entry['id'], $entry_timeline_note);

            // Update user meta for expiration date
            //update_user_meta($user_id, 'mepr_expiry_renewal_date', $expiration_date_object->format('d-m-Y'));

            error_log (' == TXN Creation step complete ==');
            return true;
        }
    }

    Gravity_Flow_Steps::register( new Gravity_Flow_Step_Add_Mepr_Txn() );
});
