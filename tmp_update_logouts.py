import os
import glob
import re

files = glob.glob(r"c:\wamp64\www\RMU-Medical-Management-System\php\dashboards\*.php")
files.append(r"c:\wamp64\www\RMU-Medical-Management-System\php\includes\_sidebar.php")
files.append(r"c:\wamp64\www\RMU-Medical-Management-System\php\home.php")

script_tag = '\n<script src="/RMU-Medical-Management-System/js/logout.js"></script>\n</body>'

for f in files:
    try:
        with open(f, 'r', encoding='utf-8') as file:
            content = file.read()
        
        dirty = False
        
        # Replace logout links matching class="adm-logout-btn"
        new_content = re.sub(
            r'<a href="[^"]*logout\.php"[^>]*class="adm-logout-btn"[^>]*>',
            lambda m: f'<a href="#" onclick="confirmLogout(event, \'{os.path.basename(f)}\')" class="adm-logout-btn">',
            content
        )
        if new_content != content:
            dirty = True
            content = new_content
            
        # Replace logout links NOT having class="adm-logout-btn" but pointing to logout.php
        # Example from home.php: <a href="logout.php" class="..."> or ../logout.php
        new_content2 = re.sub(
            r'<a href="[^"]*logout\.php"[^>]*>',
            lambda m: m.group(0).replace('href="' + re.search(r'href="([^"]*logout\.php)"', m.group(0)).group(1) + '"', f'href="#" onclick="confirmLogout(event, \'{os.path.basename(f)}\')"'),
            content
        )
        if new_content2 != content:
            dirty = True
            content = new_content2

        # Inject script before </body>
        if dirty and 'js/logout.js' not in content:
            if '</body>' in content:
                content = content.replace('</body>', script_tag)
            elif '</html>' in content:
                content = content.replace('</html>', script_tag.replace('</body>','') + '\n</html>')
            else:
                content += '\n<script src="/RMU-Medical-Management-System/js/logout.js"></script>'
            
        if dirty:
            with open(f, 'w', encoding='utf-8') as file:
                file.write(content)
            print(f"Updated {f}")
    except Exception as e:
        print(f"Error processing {f}: {e}")
