import re

with open('purchase.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Make stock collapsible
content = content.replace('<!-- Available Stock Quick Reference Cards -->\n        <div class="content-card">\n            <div class="card-header-bar">', '<!-- Available Stock Quick Reference Cards -->\n        <div class="content-card">\n            <div class="card-header-bar" style="cursor:pointer;" onclick="toggleSection(\'stock_section\')">')
content = content.replace('<div class="table-responsive">\n                <table class="app-table">', '<div class="table-responsive" id="stock_section" style="display:none;">\n                <table class="app-table">')

# Make bills collapsible
content = content.replace('<!-- Recent Purchases History Table with Philippine Bill View Button -->\n        <div class="content-card">\n            <div class="card-header-bar">', '<!-- Recent Purchases History Table with Philippine Bill View Button -->\n        <div class="content-card">\n            <div class="card-header-bar" style="cursor:pointer;" onclick="toggleSection(\'bills_section\')">')
content = content.replace('<div class="table-responsive">\n                <?php\n                    $customers = [];', '<div class="table-responsive" id="bills_section" style="display:none;">\n                <?php\n                    $customers = [];')

# Swap
stock_pattern = re.compile(r'(<!-- Available Stock Quick Reference Cards -->.*?)(?=<!-- Purchase Input Form -->)', re.DOTALL)
form_pattern = re.compile(r'(<!-- Purchase Input Form -->.*?)(?=<!-- Recent Purchases History Table)', re.DOTALL)

stock_match = stock_pattern.search(content)
form_match = form_pattern.search(content)

if stock_match and form_match:
    stock_text = stock_match.group(1)
    form_text = form_match.group(1)
    
    content = content.replace(stock_text, form_text)
    content = content.replace(form_text, stock_text, 1)

with open('purchase.php', 'w', encoding='utf-8') as f:
    f.write(content)
