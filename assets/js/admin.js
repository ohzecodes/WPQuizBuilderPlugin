jQuery(document).ready(function($) {
    // Question type handler
    $(document).on('change', '.question-type-select', function() {
        var questionType = $(this).val();
        var questionBox = $(this).closest('.question-box');
        
        if (questionType === 'single') {
            questionBox.find('.question-type-help').text('Select one correct answer below (radio buttons)');
            
            // Convert checkboxes to radio buttons
            questionBox.find('.option-correct').each(function() {
                var optionId = $(this).attr('id');
                var isChecked = $(this).prop('checked');
                
                $(this).replaceWith(
                    $('<input>')
                        .attr('type', 'radio')
                        .attr('id', optionId)
                        .attr('name', 'option_correct_' + questionBox.data('question-index'))
                        .addClass('option-correct')
                        .prop('checked', isChecked)
                );
            });
        } else if (questionType === 'multiple') {
            questionBox.find('.question-type-help').text('Select one or more correct answers below (checkboxes)');
            
            // Convert radio buttons to checkboxes
            questionBox.find('.option-correct').each(function() {
                var optionId = $(this).attr('id');
                var isChecked = $(this).prop('checked');
                
                $(this).replaceWith(
                    $('<input>')
                        .attr('type', 'checkbox')
                        .attr('id', optionId)
                        .addClass('option-correct')
                        .prop('checked', isChecked)
                );
            });
        }
    });
    
    // Add question button
    $('#add-question').on('click', function() {
        var questionIndex = $('.question-box').length;
        
        var questionTemplate = `
            <div class="question-box" data-question-index="${questionIndex}">
                <div class="question-header">
                    <h3>Question ${questionIndex + 1}</h3>
                    <button type="button" class="button remove-question">Remove</button>
                </div>
                <div class="question-body">
                    <div class="form-field">
                        <label for="question_text_${questionIndex}">Question Text:</label>
                        <textarea id="question_text_${questionIndex}" name="question_text_${questionIndex}" class="question-text" rows="2" required></textarea>
                    </div>
                    <div class="form-field">
                        <label for="question_type_${questionIndex}">Question Type:</label>
                        <select id="question_type_${questionIndex}" name="question_type_${questionIndex}" class="question-type-select">
                            <option value="single">Single Choice</option>
                            <option value="multiple">Multiple Choice</option>
                        </select>
                        <p class="question-type-help">Select one correct answer below (radio buttons)</p>
                    </div>
                    <div class="options-container">
                        <h4>Answer Options</h4>
                        <div class="option-list"></div>
                        <button type="button" class="button add-option">Add Option</button>
                    </div>
                </div>
            </div>
        `;
        
        $('#questions-container').append(questionTemplate);
        
        // Add first two options to the new question
        var $newQuestion = $('.question-box').last();
        addOption($newQuestion, 0);
        addOption($newQuestion, 1);
    });
    
    // Add option button
    $(document).on('click', '.add-option', function() {
        var $questionBox = $(this).closest('.question-box');
        var optionIndex = $questionBox.find('.option-item').length;
        
        addOption($questionBox, optionIndex);
    });
    
    // Remove option button
    $(document).on('click', '.remove-option', function() {
        var $optionItem = $(this).closest('.option-item');
        var $questionBox = $optionItem.closest('.question-box');
        
        $optionItem.remove();
        
        // Update option numbers
        $questionBox.find('.option-item').each(function(index) {
            $(this).find('.option-number').text(index + 1);
        });
    });
    
    // Remove question button
    $(document).on('click', '.remove-question', function() {
        var $questionBox = $(this).closest('.question-box');
        
        $questionBox.remove();
        
        // Update question numbers
        $('.question-box').each(function(index) {
            $(this).attr('data-question-index', index);
            $(this).find('.question-header h3').text('Question ' + (index + 1));
        });
    });
    
    // Function to add a new option to a question
    function addOption($questionBox, optionIndex) {
        var questionIndex = $questionBox.data('question-index');
        var questionType = $questionBox.find('.question-type-select').val();
        var inputType = (questionType === 'single') ? 'radio' : 'checkbox';
        
        var optionTemplate = `
            <div class="option-item">
                <span class="option-number">${optionIndex + 1}</span>
                <input type="text" class="option-text" placeholder="Option text" required>
                <label class="correct-label">
                    <input type="${inputType}" id="option_correct_${questionIndex}_${optionIndex}" class="option-correct" ${inputType === 'radio' ? 'name="option_correct_' + questionIndex + '"' : ''}>
                    Correct
                </label>
                <button type="button" class="button remove-option">Remove</button>
            </div>
        `;
        
        $questionBox.find('.option-list').append(optionTemplate);
    }
    
    // Save quiz
    $('#quiz-form').on('submit', function(e) {
        e.preventDefault();
        
        var quizId = $('#quiz_id').val();
        var title = $('#quiz_title').val();
        var description = $('#quiz_description').val();
        
        if (!title) {
            alert('Please enter a quiz title');
            return;
        }
        
        var questions = [];
        
        $('.question-box').each(function() {
            var $question = $(this);
            var questionText = $question.find('.question-text').val();
            var questionType = $question.find('.question-type-select').val();
            
            if (!questionText) {
                return;
            }
            
            var options = [];
            
            $question.find('.option-item').each(function() {
                var $option = $(this);
                var optionText = $option.find('.option-text').val();
                var isCorrect = $option.find('.option-correct').prop('checked');
                
                if (!optionText) {
                    return;
                }
                
                options.push({
                    text: optionText,
                    correct: isCorrect
                });
            });
            
            questions.push({
                text: questionText,
                type: questionType,
                options: options
            });
        });
        
        if (questions.length === 0) {
            alert('Please add at least one question');
            return;
        }
        
        // Disable the submit button
        $('#quiz-form button[type="submit"]').prop('disabled', true).text('Saving...');
        
        // Send AJAX request
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
                if (response.success) {
                    // Show success message
                    $('#message').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // If this is a new quiz, update the quiz ID and show shortcode
                    if (!quizId) {
                        $('#quiz_id').val(response.data.quiz_id);
                        $('#shortcode-display').html('<p>Use this shortcode to display the quiz: <code>' + response.data.shortcode + '</code></p>').show();
                    }
                    
                    // Redirect to quiz list after delay if this is a new quiz
                    if (!quizId) {
                        setTimeout(function() {
                            window.location.href = 'admin.php?page=simple-quiz-builder&edit=' + response.data.quiz_id;
                        }, 1000);
                    }
                } else {
                    // Show error message
                    $('#message').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                // Show error message
                $('#message').html('<div class="notice notice-error"><p>An error occurred while saving the quiz.</p></div>');
            },
            complete: function() {
                // Re-enable the submit button
                $('#quiz-form button[type="submit"]').prop('disabled', false).text('Save Quiz');
                
                // Scroll to top for message visibility
                $('html, body').animate({ scrollTop: 0 }, 'slow');
            }
        });
    });
    
    // Delete quiz button
    $(document).on('click', '.delete-quiz', function(e) {
        e.preventDefault();
        
        var quizId = $(this).data('quiz-id');
        
        if (!quizId) {
            return;
        }
        
        if (!confirm(simple_quiz_obj.confirm_delete)) {
            return;
        }
        
        // Disable the button
        $(this).prop('disabled', true).text('Deleting...');
        
        // Send AJAX request
        $.ajax({
            url: simple_quiz_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_quiz',
                nonce: simple_quiz_obj.nonce,
                quiz_id: quizId
            },
            success: function(response) {
                if (response.success) {
                    // Remove the quiz row
                    $('#quiz-row-' + quizId).fadeOut('slow', function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while deleting the quiz.');
            }
        });
    });
    
    // Initialize sortable for options
    $('.option-list').sortable({
        handle: '.option-number',
        update: function() {
            // Update option numbers
            $(this).find('.option-item').each(function(index) {
                $(this).find('.option-number').text(index + 1);
            });
        }
    });
    
    // Initialize sortable for questions
    $('#questions-container').sortable({
        handle: '.question-header',
        update: function() {
            // Update question numbers
            $('.question-box').each(function(index) {
                $(this).attr('data-question-index', index);
                $(this).find('.question-header h3').text('Question ' + (index + 1));
            });
        }
    });
    
    // Add initial question if form is empty
    if ($('.question-box').length === 0 && $('#add-question').length > 0) {
        $('#add-question').click();
    }
});