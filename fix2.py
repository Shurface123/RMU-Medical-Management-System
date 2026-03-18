import re

files = ['diagrams_flowchart.md', 'diagrams_sequence.md', 'diagrams_use_case.md', 'diagrams_er.md']
for file in files:
    with open(f'C:\\wamp64\\www\\RMU-Medical-Management-System\\{file}', 'r', encoding='utf-8') as f:
        content = f.read()

    # Mermaid gets very upset at unescaped characters in text block nodes
    content = content.replace(' \\& ', ' and ').replace(' \\&', ' and').replace('\\& ', 'and ')
    content = content.replace(' \u0026 ', ' and ')
    content = content.replace(' & ', ' and ').replace(' &', ' and').replace('& ', 'and ')
    
    # Strip quotes inside node text that could break parsing
    content = content.replace("'", "")
    content = content.replace("`", "")

    # For Flowcharts: Fix missing closing brackets
    content = re.sub(r'(\[/.*?/)(?!\s*\])(?=\s*$|\s*:)', r'\1]', content, flags=re.MULTILINE)

    # For Sequence Diagrams: Fix alt/opt/else blocks with brackets
    # alt [Condition] -> alt Condition
    content = re.sub(r'alt\s+\[(.*?)\]', r'alt \1', content)
    content = re.sub(r'opt\s+\[(.*?)\]', r'opt \1', content)
    content = re.sub(r'else\s+\[(.*?)\]', r'else \1', content)

    # Make absolutely sure Start and End strings are safely wrapped
    content = re.sub(r'\bStart\(\[', 'StartNode([', content)
    content = re.sub(r'--> Start\b', '--> StartNode', content)
    content = re.sub(r'\bEnd\(\[', 'EndNode([', content)
    content = re.sub(r'--> End\b', '--> EndNode', content)

    # Remove any trailing colons that lost their classes e.g. :::
    content = re.sub(r'\]:::(?=\s*$)', r']', content, flags=re.MULTILINE)

    # Fix unbracketed newlines inside parens (nodes) which can break older mermaid parsing
    content = content.replace('\\n', ' ')

    with open(f'C:\\wamp64\\www\\RMU-Medical-Management-System\\{file}', 'w', encoding='utf-8') as f:
        f.write(content)

print('Fully sanitized all diagram syntax edges!')
