<?php

use core_course_external;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use core_cohort_external;
use core_user_external;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/cohort/externallib.php');
require_once($CFG->dirroot . '/user/externallib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/externallib.php');

class local_analytics_external extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_user_enrolment_data_parameters() {
        return new external_function_parameters(
                [
                        'userid' => new external_value(PARAM_INT, 'User ID'),
                        'courseid' => new external_value(PARAM_INT, 'Course ID')
                ]
        );
    }

    public static function get_user_enrolment_data($userid, $courseid) {
        global $DB;
        $user_enrolment_datas = $DB->get_records("user_enrolments", array('userid' => $userid));

        $enrol_data = null;
        $user_enrolment_data = null;
        foreach ($user_enrolment_datas as $user_enrol) {
            $enrol_db_data = $DB->get_record("enrol", array('courseid' => $courseid, 'id' => $user_enrol->enrolid));
            if ($enrol_db_data != null) {
                $enrol_data = $enrol_db_data;
                $user_enrolment_data = $user_enrol;
            }
        };

        $data = new \stdClass();

        $data->userid = $user_enrolment_data->userid;
        $data->courseid = $enrol_data->courseid;
        $data->timecreated = $enrol_data->timecreated;
        $data->enrol = $enrol_data->enrol;
        $data->roleid = $enrol_data->roleid;
        $data->enrolstartdate = $user_enrolment_data->timestart;
        $data->enrolenddate = $user_enrolment_data->timeend;

        return $data;
    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     */
    public static function get_user_enrolment_data_returns(): external_single_structure {
        return new external_single_structure(
                array(
                        'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                        'courseid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                        'timecreated' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                        'enrol' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                        'roleid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                        'enrolstartdate' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                        'enrolenddate' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                )
        );

    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_user_certificates_parameters() {
        return new external_function_parameters(
                [
                        'userid' => new external_value(PARAM_INT, 'User ID'),
                        'courseid' => new external_value(PARAM_INT, 'Course ID'),
                        'coursecategory' => new external_value(PARAM_INT, 'Course Category'),
                        'courseidnumber' => new external_value(PARAM_TEXT, 'Course ID Number')
                ]
        );
    }

    public static function get_user_certificates($userid, $courseid, $coursecategory, $courseidnumber) {
        global $DB;

        $courses= core_course_external::get_courses_by_field('category',$coursecategory);
        $certificates_in_category = [];

        foreach ($courses["courses"] as $key => $course) {
            if ($course["id"]!=$courseid){
                $sql = "SELECT cc.id, ci.timecreated, cc.name FROM {customcert_issues} AS ci INNER JOIN {customcert} as CC ON ci.customcertid = cc.id  WHERE ci.userid=:userid and cc.course=:courseid ORDER BY ci.timecreated ASC";

                $user_certificates_data = $DB->get_records_sql($sql, array('userid' => $userid, 'courseid' => $course["id"]));
                $index = 0;
                foreach ($user_certificates_data as $key2 => $certificate) {
                    $data = new \stdClass();
                    $data->certificateid = $certificate->id;
                    $data->timecreated = $certificate->timecreated;
                    $data->name = $certificate->name;
                    $data->userid = $userid;
                    $data->courseid = $courseid;
                    $data->type = "T";
                    if (count($user_certificates_data) <= 1) {
                        if (substr($courseidnumber,0,2) === "RC"){
                            $data->type = "R";
                        }
                    } else if ($index == (count($user_certificates_data) - 1)) {
                        $data->type = "P";
                    }
                    $index++;

                    $certificates_in_category[$course["id"]] = $data;
                }
            }
        }


        //viejo

        $sql = "SELECT cc.id, ci.timecreated, cc.name FROM {customcert_issues} AS ci INNER JOIN {customcert} as CC ON ci.customcertid = cc.id  WHERE ci.userid=:userid and cc.course=:courseid ORDER BY ci.timecreated ASC";

        $user_certificates_data = $DB->get_records_sql($sql, array('userid' => $userid, 'courseid' => $courseid));

        $certificates_to_retrieve = [];

        $index = 0;
        foreach ($user_certificates_data as $key => $certificate) {

            $data = new \stdClass();
            $data->certificateid = $certificate->id;
            $data->timecreated = $certificate->timecreated;
            $data->name = $certificate->name;
            $data->userid = $userid;
            $data->courseid = $courseid;
            $data->type = "T";
            $data->latest = true;
            if (count($user_certificates_data) <= 1) {
                if (substr($courseidnumber,0,2) === "RC"){
                    $data->type = "R";
                }
            } else if ($index == (count($user_certificates_data) - 1)) {
                $data->type = "P";
            }

            foreach ($certificates_in_category as $key2 => $course){
                #other certificate time created > current certificate time created
                if($course->timecreated > $certificate->timecreated){
                    $data->latest = false;
                }
            }

            $index++;

            array_push($certificates_to_retrieve, $data);
        }
        //termina viejo

        return $certificates_to_retrieve;
    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     */
    public static function get_user_certificates_returns(): external_multiple_structure {
        return new external_multiple_structure(
                new external_single_structure(
                        array(
                                'certificateid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                                'timecreated' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                                'name' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                                'userid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                                'courseid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                                'type' => new external_value(PARAM_TEXT, 'Standard Moodle primary key'),
                                'latest' => new external_value(PARAM_BOOL, 'Standard Moodle primary key')
                        )
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_user_supervisor_parameters() {
        return new external_function_parameters(
                [
                        'userid' => new external_value(PARAM_INT, 'User ID')
                ]
        );
    }

    public static function update_user_supervisor($userid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $supervisor_username = '';
        $cohorts = core_cohort_external::get_cohorts();
        foreach ($cohorts as $key => $cohort) {
            $cohort_members = core_cohort_external::get_cohort_members([$cohort["id"]]);
            $key2 = array_search($userid, $cohort_members[0]["userids"]);
            if ($key2 !== false) {
                $supervisor_username = $cohort["idnumber"];
            }

        }

        //TODO:
        // - find admin's email
        $supervisor = core_user_external::get_users_by_field('username', [$supervisor_username]);
        $supervisor_email = $supervisor[0]['email'];
        $supervisor_name = $supervisor[0]['fullname'];
        //TODO:
        // - update user data with the supervisor

        $user_customfield_supervisor_name = [
            //'name' => 'Supervisor Name',
                'value' => $supervisor_name,
                'type' => 'supervisor_name',
            //'shortname'=>'supervisor_name'
        ];

        $user_customfield_supervisor_email = [
            //'name' => 'Supervisor Email',
                'value' => $supervisor_email,
                'type' => 'supervisor_email',
            //'shortname'=> 'supervisor_email'
        ];

        $updated_user = [
                'id' => $userid,
                'customfields' => [
                        $user_customfield_supervisor_name,
                        $user_customfield_supervisor_email
                ]
        ];

        core_user_external::update_users([$updated_user]);

        $transaction->allow_commit();

        return [
                'success' => 'true',
        ];

    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     */
    public static function update_user_supervisor_returns(): external_single_structure {
        return new external_single_structure(
                array(
                        'success' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                )
        );
    }
}