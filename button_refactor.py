import os
import re

EXCLUDE_CLASSES = {
    'adm-menu-toggle', 'adm-theme-toggle', 'lp-mobile-menu', 'pw-toggle', 
    'theme-toggle', 'lp-carousel-btn', 'lp-chatbot-btn', 'theme-toggle-header', 'lp-lightbox-close', 'btn-close'
}

def extract_tags(html, tag_name):
    idx = 0
    tag_lower = f"<{tag_name}"
    while True:
        start_idx = html.lower().find(tag_lower, idx)
        if start_idx == -1: break
            
        if start_idx + len(tag_lower) < len(html):
            if html[start_idx + len(tag_lower)] not in " >\n\r\t":
                idx = start_idx + 1
                continue
                
        i = start_idx + len(tag_lower)
        in_php = False
        tag_open_end = -1
        while i < len(html):
            if in_php:
                if html[i:i+2] == '?>':
                    in_php = False
                    i += 2
                    continue
            else:
                if html[i:i+2] == '<?':
                    in_php = True
                    i += 2
                    continue
                if html[i] == '>':
                    tag_open_end = i
                    break
            i += 1
            
        if tag_open_end == -1: break
            
        tag_open = html[start_idx:tag_open_end+1]
        content_start = tag_open_end + 1
        
        if tag_open.endswith("/>"):
            idx = content_start
            continue

        close_tag = f"</{tag_name}>"
        close_idx = html.lower().find(close_tag, content_start)
        if close_idx == -1:
            idx = content_start
            continue
            
        inner_content = html[content_start:close_idx]
        yield (start_idx, close_idx + len(close_tag), tag_open, inner_content)
        idx = close_idx + len(close_tag)

def process_button(tag_open, inner_text, is_a_tag):
    tag_open_original = tag_open
    
    if is_a_tag and 'btn' not in tag_open.lower():
        return tag_open_original + inner_text + "</a>"
        
    if 'btn-text' in inner_text or not inner_text.strip():
        return tag_open_original + inner_text + f"</{'a' if is_a_tag else 'button'}>"

    for exc in EXCLUDE_CLASSES:
        if exc in tag_open:
            return tag_open_original + inner_text + f"</{'a' if is_a_tag else 'button'}>"

    # Simplify classes dynamically
    tag_open = tag_open.replace('adm-btn', 'btn')
    
    lower_text = inner_text.lower()
    classes_to_add = []
    
    if re.search(r'admit patient', lower_text): classes_to_add.extend(['btn-primary', 'btn-lg', 'btn-pulse', 'btn-icon'])
    elif re.search(r'book', lower_text): classes_to_add.extend(['btn-primary', 'btn-lg', 'btn-icon'])
    elif re.search(r'save|submit|add\b|update|register|login|verify|send|proceed', lower_text): classes_to_add.append('btn-primary')
    elif re.search(r'approve|discharge|confirm|okay', lower_text): classes_to_add.extend(['btn-success', 'btn-icon'])
    elif re.search(r'delete|remove|reject|clear', lower_text): classes_to_add.extend(['btn-danger', 'btn-sm', 'btn-icon'])
    elif re.search(r'flag|warn|pending', lower_text): classes_to_add.extend(['btn-warning', 'btn-icon'])
    elif re.search(r'cancel|back|close|return', lower_text): classes_to_add.append('btn-ghost')
    elif re.search(r'view|export|read|print|download', lower_text): classes_to_add.extend(['btn-outline', 'btn-icon'])
    else: classes_to_add.append('btn-primary')

    # Check if a class attribute already exists
    if 'class="' not in tag_open and "class='" not in tag_open:
        if not is_a_tag:
            tag_open = tag_open[:-1] + f' class="btn {" ".join(classes_to_add)}">'
    else:
        match = re.search(r'class=(["\'])', tag_open, re.IGNORECASE)
        if match:
            has_color = any(c in tag_open for c in ['btn-primary', 'btn-danger', 'btn-success', 'btn-ghost', 'btn-warning', 'btn-outline'])
            inj = ' '.join(classes_to_add)
            if has_color:
                if 'btn-icon' in inj and 'btn-icon' not in tag_open:
                    tag_open = tag_open[:match.end()] + "btn-icon " + tag_open[match.end():]
                if 'btn-pulse' in inj and 'btn-pulse' not in tag_open:
                    tag_open = tag_open[:match.end()] + "btn-pulse " + tag_open[match.end():]
                if not 'btn ' in tag_open and not 'btn"' in tag_open and not "btn'" in tag_open:
                    tag_open = tag_open[:match.end()] + "btn " + tag_open[match.end():]
            else:
                tag_open = tag_open[:match.end()] + "btn " + inj + " " + tag_open[match.end():]

    wrapped_text = f'<span class="btn-text">{inner_text}</span>'
    return tag_open + wrapped_text + f"</{'a' if is_a_tag else 'button'}>"


def refactor_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        html = f.read()
        
    changed = False
    
    new_html = ""
    idx = 0
    for start_idx, end_idx, tag_open, inner_content in extract_tags(html, 'button'):
        new_html += html[idx:start_idx]
        new_tag = process_button(tag_open, inner_content, False)
        new_html += new_tag
        if new_tag != (tag_open + inner_content + '</button>'): changed = True
        idx = end_idx
    new_html += html[idx:]
    html = new_html

    new_html = ""
    idx = 0
    for start_idx, end_idx, tag_open, inner_content in extract_tags(html, 'a'):
        new_html += html[idx:start_idx]
        new_tag = process_button(tag_open, inner_content, True)
        new_html += new_tag
        if new_tag != (tag_open + inner_content + '</a>'): changed = True
        idx = end_idx
    new_html += html[idx:]
    
    if changed:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_html)
            
    return changed

def main():
    root_dir = r"c:\wamp64\www\RMU-Medical-Management-System"
    count = 0
    for foldername, subfolders, filenames in os.walk(root_dir):
        if any(x in foldername for x in ['vendor', 'node_modules', '.git', 'ckeditor']):
            continue
        for filename in filenames:
            if filename.endswith('.php') or filename.endswith('.html'):
                file_path = os.path.join(foldername, filename)
                try:
                    if refactor_file(file_path):
                        count += 1
                        print(f"Refactored: {file_path}")
                except Exception as e:
                    print(f"Error on {file_path}: {e}")
    print(f"Total files refactored: {count}")

if __name__ == "__main__":
    main()
