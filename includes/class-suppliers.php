<?php
if (!defined('ABSPATH')) exit;


class SCM_Suppliers {


    /**
     * Compute supplier performance score
     * Weight factors can be tuned per company
     */
    public static function calculate_score($on_time, $cost_var, $defects, $lead_time) {
        // Weights
        $w1 = 0.4; // On-time
        $w2 = 0.2; // Cost variance
        $w3 = 0.2; // Defects
        $w4 = 0.2; // Lead time reliability


        // Normalize (lower defect & cost variance = better)
        $score = ($w1 * $on_time)
               + ($w2 * (100 - $cost_var))
               + ($w3 * (100 - $defects))
               + ($w4 * (100 - $lead_time));


        return round($score / 4, 2);
    }


    /**
     * Save supplier performance metrics
     */
    public static function save_supplier($name, $on_time, $cost_var, $defects, $lead_time) {
        global $wpdb;
        $table = $wpdb->prefix . 'scm_suppliers';


        $wpdb->insert($table, [
            'supplier_name' => sanitize_text_field($name),
            'on_time'       => floatval($on_time),
            'cost_variance' => floatval($cost_var),
            'defect_rate'   => floatval($defects),
            'avg_lead_time' => floatval($lead_time)
        ]);
    }
}

