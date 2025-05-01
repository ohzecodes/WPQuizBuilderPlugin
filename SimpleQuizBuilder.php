<?php
/**
 * Plugin Name: Simple Quiz Builder
 * Plugin URI: 
 * Description: A plugin that allows website owners to create quizzes for their WordPress websites
 * Version: 1.0.0
 * Author: ohzecodes
 * Author URI: 
 * Text Domain: 
 * License: 
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SimpleQuizBuilder {
    
    public function __construct() {
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));
        
        // Initialize plugin
        add_action('init', array($this, 'init'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // Register shortcode
        add_shortcode('simple_quiz', array($this, 'quiz_shortcode'));
        
        // AJAX handlers for the admin panel
        add_action('wp_ajax_save_quiz', array($this, 'save_quiz'));
        add_action('wp_ajax_delete_quiz', array($this, 'delete_quiz'));
        
        // AJAX handlers for the frontend
        add_action('wp_ajax_submit_quiz', array($this, 'submit_quiz'));
        add_action('wp_ajax_nopriv_submit_quiz', array($this, 'submit_quiz'));
    }
    
    /**
     * Plugin activation
     */
    public function plugin_activation() {
        global $wpdb;
        
        // Create necessary tables
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for quizzes
        $table_quizzes = $wpdb->prefix . 'simple_quizzes';
        $sql_quizzes = "CREATE TABLE $table_quizzes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Table for questions
        $table_questions = $wpdb->prefix . 'simple_quiz_questions';
        $sql_questions = "CREATE TABLE $table_questions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id mediumint(9) NOT NULL,
            question_text text NOT NULL,
            question_type varchar(50) NOT NULL,
            question_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY quiz_id (quiz_id)
        ) $charset_collate;";
        
        // Table for options
        $table_options = $wpdb->prefix . 'simple_quiz_options';
        $sql_options = "CREATE TABLE $table_options (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question_id mediumint(9) NOT NULL,
            option_text text NOT NULL,
            is_correct tinyint(1) NOT NULL DEFAULT 0,
            option_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY question_id (question_id)
        ) $charset_collate;";
        
        // Table for results
        $table_results = $wpdb->prefix . 'simple_quiz_results';
        $sql_results = "CREATE TABLE $table_results (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            quiz_id mediumint(9) NOT NULL,
            user_id bigint(20) NULL,
            user_ip varchar(100) NOT NULL,
            score int(11) NOT NULL,
            max_score int(11) NOT NULL,
            completed_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY quiz_id (quiz_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_quizzes);
        dbDelta($sql_questions);
        dbDelta($sql_options);
        dbDelta($sql_results);
        
        // Create a directory for any files we might need
        $upload_dir = wp_upload_dir();
        $quiz_dir = $upload_dir['basedir'] . '/simple-quiz-builder';
        
        if (!file_exists($quiz_dir)) {
            wp_mkdir_p($quiz_dir);
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function plugin_deactivation() {     }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Register custom post type or any other initialization
        load_plugin_textdomain('simple-quiz-builder', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Simple Quiz Builder', 'simple-quiz-builder'),
            __('Quiz Builder', 'simple-quiz-builder'),
            'manage_options',
            'simple-quiz-builder',
            array($this, 'admin_page'),
            'dashicons-clipboard',
            30
        );
        
        add_submenu_page(
            'simple-quiz-builder',
            __('All Quizzes', 'simple-quiz-builder'),
            __('All Quizzes', 'simple-quiz-builder'),
            'manage_options',
            'simple-quiz-builder',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'simple-quiz-builder',
            __('Add New Quiz', 'simple-quiz-builder'),
            __('Add New Quiz', 'simple-quiz-builder'),
            'manage_options',
            'simple-quiz-builder-new',
            array($this, 'add_new_quiz_page')
        );
        
        // add_submenu_page(
        //     'simple-quiz-builder',
        //     __('Quiz Results', 'simple-quiz-builder'),
        //     __('Quiz Results', 'simple-quiz-builder'),
        //     'manage_options',
        //     'simple-quiz-builder-results',
        //     array($this, 'results_page')
        // );
        
        // add_submenu_page(
        //     'simple-quiz-builder',
        //     __('Settings', 'simple-quiz-builder'),
        //     __('Settings', 'simple-quiz-builder'),
        //     'manage_options',
        //     'simple-quiz-builder-settings',
        //     array($this, 'settings_page')
        // );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'simple-quiz-builder') === false) {
            return;
        }
        
        wp_enqueue_style('simple-quiz-admin-css', plugins_url('assets/css/admin.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('simple-quiz-admin-js', plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'jquery-ui-sortable'), '1.0.0', true);
        
        wp_localize_script('simple-quiz-admin-js', 'simple_quiz_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simple-quiz-nonce'),
            'confirm_delete' => __('Are you sure you want to delete this quiz? This action cannot be undone.', 'simple-quiz-builder')
        ));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_scripts() {
        wp_enqueue_style('simple-quiz-frontend-css', plugins_url('assets/css/frontend.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('simple-quiz-frontend-js', plugins_url('assets/js/frontend.js', __FILE__), array('jquery'), '1.0.0', true);
        
        wp_localize_script('simple-quiz-frontend-js', 'simple_quiz_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simple-quiz-frontend-nonce'),
            'please_answer' => __('Please answer all questions before submitting.', 'simple-quiz-builder')
        ));
    }
    
    /**
     * Admin page for listing all quizzes
     */
    public function admin_page() {
        global $wpdb;
        
        // Get all quizzes
        $quizzes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}simple_quizzes ORDER BY id DESC");
        
        // Check if we need to edit a quiz
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        
        if ($edit_id > 0) {
            $this->edit_quiz_page($edit_id);
            return;
        }
        
        include plugin_dir_path(__FILE__) . 'templates/admin-quizzes.php';
    }
    
    /**
     * Admin page for adding a new quiz
     */
    public function add_new_quiz_page() {
        include plugin_dir_path(__FILE__) . 'templates/admin-new-quiz.php';
    }
    
    /**
     * Admin page for editing a quiz
     */
    public function edit_quiz_page($quiz_id) {
        global $wpdb;
        
        // Get quiz data
        $quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}simple_quizzes WHERE id = %d", $quiz_id));
        
        if (!$quiz) {
            wp_die(__('Quiz not found', 'simple-quiz-builder'));
        }
        
        // Get questions
        $questions = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}simple_quiz_questions 
            WHERE quiz_id = %d 
            ORDER BY question_order ASC
        ", $quiz_id));
        
        // Get options for each question
        foreach ($questions as &$question) {
            $question->options = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}simple_quiz_options 
                WHERE question_id = %d 
                ORDER BY option_order ASC
            ", $question->id));
        }
        
        include plugin_dir_path(__FILE__) . 'templates/admin-edit-quiz.php';
    }
    
    /**
     * Admin page for viewing quiz results
     */
    public function results_page() {
        global $wpdb;
        
        // Get quiz ID if specified
        $quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
        
        if ($quiz_id > 0) {
            // Get results for specific quiz
            $quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}simple_quizzes WHERE id = %d", $quiz_id));
            
            if (!$quiz) {
                wp_die(__('Quiz not found', 'simple-quiz-builder'));
            }
            
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT r.*, u.display_name 
                FROM {$wpdb->prefix}simple_quiz_results r 
                LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                WHERE r.quiz_id = %d 
                ORDER BY r.completed_at DESC
            ", $quiz_id));
            
            include plugin_dir_path(__FILE__) . 'templates/admin-quiz-results.php';
        } else {
            // List all quizzes with result counts
            $quiz_stats = $wpdb->get_results("
                SELECT q.id, q.title, COUNT(r.id) as result_count, AVG(r.score/r.max_score*100) as avg_score
                FROM {$wpdb->prefix}simple_quizzes q
                LEFT JOIN {$wpdb->prefix}simple_quiz_results r ON q.id = r.quiz_id
                GROUP BY q.id
                ORDER BY q.id DESC
            ");
            
            include plugin_dir_path(__FILE__) . 'templates/admin-results-overview.php';
        }
    }
    
    /**
     * Admin page for plugin settings
     */
    // public function settings_page() {
    //     // Process form submission
    //     if (isset($_POST['simple_quiz_settings_nonce']) && wp_verify_nonce($_POST['simple_quiz_settings_nonce'], 'simple_quiz_settings')) {
    //         // Save settings
    //         $settings = array(
    //             'show_results' => isset($_POST['show_results']) ? 1 : 0,
    //             'show_correct_answers' => isset($_POST['show_correct_answers']) ? 1 : 0,
    //             'result_message_perfect' => sanitize_textarea_field($_POST['result_message_perfect']),
    //             'result_message_good' => sanitize_textarea_field($_POST['result_message_good']),
    //             'result_message_average' => sanitize_textarea_field($_POST['result_message_average']),
    //             'result_message_poor' => sanitize_textarea_field($_POST['result_message_poor']),
    //             'quiz_color_scheme' => sanitize_text_field($_POST['quiz_color_scheme']),
    //         );
            
    //         update_option('simple_quiz_settings', $settings);
            
    //         echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully.', 'simple-quiz-builder') . '</p></div>';
    //     }
        
    //     // Get current settings
    //     $default_settings = array(
    //         'show_results' => 1,
    //         'show_correct_answers' => 1,
    //         'result_message_perfect' => __('Perfect! You got all questions correct!', 'simple-quiz-builder'),
    //         'result_message_good' => __('Great job! You have good knowledge on this topic.', 'simple-quiz-builder'),
    //         'result_message_average' => __('Not bad! But there\'s still room for improvement.', 'simple-quiz-builder'),
    //         'result_message_poor' => __('Keep learning! You\'ll do better next time.', 'simple-quiz-builder'),
    //         'quiz_color_scheme' => 'blue',
    //     );
        
    //     $settings = get_option('simple_quiz_settings', $default_settings);
        
    //     include plugin_dir_path(__FILE__) . 'templates/admin-settings.php';
    // }
    
    /**
     * AJAX handler for saving quiz
     */
    public function save_quiz() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'simple-quiz-nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'simple-quiz-builder')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You don\'t have permission to do this', 'simple-quiz-builder')));
        }
        
        global $wpdb;
        
        // Get quiz data
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $questions = isset($_POST['questions']) ? $_POST['questions'] : array();
        
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Quiz title is required', 'simple-quiz-builder')));
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Insert or update quiz
            if ($quiz_id > 0) {
                // Update existing quiz
                $wpdb->update(
                    $wpdb->prefix . 'simple_quizzes',
                    array(
                        'title' => $title,
                        'description' => $description,
                    ),
                    array('id' => $quiz_id)
                );
            } else {
                // Insert new quiz
                $wpdb->insert(
                    $wpdb->prefix . 'simple_quizzes',
                    array(
                        'title' => $title,
                        'description' => $description,
                    )
                );
                $quiz_id = $wpdb->insert_id;
            }
            
            // If updating, delete all existing questions and options to replace them
            if ($quiz_id > 0) {
                // Get all question IDs for this quiz
                $question_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}simple_quiz_questions WHERE quiz_id = %d", $quiz_id));
                
                // Delete options for all questions
                if (!empty($question_ids)) {
                    $wpdb->query("DELETE FROM {$wpdb->prefix}simple_quiz_options WHERE question_id IN (" . implode(',', $question_ids) . ")");
                }
                
                // Delete all questions
                $wpdb->delete($wpdb->prefix . 'simple_quiz_questions', array('quiz_id' => $quiz_id));
            }
            
            // Process questions
            if (is_array($questions)) {
                foreach ($questions as $q_index => $question) {
                    if (empty($question['text'])) continue;
                    
                    // Insert question
                    $wpdb->insert(
                        $wpdb->prefix . 'simple_quiz_questions',
                        array(
                            'quiz_id' => $quiz_id,
                            'question_text' => sanitize_textarea_field($question['text']),
                            'question_type' => sanitize_text_field($question['type']),
                            'question_order' => $q_index
                        )
                    );
                    
                    $question_id = $wpdb->insert_id;
                    
                    // Process options
                    if (isset($question['options']) && is_array($question['options'])) {
                        foreach ($question['options'] as $o_index => $option) {
                            if (empty($option['text'])) continue;
                            
                            $wpdb->insert(
                                $wpdb->prefix . 'simple_quiz_options',
                                array(
                                    'question_id' => $question_id,
                                    'option_text' => sanitize_textarea_field($option['text']),
                                    'is_correct' => isset($option['correct']) && $option['correct'] ? 1 : 0,
                                    'option_order' => $o_index
                                )
                            );
                        }
                    }
                }
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            wp_send_json_success(array(
                'message' => __('Quiz saved successfully', 'simple-quiz-builder'),
                'quiz_id' => $quiz_id,
                'shortcode' => '[simple_quiz id="' . $quiz_id . '"]'
            ));
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            
            wp_send_json_error(array('message' => __('Error saving quiz', 'simple-quiz-builder') . ': ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for deleting quiz
     */
    public function delete_quiz() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'simple-quiz-nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'simple-quiz-builder')));
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You don\'t have permission to do this', 'simple-quiz-builder')));
        }
        
        global $wpdb;
        
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        
        if ($quiz_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid quiz ID', 'simple-quiz-builder')));
        }
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Get all question IDs for this quiz
            $question_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}simple_quiz_questions WHERE quiz_id = %d", $quiz_id));
            
            // Delete options for all questions
            if (!empty($question_ids)) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}simple_quiz_options WHERE question_id IN (" . implode(',', $question_ids) . ")");
            }
            
            // Delete questions
            $wpdb->delete($wpdb->prefix . 'simple_quiz_questions', array('quiz_id' => $quiz_id));
            
            // Delete results
            $wpdb->delete($wpdb->prefix . 'simple_quiz_results', array('quiz_id' => $quiz_id));
            
            // Delete quiz
            $wpdb->delete($wpdb->prefix . 'simple_quizzes', array('id' => $quiz_id));
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            wp_send_json_success(array('message' => __('Quiz deleted successfully', 'simple-quiz-builder')));
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            
            wp_send_json_error(array('message' => __('Error deleting quiz', 'simple-quiz-builder') . ': ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for submitting quiz
     */
    public function submit_quiz() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'simple-quiz-frontend-nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'simple-quiz-builder')));
        }
        
        global $wpdb;
        
        $quiz_id = isset($_POST['quiz_id']) ? intval($_POST['quiz_id']) : 0;
        $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
        
        if ($quiz_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid quiz ID', 'simple-quiz-builder')));
        }
        
        // Get quiz
        $quiz = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}simple_quizzes WHERE id = %d", $quiz_id));
        
        if (!$quiz) {
            wp_send_json_error(array('message' => __('Quiz not found', 'simple-quiz-builder')));
        }
        
        // Get questions
        $questions = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}simple_quiz_questions 
            WHERE quiz_id = %d 
            ORDER BY question_order ASC
        ", $quiz_id));
        
        // Calculate score
        $score = 0;
        $max_score = count($questions);
        $user_answers = array();
        $correct_answers = array();
        
        foreach ($questions as $question) {
            // Get correct answers for this question
            $options = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}simple_quiz_options 
                WHERE question_id = %d 
                ORDER BY option_order ASC
            ", $question->id));
            
            $correct_option_ids = array();
            foreach ($options as $option) {
                if ($option->is_correct) {
                    $correct_option_ids[] = $option->id;
                }
            }
            
            $correct_answers[$question->id] = $correct_option_ids;
            
            // Check user's answer
            $user_answer = isset($answers[$question->id]) ? $answers[$question->id] : array();
            $user_answers[$question->id] = $user_answer;
            
            // For multiple choice, handle single and array answers
            if (!is_array($user_answer)) {
                $user_answer = array($user_answer);
            }
            
            // Check if answer is correct
            $is_correct = false;
            
            if ($question->question_type == 'multiple') {
                // For multiple choice, all correct options must be selected and no incorrect ones
                $user_answer_count = count($user_answer);
                $correct_count = count($correct_option_ids);
                
                if ($user_answer_count == $correct_count) {
                    $intersection = array_intersect($user_answer, $correct_option_ids);
                    $is_correct = (count($intersection) == $correct_count);
                }
            } else {
                // For single choice, only one answer can be correct
                $is_correct = (count($user_answer) == 1 && in_array($user_answer[0], $correct_option_ids));
            }
            
            if ($is_correct) {
                $score++;
            }
        }
        
        // Get user information
        $user_id = get_current_user_id();
        $user_ip = $_SERVER['REMOTE_ADDR'];
        
        // Save result to database
        $wpdb->insert(
            $wpdb->prefix . 'simple_quiz_results',
            array(
                'quiz_id' => $quiz_id,
                'user_id' => $user_id ? $user_id : null,
                'user_ip' => $user_ip,
                'score' => $score,
                'max_score' => $max_score,
            )
        );
        
        // Get settings
        $default_settings = array(
            'show_results' => 1,
            'show_correct_answers' => 1,
            'result_message_perfect' => __('Perfect! You got all questions correct!', 'simple-quiz-builder'),
            'result_message_good' => __('Great job! You have good knowledge on this topic.', 'simple-quiz-builder'),
            'result_message_average' => __('Not bad! But there\'s still room for improvement.', 'simple-quiz-builder'),
            'result_message_poor' => __('Keep learning! You\'ll do better next time.', 'simple-quiz-builder'),
        );
        
        $settings = get_option('simple_quiz_settings', $default_settings);
        
        // Determine message based on score
        $percentage = ($score / $max_score) * 100;
        $message = '';
        
        if ($percentage == 100) {
            $message = $settings['result_message_perfect'];
        } elseif ($percentage >= 75) {
            $message = $settings['result_message_good'];
        } elseif ($percentage >= 50) {
            $message = $settings['result_message_average'];
        } else {
            $message = $settings['result_message_poor'];
        }
        
        // Send response
        wp_send_json_success(array(
            'score' => $score,
            'max_score' => $max_score,
            'percentage' => round($percentage, 1),
            'message' => $message,
            'show_correct_answers' => $settings['show_correct_answers'],
            'user_answers' => $user_answers,
            'correct_answers' => $correct_answers,
        ));
    }
  
    /**
     * Quiz shortcode
     */
    
    
     public function quiz_shortcode($atts) {
        ob_start();
        
        // Set default attributes for the shortcode
        $atts = shortcode_atts(array(
            'id' => 0, // Quiz ID to be passed in the shortcode
        ), $atts, 'simple_quiz');
        
        // Ensure we have a valid quiz ID
        $quiz_id = intval($atts['id']);
        
        if ($quiz_id <= 0) {
            return '<p>' . __('Invalid quiz ID', 'simple-quiz-builder') . '</p>';
        }
        
        global $wpdb;
        
        // Get quiz data from the database
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}simple_quizzes WHERE id = %d", 
            $quiz_id
        ));
        
        // Check if the quiz exists
        if (!$quiz) {
            return '<p>' . __('Quiz not found', 'simple-quiz-builder') . '</p>';
        }
        
        // Get all questions for the quiz
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}simple_quiz_questions WHERE quiz_id = %d ORDER BY question_order ASC", 
            $quiz_id
        ));
        // Loop through each question and fetch options
        foreach ($questions as &$question) {
            $question->options = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}simple_quiz_options WHERE question_id = %d ORDER BY option_order ASC", 
                $question->id
            ));
        }
        
        $default_settings = array(
            'quiz_color_scheme' => 'white', 
        );
        $settings = get_option('simple_quiz_settings', $default_settings);
        ?>
        <div class="simple-quiz" style="background-color: white; border:1px solid black; ">
            <h3 style="text-align:center; margin-bottom:5px">Quiz:<?php echo esc_html($quiz->title); ?></h3>
            <p  style="text-align:center; margin-top:0" ><?php echo esc_html($quiz->description); ?></p>
            <hr/>
            <form id="simple-quiz-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
                <input type="hidden" name="action" value="submit_quiz">
                <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id); ?>">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('simple-quiz-nonce'); ?>">
        
                <?php foreach ($questions as $question): ?>
                    <div class="quiz-question">
                        <h3><?php echo esc_html($question->question_text); ?></h3>
                        <ul style="list-style:none; padding: 0; margin: 0;">
    <?php foreach ($question->options as $option): ?>
        <li style="margin-bottom: 10px;">
            <label style="display: block; cursor: pointer; padding: 10px; border: 2px solid #ccc; border-radius: 5px; transition: all 0.3s ease; 
                background-color: transparent; font-size: 14px; color: #333; margin: 5px 0;" 
                for="option_<?php echo esc_attr($option->id); ?>"
                id="label_<?php echo esc_attr($option->id); ?>"
                onclick="updateSelection('<?php echo esc_attr($option->id); ?>')">
                <input type="radio" name="question_<?php echo esc_attr($question->id); ?>" value="<?php echo esc_attr($option->id); ?>"
                    style="display:none;" id="option_<?php echo esc_attr($option->id); ?>">
                <span style="visibility: hidden;"></span> 
                <?php echo esc_html($option->option_text); ?>
            </label>
        </li>
    <?php endforeach; ?>
</ul>

<script>
    // JavaScript to change the box's border color and background color when clicked
    function updateSelection(optionId) {
        const labels = document.querySelectorAll('label');
        labels.forEach(label => {
            label.style.borderColor = '#ccc'; // reset all borders
            label.style.backgroundColor = 'transparent'; // reset background
            label.style.color = '#000';
        });

        const selectedLabel = document.getElementById('label_' + optionId);
        selectedLabel.style.borderColor = 'blue'; // set selected border to blue
        selectedLabel.style.backgroundColor = 'blue'; // set background to blue
        selectedLabel.style.color = '#fff'; // optional: make text white when selected
    }
</script>

                    </div>
                <?php endforeach; ?>
                
                <button style=" display:inline-flex;padding:.375rem .75rem;font-size:1rem;font-weight:400;line-height:1.5;text-align:center;cursor:pointer;border-radius:.25rem;transition:background-color .3s ease,border-color .3s ease,color .3s ease;border:1px solid transparent;background-color:#007bff;color:#fff;border-color:#007bff;
    
    " type="submit" class="submit-quiz-btn"><?php _e('Submit Quiz', 'simple-quiz-builder'); ?></button>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
}

// Initialize the plugin
$simple_quiz_builder = new SimpleQuizBuilder();
add_action('init', array($simple_quiz_builder, 'init'));
add_action('admin_menu', array($simple_quiz_builder, 'add_admin_menu'));
add_action('admin_enqueue_scripts', array($simple_quiz_builder, 'admin_scripts'));
add_action('wp_enqueue_scripts', array($simple_quiz_builder, 'frontend_scripts'));