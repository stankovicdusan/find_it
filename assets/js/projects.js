$(document).ready(function() {
    let currentStep = $('.step.active').data('step');

    $(document).on('click', '.project-card', function (e) {
        $('.project-card').removeClass('active');

        $(this).addClass('active');

        let templateId = $(this).find('img').data('id');
        $('.project-template').val(templateId);

        $('.next').prop('disabled', false);
    });

    $(document).on('click', '.next', function (e) {
        e.preventDefault();

        let validationMessage = validateCreateProjectForm(currentStep);

        if (validationMessage != null) {
            var toastElement = new bootstrap.Toast($('#toast')[0]);

            $(".toast-body").text(validationMessage);
            toastElement.show();

            return;
        }

        processData(currentStep);

        if (currentStep < 3) {
            currentStep++;
            showStep(currentStep);
        }
    });

    $(document).on('click', '.prev', function () {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    $('input').on('input', function () {
        $('.form-error').hide();

        $('.next').prop('disabled', false);
    });

    function showStep(step) {
        // Hide all step contents and remove active class from steps
        $(".step-content").removeClass("active");
        $(".step").removeClass("active");

        // Show the current step content and set the current step as active
        $(`.step-content[data-step="${step}"]`).addClass("active");
        $(`.step[data-step="${step}"]`).addClass("active");

        // Toggle visibility of buttons based on step
        $(".prev").css("display", step === 1 ? "none" : "inline-block");
        $(".next").css("display", step === 3 ? "none" : "inline-block");
        $(".finish").css("display", step === 3 ? "inline-block" : "none");
    }

    function validateCreateProjectForm(currentStep) {
        let message = null;

        switch (currentStep) {
            case 1:
                if (!isProjectTemplateSelected()) {
                    $('.next').prop('disabled', true);
        
                    message = 'Please select a project template before proceeding!';
                }
                break;
            case 2:
                if (!validateProjectForm()) {
                    $('.next').prop('disabled', true);
                    $('.form-error').show();
        
                    message = 'Please fill all required fields!';
                }
                break;
        }

        return message;
    }

    function isProjectTemplateSelected() {
        return $(".project-card.active").length > 0;
    }

    function validateProjectForm() {
        let isValid = true;

        let projectName = $('.name').val().trim();
        let projectKey = $('.key').val().trim();

        if (!projectName) {
            $('.name-error').text('Name field is mandatory.');
            isValid = false;
        } else {
            $('.name-error').text('');
        }

        if (!projectKey) {
            $('.key-error').text('Key field is mandatory.');
            isValid = false;
        } else {
            if (validateKeyUniqueness(projectKey)) {
                $('.key-error').text('Key value should be unique.');
                isValid = false;
            } else {
                $('.key-error').text('');
            }
        }

        return isValid;
    }

    function processData(currentStep) {
        switch (currentStep) {
            case 1:
                let templateImageSrc = $('.project-card.active').find('img').attr('src');
                let templateValue = $('.project-card.active').find('img').data('type');

                $('.template-icon').find('img').attr('src', templateImageSrc);
                $('.template-details').find('p').html(templateValue);

                break;
            case 2:
                let projectName = $('.name').val();
                let projectKey = $('.key').val();

                $('.project-name').find('span').html(projectName);
                $('.project-key').find('span').html(projectKey);

                break;
        }
    }

    function validateKeyUniqueness(key) {
        let isUnique = true;
        let url = $('.key').data('validate-url');

        $.ajax({
            method: 'post',
            url: url,
            data: {
                key: key
            },
            async: false,
            success: function (response) {
                isUnique = response.data;
            },
            error: function (xhr) {
                console.log(xhr);
            }
        });

        return isUnique;
    }

    showStep(currentStep);
});