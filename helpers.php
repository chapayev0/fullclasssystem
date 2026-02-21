<?php
/**
 * Sanitize HTML content to prevent XSS.
 * Allows basic formatting tags but strips scripts, iframes, and dangerous attributes.
 */
function sanitize_html($html) {
    if (empty($html)) return '';

    // Suppress warnings for HTML5 elements not recognized by DOMDocument
    $libxml_previous_state = libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    // Use proper encoding
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $allowed_tags = ['p', 'b', 'strong', 'i', 'em', 'u', 'br', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'span', 'div', 'a', 'img', 'table', 'thead', 'tbody', 'tr', 'td', 'th'];
    $allowed_attributes = ['src', 'href', 'alt', 'title', 'class', 'style', 'width', 'height', 'target'];

    $xpath = new DOMXPath($dom);
    
    // 1. Remove disallowed tags (scripts, iframes, etc.)
    // We iterate backwards to avoid messing up the node list as we delete
    $nodes = $xpath->query('//*');
    for ($i = $nodes->length - 1; $i >= 0; $i--) {
        $node = $nodes->item($i);
        if (!in_array($node->nodeName, $allowed_tags)) {
            $node->parentNode->removeChild($node);
        }
    }

    // 2. Remove disallowed attributes and clean URLs
    $nodes = $xpath->query('//*');
    foreach ($nodes as $node) {
        if (!$node->hasAttributes()) continue;

        // Iterate backwards through attributes to safely remove them
        for ($i = $node->attributes->length - 1; $i >= 0; $i--) {
            $attr = $node->attributes->item($i);
            $attrName = strtolower($attr->name);
            
            if (!in_array($attrName, $allowed_attributes)) {
                $node->removeAttribute($attr->name);
                continue;
            }

            // Specific checks for dangerous values (javascript:)
            if (in_array($attrName, ['src', 'href'])) {
                $value = strtolower(trim($attr->value));
                if (strpos($value, 'javascript:') === 0 || strpos($value, 'data:') === 0 || strpos($value, 'vbscript:') === 0) {
                    $node->removeAttribute($attr->name);
                }
            }
        }
    }

    $clean_html = $dom->saveHTML();
    libxml_clear_errors();
    libxml_use_internal_errors($libxml_previous_state);

    return $clean_html;
}

/**
 * Generate a CSRF token and store it in the session.
 * @return string The generated token.
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden input field with the CSRF token.
 */
function csrf_input() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verify the submitted CSRF token.
 * @param string $token The token submitted with the form.
 * @return bool True if valid, False otherwise.
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Format grade number with its category (O/L or A/L) if applicable.
 * @param int|string $grade The grade number.
 * @return string The formatted grade string.
 */
function format_grade($grade) {
    if (!$grade) return '';
    $grade = (int)$grade;
    if ($grade == 10 || $grade == 11) {
        return "Grade $grade (O/L)";
    } elseif ($grade == 12 || $grade == 13) {
        return "Grade $grade (A/L)";
    }
    return "Grade $grade";
}
?>
