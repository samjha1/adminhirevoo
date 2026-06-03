<script>
    (function () {
        document.querySelectorAll('form[data-kanban-stage-form]').forEach(function (form) {
            const select = form.querySelector('[data-kanban-stage-select]');
            if (!select) {
                return;
            }
            const followUpValue = select.dataset.followUpValue || 'follow_up';
            const meetingValue = select.dataset.meetingValue || 'meeting_scheduled';
            const interviewValue = select.dataset.interviewValue || 'interview';
            const detailUrl = form.dataset.detailUrl;
            const previous = select.value;
            const needsDetail = [followUpValue, meetingValue, interviewValue];

            select.addEventListener('change', function () {
                if (needsDetail.includes(select.value) && detailUrl) {
                    select.value = previous;
                    window.location = detailUrl;
                    return;
                }
                form.submit();
            });
        });
    })();
</script>
