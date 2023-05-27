<?php
global $CFG;
require_once("$CFG->libdir/formslib.php");

class starintrochecklist_form extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG;
        global $USER;

        $mform = $this->_form;
        $options = array(
            'Approved' => 'Approve',
            'Needs Action' => 'Needs action'
        );
        $mform->addElement('select', 'status', 'Status:', $options);

        $this->add_action_buttons(true, 'Save changes');

    }
    //Custom validation should be added here
    public function validation($data, $files)
    {
        return array();
    }
}