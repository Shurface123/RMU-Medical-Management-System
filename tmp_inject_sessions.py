import os
import glob
import re

files = glob.glob(r"c:\wamp64\www\RMU-Medical-Management-System\php\dashboards\*\tab_settings.php")
# If admin settings exists:
admin_set = r"c:\wamp64\www\RMU-Medical-Management-System\php\admin\settings_v2.php"
if os.path.exists(admin_set):
    files.append(admin_set)

include_tag = "<?php include __DIR__.'/../../includes/active_sessions_panel.php'; ?>"
# For admin we need just single ../
include_tag_admin = "<?php include __DIR__.'/../includes/active_sessions_panel.php'; ?>"

for f in files:
    try:
        with open(f, 'r', encoding='utf-8') as file:
            content = file.read()
        
        if 'active_sessions_panel.php' not in content:
            # We want to inject it inside the right-column flex column layout.
            # In tab_settings, it's typically inside `<div style="display:flex;flex-direction:column;gap:1.5rem;">`
            # Look for the last `</div>` inside `<div id="sec-settings"`
            
            # Simple approach: append it right before the last closing </div> inside the main container block.
            # Let's use regex to insert before the last `</div>` of the `<div style="display:flex;flex-direction:column;gap:1.5rem;">` block
            
            # Since HTML parsing is hard with regex, we can just replace the end signature.
            # doctor_dashboard:
            #       </div>
            #     </div>
            #   </div>
            # </div>
            
            tag = include_tag_admin if "admin" in f else include_tag
            
            # Search for the block closing signature: it usually looks like `</div>\n    </div>\n  </div>`
            if '</div>\n    </div>\n  </div>\n</div>' in content:
                content = content.replace('</div>\n    </div>\n  </div>\n</div>', f'{tag}\n    </div>\n  </div>\n</div>', 1)
            elif '</div>\n  </div>\n</div>' in content:
                content = content.replace('</div>\n  </div>\n</div>', f'{tag}\n  </div>\n</div>', 1)
            else:
                # append near bottom
                if '<script>' in content:
                    content = content.replace('<script>', f'{tag}\n<script>', 1)
                else:
                    content += '\n' + tag
            
            with open(f, 'w', encoding='utf-8') as file:
                file.write(content)
            print(f"Injected into {f}")
    except Exception as e:
        print(f"Error processing {f}: {e}")
