<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php _e('Add New Quiz', 'simple-quiz-builder'); ?></h1>
    
    <div id="quiz-builder-container">
        <div id="quiz-builder-form">
            <div class="quiz-meta">
                <label for="quiz-title"><?php _e('Quiz Title', 'simple-quiz-builder'); ?></label>
                <input type="text" id="quiz-title" placeholder="<?php _e('Enter quiz title', 'simple-quiz-builder'); ?>" class="regular-text">
                
                <label for="quiz-description"><?php _e('Description (Optional)', 'simple-quiz-builder'); ?></label>
                <textarea id="quiz-description" placeholder="<?php _e('Enter quiz description', 'simple-quiz-builder'); ?>" rows="3" class="large-text"></textarea>
            </div>
            
            <h2><?php _e('Questions', 'simple-quiz-builder'); ?></h2>
            
            <div id="quiz-questions"></div>
            
            <button type="button" class="button button-secondary add-question">
                <span class="dashicons dashicons-plus" style="vertical-align: text-top;"></span> 
                <?php _e('Add Question', 'simple-quiz-builder'); ?>
            </button>
            
            <div class="submit-buttons">
                <button type="button" class="button button-primary save-quiz"><?php _e('Save Quiz', 'simple-quiz-builder'); ?></button>
                <span class="spinner"></span>
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
    var questionCount = 0;
    
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
                title: title,
                description: description,
                questions: questions
            },
            success: function(response) {
                $('.spinner').removeClass('is-active');
                
                if (response.success) {
                    // Redirect to quiz list
                    window.location.href = '<?php echo admin_url('admin.php?page=simple-quiz-builder'); ?>';
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Add a default question to start
    addQuestion();
    
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