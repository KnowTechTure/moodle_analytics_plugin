<?php

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_analytics_get_user_enrolment_data' => array(
        'classname' => 'local_analytics_external',
        'methodname'  => 'get_user_enrolment_data',
        'description' => 'Get enrolment analytics data',
        'classpath' => 'local/analytics/externallib.php',
        'type' => 'read',
        'ajax' => true,
    ),
    'local_analytics_get_certificate_data' => array(
    'classname' => 'local_analytics_external',
    'methodname'  => 'get_user_certificates',
    'description' => 'Get user certificates data',
    'classpath' => 'local/analytics/externallib.php',
    'type' => 'read',
    'ajax' => true,

),
    'local_analytics_update_user_supervisor' => array(
        'classname' => 'local_analytics_external',
        'methodname'  => 'update_user_supervisor',
        'description' => 'Update user supervisor data',
        'classpath' => 'local/analytics/externallib.php',
        'type' => 'read',
        'ajax' => true,)

];

$services = array(
    'Moodle Analytics Plugin' => array(
        'functions' => array('local_analytics_get_user_enrolment_data','local_analytics_get_certificate_data', 'local_analytics_update_user_supervisor'),
        'restrictedusers' => 0,
        'enabled' => 1,
    ),
);
