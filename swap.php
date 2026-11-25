<?php
$content = file_get_contents('purchase.php');

$stock_pattern = '/(<!-- Available Stock Quick Reference Cards -->.*?)(?=<!-- Purchase Input Form -->)/s';
$form_pattern = '/(<!-- Purchase Input Form -->.*?)(?=<!-- Recent Purchases History Table)/s';

preg_match($stock_pattern, $content, $stock_match);
preg_match($form_pattern, $content, $form_match);

if ($stock_match && $form_match) {
    $stock_text = $stock_match[1];
    $form_text = $form_match[1];
    
    // Make stock collapsible
    $stock_text = str_replace('class="card-header-bar"', 'class="card-header-bar" style="cursor:pointer;" onclick="toggleSection(\'stock_section\')"', $stock_text);
    $stock_text = str_replace('<div class="table-responsive">', '<div class="table-responsive" id="stock_section" style="display:none;">', $stock_text);
    
    // Swap
    $content = str_replace($stock_match[1], $form_text, $content);
    $content = str_replace($form_match[1], $stock_text, $content);
    
    // Make bills collapsible
    $content = str_replace(
        '<!-- Recent Purchases History Table with Philippine Bill View Button -->
        <div class="content-card">
            <div class="card-header-bar">',
        '<!-- Recent Purchases History Table with Philippine Bill View Button -->
        <div class="content-card">
            <div class="card-header-bar" style="cursor:pointer;" onclick="toggleSection(\'bills_section\')">',
        $content
    );
    $content = str_replace(
        '<div class="table-responsive">
                <?php
                    $customers = [];',
        '<div class="table-responsive" id="bills_section" style="display:none;">
                <?php
                    $customers = [];',
        $content
    );
    
    file_put_contents('purchase.php', $content);
    echo "Swapped successfully.\n";
} else {
    echo "Failed to match patterns.\n";
}
