/**
 * toastv3.js — Toast Notification System
 * ========================================
 * Creates animated, auto-dismissing toast messages.
 *
 * Requires: jQuery
 * HTML:     <div class="toast-panel" id="toast-container"></div>
 *
 * @example
 *   showToast('success', 'Saved!', 'Your post has been published.');
 *   showToast('error', 'Failed', 'Something went wrong. Try again.');
 *   showToast('warning', 'Warning', 'This action cannot be undone.');
 *   showToast('help', 'Info', 'Use the sidebar to navigate.');
 *
 * Types: 'success' | 'error' | 'warning' | 'help'
 */

/**
 * showToast — Display a toast notification.
 *
 * @param {'success'|'error'|'warning'|'help'} type  Toast variant
 * @param {string} title   Bold heading text
 * @param {string} message Body/description text
 * @param {number} [duration=4000] Auto-dismiss delay in ms
 */
function showToast(type, title, message, duration = 4000) {
    var lowerType  = type.toLowerCase();
    var $panel     = $('#toast-container');

    if (!$panel.length) {
        console.warn('toastv3: #toast-container not found in DOM.');
        return;
    }

    var $item = $('<div class="toast-item ' + lowerType + '"></div>');
    var $toast = $(
        '<div class="toast ' + lowerType + '">' +
        '  <label class="close" title="Dismiss"></label>' +
        '  <h3>' + title + '</h3>' +
        '  <p>'  + message + '</p>' +
        '</div>'
    );

    $item.append($toast);
    $panel.append($item);

    // Close button
    $item.find('.close').on('click', function () {
        $item.fadeOut(300, function () { $(this).remove(); });
    });

    // Auto-dismiss
    setTimeout(function () {
        $item.fadeOut(400, function () { $(this).remove(); });
    }, duration);
}

// ─── Convenience Aliases ──────────────────────────────────
const toast = {
    success: (t, m, d) => showToast('success', t, m, d),
    error:   (t, m, d) => showToast('error',   t, m, d),
    warning: (t, m, d) => showToast('warning', t, m, d),
    info:    (t, m, d) => showToast('help',    t, m, d),
};
