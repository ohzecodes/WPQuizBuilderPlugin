<?php
<?php echo 'Template is loading'; ?>
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ensure quiz and questions are passed
if (!isset($quiz) || empty($questions)) {
    echo '<p>' . __('No quiz data available.', 'simple-quiz-builder') . '</p>';
    return;
}
echo $question
?>

<div class="simple-quiz-container">
    <h2><?php echo esc_html($quiz->quiz_title); ?></h2> <!-- Replace with your quiz title field -->
    
    <form method="post" id="simple-quiz-form" class="simple-quiz-form">
        <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz->id); ?>">

        <?php foreach ($questions as $index => $question) : ?>
            <div class="quiz-question">
                <h3><?php echo esc_html($question->question_text); ?></h3> <!-- Replace with your question text field -->
                <div class="quiz-options">
                    <?php foreach ($question->options as $option) : ?>
                        <label>
                            <input type="radio" name="question_<?php echo esc_attr($index); ?>" value="<?php echo esc_attr($option->id); ?>">
                            <?php echo esc_html($option->option_text); ?> <!-- Replace with your option text field -->
                        </label><br>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <input type="submit" value="<?php _e('Submit Quiz', 'simple-quiz-builder'); ?>" class="button">
    </form>
    
    <div id="quiz-result" style="display:none;"></div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#simple-quiz-form').on('submit', function(e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: formData + '&action=submit_quiz',
                success: function(response) {
                    $('#quiz-result').html(response.message).show();
                }
            });
        });
    });
</script>
