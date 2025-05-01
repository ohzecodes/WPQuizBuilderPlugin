<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Quiz Settings', 'simple-quiz-builder'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('simple_quiz_settings', 'simple_quiz_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Result Display', 'simple-quiz-builder'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="show_results" value="1" <?php checked($settings['show_results'], 1); ?>>
                        <?php _e('Show quiz results after submission', 'simple-quiz-builder'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Correct Answers', 'simple-quiz-builder'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="show_correct_answers" value="1" <?php checked($settings['show_correct_answers'], 1); ?>>
                        <?php _e('Show correct answers after submission', 'simple-quiz-builder'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Result Messages', 'simple-quiz-builder'); ?></th>
                <td>
                    <p>
                        <label>
                            <strong><?php _e('Perfect Score (100%)', 'simple-quiz-builder'); ?></strong><br>
                            <textarea name="result_message_perfect" rows="2" class="large-text"><?php echo esc_textarea($settings['result_message_perfect']); ?></textarea>
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <strong><?php _e('Good Score (75-99%)', 'simple-quiz-builder'); ?></strong><br>
                            <textarea name="result_message_good" rows="2" class="large-text"><?php echo esc_textarea($settings['result_message_good']); ?></textarea>
                        </label>
                    </p>
                    
                    <p>
                        <label>
                            <strong><?php _e('Average Score (50-74%)', 'simple-quiz-builder'); ?></strong><br>
                            <textarea name="result_message_average" rows="2" class="large-text"><?php echo esc_textarea($settings['result_message_average']); ?></textarea>
                        </label>
                    </p>

                    <p>
                        <label>
                            <strong><?php _e('Poor Score (0-49%)', 'simple-quiz-builder'); ?></strong><br>
                            <textarea name="result_message_poor" rows="2" class="large-text"><?php echo esc_textarea($settings['result_message_poor']); ?></textarea>
                        </label>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Save Settings', 'simple-quiz-builder')); ?>
    </form>
</div>
