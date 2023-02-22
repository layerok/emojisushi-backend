/*
 * Checkbox control
 */

(function($) {

    //
    // Intermediate checkboxes
    //

    $(document).render(function() {
        $('.form-check.is-indeterminate > input').each(function() {
            var $el = $(this),
                checked = $el.data('checked');

            switch (checked) {

                // Unchecked
                case 1:
                    $el.prop('indeterminate', true);
                    break;

                // Checked
                case 2:
                    $el.prop('indeterminate', false);
                    $el.prop('checked', true);
                    break;

                // Unchecked
                default:
                    $el.prop('indeterminate', false);
                    $el.prop('checked', false);
            }
        })
    })

    $(document).on('click', '.form-check.is-indeterminate > input', function() {
        var $el = $(this),
            checked = $el.data('checked');

        if (checked === undefined) {
            checked = $el.is(':checked') ? 1 : 0;
        }

        switch (checked) {

            // Unchecked, going indeterminate
            case 0:
                $el.data('checked', 1);
                $el.prop('indeterminate', true);
                break;

            // Indeterminate, going checked
            case 1:
                $el.data('checked', 2);
                $el.prop('indeterminate', false);
                $el.prop('checked', true);
                break;

            // Checked, going unchecked
            default:
                $el.data('checked', 0);
                $el.prop('indeterminate', false);
                $el.prop('checked', false);
        }
    });

})(jQuery);
