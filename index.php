<?php

global $USER, $DB, $CFG, $COURSE, $SESSION, $PAGE, $OUTPUT;
require_once '../../config.php';
require_once("forms/grading.php");
require_once($CFG->dirroot.'/lib/completionlib.php');
require_once("locallib.php");
require_once($CFG->dirroot.'/blocks/starchecklist/star_checklist_form.php');



$userid = optional_param('userid', false, PARAM_INT);
$action = optional_param('action', false, PARAM_INT);
if($userid) {
    $SESSION->uid = $userid;
} else {
    // TODO this causes an error when the userid param is not present
    $userid = $SESSION->uid;
}

$trainer = false;
$roles = $DB->get_records('role_assignments', array('userid' => $USER->id));
foreach ($roles as $r) {
    $role = $DB->get_record('role', array('id' => $r->roleid));
    if ($role->name != 'Student') {
        $trainer = true;
    }
}
$course = $DB->get_record('course', ['shortname'=>'STAR']);
$courseid = $course->id;

$PAGE->set_url('/local/star_checklist/index.php');
$PAGE->set_context(context_system::instance());

require_login();

$PAGE->set_title('Participant Checklist');
$PAGE->set_heading('Student Achievement in Reading');
$PAGE->set_pagelayout('starcourse');

$toform = $data = array();
$starchecklist = new star_checklist();
$time = new DateTime("now", core_date::get_user_timezone_object());
$timestamp = $time->getTimestamp();
if($action == 'submit') {
    $checklistform = new block_starchecklist_form();
    $fromform = $checklistform->get_data();
    if($fromform == null) {
        redirect('/mod/page/view.php?id=9203', 'All checkboxes must be selected to submit.', null, \core\output\notification::NOTIFY_ERROR);
    }

}
$form = $DB->get_record('local_starchecklist', ['userid'=>$userid]);
if(!$form) {
    $toform['userid'] = $userid;
    $toform['status'] = 'Awaiting feedback';
    $toform['submission'] = 'Submitted';
    // Date to send to db table
    $toform['date'] = $timestamp;
    $DB->insert_record('local_starchecklist', $toform);
    $form = $DB->get_record('local_starchecklist', ['userid'=>$userid]);
    $starchecklist->submit_for_grading($form, $trainer);
    // Date to display on form
    $date = date('l, F j, Y, g:i A', $form->date);
    $toform['date'] = $timestamp;
}
// Get form details to display on submission view
$form = $DB->get_record('local_starchecklist', ['userid'=>$userid]);
if(!empty($form)) {
    $toform['userid'] = $form->userid;
    $toform['status'] = $form->status;
    $toform['submission'] = $form->submission;
    $date = date('l, F j, Y, g:i A', $form->date);
    $toform['date'] = $date;
    array_push($data, $toform);
}

$userinfo = $DB->get_record('user', array('id' => $userid));
// Load user profile fields and get starusername field.
profile_load_custom_fields($userinfo);
$starusername = $userinfo->username;
if (isset($userinfo->profile['starusername']) && !empty($userinfo->profile['starusername'])) {
  $starusername = $userinfo->profile['starusername'];
}
$username = $starusername;
$mform = new starchecklist_form();
if ($fromform = $mform->get_data()) {
    $submission = $DB->get_record('local_starchecklist', ['userid'=>$userid]);
    foreach($fromform as $key => $value) {
        $toform2[$key] = $value;

        if($submission) {
            $toform2['status'] = $toform2['status'];
            $toform2['id'] = $submission->id;
            $toform2['date'] = $timestamp;
            $DB->update_record('local_starchecklist', $toform2);
        }
    }
    $starchecklist->submit_for_grading($submission, $trainer);

    // Update completion status
    if($fromform->status == 'Approved') {
        $modinfo = get_fast_modinfo($courseid);
        $modid = '';
        foreach ($modinfo->cms as $item) {
            if ($item->name == '1. Introduction') {
                $modid = $item->id;
            }
        }
        if($modid != '') {
            $cm = get_coursemodule_from_id('page', $modid);

            // Update completion state
            $completion=new completion_info($course);
            $completion->update_state($cm,COMPLETION_COMPLETE, $userid);
        }

    }
    $url = '/local/star_checklist/index.php?userid=' . $userid;
    redirect($url, 'Form status updated', 10,  \core\output\notification::NOTIFY_SUCCESS);
}

$results = new stdClass();
$results->data = array_values($data);
$formtitle = $username . ': Participant checklist';

$roles = $DB->get_records('role_assignments', array('userid' => $USER->id));
$student = false;
foreach ($roles as $r) {
    $role = $DB->get_record('role', array('id' => $r->roleid));
    if ($role->name === 'Student') {
        $student = true;
        break;
    }
}

// Breadcrumbs
$PAGE->navbar->add('My courses');
$PAGE->navbar->add('STAR', new moodle_url('/course/view.php', array('id' => $courseid)));
$groupname = $groupid = '';
$groupmemlist = $DB->get_record_select('groups_members', 'userid = ?', [$userid]);
if (!empty($groupmemlist)) {
  $grouplist = $DB->get_records_select('groups', 'id = ?', [$groupmemlist->groupid]);
  if (!empty($grouplist)) {
    foreach ($grouplist as $group) {
      $groupid = $group->id;
      $groupname = $group->name;
    }
  }
}
$program_team_url = '/report/stardashboard/groups.php?userid=' . $userid . '&groupid=' . $groupid . '&courseid=' . $courseid;
if (!$student) {
  $PAGE->navbar->add($groupname, new moodle_url($program_team_url));
}
$PAGE->navbar->add('Section 1', new moodle_url($program_team_url . '#section-1'));
$PAGE->navbar->add('Participant checklist');

echo $OUTPUT->header();
echo '<h2>' . $formtitle . '</h2>';

if($student) {
    echo '<p>Thanks for taking the time to review and complete this background information on STAR. You\'ll have access to Modules 2-9 once your trainer sees you\'ve completed everything in this module, and your team is ready to begin.</p>';
}
echo $OUTPUT->render_from_template('local_star_checklist/trainerview', $results);

if(!$student){

    $mform->set_data($toform);
    $mform->display();
}

echo $OUTPUT->footer();
