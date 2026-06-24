/**
 * SEO Tool CG - Post-Editor Meta Box JavaScript
 */
jQuery(document).ready(function($) {
    var $btn = $('#stcg-generate-btn');
    var $status = $('#stcg-status');
    var $preview = $('#stcg-preview');
    var $previewContent = $('#stcg-preview-content');

    $btn.on('click', function(e) {
        e.preventDefault();

        if (!seoToolCG.hasKey) {
            $status.html('<span style="color: #d63638;">' + seoToolCG.i18n.noKey + '</span>');
            return;
        }

        var keyword = $('#stcg-keyword').val().trim();
        if (!keyword) {
            $status.html('<span style="color: #d63638;">Bitte Keyword eingeben.</span>');
            return;
        }

        // Bestätigung wenn bereits Content vorhanden
        var existingContent = $('#content').val();
        if (existingContent && existingContent.trim().length > 0) {
            if (!confirm(seoToolCG.i18n.confirmReplace)) {
                return;
            }
        }

        var params = {
            action: 'seo_tool_cg_generate',
            nonce: seoToolCG.nonce,
            keyword: keyword,
            intent: $('#stcg-intent').val(),
            word_count: $('#stcg-word-count').val(),
            include_faq: $('#stcg-include-faq').is(':checked') ? 1 : 0,
            include_meta: $('#stcg-include-meta').is(':checked') ? 1 : 0,
        };

        $btn.prop('disabled', true).text('Generiere…');
        $status.html('<span style="color: #2271b1;">' + seoToolCG.i18n.generating + '</span>');

        $.ajax({
            url: seoToolCG.ajaxUrl,
            method: 'POST',
            timeout: 180000, // 3 Minuten — Generierung kann dauern
            data: params,
            success: function(response) {
                if (response.success) {
                    $status.html('<span style="color: #00a32a;">✓ ' + seoToolCG.i18n.success + '</span>');

                    // Zeige Preview
                    $previewContent.text('Titel: ' + response.data.title + '\nTags: ' + (response.data.tags || []).join(', '));
                    $preview.show();

                    // Weiterleitung zum Editor nach kurzer Pause
                    setTimeout(function() {
                        window.location.href = response.data.edit_url;
                    }, 1500);
                } else {
                    $status.html('<span style="color: #d63638;">' + seoToolCG.i18n.error + ' ' + response.data.message + '</span>');
                }
            },
            error: function(xhr, status, error) {
                $status.html('<span style="color: #d63638;">' + seoToolCG.i18n.error + ' ' + error + '</span>');
            },
            complete: function() {
                $btn.prop('disabled', false).html('✨ Mit AI generieren');
            }
        });
    });
});
