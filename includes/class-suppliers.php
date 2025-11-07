<?php
if (!defined('ABSPATH')) exit;

class SCM_Suppliers {

    /**
     * Calculate supplier performance score
     * Uses weighted scoring across multiple metrics
     * 
     * @param float $on_time      On-time delivery % (0-100)
     * @param float $cost_var     Cost variance % (0-100, lower is better)
     * @param float $defects      Defect rate % (0-100, lower is better)
     * @param float $lead_time    Lead time reliability % (0-100, lower variance is better)
     * @param array $weights      Optional custom weights
     * @return float|WP_Error     Score (0-100) or error
     */
    public static function calculate_score($on_time, $cost_var, $defects, $lead_time, $weights = null) {
        // Validate inputs
        $metrics = [
            'on_time'   => $on_time,
            'cost_var'  => $cost_var,
            'defects'   => $defects,
            'lead_time' => $lead_time
        ];
        
        foreach ($metrics as $name => $value) {
            if (!is_numeric($value) || $value < 0 || $value > 100) {
                return new WP_Error(
                    'invalid_metric',
                    sprintf(__('%s must be between 0 and 100.', 'scm-plugin'), $name)
                );
            }
    }
        
        // Default weights (must sum to 1.0)
        if (!$weights) {
            $weights = [
                'on_time'   => 0.4,  // 40% weight
                'cost_var'  => 0.2,  // 20% weight
                'defects'   => 0.2,  // 20% weight
                'lead_time' => 0.2   // 20% weight
            ];
        }
        
        // Validate custom weights
        $weight_sum = array_sum($weights);
        if (abs($weight_sum - 1.0) > 0.01) {
            return new WP_Error('invalid_weights', __('Weights must sum to 1.0.', 'scm-plugin'));
        }
        
        // Calculate weighted score
        // On-time is positive (higher is better)
        // Others are negative (lower is better, so we invert them)
        $score = ($weights['on_time'] * $on_time)
               + ($weights['cost_var'] * (100 - $cost_var))
               + ($weights['defects'] * (100 - $defects))
               + ($weights['lead_time'] * (100 - $lead_time));
        
        return round($score, 2);
    }
    
    /**
     * Get performance rating based on score
     * 
     * @param float $score
     * @return string
     */
    public static function get_performance_rating($score) {
        if ($score >= 90) return 'Excellent';
        if ($score >= 80) return 'Good';
        if ($score >= 70) return 'Satisfactory';
        if ($score >= 60) return 'Needs Improvement';
        return 'Poor';
    }

    /**
     * Save supplier performance metrics
     * 
     * @param string $name
     * @param float  $on_time
     * @param float  $cost_var
     * @param float  $defects
     * @param float  $lead_time
     * @param float  $score Optional pre-calculated score
     * @return int|WP_Error Insert ID or error
     */
    public static function save_supplier($name, $on_time, $cost_var, $defects, $lead_time, $score = null) {
        global $wpdb;
        
        // Validate supplier name
        if (empty($name)) {
            return new WP_Error('invalid_name', __('Supplier name is required.', 'scm-plugin'));
        }
        
        // Calculate score if not provided
        if ($score === null) {
            $score = self::calculate_score($on_time, $cost_var, $defects, $lead_time);
            if (is_wp_error($score)) {
                return $score;
            }
        }
        
        $table = $wpdb->prefix . 'scm_suppliers';
        
        // Check if supplier exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE supplier_name = %s ORDER BY id DESC LIMIT 1",
            sanitize_text_field($name)
        ));
        
        $data = [
            'supplier_name'      => sanitize_text_field($name),
            'on_time'            => floatval($on_time),
            'cost_variance'      => floatval($cost_var),
            'defect_rate'        => floatval($defects),
            'avg_lead_time'      => floatval($lead_time),
            'performance_score'  => floatval($score),
            'updated_at'         => current_time('mysql')
        ];
        
        $format = ['%s', '%f', '%f', '%f', '%f', '%f', '%s'];
        
        if ($existing) {
            // Update existing supplier
            $result = $wpdb->update(
                $table,
                $data,
                ['id' => $existing],
                $format,
                ['%d']
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', __('Failed to update supplier data.', 'scm-plugin'));
            }
            
            do_action('scm_supplier_updated', $existing, $name);
            return $existing;
            
        } else {
            // Insert new supplier
            $data['created_at'] = current_time('mysql');
            $format[] = '%s';
            
            $result = $wpdb->insert($table, $data, $format);
            
            if ($result === false) {
                return new WP_Error('insert_failed', __('Failed to save supplier data.', 'scm-plugin'));
            }
            
            $insert_id = $wpdb->insert_id;
            do_action('scm_supplier_created', $insert_id, $name);
            
            return $insert_id;
        }
    }
    
    /**
     * Get supplier by ID or name
     * 
     * @param mixed $identifier ID (int) or name (string)
     * @return object|null
     */
    public static function get_supplier($identifier) {
        global $wpdb;
        $table = $wpdb->prefix . 'scm_suppliers';
        
        if (is_numeric($identifier)) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                absint($identifier)
            ));
        } else {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE supplier_name = %s ORDER BY id DESC LIMIT 1",
                sanitize_text_field($identifier)
            ));
        }
    }
    
    /**
     * Get all suppliers with optional filtering
     * 
     * @param array $args Query arguments
     * @return array
     */
    public static function get_suppliers($args = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'scm_suppliers';
        
        $defaults = [
            'orderby' => 'performance_score',
            'order'   => 'DESC',
            'limit'   => 100,
            'offset'  => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $allowed_orderby = ['supplier_name', 'performance_score', 'on_time', 'created_at', 'updated_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'performance_score';
        
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
            absint($args['limit']),
            absint($args['offset'])
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Get suppliers below performance threshold
     * 
     * @param float $threshold Score threshold (0-100)
     * @return array
     */
    public static function get_underperforming_suppliers($threshold = 70) {
        global $wpdb;
        $table = $wpdb->prefix . 'scm_suppliers';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE performance_score < %f ORDER BY performance_score ASC",
            floatval($threshold)
        ), ARRAY_A);
    }
}