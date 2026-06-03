<script>
    (function () {
        document.querySelectorAll('form[data-crm-stage-form]').forEach(function (form) {
            const stageSelect = form.querySelector('[data-crm-stage-select]');
            const fields = form.querySelector('[data-follow-up-fields]');
            const scheduledInput = form.querySelector('[name="follow_up_scheduled_at"]');
            if (!stageSelect || !fields) {
                return;
            }
            const followUpValue = stageSelect.dataset.followUpValue || 'follow_up';
            function refresh() {
                const isFollowUp = stageSelect.value === followUpValue;
                fields.classList.toggle('d-none', !isFollowUp);
                if (scheduledInput) {
                    scheduledInput.required = isFollowUp;
                }
            }
            stageSelect.addEventListener('change', refresh);
            refresh();
        });
    })();
</script>
