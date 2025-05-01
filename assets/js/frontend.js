jQuery(document).ready(function($) {
    // Quiz submission handler
    $('.simple-quiz-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var quizId = $form.data('quiz-id');
        var $submitButton = $form.find('button[type="submit"]');
        
        // Check if all questions are answered
        var allAnswered = true;
        var answers = {};
        
        $form.find('.quiz-question').each(function() {
            var questionId = $(this).data('question-id');
            var questionType = $(this).data('question-type');
            
            if (questionType === 'single') {
                var selected = $(this).find('input[type="radio"]:checked').val();
                
                if (!selected) {
                    allAnswered = false;
                    return false;
                }
                
                answers[questionId] = selected;
            } else if (questionType === 'multiple') {
                var selected = [];
                
                $(this).find('input[type="checkbox"]:checked').each(function() {
                    selected.push($(this).val());
                });
                
                if (selected.length === 0) {
                    allAnswered = false;
                    return false;
                }
                
                answers[questionId] = selected;
            }
        });
        
        if (!allAnswered) {
            alert(simple_quiz_obj.please_answer);
            return;
        }
        
        // Disable submit button
        $submitButton.prop('disabled', true).text('Submitting...');
        
        // Send AJAX request
        $.ajax({
            url: simple_quiz_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_quiz',
                nonce: simple_quiz_obj.nonce,
                quiz_id: quizId,
                answers: answers
            },
            success: function(response) {
                if (response.success) {
                    // Hide quiz form
                    $form.hide();
                    
                    // Show results
                    var $results = $form.siblings('.quiz-results');
                    
                    $results.find('.score-value').text(response.data.score);
                    $results.find('.max-score-value').text(response.data.max_score);
                    $results.find('.percentage-value').text(response.data.percentage + '%');
                    $results.find('.result-message').text(response.data.message);
                    
                    $results.show();
                    
                    // Show correct answers if enabled
                    if (response.data.show_correct_answers) {
                        var userAnswers = response.data.user_answers;
                        var correctAnswers = response.data.correct_answers;
                        
                        $('.quiz-question').each(function() {
                            var questionId = $(this).data('question-id');
                            var $options = $(this).find('.quiz-option');
                            
                            // Mark user's answers
                            $options.each(function() {
                                var optionId = $(this).data('option-id');
                                var userAnswer = userAnswers[questionId] || [];
                                
                                if (!Array.isArray(userAnswer)) {
                                    userAnswer = [userAnswer];
                                }
                                
                                if (userAnswer.includes(optionId.toString())) {
                                    $(this).addClass('user-selected');
                                }
                                
                                // Mark correct answers
                                if (correctAnswers[questionId].includes(optionId)) {
                                    $(this).addClass('correct-answer');
                                    
                                    // If user selected this and it's correct
                                    if (userAnswer.includes(optionId.toString())) {
                                        $(this).addClass('user-correct');
                                    }
                                } else {
                                    // If user selected this and it's incorrect
                                    if (userAnswer.includes(optionId.toString())) {
                                        $(this).addClass('user-incorrect');
                                    }
                                }
                            });
                            
                            // Add question result indicator
                            var allCorrect = true;
                            for (var i = 0; i < correctAnswers[questionId].length; i++) {
                                var correctId = correctAnswers[questionId][i];
                                if (!userAnswer.includes(correctId.toString())) {
                                    allCorrect = false;
                                    break;
                                }
                            }
                            
                            for (var i = 0; i < userAnswer.length; i++) {
                                if (!correctAnswers[questionId].includes(parseInt(userAnswer[i]))) {
                                    allCorrect = false;
                                    break;
                                }
                            }
                            
                            if (allCorrect) {
                                $(this).addClass('question-correct');
                                $(this).find('.question-result').html('<span class="dashicons dashicons-yes"></span> Correct');
                            } else {
                                $(this).addClass('question-incorrect');
                                $(this).find('.question-result').html('<span class="dashicons dashicons-no"></span> Incorrect');
                            }
                        });
                        
                        // Show the answers section
                        $('.quiz-answers').show();
                    }
                    
                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $results.offset().top - 50
                    }, 500);
                    
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while submitting the quiz.');
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false).text('Submit Quiz');
            }
        });
    });
    
    // Restart quiz button
    $(document).on('click', '.restart-quiz', function(e) {
        e.preventDefault();
        
        // Hide results
        $('.quiz-results').hide();
        $('.quiz-answers').hide();
        
        // Reset form
        var $form = $('.simple-quiz-form');
        $form.find('input[type="radio"], input[type="checkbox"]').prop('checked', false);
        $form.find('.quiz-question').removeClass('question-correct question-incorrect');
        $form.find('.quiz-option').removeClass('user-selected correct-answer user-correct user-incorrect');
        $form.find('.question-result').empty();
        
        // Show form
        $form.show();
        
        // Scroll to top of quiz
        $('html, body').animate({
            scrollTop: $form.offset().top - 50
        }, 500);
    });
});