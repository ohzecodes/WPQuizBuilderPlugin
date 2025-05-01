<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php _e('Edit Quiz', 'simple-quiz-builder'); ?></h1>
    
    <div id="quiz-builder-container">
        <div id="quiz-builder-form">
            <div class="quiz-meta">
                <label for="quiz-title"><?php _e('Quiz Title', 'simple-quiz-builder'); ?></label>
                <input type="text" id="quiz-title" value="<?php echo esc_attr($quiz->title); ?>" class="regular-text">
                
                <label for="quiz-description"><?php _e('Description (Optional)', 'simple-quiz-builder'); ?></label>
                <textarea id="quiz-description" rows="3" class="large-text"><?php echo esc_textarea($quiz->description); ?></textarea>
            </div>
            
            <h2><?php _e('Questions', 'simple-quiz-builder'); ?></h2>
            
            <div id="quiz-questions">
                <?php foreach ($questions as $q_index => $question): ?>
                <div class="question-item" data-index="<?php echo $q_index; ?>">
                    <div class="question-header">
                        <span class="question-number">#<?php echo ($q_index + 1); ?></span>
                        <span class="question-controls">
                            <span class="dashicons dashicons-move"></span>
                            <span class="dashicons dashicons-trash delete-question"></span>
                        </span>
                    </div>
                    
                    <div class="question-content">
                        <div class="question-text">
                            <label><?php _e('Question', 'simple-quiz-builder'); ?></label>
                            <textarea class="large-text question-text-input" rows="2"><?php echo esc_textarea($question->question_text); ?></textarea>
                        </div>
                        
                        <div class="question-type">
                            <label><?php _e('Question Type', 'simple-quiz-builder'); ?></label>
                            <select class="question-type-select">
                                <option value="single" <?php selected($question->question_type, 'single'); ?>><?php _e('Single Choice (Radio)', 'simple-quiz-builder'); ?></option>
                                <option value="multiple" <?php selected($question->question_type, 'multiple'); ?>><?php _e('Multiple Choice (Checkbox)', 'simple-quiz-builder'); ?></option>
                            </select>
                        </div>
                        
                        <div class="question-options">
                            <label><?php _e('Options', 'simple-quiz-builder'); ?></label>
                            <div class="options-list">
                                <?php foreach ($question->options as $o_index => $option): 
                                    $input_type = ($question->question_type == 'multiple') ? 'checkbox' : 'radio';
                                ?>
                                <div class="option-item" data-index="<?php echo $o_index; ?>">
                                    <span class="dashicons dashicons-menu option-drag"></span>
                                    <input type="text" value="<?php echo esc_attr($option->option_text); ?>" class="option-text">
                                    <label class="correct-option">
                                        <input type="<?php echo $input_type; ?>" class="option-correct" name="question_<?php echo $q_index; ?>_correct" <?php checked($option->is_correct, 1); ?>> 
                                        <?php _e('Correct', 'simple-quiz-builder'); ?>
                                    </label>
                                    <span class="dashicons dashicons-trash delete-option"></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="button button-small add-option">
                                <span class="dashicons dashicons-plus" style="vertical-align: text-top;"></span> 
                                <?php _e('Add Option', 'simple-quiz-builder'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="button button-secondary add-question">
                <span class="dashicons dashicons-plus" style="vertical-align: text-top;"></span> 
                <?php _e('Add Question', 'simple-quiz-builder'); ?>
            </button>
            
            <div class="submit-buttons">
                <button type="button" class="button button-primary save-quiz" data-id="<?php echo $quiz->id; ?>"><?php _e('Update Quiz', 'simple-quiz-builder'); ?></button>
                <span class="spinner"></span>
            </div>
            
            <div class="shortcode-info">
                <h3><?php _e('Shortcode', 'simple-quiz-builder'); ?></h3>
                <p><?php _e('Use this shortcode to display the quiz in your posts or pages:', 'simple-quiz-builder'); ?></p>
                <input type="text" value="[simple_quiz id=&quot;<?php echo $quiz->id; ?>&quot;]" readonly onclick="this.select();" class="large-text">
            </div>
        </div>
    </div>
</div>

<!-- Templates -->
<script type="text/template" id="question-template">
    <div class="question-item" data-index="{index}">
        <div class="question-header">
            <span class="question-number">#{number}</span>
            <span class="question-controls">
                <span class="dashicons dashicons-move"></span>
                <span class="dashicons dashicons-trash delete-question"></span>
            </span>
        </div>
        
        <div class="question-content">
            <div class="question-text">
                <label><?php _e('Question', 'simple-quiz-builder'); ?></label>
                <textarea placeholder="<?php _e('Enter your question', 'simple-quiz-builder'); ?>" rows="2" class="large-text question-text-input"></textarea>
            </div>
            
            <div class="question-type">
                <label><?php _e('Question Type', 'simple-quiz-builder'); ?></label>
                <select class="question-type-select">
                    <option value="single"><?php _e('Single Choice (Radio)', 'simple-quiz-builder'); ?></option>
                    <option value="multiple"><?php _e('Multiple Choice (Checkbox)', 'simple-quiz-builder'); ?></option>
                </select>
            </div>
            
            <div class="question-options">
                <label><?php _e('Options', 'simple-quiz-builder'); ?></label>
                <div class="options-list"></div>
                <button type="button" class="button button-small add-option">
                    <span class="dashicons dashicons-plus" style="vertical-align: text-top;"></span> 
                    <?php _e('Add Option', 'simple-quiz-builder'); ?>
                </button>
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="option-template">
    <div class="option-item" data-index="{index}">
        <span class="dashicons dashicons-menu option-drag"></span>
        <input type="text" placeholder="<?php _e('Option text', 'simple-quiz-builder'); ?>" class="option-text">
        <label class="correct-option">
            <input type="{input_type}" class="option-correct" name="question_{q_index}_correct"> 
            <?php _e('Correct', 'simple-quiz-builder'); ?>
        </label>
        <span class="dashicons dashicons-trash delete-option"></span>
    </div>
</script>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var questionCount = <?php echo count($questions); ?>;
    
    // Add question
    $('.add-question').on('click', function() {
        addQuestion();
    });
    
    // Delete question
    $(document).on('click', '.delete-question', function() {
        $(this).closest('.question-item').remove();
        renumberQuestions();
    });
    
    // Add option
    $(document).on('click', '.add-option', function() {
        var questionItem = $(this).closest('.question-item');
        var questionIndex = questionItem.data('index');
        var optionsList = questionItem.find('.options-list');
        var optionCount = optionsList.find('.option-item').length;
        var inputType = questionItem.find('.question-type-select').val() === 'multiple' ? 'checkbox' : 'radio';
        
        addOption(optionsList, questionIndex, optionCount, inputType);
    });
    
    // Delete option
    $(document).on('click', '.delete-option', function() {
        $(this).closest('.option-item').remove();
    });
    
    // Change question type
    $(document).on('change', '.question-type-select', function() {
        var questionItem = $(this).closest('.question-item');
        var inputType = $(this).val() === 'multiple' ? 'checkbox' : 'radio';
        
        questionItem.find('.option-correct').each(function() {
            $(this).attr('type', inputType);
        });
    });
    
    // Save quiz
    $('.save-quiz').on('click', function() {
        var quizId = $(this).data('id');
        var title = $('#quiz-title').val();
        var description = $('#quiz-description').val();
        
        if (!title) {
            alert('<?php _e('Please enter a quiz title', 'simple-quiz-builder'); ?>');
            return;
        }
        
        var questions = [];
        
        $('.question-item').each(function() {
            var $question = $(this);
            var questionText = $question.find('.question-text-input').val();
            
            if (!questionText) return;
            
            var questionType = $question.find('.question-type-select').val();
            var options = [];
            
            $question.find('.option-item').each(function() {
                var $option = $(this);
                var optionText = $option.find('.option-text').val();
                
                if (!optionText) return;
                
                options.push({
                    text: optionText,
                    correct: $option.find('.option-correct').prop('checked')
                });
            });
            
            if (options.length < 2) return;
            
            questions.push({
                text: questionText,
                type: questionType,
                options: options
            });
        });
        
        if (questions.length === 0) {
            alert('<?php _e('Please add at least one question with at least two options', 'simple-quiz-builder'); ?>');
            return;
        }
        
        // Show spinner
        $('.spinner').addClass('is-active');
        
        // Save quiz via AJAX
        $.ajax({
            url: simple_quiz_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'save_quiz',
                nonce: simple_quiz_obj.nonce,
                quiz_id: quizId,
                title: title,
                description: description,
                questions: questions
            },
            success: function(response) {
                $('.spinner').removeClass('is-active');
                
                if (response.success) {
                    alert('<?php _e('Quiz updated successfully!', 'simple-quiz-builder'); ?>');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Functions
    function addQuestion() {
        var template = $('#question-template').html();
        var questionNumber = questionCount + 1;
        
        template = template.replace(/{index}/g, questionCount)
                          .replace(/{number}/g, questionNumber);
        
        $('#quiz-questions').append(template);
        
        var newQuestion = $('#quiz-questions .question-item').last();
        
        // Add two default options
        var optionsList = newQuestion.find('.options-list');
        var inputType = 'radio'; // Default is single choice
        
        addOption(optionsList, questionCount, 0, inputType);
        addOption(optionsList, questionCount, 1, inputType);
        
        questionCount++;
    }
    
    function addOption(optionsList, questionIndex, optionIndex, inputType) {
        var template = $('#option-template').html();
        
        template = template.replace(/{index}/g, optionIndex)
                          .replace(/{q_index}/g, questionIndex)
                          .replace(/{input_type}/g, inputType);
        
        optionsList.append(template);
    }
    
    function renumberQuestions() {
        $('.question-item').each(function(index) {
            $(this).find('.question-number').text('#' + (index + 1));
        });
    }
    
    // Make questions sortable
    $('#quiz-questions').sortable({
        handle: '.dashicons-move',
        update: renumberQuestions
    });
    
    // Make options sortable
    $('.options-list').sortable({
        handle: '.option-drag'
    });
});
</script>