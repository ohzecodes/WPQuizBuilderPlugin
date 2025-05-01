<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Quizzes', 'simple-quiz-builder'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=simple-quiz-builder-new'); ?>" class="page-title-action"><?php _e('Add New', 'simple-quiz-builder'); ?></a>
    
    <hr class="wp-header-end">
    
    <?php if (empty($quizzes)): ?>
        <div class="notice notice-info">
            <p><?php _e('No quizzes found. Click the "Add New" button to create your first quiz!', 'simple-quiz-builder'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'simple-quiz-builder'); ?></th>
                    <th><?php _e('Title', 'simple-quiz-builder'); ?></th>
                    <th><?php _e('Shortcode', 'simple-quiz-builder'); ?></th>
                    <th><?php _e('Questions', 'simple-quiz-builder'); ?></th>
                    <th><?php _e('Created', 'simple-quiz-builder'); ?></th>
                    <th><?php _e('Actions', 'simple-quiz-builder'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): 
                    // Get question count for this quiz
                    $question_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}simple_quiz_questions WHERE quiz_id = %d", $quiz->id));
                ?>
                <tr>
                    <td><?php echo $quiz->id; ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=simple-quiz-builder&edit=' . $quiz->id); ?>">
                            <strong><?php echo esc_html($quiz->title); ?></strong>
                        </a>
                    </td>
                    <td>
                        <input type="text" value="[simple_quiz id=&quot;<?php echo $quiz->id; ?>&quot;]" readonly onclick="this.select();" style="width: 200px;">
                    </td>
                    <td><?php echo $question_count; ?></td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($quiz->created_at)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=simple-quiz-builder&edit=' . $quiz->id); ?>" class="button button-small"><?php _e('Edit', 'simple-quiz-builder'); ?></a>
                        <a href="<?php echo admin_url('admin.php?page=simple-quiz-builder-results&quiz_id=' . $quiz->id); ?>" class="button button-small"><?php _e('Results', 'simple-quiz-builder'); ?></a>
                        <a href="#" class="button button-small delete-quiz" data-id="<?php echo $quiz->id; ?>"><?php _e('Delete', 'simple-quiz-builder'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Delete quiz
    $('.delete-quiz').on('click', function(e) {
        e.preventDefault();
        
        if (confirm(simple_quiz_obj.confirm_delete)) {
            var quiz_id = $(this).data('id');
            
            $.ajax({
                url: simple_quiz_obj.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_quiz',
                    quiz_id: quiz_id,
                    nonce: simple_quiz_obj.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
});
</script>