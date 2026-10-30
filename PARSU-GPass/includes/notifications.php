<?php
/**
 * Notification & Flash Message Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sets a flash message in session
 */
function setFlashMessage(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message,
        'time'    => time()
    ];
}

/**
 * Retrieves and clears flash messages from session
 */
function getFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Renders flash messages as HTML alert elements
 */
function renderFlashMessages(): string {
    $messages = getFlashMessages();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="notifications-container" id="notificationsContainer">';
    foreach ($messages as $msg) {
        $type = htmlspecialchars($msg['type']);
        $text = htmlspecialchars($msg['message']);
        
        $icon = match($type) {
            'success' => '✓',
            'error', 'danger' => '✕',
            'warning' => '⚠',
            default => 'ℹ'
        };

        $html .= sprintf(
            '<div class="toast toast-%s" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-icon">%s</div>
                <div class="toast-body">%s</div>
                <button type="button" class="toast-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
            </div>',
            $type,
            $icon,
            $text
        );
    }
    $html .= '</div>';

    return $html;
}

