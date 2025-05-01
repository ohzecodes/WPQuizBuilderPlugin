<?php
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
    
    // Load plugin settings (color scheme or other options if needed)
    $default_settings = array(
        'quiz_color_scheme' => 'blue', // Default color scheme
    );
    $settings = get_option('simple_quiz_settings', $default_settings);
    
    // Output the quiz
    ?>
    <div class="simple-quiz" style="background-color: <?php echo esc_attr($settings['quiz_color_scheme']); ?>;">
        <h2><?php echo esc_html($quiz->title); ?></h2>
        <p><?php echo esc_html($quiz->description); ?></p>
        
        <form id="simple-quiz-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
            <input type="hidden" name="action" value="submit_quiz">
            <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id); ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('simple-quiz-nonce'); ?>">
    
            <?php foreach ($questions as $question): ?>
                <div class="quiz-question">
                    <h3><?php echo esc_html($question->question_text); ?></h3>
                    <ul>
                        <?php foreach ($question->options as $option): ?>
                            <li>
                                <label>
                                    <input type="radio" name="question_<?php echo esc_attr($question->id); ?>" value="<?php echo esc_attr($option->id); ?>">
                                    <?php echo esc_html($option->option_text); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="submit-quiz-btn"><?php _e('Submit Quiz', 'simple-quiz-builder'); ?></button>
        </form>
    </div>
    <?php
    
    return ob_get_clean();
}
