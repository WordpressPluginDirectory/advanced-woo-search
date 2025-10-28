jQuery(document).ready(function ($) {
    'use strict';

    // Tooltips
    function awsInitTipTip() {

        $( '.aws-tip' ).tipTip( {
            'attribute': 'data-tip',
            'fadeIn': 50,
            'fadeOut': 50,
            'delay': 50,
        } );

    }

    awsInitTipTip();

    // Options dependencies toggler
    $(document).on( 'change', '#aws_form [data-dependencies] input, #aws_form [data-dependencies] select', function ( e ) {

        var $currentTable = $(this).closest('table');
        var option_name = $(this).closest('[data-option]').data('option');
        var dependencies = $(this).closest('[data-dependencies]').data('dependencies');
        var newValue = $(this).val();

        if ( $(this).hasClass('aws-toggler') ) {
            var newValue = $(this).is(':checked') ? 'true' : 'false';
        }

        if ( dependencies && typeof dependencies === 'object' ) {

            var optionsToHide = dependencies;
            if ( dependencies.hasOwnProperty(newValue) ) {

                $.each(dependencies[newValue], function(index, value) {
                    $currentTable.find('[data-option="'+ value +'"]').show().find('.aws-row-name').addClass('aws-opt-highlight');
                });

                optionsToHide = Object.fromEntries(
                    Object.entries(dependencies).filter(([key]) => key !== newValue)
                );

                setTimeout(function() {
                    $currentTable.find('.aws-opt-highlight').removeClass('aws-opt-highlight');
                }, 700);

                //aws_init_select2();

            }

            $.each(optionsToHide, function(index, value) {
                $.each(value, function(i, opt_to_hide) {
                    $currentTable.find('[data-option="'+ opt_to_hide +'"]').hide();
                });
            });

        }

    } );

    var $reindexBlock = $('#aws-reindex');
    var $reindexBtn = $('#aws-reindex .button');
    var $reindexProgress = $('#aws-reindex .reindex-progress');
    var $reindexCount = $('#aws-reindex-count strong');
    var syncStatus;
    var processed;
    var toProcess;
    var syncData = false;

    var $clearCacheBtn = $('#aws-clear-cache .button');


    // If search field is not in index - ask and add it
    $(document).on('change', '.aws-table-sources-item .aws-name input[name*="search_in"][name*="[value]"]', function(e) {
        if ( $(this).closest('.aws-name').find('[data-index-disabled]').length > 0 ) {
            if ( confirm( aws_vars.index_text ) ) {
                // ajax to enable index
                enableIndexField( $(this).data('field') );
                $(this).closest('.aws-name').find('[data-index-disabled]').remove();
            } else {
                $(this).prop('checked', false);
            }
        }
    });

    // If index disabled - disable appropriate search source
    $(document).on('change', '.aws-table-sources-item .aws-name input[name*="index_sources"][name*="[value]"]', function(e) {
        if ( ! $(this).is(':checked') ) {
            if ( confirm( aws_vars.index_disable_text ) ) {
                disableIndexField( $(this).data('field') );
            } else {
                $(this).prop('checked', true);
            }
        }
    });

    // enable needed index fields
    function enableIndexField( field, subField ) {

        var data = {
            action: 'aws-indexEnable',
            field: field,
            _ajax_nonce: aws_vars.ajax_nonce
        };

        if ( typeof subField !== 'undefined' ) {
            data.subField = subField;
        }

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: data,
            dataType: "json",
            success: function (data) {
            }
        });

    }

    // enable needed index fields
    function disableIndexField( field, subField ) {

        var data = {
            action: 'aws-indexDisabled',
            field: field,
            _ajax_nonce: aws_vars.ajax_nonce
        };

        if ( typeof subField !== 'undefined' ) {
            data.subField = subField;
        }

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: data,
            dataType: "json",
            success: function (data) {
            }
        });

    }

    // Edit source tables items
    var editButton = $('.aws-table-sources .aws-actions [data-edit]');
    editButton.on( 'click', function(e){
        e.preventDefault();
        var isActive = $(this).closest('.aws-table-sources-item').hasClass('on-edit');
        $('.aws-table-sources .aws-table-sources-item').removeClass('on-edit');
        if ( ! isActive ) {
            $(this).closest('.aws-table-sources-item').addClass('on-edit');
        }
    } );

    // Reindex table
    $reindexBtn.on( 'click', function(e) {

        e.preventDefault();

        syncStatus = 'sync';
        toProcess  = 0;
        processed = 0;

        $reindexBlock.addClass('loading');
        $reindexProgress.html ( processed + '%' );

        sync('start');

    });


    function sync( data ) {

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-reindex',
                data: data,
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            timeout:0,
            success: function (response) {
                if ( 'sync' !== syncStatus ) {
                    return;
                }

                toProcess = response.data.found_posts;
                processed = response.data.offset;

                processed = Math.floor( processed / toProcess * 100 );
                if ( processed > 100 ) {
                    processed = 100;
                }

                syncData = response.data;

                if ( 0 === response.data.offset && ! response.data.start ) {

                    // Sync finished
                    syncStatus = 'finished';

                    console.log( response.data );
                    console.log( "Reindex finished!" );

                    $reindexBlock.removeClass('loading');

                    $reindexCount.text( response.data.found_posts );

                } else {

                    console.log( response.data );

                    $reindexProgress.html( processed + '%' );

                    // We are starting a sync
                    syncStatus = 'sync';

                    sync( response.data );
                }

            },
            error : function( jqXHR, textStatus, errorThrown ) {
                console.log( "Request failed: " + textStatus );

                if ( textStatus == 'timeout' || jqXHR.status == 504 ) {
                    console.log( 'timeout' );
                    if ( syncData ) {
                        setTimeout(function() { sync( syncData ); }, 1000);
                    }
                } else if ( textStatus == 'error') {
                    if ( syncData ) {

                        if ( 0 !== syncData.offset && ! syncData.start ) {
                            setTimeout(function() { sync( syncData ); }, 3000);
                        }

                    }
                }

            },
            complete: function ( jqXHR, textStatus ) {
            }
        });

    }

    // Clear cache
    $clearCacheBtn.on( 'click', function(e) {

        e.preventDefault();

        var $clearCacheBlock = $(this).closest('#aws-clear-cache');

        $clearCacheBlock.addClass('loading');

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-clear-cache',
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            success: function (data) {
                $clearCacheBlock.removeClass('loading');
                alert('Cache cleared!');
            }
        });

    });


    // Dismiss welcome notice

    $( '.aws-welcome-notice.is-dismissible' ).on('click', '.notice-dismiss', function ( event ) {

        $.ajax({
            type: 'POST',
            url: aws_vars.ajaxurl,
            data: {
                action: 'aws-hideWelcomeNotice',
                _ajax_nonce: aws_vars.ajax_nonce
            },
            dataType: "json",
            success: function (data) {
            }
        });

    });

});