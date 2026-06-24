/**
 * SEO Tool CG - Settings Page JavaScript
 */
jQuery(document).ready(function($) {
    $('#seo-tool-cg-test-connection').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var result = $('#seo-tool-cg-test-result');

        // Key aus Input lesen
        var apiKey = $('#seo_tool_cg_api_key').val();
        if (!apiKey) {
            result.html('<span style="color: #d63638;">' + seoToolCG.i18n.failed + ' Bitte zuerst Key eingeben und speichern.</span>');
            return;
        }

        btn.prop('disabled', true).text(seoToolCG.i18n.testing);
        result.html('');

        $.ajax({
            url: seoToolCG.ajaxUrl,
            method: 'POST',
            data: {
                action: 'seo_tool_cg_test_connection',
                nonce: seoToolCG.nonce,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    result.html('<span style="color: #00a32a;">✓ ' + seoToolCG.i18n.success + ' (' + response.data.model + ')</span>');
                } else {
                    result.html('<span style="color: #d63638;">' + seoToolCG.i18n.failed + ' ' + response.data.message + '</span>');
                }
            },
            error: function(xhr, status, error) {
                result.html('<span style="color: #d63638;">' + seoToolCG.i18n.failed + ' ' + error + '</span>');
            },
            complete: function() {
                btn.prop('disabled', false).text('Verbindung testen');
            }
        });
    });
});
