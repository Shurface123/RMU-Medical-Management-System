import re

file_path = r'C:\wamp64\www\RMU-Medical-Management-System\diagrams_flowchart.md'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Fix End reserved word used as node name
content = re.sub(r'\bEnd\(\[', 'EndNode([', content)
content = re.sub(r'--> End\b', '--> EndNode', content)

# Fix single quotes inside [] that break Mermaid 10
content = content.replace("Show 'Pending Approval' Message", "Show Pending Approval Message")
content = content.replace("Show 'Awaiting Doctor Review'", "Show Awaiting Doctor Review")

# Fix colons in node names (replace with hyphen) inside brackets
content = re.sub(r'\[(.*?):\s*(.*?)\]', r'[\1 - \2]', content)

# Fix angle brackets in edge labels
content = content.replace("> 24hrs", "Over 24hrs")

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

gen_path = r'C:\wamp64\www\RMU-Medical-Management-System\generate_diagrams_export.py'
with open(gen_path, 'r', encoding='utf-8') as f:
    gen_content = f.read()

old_css = """.diagram-section {
            display: none;
            animation: fadeIn 0.4s ease-out forwards;
        }

        .diagram-section.active {
            display: block;
        }"""

new_css = """.diagram-section {
            position: absolute;
            visibility: hidden;
            opacity: 0;
            top: 0;
            left: 0;
            height: 0;
            overflow: hidden;
        }

        .diagram-section.active {
            position: relative;
            visibility: visible;
            opacity: 1;
            top: auto;
            left: auto;
            height: auto;
            overflow: visible;
            animation: fadeIn 0.4s ease-out forwards;
        }"""
gen_content = gen_content.replace(old_css, new_css)
gen_content = gen_content.replace('.diagram-section { display: block !important; }', '.diagram-section { display: block !important; position: relative !important; visibility: visible !important; opacity: 1 !important; height: auto !important; }')

with open(gen_path, 'w', encoding='utf-8') as f:
    f.write(gen_content)

print('Sanitized nodes and patched exporter CSS!')
