<script>
/**
 * Jquery Ajax Setup
 **/
$.ajaxSetup({
    headers: {
        'X-CSRF-Token': "{{ csrf_token() }}",   
    }
});

/**
 * Flash Message
 * */
function flashMessage(data) {
    let alert = '';
    let message = '';
    if (data.responseJSON) {
        alert = @json(errorFlashMessage());
        message = data.responseJSON.message;
    } else if(data.message) {
        if (data.status == 'success') {
            alert = @json(successFlashMessage());
            message = data.message;
        } else if (data.status == 'error') {
            alert = @json(errorFlashMessage());
            message = data.message;
        }
    } else {
        alert = @json(errorFlashMessage());
        message = 'Oops! Something went wrong. Please try again later';
    }
    $('div#main').prepend(alert);
    $('div.alert strong').html(message);
    scroll(0,0);  
    setTimeout(() => {
        $('div.alert').remove();
        if (data.redirectTo) location.href = data.redirectTo;
    }, 5000);
}

/**
 * Initiate select2
 **/
$('.select2').each(function() {
    $(this).css('width', '100%').select2({allowClear: true});
});

$(() => {
    /**
     * Global delete via form handler
     **/
    $(document).on('click', '.destroy', function() {
        if ($(this).children('form').length && confirm('Are you sure?')) {
            $(this).children('form').submit();
        }
    });

    /**
     * Persist Bootstrap tab on page refresh
    **/
    $('.nav-tabs .nav-link').on('show.bs.tab', function() {
        localStorage['tabIndex'] = $(this).attr('data-bs-target');
    });
    const tabIndex = localStorage['tabIndex'];
    const activeTab = $('.nav-item button[data-bs-target="'+ tabIndex +'"]');
    if (tabIndex && activeTab.length) activeTab.click(); 
    else $('.nav-tabs .nav-link:first').click();


    /**
     * Quill RichText Editor
     **/
    $('div.richtext').each(function() {
        const editor = new Quill('#'+ $(this).attr('id'), {theme: 'snow'});
    });
    $('div.richtext').on('keyup', function() {
        let inputId = $(this).attr('id').split('_')[0];
        let value = $(this).find('.ql-editor').html();
        if (value == '<p><br></p>') value = '';
        $('#'+inputId).val(value).keyup();
    });
});
</script>