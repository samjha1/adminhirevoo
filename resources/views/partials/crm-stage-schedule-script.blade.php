<script>
    (function () {
        document.querySelectorAll('form[data-crm-stage-form]').forEach(function (form) {
            const stageSelect = form.querySelector('[data-crm-stage-select]');
            const followUpFields = form.querySelector('[data-follow-up-fields]');
            const meetingFields = form.querySelector('[data-meeting-fields]');
            const followUpInput = form.querySelector('[name="follow_up_scheduled_at"]');
            const meetingInput = form.querySelector('[name="meeting_scheduled_at"]');
            if (!stageSelect) {
                return;
            }
            const followUpValue = stageSelect.dataset.followUpValue || 'follow_up';
            const meetingValue = stageSelect.dataset.meetingValue || 'meeting_scheduled';
            const interviewValue = stageSelect.dataset.interviewValue || 'interview';

            function refresh() {
                const isFollowUp = stageSelect.value === followUpValue
                    || stageSelect.value === interviewValue;
                const isMeeting = stageSelect.value === meetingValue;

                if (followUpFields) {
                    followUpFields.classList.toggle('d-none', !isFollowUp);
                }
                if (meetingFields) {
                    meetingFields.classList.toggle('d-none', !isMeeting);
                }
                if (followUpInput) {
                    followUpInput.required = isFollowUp;
                }
                if (meetingInput) {
                    meetingInput.required = isMeeting;
                }
            }

            stageSelect.addEventListener('change', refresh);
            refresh();
        });
    })();
</script>
